<?php

namespace App\Http\Controllers\Admin;

use App\Cloud\CloudOrderException;
use App\Cloud\CloudSettings;
use App\Cloud\Provisioner;
use App\Enums\CloudProduct;
use App\Enums\CloudStatus;
use App\Http\Controllers\Controller;
use App\Models\CloudService;
use App\Models\Setting;
use App\Services\InterServer\InterServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Cloud in the admin panel: clients' orders to approve (approving buys them
 * on InterServer, paid from the prepay balance), the services that are
 * running, and Cloud's settings (markups, which products are on offer).
 */
class CloudController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:manage cloud'),
        ];
    }

    public function index(Request $request, InterServer $api): View
    {
        $status = CloudStatus::tryFrom((string) $request->query('status'));
        $counts = CloudService::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.cloud.index', [
            'services' => CloudService::with('user')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderByRaw("case status when 'requested' then 0 when 'failed' then 1 when 'provisioning' then 2 else 3 end")
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => $counts,
            'connection' => $this->connection($api),
        ]);
    }

    public function show(CloudService $cloudService, Provisioner $provisioner): View
    {
        $provisioner->syncIfStale($cloudService);

        return view('admin.cloud.show', [
            'service' => $cloudService->fresh(['user', 'approver']),
            'markup' => CloudSettings::markup($cloudService->product),
        ]);
    }

    /**
     * Buy it on InterServer, paid from the prepay balance.
     */
    public function approve(Request $request, CloudService $cloudService, Provisioner $provisioner): RedirectResponse
    {
        try {
            $provisioner->approve($cloudService, $request->user());
        } catch (CloudOrderException $e) {
            return back()->withErrors(['cloud' => $e->getMessage()]);
        }

        return back()->with('success', "Bought on InterServer and paid from the prepay balance. {$cloudService->name} is being set up.");
    }

    public function reject(Request $request, CloudService $cloudService): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        abort_if($cloudService->isOrdered() || ! in_array($cloudService->status, [CloudStatus::Requested, CloudStatus::Failed], true), 409);

        $cloudService->update([
            'status' => CloudStatus::Rejected,
            'note' => $data['reason'] ?? null,
            'secret' => null,
        ]);

        return to_route('admin.cloud.index')->with('success', "The order for {$cloudService->name} was declined.");
    }

    /**
     * After a failure: pay what was ordered, or order it again if nothing was.
     */
    public function retry(Request $request, CloudService $cloudService, Provisioner $provisioner): RedirectResponse
    {
        try {
            $provisioner->retry($cloudService, $request->user());
        } catch (CloudOrderException $e) {
            return back()->withErrors(['cloud' => $e->getMessage()]);
        }

        return back()->with('success', "Done: {$cloudService->name} is paid for and being set up.");
    }

    public function sync(CloudService $cloudService, Provisioner $provisioner): RedirectResponse
    {
        $provisioner->sync($cloudService);

        return back()->with('success', 'Updated from InterServer.');
    }

    public function cancel(Request $request, CloudService $cloudService, Provisioner $provisioner): RedirectResponse
    {
        $data = $request->validate(['when' => ['required', 'in:renewal,now']]);

        try {
            $text = $provisioner->cancel($cloudService, $data['when']);
        } catch (CloudOrderException $e) {
            return back()->withErrors(['cloud' => $e->getMessage()]);
        }

        return back()->with('success', $text);
    }

    public function settings(InterServer $api): View
    {
        return view('admin.cloud.settings', [
            'products' => CloudProduct::cases(),
            'connection' => $this->connection($api, fresh: true),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'markup' => ['required', 'array'],
            'markup.*' => ['required', 'numeric', 'min:0', 'max:500'],
            'enabled' => ['array'],
            'enabled.*' => ['boolean'],
        ]);

        foreach (CloudProduct::cases() as $product) {
            Setting::put("cloud.markup.{$product->value}", round((float) ($data['markup'][$product->value] ?? CloudSettings::DEFAULT_MARKUP), 2));
            Setting::put("cloud.enabled.{$product->value}", (bool) ($data['enabled'][$product->value] ?? false));
        }

        return to_route('admin.cloud.settings')->with('success', 'Cloud settings saved.');
    }

    /**
     * Whether an API key is set and InterServer answers to it.
     *
     * @return array{configured: bool, reachable: ?bool}
     */
    private function connection(InterServer $api, bool $fresh = false): array
    {
        if (! $api->configured()) {
            return ['configured' => false, 'reachable' => null];
        }

        if ($fresh) {
            Cache::forget('interserver.ping');
        }

        return ['configured' => true, 'reachable' => Cache::remember('interserver.ping', now()->addMinutes(5), fn () => $api->ping())];
    }
}
