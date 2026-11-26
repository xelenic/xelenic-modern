{{-- One of the client's cloud services (CloudController@show): where the order
     is, how to reach it, what can be done with it, and its billing. --}}
@use('App\Enums\CloudProduct')
@use('App\Enums\CloudStatus')

@php
    $status = $service->status;
    $product = $service->product;
    $setup = in_array($status, [CloudStatus::Requested, CloudStatus::Provisioning], true);

    // What InterServer reports, labelled for the client.
    $labels = [
        'hostname' => 'Hostname',
        'ip' => 'IP address',
        'ipv6' => 'IPv6 address',
        'os' => 'Operating system',
        'slices' => 'Slices',
        'power' => 'Power',
        'username' => 'Username',
        'nameserver1' => 'Nameserver 1',
        'nameserver2' => 'Nameserver 2',
        'package' => 'Package',
    ];
    $details = collect($labels)->filter(fn ($label, $key) => is_scalar($service->remote($key)) && filled($service->remote($key)));
    $mono = ['ip', 'ipv6', 'hostname', 'username', 'nameserver1', 'nameserver2'];

    $power = array_intersect_key($actions, array_flip(['start', 'restart', 'stop', 'power_on', 'reboot', 'shutdown']));
    $confirm = [
        'stop' => 'Stop this server? Anything running on it goes offline until you start it again.',
        'restart' => 'Restart this server? It will be offline for a minute or two.',
        'reboot' => 'Reboot this server? It will be offline for a few minutes.',
        'shutdown' => 'Shut down this server? Anything running on it goes offline until you power it on.',
    ];

    $steps = [
        ['label' => 'Ordered', 'done' => true],
        ['label' => 'Confirmed by our team', 'done' => $status !== CloudStatus::Requested],
        ['label' => 'Set up', 'done' => false],
    ];
@endphp

<x-layouts.app :title="$service->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('cloud.index')">Back to Cloud</x-button>
        @if ($service->isOrdered() && $status->isOpen())
            <form method="POST" action="{{ route('cloud.refresh', $service) }}">
                @csrf
                <x-button variant="secondary">Refresh</x-button>
            </form>
        @endif
    </x-slot:actions>

    @if ($status === CloudStatus::Provisioning)
        {{-- Check again shortly; resources/js/cloud-order.js reloads the page. --}}
        <span data-auto-refresh="20" hidden></span>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $product->tint() }}">
            <x-icon :name="$product->icon()" class="size-6" />
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="truncate font-display text-2xl font-semibold text-slate-900">{{ $service->name }}</h2>
                <x-badge :color="$status->color()">{{ $status->label() }}</x-badge>
            </div>
            <p class="mt-1 text-slate-500">{{ $product->label() }} · {{ $service->plan }}</p>
        </div>
    </div>

    @if ($errors->any())
        <x-alert type="error" class="mt-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            {{-- Where the order is --}}
            @if ($setup)
                <x-card>
                    <ol class="grid gap-4 sm:grid-cols-3">
                        @foreach ($steps as $i => $step)
                            @php($current = ! $step['done'] && ($i === 0 || $steps[$i - 1]['done']))
                            <li class="flex items-center gap-3">
                                <span @class([
                                    'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                    'bg-emerald-600 text-white' => $step['done'],
                                    'bg-blue-50 text-blue-700 ring-2 ring-blue-600' => $current,
                                    'bg-slate-100 text-slate-400' => ! $step['done'] && ! $current,
                                ])>
                                    @if ($step['done'])
                                        <x-icon name="check" class="size-4" />
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <span @class(['text-sm', 'font-medium text-slate-900' => $step['done'] || $current, 'text-slate-400' => ! $step['done'] && ! $current])>{{ $step['label'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $status->description() }}</p>
                </x-card>
            @elseif (! $status->isOpen() || $status === CloudStatus::Failed)
                <div @class([
                    'rounded-2xl border px-5 py-4 text-sm',
                    'border-violet-200 bg-violet-50 text-violet-800' => $status === CloudStatus::Failed,
                    'border-slate-200 bg-white text-slate-600' => $status !== CloudStatus::Failed,
                ])>
                    <p>{{ $status->description() }}</p>
                    @if ($status === CloudStatus::Rejected && $service->note)
                        <p class="mt-2"><span class="font-medium text-slate-900">Note from our team:</span> {{ $service->note }}</p>
                    @endif
                </div>
            @endif

            @if ($service->cancel_requested_at && $status->isOpen())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                    You asked to cancel this on {{ $service->cancel_requested_at->format('j M Y') }}. Our team will confirm the date with you.
                </div>
            @endif

            {{-- How to reach it --}}
            @if ($details->isNotEmpty() || $service->remote('nameservers'))
                <x-card title="Details">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ($details as $key => $label)
                            <div class="min-w-0">
                                <dt class="text-xs text-slate-500">{{ $label }}</dt>
                                <dd @class(['mt-0.5 flex items-center gap-2 text-sm text-slate-900', 'font-mono' => in_array($key, $mono, true)])>
                                    @if ($key === 'power')
                                        <span @class(['size-2 rounded-full', 'bg-emerald-500' => in_array(strtolower($service->remote('power')), ['running', 'on', 'active'], true), 'bg-slate-300' => ! in_array(strtolower($service->remote('power')), ['running', 'on', 'active'], true)])></span>
                                        {{ ucfirst($service->remote('power')) }}
                                    @else
                                        <span class="truncate">{{ $service->remote($key) }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                        @if ($product === CloudProduct::Domain && $service->remote('nameservers'))
                            <div class="min-w-0 sm:col-span-2">
                                <dt class="text-xs text-slate-500">Nameservers</dt>
                                <dd class="mt-0.5 font-mono text-sm text-slate-900">{{ implode(', ', (array) $service->remote('nameservers')) }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($service->synced_at)
                        <p class="mt-5 text-xs text-slate-400">Checked {{ $service->synced_at->diffForHumans() }}.</p>
                    @endif
                </x-card>
            @endif

            {{-- What they can do with it --}}
            @if ($power)
                <x-card title="Power" description="Changes take a minute or two to show here.">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($power as $action => $label)
                            <form method="POST" action="{{ route('cloud.action', [$service, $action]) }}" @isset($confirm[$action]) data-confirm="{{ $confirm[$action] }}" @endisset>
                                @csrf
                                <x-button :variant="in_array($action, ['stop', 'shutdown'], true) ? 'danger' : 'secondary'">{{ $label }}</x-button>
                            </form>
                        @endforeach
                    </div>
                </x-card>
            @endif

            @isset($actions['password'])
                <x-card title="Root password" description="Set a new password for the server's root user.">
                    <form method="POST" action="{{ route('cloud.action', [$service, 'password']) }}" class="space-y-5" data-confirm="Change the root password? On KVM servers this restarts the server.">
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" strength hint="At least 10 characters, with upper and lower case and a number." />
                            <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" confirms="password" />
                        </div>
                        <x-button>Change password</x-button>
                    </form>
                </x-card>
            @endisset

            @isset($actions['login'])
                <x-card title="Control panel" description="Manage your website's files, email accounts and databases.">
                    <form method="POST" action="{{ route('cloud.action', [$service, 'login']) }}" target="_blank">
                        @csrf
                        <x-button>
                            Open control panel
                            <x-icon name="arrow-right" class="size-4" />
                        </x-button>
                    </form>
                    @if ($service->remote('nameserver1'))
                        <p class="mt-4 text-sm text-slate-500">
                            To put the website live, point your domain's nameservers to
                            <span class="font-mono text-slate-900">{{ $service->remote('nameserver1') }}</span>
                            @if ($service->remote('nameserver2')) and <span class="font-mono text-slate-900">{{ $service->remote('nameserver2') }}</span>@endif.
                        </p>
                    @endif
                </x-card>
            @endisset

            @isset($actions['nameservers'])
                @php($nameservers = array_pad(array_slice((array) old('nameservers', $service->remote('nameservers', [])), 0, 4), 4, ''))
                <x-card title="Nameservers" description="Where your domain's DNS is managed. Point it to your web host's nameservers to put a website live.">
                    <form method="POST" action="{{ route('cloud.action', [$service, 'nameservers']) }}" class="space-y-5">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($nameservers as $i => $ns)
                                <x-form.input name="nameservers[{{ $i }}]" :label="'Nameserver '.($i + 1).($i > 1 ? ' (optional)' : '')" :value="$ns" :required="$i < 2" placeholder="ns{{ $i + 1 }}.example.com" class="font-mono" />
                            @endforeach
                        </div>
                        <x-button>Save nameservers</x-button>
                    </form>
                </x-card>
            @endisset
        </div>

        {{-- Billing --}}
        <aside class="space-y-4">
            <x-card title="Billing">
                <p class="font-display text-2xl font-semibold text-slate-900 tabular-nums">{{ $service->priceLabel() }}</p>
                @if ($service->period > 1 && $product !== CloudProduct::Domain)
                    <p class="mt-1 text-xs text-slate-500">About ${{ number_format($service->monthly_price, 2) }} a month.</p>
                @endif
                <dl class="mt-5 space-y-2.5 border-t border-slate-100 pt-5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Ordered</dt>
                        <dd class="text-slate-900">{{ $service->created_at->format('j M Y') }}</dd>
                    </div>
                    @if ($service->activated_at)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Active since</dt>
                            <dd class="text-slate-900">{{ $service->activated_at->format('j M Y') }}</dd>
                        </div>
                    @endif
                    @if ($service->next_due_at && $status->isOpen())
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ $product === CloudProduct::Domain ? 'Expires' : 'Renews' }}</dt>
                            <dd class="text-slate-900">{{ $service->next_due_at->format('j M Y') }}</dd>
                        </div>
                    @endif
                    @if ($service->cancelled_at)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Cancelled</dt>
                            <dd class="text-slate-900">{{ $service->cancelled_at->format('j M Y') }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($status === CloudStatus::Requested)
                    <p class="mt-5 text-xs leading-relaxed text-slate-500">Nothing is charged yet. Our team will confirm the price with you when they approve the order.</p>
                @endif
            </x-card>

            @if ($status === CloudStatus::Requested)
                <form method="POST" action="{{ route('cloud.cancel', $service) }}" data-confirm="Withdraw this order? Nothing has been charged.">
                    @csrf
                    <x-button variant="secondary" class="w-full">Withdraw order</x-button>
                </form>
            @elseif (in_array($status, [CloudStatus::Active, CloudStatus::Provisioning], true) && ! $service->cancel_requested_at)
                <form method="POST" action="{{ route('cloud.cancel', $service) }}" data-confirm="Ask us to cancel this service? We'll confirm the date with you before anything is switched off.">
                    @csrf
                    <x-button variant="secondary" class="w-full">Request cancellation</x-button>
                </form>
            @endif

            <p class="px-1 text-xs leading-relaxed text-slate-500">
                Need a hand? <a href="{{ route('home') }}#contact" class="font-medium text-sky-700 hover:text-sky-800">Contact our team</a> and mention {{ $service->name }}.
            </p>
        </aside>
    </div>
</x-layouts.app>
