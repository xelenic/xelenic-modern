<?php

namespace App\Cloud;

use App\Enums\CloudProduct;
use App\Models\CloudService;
use App\Services\InterServer\InterServer;
use App\Services\InterServer\InterServerException;
use Illuminate\Support\Carbon;

/**
 * One kind of cloud service, as resold from InterServer: what clients can
 * choose (catalog), how their choice becomes an InterServer order (prepare,
 * order), how its state is read back (fetch), and what can be done with it
 * once it's running (actions, perform).
 */
abstract class Product
{
    public function __construct(protected InterServer $api) {}

    abstract public function key(): CloudProduct;

    /**
     * InterServer's module path for the service's own endpoints.
     */
    abstract public function module(): string;

    /**
     * What the order form offers.
     *
     * @return array<string, mixed>
     *
     * @throws InterServerException
     */
    abstract public function catalog(): array;

    /**
     * Validation rules for the order form.
     *
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * Turn the client's validated choices into an order, priced by
     * InterServer without placing it.
     *
     * @param  array<string, mixed>  $input
     * @return array{config: array<string, mixed>, secret: ?string, name: string, plan: string, period: int, cost: float, quote: array<string, mixed>}
     *
     * @throws CloudOrderException when InterServer won't take it as it is
     */
    abstract public function prepare(array $input): array;

    /**
     * Place the order on InterServer (unpaid).
     *
     * @return array{uuid: string, invoices: list<string>}
     */
    abstract public function order(CloudService $service): array;

    /**
     * The service as InterServer has it now: details to show, its status
     * there, and when it renews.
     *
     * @return array{remote: array<string, mixed>, status: ?string, next_due_at: ?Carbon}
     */
    abstract public function fetch(CloudService $service): array;

    /**
     * What the client can do with it while it's active: name => label.
     *
     * @return array<string, string>
     */
    public function actions(CloudService $service): array
    {
        return [];
    }

    /**
     * Do one of actions(); returns what to tell the client. A null-returning
     * action is handled by the controller (e.g. a redirect).
     *
     * @param  array<string, mixed>  $input
     */
    public function perform(CloudService $service, string $action, array $input = []): string
    {
        throw new CloudOrderException('That can\'t be done with this service.');
    }

    /**
     * Validation rules for an action's input, if it takes any.
     *
     * @return array<string, mixed>
     */
    public function actionRules(string $action): array
    {
        return [];
    }

    /**
     * Cancel it on InterServer, now or the day before it renews.
     */
    public function cancel(CloudService $service, string $when): string
    {
        return $this->api->cancel($this->module(), $service->interserver_uuid, $when);
    }

    /**
     * The service UUID and invoice UUIDs from InterServer's order response.
     *
     * @param  array<string, mixed>  $response
     * @return array{uuid: string, invoices: list<string>}
     */
    protected function ordered(array $response): array
    {
        if (array_key_exists('continue', $response) && ! $response['continue']) {
            throw new CloudOrderException('InterServer didn\'t accept the order: '.$this->errors($response));
        }

        $uuid = $response['service_uuid'] ?? data_get($response, 'order_details.service_uuid');
        $invoices = $response['invoice_uuids'] ?? array_filter([$response['invoice_uuid'] ?? data_get($response, 'order_details.invoice_uuid')]);

        if (! $uuid || ! $invoices) {
            throw new CloudOrderException('InterServer\'s reply to the order had no service or invoice in it.');
        }

        return ['uuid' => (string) $uuid, 'invoices' => array_values(array_map('strval', (array) $invoices))];
    }

    /**
     * InterServer's validation errors as one line.
     *
     * @param  array<string, mixed>  $response
     */
    protected function errors(array $response): string
    {
        $errors = collect((array) ($response['errors'] ?? []))->flatten()->filter()->implode(' ');

        return $errors !== '' ? strip_tags($errors) : 'no reason given.';
    }

    protected function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
