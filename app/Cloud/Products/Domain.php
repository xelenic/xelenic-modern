<?php

namespace App\Cloud\Products;

use App\Cloud\CloudOrderException;
use App\Cloud\Product;
use App\Enums\CloudProduct;
use App\Models\CloudService;
use App\Services\InterServer\InterServerException;
use Illuminate\Support\Arr;

/**
 * Domains: registered through InterServer (OpenSRS) in the client's name,
 * for a year at a time, with optional Whois privacy.
 */
class Domain extends Product
{
    /**
     * The registrant contact fields, as InterServer names them.
     */
    public const CONTACT = ['first_name', 'last_name', 'org_name', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'postal_code', 'country'];

    public const PATTERN = '/^(?=.{3,120}$)([a-z0-9](-*[a-z0-9])*)(\.[a-z0-9](-*[a-z0-9])*)+$/i';

    public function key(): CloudProduct
    {
        return CloudProduct::Domain;
    }

    public function module(): string
    {
        return 'domains';
    }

    public function catalog(): array
    {
        return ['privacy' => (float) ($this->api->domainCatalog()['whoisPrivacyCost'] ?? 0)];
    }

    /**
     * Whether a domain can be registered, and what it costs InterServer.
     *
     * @return array{domain: string, available: bool, premium: bool, cost: float, renewal: float, privacy: bool}
     */
    public function check(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $lookup = $this->api->domainLookup($domain);

        return [
            'domain' => $domain,
            'available' => (bool) ($lookup['available'] ?? false),
            'premium' => (bool) ($lookup['premium'] ?? false),
            'cost' => (float) ($lookup['new'] ?? data_get($lookup, 'service.services_cost', 0)),
            'renewal' => (float) ($lookup['renewal'] ?? 0),
            'privacy' => (bool) ($lookup['whois_privacy'] ?? false),
        ];
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:120', 'regex:'.self::PATTERN],
            'privacy' => ['boolean'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'org_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            // As registries expect it: +country code, a dot, then the number.
            'phone' => ['required', 'string', 'regex:/^\+\d{1,3}\.\d{4,14}$/'],
            'address1' => ['required', 'string', 'max:120'],
            'address2' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:60'],
            'state' => ['required', 'string', 'max:60'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2', 'alpha'],
        ];
    }

    public function prepare(array $input): array
    {
        $check = $this->check($input['domain']);
        if (! $check['available']) {
            throw new CloudOrderException("{$check['domain']} isn't available to register. Try another name.");
        }

        $privacy = (bool) ($input['privacy'] ?? false) && $check['privacy'];
        $contact = Arr::only($input, self::CONTACT);
        $contact['country'] = strtoupper($contact['country']);
        $config = ['hostname' => $check['domain'], 'type' => 'register', 'whois_privacy' => $privacy ? 'enable' : 'disable', ...array_filter($contact, fn ($v) => filled($v))];

        // The registry's rules for these contact details, before the team sees it.
        $problems = $this->api->validateDomain($config);
        if ($problems) {
            throw new CloudOrderException('The registry didn\'t accept the contact details: '.strip_tags(collect($problems)->flatten()->implode(' ')));
        }

        $privacyCost = $privacy ? $this->catalog()['privacy'] : 0;

        return [
            'config' => $config,
            'secret' => null,
            'name' => $check['domain'],
            'plan' => 'Registration · 1 year'.($privacy ? ' · Whois privacy' : '').($check['premium'] ? ' · Premium name' : ''),
            'period' => 1,
            'cost' => round($check['cost'] + $privacyCost, 2),
            'quote' => [...$check, 'privacy_cost' => $privacyCost],
        ];
    }

    public function order(CloudService $service): array
    {
        // The registry's own fields for this TLD, filled in from the account,
        // with the client's details over them.
        try {
            $defaults = collect($this->api->domainFields($service->config['hostname']))
                ->map(fn ($field) => is_array($field) ? ($field['value'] ?? null) : $field)
                ->filter(fn ($value) => filled($value) && is_scalar($value))
                ->all();
        } catch (InterServerException) {
            $defaults = [];
        }

        return $this->ordered($this->api->orderDomain([...$defaults, ...$service->config]));
    }

    public function fetch(CloudService $service): array
    {
        $details = $this->api->service('domains', $service->interserver_uuid);
        $info = (array) ($details['serviceInfo'] ?? []);

        return [
            'remote' => array_filter([
                'hostname' => $info['domain_hostname'] ?? null,
                'expires' => $info['domain_expire_date'] ?? null,
                'nameservers' => $service->remote('nameservers'),
                'payment' => data_get($details, 'billingDetails.service_payment_status'),
            ], fn ($value) => filled($value)),
            'status' => $info['domain_status'] ?? null,
            'next_due_at' => $this->date($info['domain_expire_date'] ?? data_get($details, 'billingDetails.next_date')),
        ];
    }

    public function actions(CloudService $service): array
    {
        return ['nameservers' => 'Change nameservers'];
    }

    public function actionRules(string $action): array
    {
        return $action === 'nameservers' ? [
            'nameservers' => ['required', 'array', 'min:2', 'max:6'],
            'nameservers.*' => ['nullable', 'string', 'max:120', 'regex:'.self::PATTERN],
        ] : [];
    }

    public function perform(CloudService $service, string $action, array $input = []): string
    {
        if ($action !== 'nameservers') {
            return parent::perform($service, $action, $input);
        }

        $nameservers = array_values(array_filter(array_map(fn ($ns) => strtolower(trim((string) $ns)), $input['nameservers'])));
        if (count($nameservers) < 2) {
            throw new CloudOrderException('Give at least two nameservers.');
        }

        $this->api->setNameservers($service->interserver_uuid, $nameservers);
        $service->update(['remote' => [...(array) $service->remote, 'nameservers' => $nameservers]]);

        return 'Nameservers updated. Changes can take a few hours to spread across the internet.';
    }
}
