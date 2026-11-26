<?php

namespace App\Http\Controllers;

use App\Cloud\CloudOrderException;
use App\Cloud\CloudSettings;
use App\Cloud\Products;
use App\Cloud\Products\Domain;
use App\Cloud\Provisioner;
use App\Enums\CloudProduct;
use App\Enums\CloudStatus;
use App\Models\CloudService;
use App\Services\InterServer\InterServer;
use App\Services\InterServer\InterServerException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cloud in the client panel: order a VPS, web hosting, a domain or a
 * dedicated server (resold from InterServer, see App\Cloud), and look after
 * the ones you have. Ordering never buys anything: it waits for the team to
 * approve it in the admin panel.
 */
class CloudController extends Controller
{
    public function index(Request $request, InterServer $api): View
    {
        return view('cloud.index', [
            'services' => $request->user()->cloudServices()->latest()->get(),
            'products' => collect(CloudProduct::cases())->filter(fn (CloudProduct $product) => CloudSettings::enabled($product))->values(),
            'available' => $api->configured(),
        ]);
    }

    public function create(Request $request, CloudProduct $product, InterServer $api): View
    {
        abort_unless(CloudSettings::enabled($product), 404);

        $catalog = null;
        $error = $api->configured() ? null : 'Ordering online isn\'t open yet. Contact us and we\'ll set it up for you.';
        if (! $error && $product !== CloudProduct::Domain) {
            try {
                $catalog = Products::for($product)->catalog();
            } catch (InterServerException $e) {
                report($e);
                $error = 'We couldn\'t load the options just now. Please try again in a few minutes.';
            }
        }

        return view('cloud.order', [
            'product' => $product,
            'catalog' => $catalog,
            'error' => $error,
            'markup' => CloudSettings::markup($product),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request, CloudProduct $product, InterServer $api): RedirectResponse
    {
        abort_unless(CloudSettings::enabled($product) && $api->configured(), 404);

        $handler = Products::for($product);
        $input = $request->validate($handler->rules(), $this->messages());

        try {
            $order = $handler->prepare($input);
        } catch (CloudOrderException $e) {
            return back()->withInput($request->except(['password', 'password_confirmation']))->withErrors(['order' => $e->getMessage()]);
        } catch (InterServerException $e) {
            report($e);

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['order' => 'We couldn\'t check that with our provider just now. Please try again in a few minutes.']);
        }

        $price = CloudSettings::price($product, $order['cost']);
        $service = $request->user()->cloudServices()->create([
            'product' => $product,
            'status' => CloudStatus::Requested,
            'name' => $order['name'],
            'plan' => $order['plan'],
            'config' => $order['config'],
            'secret' => $order['secret'],
            'period' => $order['period'],
            'cost' => $order['cost'],
            'price' => $price,
            'monthly_price' => round($price / max(1, $order['period']), 2),
            'quote' => $order['quote'],
        ]);

        return to_route('cloud.show', $service)->with('success', 'Thanks! Your order is in. Our team will confirm it and set it up.');
    }

    public function show(Request $request, CloudService $cloudService, Provisioner $provisioner): View
    {
        abort_unless($cloudService->user()->is($request->user()), 404);

        $provisioner->syncIfStale($cloudService);
        $cloudService->refresh();

        return view('cloud.show', [
            'service' => $cloudService,
            'actions' => $cloudService->isActive() && $cloudService->isOrdered() ? Products::for($cloudService->product)->actions($cloudService) : [],
        ]);
    }

    /**
     * Start, restart, change a password, open the control panel…
     */
    public function action(Request $request, CloudService $cloudService, string $action): RedirectResponse
    {
        abort_unless($cloudService->user()->is($request->user()), 404);

        $handler = Products::for($cloudService->product);
        abort_unless($cloudService->isActive() && $cloudService->isOrdered() && array_key_exists($action, $handler->actions($cloudService)), 404);

        $input = $request->validate($handler->actionRules($action), $this->messages());

        try {
            $result = $handler->perform($cloudService, $action, $input);
        } catch (CloudOrderException|InterServerException $e) {
            report($e);

            return back()->withErrors(['action' => $e instanceof CloudOrderException ? $e->getMessage() : 'That didn\'t go through. Please try again in a moment.']);
        }

        return $action === 'login' ? redirect()->away($result) : back()->with('success', $result);
    }

    public function refresh(Request $request, CloudService $cloudService, Provisioner $provisioner): RedirectResponse
    {
        abort_unless($cloudService->user()->is($request->user()), 404);

        $provisioner->sync($cloudService);

        return back()->with('success', 'Updated just now.');
    }

    /**
     * Withdraw an order nobody has approved yet, or ask to cancel a service.
     */
    public function cancel(Request $request, CloudService $cloudService): RedirectResponse
    {
        abort_unless($cloudService->user()->is($request->user()), 404);

        if ($cloudService->status === CloudStatus::Requested) {
            $cloudService->update(['status' => CloudStatus::Cancelled, 'cancelled_at' => now(), 'secret' => null]);

            return to_route('cloud.index')->with('success', 'Your order was withdrawn. Nothing was charged.');
        }

        abort_unless(in_array($cloudService->status, [CloudStatus::Active, CloudStatus::Provisioning], true), 404);
        $cloudService->update(['cancel_requested_at' => $cloudService->cancel_requested_at ?? now()]);

        return back()->with('success', 'We\'ve got your cancellation request. Our team will confirm the date with you.');
    }

    /**
     * Whether a domain is free, and its price (for the order form).
     */
    public function checkDomain(Request $request, Domain $domains): JsonResponse
    {
        $data = $request->validate(['domain' => ['required', 'string', 'max:120', 'regex:'.Domain::PATTERN]], [
            'domain.regex' => 'Enter a full domain name, like yourbrand.com.',
        ]);

        try {
            $check = $domains->check($data['domain']);
            $privacy = $check['privacy'] ? $domains->catalog()['privacy'] : null;
        } catch (InterServerException $e) {
            report($e);

            return response()->json(['message' => 'We couldn\'t check that domain just now. Please try again in a moment.'], 503);
        }

        return response()->json([
            'domain' => $check['domain'],
            'available' => $check['available'],
            'premium' => $check['premium'],
            'price' => CloudSettings::price(CloudProduct::Domain, $check['cost']),
            'renewal' => CloudSettings::price(CloudProduct::Domain, $check['renewal']),
            'privacy' => $privacy !== null ? CloudSettings::price(CloudProduct::Domain, $privacy) : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'password.regex' => 'Use upper and lower case letters and a number'.' (a dedicated server also needs a symbol).',
            'hostname.regex' => 'Use a full hostname, like server.yourbrand.com.',
            'domain.regex' => 'Enter a full domain name, like yourbrand.com.',
            'phone.regex' => 'Write the phone number as +country code, a dot, then the number, like +94.771234567.',
            'nameservers.*.regex' => 'Each nameserver must be a hostname, like ns1.example.com.',
        ];
    }
}
