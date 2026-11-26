<?php

namespace App\Cloud;

use App\Enums\CloudStatus;
use App\Models\CloudService;
use App\Models\User;
use App\Services\InterServer\InterServer;
use App\Services\InterServer\InterServerException;
use Throwable;

/**
 * Buys an approved order on InterServer and keeps it in step afterwards.
 *
 * Buying is two calls: the order (which creates unpaid invoices) and paying
 * those invoices from the prepay balance (which starts provisioning). Each
 * result is saved as soon as it's back, so a retry after a failure never
 * orders twice: it pays the invoices already there.
 */
class Provisioner
{
    public function __construct(private InterServer $api) {}

    /**
     * Approve an order and buy it. Only one approval can claim an order.
     *
     * @throws CloudOrderException
     */
    public function approve(CloudService $service, User $approver): void
    {
        $claimed = CloudService::whereKey($service->id)
            ->whereIn('status', [CloudStatus::Requested->value, CloudStatus::Failed->value])
            ->whereNull('interserver_uuid')
            ->update([
                'status' => CloudStatus::Provisioning->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'failure' => null,
            ]);

        if (! $claimed) {
            throw new CloudOrderException('This order has already been handled.');
        }

        $service->refresh();

        try {
            $order = Products::for($service->product)->order($service);
        } catch (Throwable $e) {
            $service->update(['status' => CloudStatus::Failed, 'failure' => 'Ordering on InterServer failed: '.$e->getMessage()]);

            throw new CloudOrderException($service->failure, previous: $e);
        }

        // Ordered: keep what InterServer made, and drop the password we held.
        $service->update(['interserver_uuid' => $order['uuid'], 'interserver_invoices' => $order['invoices'], 'secret' => null]);

        $this->pay($service);
    }

    /**
     * Pay an ordered service's invoices from the prepay balance.
     *
     * @throws CloudOrderException
     */
    public function pay(CloudService $service): void
    {
        try {
            $this->api->pay((array) $service->interserver_invoices);
        } catch (Throwable $e) {
            $service->update([
                'status' => CloudStatus::Failed,
                'failure' => 'Ordered on InterServer, but paying its invoice from the prepay balance failed: '.$e->getMessage()
                    .' Top up the balance, then retry payment; it won\'t order again.',
            ]);

            throw new CloudOrderException($service->failure, previous: $e);
        }

        $service->update(['status' => CloudStatus::Provisioning, 'failure' => null]);
    }

    /**
     * Try a failed order again: pay what was ordered, or order it if it
     * never got that far.
     *
     * @throws CloudOrderException
     */
    public function retry(CloudService $service, User $approver): void
    {
        if ($service->status !== CloudStatus::Failed) {
            throw new CloudOrderException('Only orders that need attention can be retried.');
        }

        $service->isOrdered() ? $this->pay($service) : $this->approve($service, $approver);
    }

    /**
     * Read the service back from InterServer, and move its status along
     * (set up → active; cancelled there → cancelled here).
     */
    public function sync(CloudService $service): void
    {
        if (! $service->isOrdered()) {
            return;
        }

        try {
            $fetched = Products::for($service->product)->fetch($service);
        } catch (InterServerException) {
            // Leave it as it was; the page says when it was last updated.
            return;
        }

        $remoteStatus = strtolower((string) $fetched['status']);
        $status = match (true) {
            $remoteStatus === 'active' => CloudStatus::Active,
            in_array($remoteStatus, ['canceled', 'cancelled', 'deleted', 'expired', 'terminated'], true) => CloudStatus::Cancelled,
            default => $service->status,
        };

        $service->update([
            'remote' => $fetched['remote'],
            'remote_status' => $fetched['status'],
            'next_due_at' => $fetched['next_due_at'],
            'synced_at' => now(),
            'status' => $status,
            'activated_at' => $status === CloudStatus::Active ? ($service->activated_at ?? now()) : $service->activated_at,
            'cancelled_at' => $status === CloudStatus::Cancelled ? ($service->cancelled_at ?? now()) : $service->cancelled_at,
        ]);
    }

    /**
     * Sync it if it's been a while (pages call this as they open).
     */
    public function syncIfStale(CloudService $service, int $seconds = 60): void
    {
        $settling = $service->status === CloudStatus::Provisioning;
        if ($service->isOrdered() && $service->status->isOpen()
            && (! $service->synced_at || $service->synced_at->lt(now()->subSeconds($settling ? 15 : $seconds)))) {
            $this->sync($service);
        }
    }

    /**
     * Cancel it on InterServer (the team decides when).
     *
     * @throws CloudOrderException
     */
    public function cancel(CloudService $service, string $when): string
    {
        if (! $service->isOrdered()) {
            $service->update(['status' => CloudStatus::Cancelled, 'cancelled_at' => now()]);

            return 'The order was cancelled. Nothing had been bought.';
        }

        try {
            $text = Products::for($service->product)->cancel($service, $when);
        } catch (InterServerException $e) {
            throw new CloudOrderException($e->getMessage(), previous: $e);
        }

        $service->update($when === 'now'
            ? ['status' => CloudStatus::Cancelled, 'cancelled_at' => now()]
            : ['note' => trim(($service->note ? $service->note."\n" : '').'Set to cancel before its next renewal ('.now()->toDateString().').')]);

        return $text;
    }
}
