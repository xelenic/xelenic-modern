<?php

use App\Enums\CloudProduct;
use App\Enums\CloudStatus;
use App\Models\CloudService;
use App\Models\Setting;
use App\Models\User;
use App\Services\InterServer\InterServer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const IS_API = 'https://isapi.test/apiv2';

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['services.interserver.key' => 'test-key', 'services.interserver.url' => IS_API]);
    app()->forgetInstance(InterServer::class);
});

/**
 * A pretend InterServer API: "METHOD /path" => response body (or a closure
 * taking the request), with $overrides replacing any of them.
 *
 * @param  array<string, mixed>  $overrides
 */
function fakeInterServer(array $overrides = []): void
{
    $routes = [
        'GET /ping' => 'pong',
        'GET /vps/order' => [
            'platformNames' => ['kvm' => 'KVM', 'hyperv' => 'Hyper-V', 'kvmstorage' => 'KVM Storage', 'openvz' => 'OpenVZ'],
            'vpsSliceKvmLCost' => 6, 'vpsSliceKvmWCost' => 10, 'vpsSliceKvmStorageCost' => 10, 'vpsSliceHypervCost' => 10,
            'ramSlice' => 2048, 'hdSlice' => 30, 'hdStorageSlice' => 1000, 'bwSlice' => 2000, 'maxSlices' => 16,
            'locationNames' => ['1' => 'Secaucus, NJ', '2' => 'Los Angeles, CA'],
            'locationStock' => ['1' => ['kvm' => true, 'hyperv' => true, 'kvmstorage' => true], '2' => ['kvm' => true, 'hyperv' => false, 'kvmstorage' => false]],
            'osNames' => ['ubuntu' => 'Ubuntu', 'windows' => 'Windows'],
            'templates' => [
                'kvm' => ['ubuntu' => ['ubuntu-24.04' => '24.04 LTS'], 'windows' => ['windows2022' => 'Server 2022']],
                'hyperv' => ['windows' => ['Windows2022' => 'Server 2022']],
            ],
            'cpanelCost' => 20, 'daCost' => 8,
        ],
        'PUT /vps/order' => fn (Request $r) => ['continue' => true, 'errors' => [], 'service_cost' => 6 * $r['slices'] * $r['period'], 'rootpass' => $r['rootpass']],
        'POST /vps/order' => ['continue' => true, 'service_uuid' => 'vps-uuid', 'invoice_uuids' => ['inv-1']],
        'GET /billing/pay/prepay/inv-1' => ['type' => 'single', 'text' => 'Paid.'],
        'GET /vps/vps-uuid' => [
            'serviceInfo' => ['vps_status' => 'active', 'vps_ip' => '203.0.113.10', 'vps_hostname' => 'server.example.com', 'vps_os' => 'ubuntu', 'vps_server_status' => 'running'],
            'billingDetails' => ['next_date' => '2026-12-25'],
        ],
        'GET /vps/vps-uuid/restart' => ['text' => 'Restarting your server.'],
        'POST /vps/vps-uuid/change_root_password' => ['text' => 'ok'],
        'DELETE /vps/vps-uuid' => ['text' => 'Cancelled.'],
        'GET /websites/order' => ['serviceTypes' => [
            '10' => ['services_id' => 10, 'services_name' => 'Standard cPanel', 'services_cost' => 5, 'services_buyable' => 1, 'services_hidden' => 0, 'services_module' => 'webhosting', 'services_description' => 'One site'],
            '11' => ['services_id' => 11, 'services_name' => 'Hidden plan', 'services_cost' => 1, 'services_buyable' => 1, 'services_hidden' => 1, 'services_module' => 'webhosting'],
        ]],
        'PUT /websites/order' => ['continue' => true, 'serviceCost' => 54],
        'GET /domains/lookup/yourbrand.com' => ['available' => true, 'premium' => false, 'new' => 12, 'renewal' => 14, 'whois_privacy' => true],
        'GET /domains/lookup/taken.com' => ['available' => false, 'premium' => false, 'new' => 12, 'renewal' => 14, 'whois_privacy' => true],
        'GET /domains/order' => ['whoisPrivacyCost' => 2],
        'PATCH /domains/order' => 'success',
        'GET /buy_now_servers_list' => [[
            'server_id' => 77, 'cpu' => ['Xeon E5-2650', ['num_cores' => 8, 'num_cpus' => 2]], 'memory' => '64 GB', 'disk' => '2x 1TB SSD',
            'bandwidth' => '10 TB', 'ips' => '5', 'location' => 'Secaucus, NJ', 'price' => 99,
        ]],
        'GET /servers/order/buy_now_server' => ['os' => ['ubuntu' => 'Ubuntu']],
        'POST /servers/order/buy_now_server' => ['order_details' => ['service_uuid' => 'srv-uuid', 'invoice_uuid' => 'inv-1']],
        ...$overrides,
    ];

    // Http::fake() keeps the first fake that answers, so later calls in a
    // test swap the routes this one reads rather than faking again.
    app()->instance('interserver.fake', $routes);

    if (app()->bound('interserver.faked')) {
        return;
    }
    app()->instance('interserver.faked', true);

    Http::fake(function (Request $request) {
        $path = substr(parse_url($request->url(), PHP_URL_PATH), strlen('/apiv2'));
        $body = app('interserver.fake')["{$request->method()} {$path}"] ?? null;
        if ($body instanceof Closure) {
            $body = $body($request);
        }

        return $body === null
            ? Http::response(['message' => "No fake for {$request->method()} {$path}"], 404)
            : Http::response($body);
    });
}

/**
 * @param  array<string, mixed>  $overrides
 */
function vpsOrder(array $overrides = []): array
{
    return [
        'platform' => 'kvm', 'location' => 1, 'os' => 'ubuntu', 'version' => 'ubuntu-24.04', 'slices' => 2, 'period' => 1,
        'hostname' => 'server.example.com', 'password' => 'Correct-Horse-9', 'password_confirmation' => 'Correct-Horse-9', 'control_panel' => 'none',
        ...$overrides,
    ];
}

/**
 * A client's VPS order, placed through the order form.
 */
function orderVps(User $client, array $overrides = []): CloudService
{
    test()->actingAs($client)->post(route('cloud.store', CloudProduct::Vps), vpsOrder($overrides))->assertSessionHasNoErrors();

    return $client->cloudServices()->latest('id')->firstOrFail();
}

function sentTo(string $method, string $path): int
{
    return Http::recorded(fn (Request $r) => $r->method() === $method && str_starts_with(parse_url($r->url(), PHP_URL_PATH), '/apiv2'.$path))->count();
}

test('clients see what they can order, and a note when ordering is not open yet', function () {
    $client = userWithRole('user');

    $this->actingAs($client)->get(route('cloud.index'))
        ->assertOk()
        ->assertSeeInOrder(['Vplates', 'HostBuckets', 'Domain', 'DedicServer'])
        ->assertDontSee('Ordering online opens soon');

    config(['services.interserver.key' => null]);
    app()->forgetInstance(InterServer::class);

    $this->actingAs($client)->get(route('cloud.index'))->assertSee('Ordering online opens soon');
    $this->actingAs($client)->get(route('cloud.create', CloudProduct::Vps))->assertOk()->assertSee('Ordering online isn');
});

test('ordering a cloud server prices it with the markup and buys nothing', function () {
    fakeInterServer();
    $client = userWithRole('user');

    $this->actingAs($client)->get(route('cloud.create', CloudProduct::Vps))
        ->assertOk()
        ->assertSee('Secaucus, NJ')
        ->assertSee('Ubuntu')
        ->assertDontSee('OpenVZ');

    $response = $this->actingAs($client)->post(route('cloud.store', CloudProduct::Vps), vpsOrder(['period' => 6]));

    $service = $client->cloudServices()->sole();
    $response->assertRedirect(route('cloud.show', $service));

    expect($service->status)->toBe(CloudStatus::Requested)
        ->and($service->product)->toBe(CloudProduct::Vps)
        ->and($service->name)->toBe('server.example.com')
        ->and((float) $service->cost)->toBe(72.0)          // 2 slices × $6 × 6 months, as quoted
        ->and((float) $service->price)->toBe(93.6)         // + 30% markup
        ->and((float) $service->monthly_price)->toBe(15.6)
        ->and($service->period)->toBe(6)
        ->and($service->secret)->toBe('Correct-Horse-9')
        ->and($service->quote)->not->toHaveKey('rootpass')
        ->and($service->getRawOriginal('secret'))->not->toContain('Correct-Horse-9');

    expect(sentTo('PUT', '/vps/order'))->toBe(1)
        ->and(sentTo('POST', '/vps/order'))->toBe(0)
        ->and(sentTo('GET', '/billing/pay'))->toBe(0);

    $this->actingAs($client)->get(route('cloud.show', $service))
        ->assertOk()
        ->assertSee('Awaiting approval')
        ->assertSee('$93.60 every 6 months')
        ->assertSee('Withdraw order');
});

test('cloud server orders are checked before they reach the team', function () {
    fakeInterServer();
    $client = userWithRole('user');

    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Vps), vpsOrder(['password' => 'weakpass', 'password_confirmation' => 'weakpass']))
        ->assertSessionHasErrors('password');

    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Vps), vpsOrder(['os' => 'windows', 'version' => 'windows2022', 'slices' => 1]))
        ->assertSessionHasErrors(['order' => 'Windows servers need at least 2 slices.']);

    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Vps), vpsOrder(['platform' => 'hyperv', 'os' => 'windows', 'version' => 'Windows2022', 'location' => 2]))
        ->assertSessionHasErrors('order');

    fakeInterServer(['PUT /vps/order' => ['continue' => false, 'errors' => ['Invalid hostname']]]);
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Vps), vpsOrder())
        ->assertSessionHasErrors(['order' => 'InterServer couldn\'t take this server as it is: Invalid hostname'])
        ->assertSessionMissing('_old_input.password');

    expect($client->cloudServices()->count())->toBe(0);
});

test('approving an order buys it on InterServer and pays from the prepay balance', function () {
    fakeInterServer();
    $service = orderVps(userWithRole('user'));
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('admin.cloud.approve', $service))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $service->refresh();
    expect($service->status)->toBe(CloudStatus::Provisioning)
        ->and($service->interserver_uuid)->toBe('vps-uuid')
        ->and($service->interserver_invoices)->toBe(['inv-1'])
        ->and($service->secret)->toBeNull()
        ->and($service->approver->is($admin))->toBeTrue();

    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/vps/order')
        && $r['rootpass'] === 'Correct-Horse-9' && $r['slices'] === 2 && $r['hostname'] === 'server.example.com' && $r->hasHeader('X-API-KEY', 'test-key'));
    expect(sentTo('GET', '/billing/pay/prepay/inv-1'))->toBe(1);

    // A second approval (another tab, a double click) buys nothing more.
    $this->actingAs($admin)->post(route('admin.cloud.approve', $service))->assertSessionHasErrors(['cloud' => 'This order has already been handled.']);
    expect(sentTo('POST', '/vps/order'))->toBe(1);
});

test('a failed payment can be retried without ordering again', function () {
    fakeInterServer([
        'GET /billing/pay/prepay/inv-1' => ['type' => 'error', 'text' => 'Insufficient prepay balance'],
        'GET /vps/vps-uuid' => ['serviceInfo' => ['vps_status' => 'pending']],
    ]);
    $service = orderVps(userWithRole('user'));
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('admin.cloud.approve', $service))->assertSessionHasErrors('cloud');

    $service->refresh();
    expect($service->status)->toBe(CloudStatus::Failed)
        ->and($service->interserver_uuid)->toBe('vps-uuid')
        ->and($service->failure)->toContain('Insufficient prepay balance')
        ->and($service->failure)->toContain('it won\'t order again');

    $this->actingAs($admin)->get(route('admin.cloud.show', $service))->assertOk()->assertSee('Retry payment')->assertDontSee('Decline order');
    $this->actingAs($admin)->post(route('admin.cloud.reject', $service))->assertStatus(409);

    fakeInterServer();
    $this->actingAs($admin)->post(route('admin.cloud.retry', $service))->assertSessionHasNoErrors();

    // Still the one order from the approval; the payment tried twice.
    expect($service->fresh()->status)->toBe(CloudStatus::Provisioning)
        ->and(sentTo('POST', '/vps/order'))->toBe(1)
        ->and(sentTo('GET', '/billing/pay/prepay/inv-1'))->toBe(2);
});

test('a failed order can be tried again or declined', function () {
    fakeInterServer(['POST /vps/order' => ['continue' => false, 'errors' => ['Location is full']]]);
    $service = orderVps(userWithRole('user'));
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('admin.cloud.approve', $service))->assertSessionHasErrors('cloud');

    $service->refresh();
    expect($service->status)->toBe(CloudStatus::Failed)
        ->and($service->interserver_uuid)->toBeNull()
        ->and($service->secret)->toBe('Correct-Horse-9')
        ->and($service->failure)->toContain('Location is full');

    fakeInterServer();
    $this->actingAs($admin)->post(route('admin.cloud.retry', $service))->assertSessionHasNoErrors();
    expect($service->fresh()->status)->toBe(CloudStatus::Provisioning);
});

test('services move to active from InterServer, and clients can manage them', function () {
    fakeInterServer();
    $client = userWithRole('user');
    $service = orderVps($client);
    $this->actingAs(userWithRole('admin'))->post(route('admin.cloud.approve', $service));

    $this->actingAs($client)->get(route('cloud.show', $service))
        ->assertOk()
        ->assertSee('203.0.113.10')
        ->assertSee('Restart')
        ->assertSee('Change password')
        ->assertSee('Request cancellation');

    $service->refresh();
    expect($service->status)->toBe(CloudStatus::Active)
        ->and($service->activated_at)->not->toBeNull()
        ->and($service->next_due_at->toDateString())->toBe('2026-12-25');

    $this->actingAs($client)->post(route('cloud.action', [$service, 'restart']))->assertSessionHas('success', 'Restarting your server.');
    expect(sentTo('GET', '/vps/vps-uuid/restart'))->toBe(1);

    $this->actingAs($client)->post(route('cloud.action', [$service, 'password']), ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
    $this->actingAs($client)->post(route('cloud.action', [$service, 'password']), ['password' => 'New-Password-77', 'password_confirmation' => 'New-Password-77'])->assertSessionHasNoErrors();
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/change_root_password') && $r['password'] === 'New-Password-77');

    $this->actingAs($client)->post(route('cloud.action', [$service, 'nameservers']))->assertNotFound();

    $this->actingAs($client)->post(route('cloud.cancel', $service))->assertSessionHas('success');
    expect($service->fresh()->cancel_requested_at)->not->toBeNull()
        ->and($service->fresh()->status)->toBe(CloudStatus::Active)
        ->and(sentTo('DELETE', '/vps/vps-uuid'))->toBe(0);
});

test('clients only see their own services', function () {
    fakeInterServer();
    $service = orderVps(userWithRole('user'));
    $other = userWithRole('user');

    $this->actingAs($other)->get(route('cloud.show', $service))->assertNotFound();
    $this->actingAs($other)->post(route('cloud.cancel', $service))->assertNotFound();
    $this->actingAs($other)->post(route('cloud.refresh', $service))->assertNotFound();
    $this->actingAs($other)->get(route('cloud.index'))->assertDontSee('server.example.com');

    // And nothing can be done with one that isn't running yet.
    $this->actingAs($service->user)->post(route('cloud.action', [$service, 'restart']))->assertNotFound();
});

test('clients can withdraw an order nobody has approved', function () {
    fakeInterServer();
    $client = userWithRole('user');
    $service = orderVps($client);

    $this->actingAs($client)->post(route('cloud.cancel', $service))->assertRedirect(route('cloud.index'));

    $service->refresh();
    expect($service->status)->toBe(CloudStatus::Cancelled)->and($service->secret)->toBeNull();
    $this->actingAs(userWithRole('admin'))->post(route('admin.cloud.approve', $service))->assertSessionHasErrors('cloud');
    expect(sentTo('POST', '/vps/order'))->toBe(0);
});

test('the team can decline an order with a reason', function () {
    fakeInterServer();
    $client = userWithRole('user');
    $service = orderVps($client);

    $this->actingAs(userWithRole('admin'))->post(route('admin.cloud.reject', $service), ['reason' => 'That location is full this week.'])
        ->assertRedirect(route('admin.cloud.index'));

    $service->refresh();
    expect($service->status)->toBe(CloudStatus::Rejected)->and($service->secret)->toBeNull();
    $this->actingAs($client)->get(route('cloud.show', $service))->assertSee('Declined')->assertSee('That location is full this week.');
    expect(sentTo('POST', '/vps/order'))->toBe(0);
});

test('the team can cancel a service on InterServer', function () {
    fakeInterServer();
    $service = orderVps(userWithRole('user'));
    $admin = userWithRole('admin');
    $this->actingAs($admin)->post(route('admin.cloud.approve', $service));

    $this->actingAs($admin)->post(route('admin.cloud.cancel', $service), ['when' => 'now'])->assertSessionHas('success');

    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_contains($r->url(), '/vps/vps-uuid?cancel_on=now'));
    expect($service->fresh()->status)->toBe(CloudStatus::Cancelled);
});

test('only the team can use the admin side of Cloud', function () {
    fakeInterServer();
    $service = orderVps(userWithRole('user'));

    foreach (['manager', 'user'] as $role) {
        $user = userWithRole($role);
        $this->actingAs($user)->get(route('admin.cloud.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.cloud.approve', $service))->assertForbidden();
    }

    $this->actingAs(userWithRole('admin'))->get(route('admin.cloud.index'))
        ->assertOk()
        ->assertSee('server.example.com')
        ->assertSee('Connected to InterServer');
    $this->actingAs(userWithRole('admin'))->get(route('admin.cloud.show', $service))
        ->assertOk()
        ->assertSee('Approve and buy')
        ->assertSee('$12.00')
        ->assertDontSee('Correct-Horse-9');
});

test('markups and products are set in the admin settings', function () {
    fakeInterServer();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get(route('admin.cloud.settings'))->assertOk()->assertSee('Connected');

    $this->actingAs($admin)->put(route('admin.cloud.settings.update'), [
        'markup' => ['vps' => 50, 'hosting' => 30, 'domain' => 20, 'dedicated' => 10],
        'enabled' => ['vps' => 1, 'hosting' => 1, 'domain' => 1, 'dedicated' => 0],
    ])->assertRedirect(route('admin.cloud.settings'));

    expect(Setting::get('cloud.markup.vps'))->toEqual(50);

    $client = userWithRole('user');
    $service = orderVps($client);
    expect((float) $service->price)->toBe(18.0); // $12 + 50%

    $this->actingAs($client)->get(route('cloud.index'))->assertDontSee('DedicServer');
    $this->actingAs($client)->get(route('cloud.create', CloudProduct::Dedicated))->assertNotFound();

    $this->actingAs($admin)->put(route('admin.cloud.settings.update'), ['markup' => ['vps' => -5]])->assertSessionHasErrors('markup.vps');
});

test('clients can look up a domain and order it with their contact details', function () {
    fakeInterServer();
    $client = userWithRole('user');

    $this->actingAs($client)->getJson(route('cloud.domains.check', ['domain' => 'yourbrand.com']))
        ->assertOk()
        ->assertJson(['domain' => 'yourbrand.com', 'available' => true, 'price' => 15.6, 'renewal' => 18.2, 'privacy' => 2.6]);
    $this->actingAs($client)->getJson(route('cloud.domains.check', ['domain' => 'taken.com']))->assertJson(['available' => false]);
    $this->actingAs($client)->getJson(route('cloud.domains.check', ['domain' => 'not a domain']))->assertUnprocessable();

    $contact = [
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'phone' => '+94.771234567',
        'address1' => '1 Main Street', 'city' => 'Colombo', 'state' => 'Western', 'postal_code' => '00100', 'country' => 'lk',
    ];

    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Domain), ['domain' => 'taken.com', 'privacy' => 1, ...$contact])
        ->assertSessionHasErrors('order');
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Domain), ['domain' => 'yourbrand.com', 'privacy' => 1, ...$contact, 'phone' => '0771234567'])
        ->assertSessionHasErrors('phone');
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Domain), ['domain' => 'yourbrand.com', 'privacy' => 1, ...$contact])
        ->assertSessionHasNoErrors();

    $service = $client->cloudServices()->sole();
    expect($service->product)->toBe(CloudProduct::Domain)
        ->and((float) $service->cost)->toBe(14.0)
        ->and((float) $service->price)->toBe(18.2)
        ->and($service->config)->toMatchArray(['hostname' => 'yourbrand.com', 'whois_privacy' => 'enable', 'country' => 'LK'])
        ->and($service->priceLabel())->toBe('$18.20 / year');
    Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['first_name'] === 'Ada');
});

test('clients can order web hosting and a dedicated server', function () {
    fakeInterServer();
    $client = userWithRole('user');

    $this->actingAs($client)->get(route('cloud.create', CloudProduct::Hosting))->assertOk()->assertSee('Standard cPanel')->assertDontSee('Hidden plan');
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Hosting), ['plan' => 10, 'domain' => 'yourbrand.com', 'period' => 12])->assertSessionHasNoErrors();
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Hosting), ['plan' => 11, 'domain' => 'yourbrand.com', 'period' => 12])->assertSessionHasErrors('order');

    $hosting = $client->cloudServices()->where('product', CloudProduct::Hosting)->sole();
    expect((float) $hosting->cost)->toBe(54.0)
        ->and((float) $hosting->price)->toBe(70.2)
        ->and($hosting->plan)->toBe('Standard cPanel · cPanel');

    $this->actingAs($client)->get(route('cloud.create', CloudProduct::Dedicated))->assertOk()->assertSee('Xeon E5-2650')->assertSee('16 cores');
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Dedicated), [
        'server' => '77', 'hostname' => 'metal.example.com', 'password' => 'NoSymbol123', 'password_confirmation' => 'NoSymbol123',
    ])->assertSessionHasErrors('password');
    $this->actingAs($client)->post(route('cloud.store', CloudProduct::Dedicated), [
        'server' => '77', 'hostname' => 'metal.example.com', 'password' => 'With-Symbol123', 'password_confirmation' => 'With-Symbol123',
    ])->assertSessionHasNoErrors();

    $dedicated = $client->cloudServices()->where('product', CloudProduct::Dedicated)->sole();
    expect((float) $dedicated->price)->toBe(128.7);

    $this->actingAs(userWithRole('admin'))->post(route('admin.cloud.approve', $dedicated))->assertSessionHasNoErrors();
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_contains($r->url(), '/servers/order/buy_now_server?a=77')
        && $r['hostname'] === 'metal.example.com' && $r['rootPassword'] === 'With-Symbol123');
    expect($dedicated->fresh()->interserver_uuid)->toBe('srv-uuid');
});
