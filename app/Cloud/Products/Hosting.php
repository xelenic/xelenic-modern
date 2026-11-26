<?php

namespace App\Cloud\Products;

use App\Cloud\CloudOrderException;
use App\Cloud\CloudSettings;
use App\Cloud\Product;
use App\Enums\CloudProduct;
use App\Models\CloudService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Web hosting: InterServer's shared hosting plans (cPanel, DirectAdmin,
 * Plesk, WordPress), for one website domain.
 */
class Hosting extends Product
{
    public function key(): CloudProduct
    {
        return CloudProduct::Hosting;
    }

    public function module(): string
    {
        return 'websites';
    }

    public function catalog(): array
    {
        $c = $this->api->hostingCatalog();

        $plans = collect((array) ($c['serviceTypes'] ?? []))
            ->filter(fn ($plan) => is_array($plan)
                && (string) ($plan['services_buyable'] ?? '1') === '1'
                && (string) ($plan['services_hidden'] ?? '0') !== '1'
                && ($plan['services_module'] ?? 'webhosting') === 'webhosting'
                && (float) ($plan['services_cost'] ?? 0) > 0)
            ->map(fn (array $plan) => [
                'id' => (int) $plan['services_id'],
                'name' => (string) $plan['services_name'],
                'cost' => (float) $plan['services_cost'],
                'panel' => $this->panel((string) $plan['services_name']),
                'description' => Str::limit(trim(strip_tags((string) ($plan['services_description'] ?? ''))), 220),
            ])
            ->sortBy('cost')
            ->values()
            ->all();

        return ['plans' => $plans, 'periods' => CloudSettings::periods()];
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', 'integer'],
            'domain' => ['required', 'string', 'max:120', 'regex:/^(?=.{1,120}$)([a-z0-9](-*[a-z0-9])*)(\.[a-z0-9](-*[a-z0-9])*)+$/i'],
            'period' => ['required', 'integer', 'in:'.implode(',', array_keys(CloudSettings::periods()))],
        ];
    }

    public function prepare(array $input): array
    {
        $plan = collect($this->catalog()['plans'])->firstWhere('id', (int) $input['plan']);
        if (! $plan) {
            throw new CloudOrderException('Choose one of the hosting plans on offer.');
        }

        $config = [
            'packageId' => $plan['id'],
            'hostname' => strtolower($input['domain']),
            'period' => (int) $input['period'],
            'coupon' => '',
            // InterServer's terms of service, accepted for the reseller account.
            'tos' => 'yes',
        ];

        $quote = $this->api->quoteHosting($config);
        if (array_key_exists('continue', $quote) && ! $quote['continue']) {
            throw new CloudOrderException('InterServer couldn\'t take this hosting order as it is: '.$this->errors($quote));
        }

        $discount = CloudSettings::periods()[$config['period']] ?? 0;
        $cost = (float) ($quote['serviceCost'] ?? 0) > 0 ? (float) $quote['serviceCost'] : $plan['cost'] * $config['period'] * (1 - $discount / 100);

        return [
            'config' => $config,
            'secret' => null,
            'name' => $config['hostname'],
            'plan' => $plan['name'].' · '.$plan['panel'],
            'period' => $config['period'],
            'cost' => round($cost, 2),
            'quote' => Arr::except($quote, ['password', 'rootpass']),
        ];
    }

    public function order(CloudService $service): array
    {
        return $this->ordered($this->api->orderHosting([
            ...$service->config,
            'comment' => "Xelenic client #{$service->user_id}, cloud service #{$service->id}",
        ]));
    }

    public function fetch(CloudService $service): array
    {
        $details = $this->api->service('websites', $service->interserver_uuid);
        $info = (array) ($details['serviceInfo'] ?? []);

        return [
            'remote' => array_filter([
                'hostname' => $info['website_hostname'] ?? null,
                'ip' => $info['website_ip'] ?? null,
                'username' => $info['website_username'] ?? null,
                'nameserver1' => data_get($details, 'serviceMaster.website_dns1'),
                'nameserver2' => data_get($details, 'serviceMaster.website_dns2'),
                'package' => $details['package'] ?? null,
                'payment' => data_get($details, 'billingDetails.service_payment_status'),
            ], fn ($value) => filled($value)),
            'status' => $info['website_status'] ?? null,
            'next_due_at' => $this->date(data_get($details, 'billingDetails.next_date')),
        ];
    }

    public function actions(CloudService $service): array
    {
        return ['login' => 'Open control panel'];
    }

    /**
     * The control panel login link (the controller sends the client to it).
     */
    public function perform(CloudService $service, string $action, array $input = []): string
    {
        if ($action !== 'login') {
            return parent::perform($service, $action, $input);
        }

        return $this->api->hostingLogin($service->interserver_uuid)
            ?? throw new CloudOrderException('The control panel didn\'t give a login link. Try again in a moment.');
    }

    private function panel(string $name): string
    {
        $name = strtolower($name);

        return match (true) {
            str_contains($name, 'cpanel') => 'cPanel',
            str_contains($name, 'direct admin') || str_contains($name, 'directadmin') || str_starts_with($name, 'da ') => 'DirectAdmin',
            str_contains($name, 'plesk') => 'Plesk',
            str_contains($name, 'wordpress') => 'WordPress',
            default => 'Web hosting',
        };
    }
}
