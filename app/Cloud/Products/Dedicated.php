<?php

namespace App\Cloud\Products;

use App\Cloud\CloudOrderException;
use App\Cloud\Product;
use App\Enums\CloudProduct;
use App\Models\CloudService;
use App\Services\InterServer\InterServerException;
use Illuminate\Support\Arr;

/**
 * Dedicated servers: InterServer's Rapid Deploy machines, already built and
 * priced, bought one at a time and billed monthly.
 */
class Dedicated extends Product
{
    public function key(): CloudProduct
    {
        return CloudProduct::Dedicated;
    }

    public function module(): string
    {
        return 'servers';
    }

    public function catalog(): array
    {
        $servers = collect($this->api->dedicatedServers())
            ->filter(fn ($server) => is_array($server) && isset($server['server_id']) && (float) ($server['price'] ?? 0) > 0)
            ->map(fn (array $server) => [
                'id' => (string) $server['server_id'],
                'cpu' => is_array($server['cpu'] ?? null) ? (string) ($server['cpu'][0] ?? '') : (string) ($server['cpu'] ?? ''),
                'cores' => (int) (data_get($server, 'cpu.1.num_cores') ?? 0) * max(1, (int) (data_get($server, 'cpu.1.num_cpus') ?? 1)),
                'memory' => (string) ($server['memory'] ?? ''),
                'disk' => $this->disk($server['disk'] ?? ''),
                'bandwidth' => (string) ($server['bandwidth'] ?? ''),
                'ips' => (string) ($server['ips'] ?? ''),
                'location' => (string) ($server['location'] ?? ''),
                'cost' => (float) $server['price'],
            ])
            ->sortBy('cost')
            ->values()
            ->all();

        return ['servers' => $servers];
    }

    public function rules(): array
    {
        return [
            'server' => ['required', 'string', 'max:20'],
            'hostname' => ['required', 'string', 'max:120', 'regex:/^(?=.{1,120}$)([a-z0-9](-*[a-z0-9])*)(\.[a-z0-9](-*[a-z0-9])*)+$/i'],
            // InterServer's rule for a chosen root password.
            'password' => ['required', 'string', 'min:10', 'max:64', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/', 'confirmed'],
        ];
    }

    public function prepare(array $input): array
    {
        $server = collect($this->catalog()['servers'])->firstWhere('id', (string) $input['server']);
        if (! $server) {
            throw new CloudOrderException('That server has just been taken. Choose another one.');
        }

        try {
            $options = $this->api->dedicatedOptions($server['id']);
        } catch (InterServerException $e) {
            throw new CloudOrderException('That server can\'t be ordered right now (it may have just sold). Choose another one.', previous: $e);
        }

        return [
            'config' => ['server_id' => $server['id'], 'hostname' => strtolower($input['hostname'])],
            'secret' => $input['password'],
            'name' => strtolower($input['hostname']),
            'plan' => implode(' · ', array_filter([$server['cpu'], $server['memory'] ? $server['memory'].' RAM' : null, $server['disk'], $server['location']])),
            'period' => 1,
            'cost' => round($server['cost'], 2),
            'quote' => [...$server, 'options' => Arr::only($options, ['os', 'cp', 'raid', 'regions'])],
        ];
    }

    public function order(CloudService $service): array
    {
        $id = $service->config['server_id'];

        return $this->ordered($this->api->orderDedicated($id, [
            'hostname' => $service->config['hostname'],
            'enablepassword' => true,
            'rootPassword' => $service->secret,
            'server_id' => (int) $id,
            'server_hostname' => $service->config['hostname'],
            'server_root_password' => $service->secret,
        ]));
    }

    public function fetch(CloudService $service): array
    {
        $details = $this->api->service('servers', $service->interserver_uuid);
        $info = (array) ($details['serviceInfo'] ?? []);

        return [
            'remote' => array_filter([
                'hostname' => $info['server_hostname'] ?? null,
                'package' => $details['package'] ?? null,
                'payment' => data_get($details, 'billingDetails.service_payment_status'),
            ], fn ($value) => filled($value)),
            'status' => $info['server_status'] ?? null,
            'next_due_at' => $this->date(data_get($details, 'billingDetails.next_date')),
        ];
    }

    public function actions(CloudService $service): array
    {
        return ['power_on' => 'Power on', 'reboot' => 'Reboot', 'shutdown' => 'Shut down'];
    }

    public function perform(CloudService $service, string $action, array $input = []): string
    {
        $command = ['power_on' => 'on', 'reboot' => 'cycle', 'shutdown' => 'soft'][$action] ?? null;
        if (! $command) {
            return parent::perform($service, $action, $input);
        }

        $this->api->postAction('servers', $service->interserver_uuid, 'ipmi_power', ['action' => $command]);

        return match ($action) {
            'power_on' => 'Powering on your server.',
            'reboot' => 'Rebooting your server.',
            default => 'Shutting down your server.',
        };
    }

    private function disk(mixed $disk): string
    {
        if (! is_array($disk)) {
            return (string) $disk;
        }

        return collect($disk)->map(fn ($part) => is_array($part) ? implode(' ', Arr::flatten($part)) : (string) $part)->filter()->implode(', ');
    }
}
