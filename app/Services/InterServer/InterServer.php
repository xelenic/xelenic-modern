<?php

namespace App\Services\InterServer;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The InterServer Management API (https://my.interserver.net/apiv2), as the
 * reseller account set in config/services.php. Services are addressed by
 * UUID. Ordering creates invoices on the account; pay() settles them from
 * its prepay balance, which is what starts provisioning.
 *
 * Reads are retried on a dropped connection; anything that buys, pays or
 * changes a server is sent once.
 */
class InterServer
{
    public function __construct(
        private readonly ?string $key,
        private readonly string $url,
        private readonly int $timeout = 30,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('services.interserver.key'), (string) config('services.interserver.url'), (int) config('services.interserver.timeout', 30));
    }

    public function configured(): bool
    {
        return filled($this->key);
    }

    /**
     * Whether the API answers, with this key.
     */
    public function ping(): bool
    {
        try {
            return $this->read('/ping') === 'pong';
        } catch (InterServerException) {
            return false;
        }
    }

    // ---- VPS ---------------------------------------------------------------

    /**
     * Platforms, OS templates, locations and slice pricing (cached briefly).
     *
     * @return array<string, mixed>
     */
    public function vpsCatalog(): array
    {
        return Cache::remember('interserver.vps-catalog', now()->addMinutes(30), fn () => (array) $this->read('/vps/order'));
    }

    /**
     * Price a VPS order without placing it.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function quoteVps(array $order): array
    {
        return (array) $this->send('put', '/vps/order', $order);
    }

    /**
     * Place a VPS order: creates its invoice(s), unpaid.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function orderVps(array $order): array
    {
        return (array) $this->send('post', '/vps/order', $order);
    }

    // ---- Web hosting ---------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function hostingCatalog(): array
    {
        return Cache::remember('interserver.hosting-catalog', now()->addMinutes(30), fn () => (array) $this->read('/websites/order'));
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function quoteHosting(array $order): array
    {
        return (array) $this->send('put', '/websites/order', $order);
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function orderHosting(array $order): array
    {
        return (array) $this->send('post', '/websites/order', $order);
    }

    /**
     * A one-time link into the website's control panel.
     */
    public function hostingLogin(string $uuid): ?string
    {
        $response = (array) $this->send('get', "/websites/{$uuid}/login");

        return $response['location'] ?? null;
    }

    // ---- Domains -------------------------------------------------------------

    /**
     * Availability and price of one domain (cached for a few minutes).
     *
     * @return array<string, mixed>
     */
    public function domainLookup(string $domain): array
    {
        $domain = strtolower($domain);

        return Cache::remember('interserver.domain.'.md5($domain), now()->addMinutes(5), fn () => (array) $this->read('/domains/lookup/'.rawurlencode($domain)));
    }

    /**
     * TLD catalog and the Whois privacy price.
     *
     * @return array<string, mixed>
     */
    public function domainCatalog(): array
    {
        return Cache::remember('interserver.domain-catalog', now()->addHours(6), fn () => (array) $this->read('/domains/order'));
    }

    /**
     * The per-TLD fields a registration needs.
     *
     * @return array<string, mixed>
     */
    public function domainFields(string $domain): array
    {
        $response = (array) $this->send('put', '/domains/order', ['hostname' => $domain, 'type' => 'register']);

        return (array) ($response['domainFields'] ?? []);
    }

    /**
     * Check a domain order's details; null when they pass, else the errors.
     *
     * @param  array<string, mixed>  $order
     * @return array<int|string, mixed>|null
     */
    public function validateDomain(array $order): ?array
    {
        $response = $this->send('patch', '/domains/order', $order);

        if ($response === 'success' || (is_array($response) && empty($response['errors']))) {
            return null;
        }

        return (array) ($response['errors'] ?? $response);
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function orderDomain(array $order): array
    {
        return (array) $this->send('post', '/domains/order', $order);
    }

    /**
     * Replace the domain's nameservers at the registrar.
     *
     * @param  list<string>  $nameservers
     */
    public function setNameservers(string $uuid, array $nameservers): string
    {
        return $this->text($this->send('put', "/domains/{$uuid}/nameservers", ['nameserver' => array_values($nameservers)]));
    }

    // ---- Rapid Deploy dedicated servers -------------------------------------

    /**
     * Servers ready to deploy, with live prices (cached briefly).
     *
     * @return list<array<string, mixed>>
     */
    public function dedicatedServers(): array
    {
        return Cache::remember('interserver.rapid-deploy', now()->addMinutes(10), fn () => array_values((array) $this->read('/buy_now_servers_list')));
    }

    /**
     * Options for one Rapid Deploy server; fails if it's been sold.
     *
     * @return array<string, mixed>
     */
    public function dedicatedOptions(int|string $serverId): array
    {
        return (array) $this->send('get', '/servers/order/buy_now_server', query: ['a' => $serverId]);
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function orderDedicated(int|string $serverId, array $order): array
    {
        return (array) $this->send('post', '/servers/order/buy_now_server?a='.rawurlencode((string) $serverId), $order);
    }

    // ---- Any service -----------------------------------------------------------

    /**
     * Full details of a service: module is vps, websites, domains or servers.
     *
     * @return array<string, mixed>
     */
    public function service(string $module, string $uuid): array
    {
        return (array) $this->read("/{$module}/{$uuid}");
    }

    /**
     * A GET action on a service (e.g. vps start / stop / restart).
     */
    public function action(string $module, string $uuid, string $action): string
    {
        return $this->text($this->send('get', "/{$module}/{$uuid}/{$action}"));
    }

    /**
     * A POST action on a service (e.g. change_root_password, ipmi_power).
     *
     * @param  array<string, mixed>  $body
     */
    public function postAction(string $module, string $uuid, string $action, array $body = []): string
    {
        return $this->text($this->send('post', "/{$module}/{$uuid}/{$action}", $body));
    }

    /**
     * Cancel a service now, or the day before it renews.
     */
    public function cancel(string $module, string $uuid, string $when = 'renewal'): string
    {
        return $this->text($this->send('delete', "/{$module}/{$uuid}", query: ['cancel_on' => $when === 'now' ? 'now' : 'renewal']));
    }

    // ---- Billing ---------------------------------------------------------------

    /**
     * Pay invoices from the account's prepay balance. Paying is what starts
     * provisioning.
     *
     * @param  list<string>  $invoiceUuids
     */
    public function pay(array $invoiceUuids): string
    {
        $invoiceUuids = array_values(array_filter($invoiceUuids));
        if ($invoiceUuids === []) {
            throw new InterServerException('There are no invoices to pay.');
        }

        $response = (array) $this->send('get', '/billing/pay/prepay/'.implode(',', $invoiceUuids));

        if (($response['type'] ?? null) !== 'single') {
            throw new InterServerException('Paying from the prepay balance didn\'t go through: '.$this->text($response));
        }

        return $this->text($response);
    }

    // ---- HTTP --------------------------------------------------------------------

    private function http(): PendingRequest
    {
        if (! $this->configured()) {
            throw new InterServerException('No InterServer API key is set (INTERSERVER_API_KEY).');
        }

        return Http::baseUrl(rtrim($this->url, '/'))
            ->withHeaders(['X-API-KEY' => $this->key])
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->withUserAgent('Xelenic Cloud (reseller)');
    }

    /**
     * A read: retried twice if the connection drops.
     */
    private function read(string $path): mixed
    {
        try {
            $response = $this->http()->retry(3, 400, fn ($e) => $e instanceof ConnectionException, throw: false)->get($path);
        } catch (ConnectionException $e) {
            throw new InterServerException('InterServer couldn\'t be reached. Try again in a moment.', previous: $e);
        }

        return $this->decode($response);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     */
    private function send(string $method, string $path, array $body = [], array $query = []): mixed
    {
        try {
            $request = $this->http();
            $response = match ($method) {
                'get' => $request->get($path, $query),
                'delete' => $request->delete($path.($query ? '?'.http_build_query($query) : ''), $body),
                default => $request->{$method}($path.($query ? '?'.http_build_query($query) : ''), $body),
            };
        } catch (ConnectionException $e) {
            throw new InterServerException('InterServer couldn\'t be reached. Try again in a moment.', previous: $e);
        }

        return $this->decode($response);
    }

    private function decode(Response $response): mixed
    {
        $data = $response->json() ?? $response->body();

        if ($response->failed()) {
            $message = is_array($data)
                ? ($data['message'] ?? $data['error'] ?? $data['text'] ?? (isset($data['errors']) ? implode(' ', Arr::flatten((array) $data['errors'])) : null))
                : (is_string($data) && strlen($data) < 300 ? $data : null);

            throw new InterServerException(trim('InterServer: '.($message ?: 'request failed')).' (HTTP '.$response->status().')');
        }

        return $data;
    }

    /**
     * The human part of a response: its text or message.
     */
    private function text(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        return (string) (data_get($response, 'text') ?? data_get($response, 'message') ?? 'Done.');
    }
}
