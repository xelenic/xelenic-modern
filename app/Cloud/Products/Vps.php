<?php

namespace App\Cloud\Products;

use App\Cloud\CloudOrderException;
use App\Cloud\CloudSettings;
use App\Cloud\Product;
use App\Enums\CloudProduct;
use App\Models\CloudService;
use Illuminate\Support\Arr;

/**
 * Cloud servers: InterServer VPS, sized in slices (each slice adds RAM, disk
 * and bandwidth), on KVM (Linux or Windows), KVM Storage or Hyper-V.
 */
class Vps extends Product
{
    /**
     * The platforms InterServer takes new orders for.
     */
    public const PLATFORMS = ['kvm', 'kvmstorage', 'hyperv'];

    public function key(): CloudProduct
    {
        return CloudProduct::Vps;
    }

    public function module(): string
    {
        return 'vps';
    }

    public function catalog(): array
    {
        $c = $this->api->vpsCatalog();

        $platforms = [];
        foreach ((array) ($c['platformNames'] ?? []) as $key => $name) {
            if (in_array($key, self::PLATFORMS, true)) {
                $platforms[$key] = [
                    'name' => (string) $name,
                    'linux' => $this->sliceCost($c, $key, false),
                    'windows' => $this->sliceCost($c, $key, true),
                ];
            }
        }

        // platform => distro => {name, versions: {template => label}, windows}
        $os = [];
        foreach ((array) ($c['templates'] ?? []) as $platform => $distros) {
            if (! isset($platforms[$platform])) {
                continue;
            }
            foreach ((array) $distros as $distro => $versions) {
                if ((array) $versions === []) {
                    continue;
                }
                $os[$platform][$distro] = [
                    'name' => (string) ($c['osNames'][$distro] ?? ucfirst((string) $distro)),
                    'versions' => array_map('strval', (array) $versions),
                    'windows' => str_starts_with(strtolower((string) $distro), 'windows') || $platform === 'hyperv',
                ];
            }
        }

        $locations = [];
        foreach ((array) ($c['locationNames'] ?? []) as $id => $name) {
            $locations[(int) $id] = ['name' => (string) $name, 'stock' => array_map('boolval', (array) ($c['locationStock'][$id] ?? []))];
        }

        return [
            'platforms' => $platforms,
            'os' => $os,
            'locations' => $locations,
            'slice' => [
                'ram' => (int) ($c['ramSlice'] ?? 2048),
                'disk' => (int) ($c['hdSlice'] ?? 30),
                'storageDisk' => (int) ($c['hdStorageSlice'] ?? 1000),
                'bandwidth' => (int) ($c['bwSlice'] ?? 2000),
            ],
            'maxSlices' => max(1, min(32, (int) ($c['maxSlices'] ?? 16))),
            'controlPanels' => ['cpanel' => (float) ($c['cpanelCost'] ?? 0), 'da' => (float) ($c['daCost'] ?? 0)],
            'periods' => CloudSettings::periods(),
            'currency' => (string) ($c['currency'] ?? 'USD'),
        ];
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:'.implode(',', self::PLATFORMS)],
            'location' => ['required', 'integer', 'min:1'],
            'os' => ['required', 'string', 'max:60'],
            'version' => ['required', 'string', 'max:120'],
            'slices' => ['required', 'integer', 'min:1', 'max:32'],
            'period' => ['required', 'integer', 'in:'.implode(',', array_keys(CloudSettings::periods()))],
            'hostname' => ['required', 'string', 'max:120', 'regex:/^(?=.{1,120}$)([a-z0-9](-*[a-z0-9])*)(\.[a-z0-9](-*[a-z0-9])*)+$/i'],
            'password' => ['required', 'string', 'min:10', 'max:64', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'confirmed'],
            'control_panel' => ['nullable', 'in:none,cpanel,da'],
        ];
    }

    public function prepare(array $input): array
    {
        $c = $this->catalog();
        $platform = $input['platform'];
        $os = $c['os'][$platform][$input['os']] ?? null;
        $location = $c['locations'][(int) $input['location']] ?? null;

        if (! isset($c['platforms'][$platform]) || ! $os || ! isset($os['versions'][$input['version']])) {
            throw new CloudOrderException('Choose an operating system that\'s offered on this platform.');
        }
        if (! $location || ! ($location['stock'][$platform] ?? true)) {
            throw new CloudOrderException('That location has no room for this platform right now. Choose another.');
        }
        $slices = (int) $input['slices'];
        if ($slices > $c['maxSlices']) {
            throw new CloudOrderException("Choose at most {$c['maxSlices']} slices.");
        }
        if ($os['windows'] && $slices < 2) {
            throw new CloudOrderException('Windows servers need at least 2 slices.');
        }

        $config = [
            'vpsPlatform' => $platform,
            'location' => (int) $input['location'],
            'osDistro' => $input['os'],
            'osVersion' => $input['version'],
            'slices' => $slices,
            'period' => (int) $input['period'],
            'hostname' => strtolower($input['hostname']),
            'controlpanel' => $os['windows'] ? 'none' : ($input['control_panel'] ?? 'none'),
            'coupon' => '',
        ];

        $quote = $this->api->quoteVps([...$config, 'rootpass' => $input['password']]);
        if (array_key_exists('continue', $quote) && ! $quote['continue']) {
            throw new CloudOrderException('InterServer couldn\'t take this server as it is: '.$this->errors($quote));
        }

        $estimate = $this->estimate($c, $config, $os['windows']);
        $cost = (float) ($quote['service_cost'] ?? 0) > 0 ? (float) $quote['service_cost'] : $estimate;
        $ram = $slices * $c['slice']['ram'] / 1024;
        $disk = $slices * ($platform === 'kvmstorage' ? $c['slice']['storageDisk'] : $c['slice']['disk']);

        return [
            'config' => $config,
            'secret' => $input['password'],
            'name' => $config['hostname'],
            'plan' => sprintf('%s · %d %s (%s GB RAM, %s GB disk) · %s %s · %s',
                $c['platforms'][$platform]['name'], $slices, $slices === 1 ? 'slice' : 'slices', rtrim(rtrim(number_format($ram, 1), '0'), '.'), number_format($disk),
                $os['name'], $os['versions'][$input['version']], $location['name']),
            'period' => $config['period'],
            'cost' => round($cost, 2),
            // InterServer echoes the root password back; it's not kept.
            'quote' => Arr::except($quote, ['rootpass', 'password']),
        ];
    }

    public function order(CloudService $service): array
    {
        return $this->ordered($this->api->orderVps([
            ...$service->config,
            'rootpass' => $service->secret,
            'comment' => "Xelenic client #{$service->user_id}, cloud service #{$service->id}",
        ]));
    }

    public function fetch(CloudService $service): array
    {
        $details = $this->api->service('vps', $service->interserver_uuid);
        $info = (array) ($details['serviceInfo'] ?? []);

        return [
            'remote' => array_filter([
                'hostname' => $info['vps_hostname'] ?? null,
                'ip' => $info['vps_ip'] ?? null,
                'ipv6' => $info['vps_ipv6'] ?? null,
                'os' => $info['vps_os'] ?? null,
                'slices' => $info['vps_slices'] ?? null,
                'platform' => $info['vps_platform'] ?? null,
                'power' => $info['vps_server_status'] ?? null,
                'payment' => data_get($details, 'billingDetails.service_payment_status'),
                'frequency' => data_get($details, 'billingDetails.service_frequency'),
            ], fn ($value) => filled($value)),
            'status' => $info['vps_status'] ?? null,
            'next_due_at' => $this->date(data_get($details, 'billingDetails.next_date')),
        ];
    }

    public function actions(CloudService $service): array
    {
        return ['start' => 'Start', 'restart' => 'Restart', 'stop' => 'Stop', 'password' => 'Change root password'];
    }

    public function actionRules(string $action): array
    {
        return $action === 'password'
            ? ['password' => ['required', 'string', 'min:10', 'max:64', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'confirmed']]
            : [];
    }

    public function perform(CloudService $service, string $action, array $input = []): string
    {
        return match ($action) {
            'start' => $this->api->action('vps', $service->interserver_uuid, 'start') ?: 'Starting your server.',
            'stop' => $this->api->action('vps', $service->interserver_uuid, 'stop') ?: 'Stopping your server.',
            'restart' => $this->api->action('vps', $service->interserver_uuid, 'restart') ?: 'Restarting your server.',
            'password' => $this->api->postAction('vps', $service->interserver_uuid, 'change_root_password', ['password' => $input['password']])
                ? 'The new root password takes effect in about two minutes. On KVM the server restarts to apply it.'
                : 'Changing the root password.',
            default => parent::perform($service, $action, $input),
        };
    }

    /**
     * A slice's monthly price on a platform.
     *
     * @param  array<string, mixed>  $c
     */
    private function sliceCost(array $c, string $platform, bool $windows): float
    {
        $cost = match ($platform) {
            'kvm' => $windows ? ($c['vpsSliceKvmWCost'] ?? null) : ($c['vpsSliceKvmLCost'] ?? null),
            'kvmstorage' => $c['vpsSliceKvmStorageCost'] ?? null,
            'hyperv' => $c['vpsSliceHypervCost'] ?? null,
            default => null,
        };

        return (float) ($cost ?? data_get($c, 'packageCosts.'.data_get($c, "platformPackages.{$platform}"), 0));
    }

    /**
     * What the order should cost for its period, if InterServer's quote
     * didn't say.
     *
     * @param  array<string, mixed>  $c
     * @param  array<string, mixed>  $config
     */
    private function estimate(array $c, array $config, bool $windows): float
    {
        $monthly = $config['slices'] * ($windows ? $c['platforms'][$config['vpsPlatform']]['windows'] : $c['platforms'][$config['vpsPlatform']]['linux'])
            + ($c['controlPanels'][$config['controlpanel']] ?? 0);
        $discount = CloudSettings::periods()[$config['period']] ?? 0;

        return $monthly * $config['period'] * (1 - $discount / 100);
    }
}
