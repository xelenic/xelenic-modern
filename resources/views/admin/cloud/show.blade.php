{{-- One cloud order or service (Admin\CloudController@show). Approving buys
     it on InterServer, paid from the prepay balance; the team bills the
     client the price. --}}
@use('App\Enums\CloudProduct')
@use('App\Enums\CloudStatus')

@php
    $status = $service->status;
    $product = $service->product;
    $config = collect((array) $service->config)->reject(fn ($value) => blank($value) || is_array($value));
    // InterServer's field names, as people say them.
    $fields = [
        'vpsPlatform' => 'Platform', 'osDistro' => 'Operating system', 'osVersion' => 'OS version', 'controlpanel' => 'Control panel',
        'period' => 'Months', 'packageId' => 'Plan ID', 'server_id' => 'Server ID', 'whois_privacy' => 'Whois privacy',
        'org_name' => 'Organisation', 'address1' => 'Address', 'address2' => 'Address line 2', 'postal_code' => 'Postal code',
        'tos' => 'Terms of service accepted', 'type' => 'Order type',
    ];
    $remote = collect((array) $service->remote)->reject(fn ($value) => blank($value));
    $unit = $product->periodUnit();
    $per = $service->period === 1 ? "a {$unit}" : "every {$service->period} {$unit}s";
@endphp

<x-layouts.admin :title="$service->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('admin.cloud.index')">All orders</x-button>
        @if ($service->isOrdered())
            <form method="POST" action="{{ route('admin.cloud.sync', $service) }}">
                @csrf
                <x-button variant="secondary">Update from InterServer</x-button>
            </form>
        @endif
    </x-slot:actions>

    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $product->tint() }}">
            <x-icon :name="$product->icon()" class="size-6" />
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="truncate font-display text-2xl font-semibold text-slate-900">{{ $service->name }}</h2>
                <x-badge :color="$status->color()">{{ $status->label() }}</x-badge>
                @if ($service->cancel_requested_at && $status->isOpen())
                    <x-badge color="amber">Client asked to cancel</x-badge>
                @endif
            </div>
            <p class="mt-1 text-slate-500">{{ $product->label() }} · {{ $service->plan }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            {{-- Waiting for the team --}}
            @if ($status === CloudStatus::Requested)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h3 class="font-display font-semibold text-slate-900">Waiting for approval</h3>
                    <p class="mt-2 text-sm text-slate-700">
                        Approving buys this on InterServer straight away for <strong class="tabular-nums">${{ number_format($service->cost, 2) }}</strong>,
                        paid from the account's prepay balance. Bill {{ $service->user->name }} <strong class="tabular-nums">${{ number_format($service->price, 2) }}</strong> {{ $per }}.
                    </p>
                    <p class="mt-1 text-xs text-slate-500">InterServer priced it when the client ordered, {{ $service->created_at->diffForHumans() }}. If its price has changed since, the charge will be InterServer's current one.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.cloud.approve', $service) }}" data-confirm="Buy {{ $service->name }} on InterServer now for ${{ number_format($service->cost, 2) }} from the prepay balance?">
                            @csrf
                            <x-button>
                                <x-icon name="check" class="size-4" />
                                Approve and buy
                            </x-button>
                        </form>
                    </div>
                </section>
            @endif

            {{-- Something went wrong --}}
            @if ($status === CloudStatus::Failed)
                <section class="rounded-2xl border border-violet-200 bg-violet-50 p-6">
                    <h3 class="font-display font-semibold text-slate-900">Needs attention</h3>
                    <p class="mt-2 text-sm text-slate-700">{{ $service->failure ?: 'The last attempt didn\'t go through.' }}</p>
                    @if ($service->isOrdered())
                        <p class="mt-2 text-xs text-slate-500">It's already ordered on InterServer; retrying only pays the open invoice{{ count((array) $service->interserver_invoices) === 1 ? '' : 's' }}, it won't order again.</p>
                    @endif
                    <form method="POST" action="{{ route('admin.cloud.retry', $service) }}" class="mt-5"
                        data-confirm="{{ $service->isOrdered() ? 'Pay the InterServer invoice now from the prepay balance?' : 'Order '.$service->name.' on InterServer again for $'.number_format($service->cost, 2).' from the prepay balance?' }}">
                        @csrf
                        <x-button>{{ $service->isOrdered() ? 'Retry payment' : 'Try ordering again' }}</x-button>
                    </form>
                </section>
            @endif

            @if ($status === CloudStatus::Rejected && $service->note)
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600">
                    <span class="font-medium text-slate-900">Reason given to the client:</span> {{ $service->note }}
                </div>
            @endif

            @if ($status === CloudStatus::Cancelled && $service->note)
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600">{{ $service->note }}</div>
            @endif

            {{-- What was ordered --}}
            <x-card title="Order" description="What the client chose, as it's sent to InterServer.">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    @foreach ($config as $key => $value)
                        <div class="min-w-0">
                            <dt class="text-xs text-slate-500">{{ $fields[$key] ?? Str::headline($key) }}</dt>
                            <dd class="mt-0.5 truncate text-sm text-slate-900" title="{{ is_bool($value) ? '' : $value }}">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</dd>
                        </div>
                    @endforeach
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500">Root password</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            @if (in_array($product, [CloudProduct::Vps, CloudProduct::Dedicated], true))
                                {{ $service->secret ? 'Chosen by the client (stored encrypted until it\'s ordered)' : 'Chosen by the client (no longer stored)' }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            {{-- What InterServer says --}}
            @if ($service->isOrdered())
                <x-card title="On InterServer">
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-xs text-slate-500">Service</dt>
                            <dd class="mt-0.5 truncate font-mono text-sm text-slate-900">{{ $service->interserver_uuid }}</dd>
                        </div>
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-xs text-slate-500">{{ Str::plural('Invoice', count((array) $service->interserver_invoices)) }}</dt>
                            <dd class="mt-0.5 font-mono text-sm break-all text-slate-900">{{ implode(', ', (array) $service->interserver_invoices) ?: '—' }}</dd>
                        </div>
                        @if ($service->remote_status)
                            <div class="min-w-0">
                                <dt class="text-xs text-slate-500">Status there</dt>
                                <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($service->remote_status) }}</dd>
                            </div>
                        @endif
                        @foreach ($remote as $key => $value)
                            <div class="min-w-0">
                                <dt class="text-xs text-slate-500">{{ Str::headline($key) }}</dt>
                                <dd class="mt-0.5 truncate text-sm text-slate-900">{{ is_array($value) ? implode(', ', Arr::flatten($value)) : $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-5 text-xs text-slate-400">
                        {{ $service->synced_at ? 'Updated '.$service->synced_at->diffForHumans().'.' : 'Not updated yet.' }}
                        Login details and welcome emails go to the InterServer account's email.
                    </p>
                </x-card>
            @endif

            {{-- Ending it --}}
            @if ($service->isOrdered() && in_array($status, [CloudStatus::Active, CloudStatus::Provisioning], true))
                <x-card title="Cancel on InterServer" description="Stops InterServer billing for it. Cancelling now switches it off straight away.">
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.cloud.cancel', $service) }}" data-confirm="Cancel {{ $service->name }} at the end of what's paid? It keeps running until then.">
                            @csrf
                            <input type="hidden" name="when" value="renewal">
                            <x-button variant="secondary">Cancel at renewal</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.cloud.cancel', $service) }}" data-confirm="Cancel {{ $service->name }} now? It's switched off straight away and its data may be lost. This can't be undone.">
                            @csrf
                            <input type="hidden" name="when" value="now">
                            <x-button variant="danger">Cancel now</x-button>
                        </form>
                    </div>
                </x-card>
            @endif

            @if (! $service->isOrdered() && in_array($status, [CloudStatus::Requested, CloudStatus::Failed], true))
                <x-card title="Decline" description="Nothing is bought. The client sees the order as declined, with your reason.">
                    <form method="POST" action="{{ route('admin.cloud.reject', $service) }}" class="space-y-4" data-confirm="Decline this order?">
                        @csrf
                        <x-form.textarea name="reason" label="Reason (optional)" rows="3" placeholder="For example: that location is full this week; we'll be in touch about another." />
                        <x-button variant="danger">Decline order</x-button>
                    </form>
                </x-card>
            @endif
        </div>

        <aside class="space-y-6">
            <x-card title="Money">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">InterServer cost</dt>
                        <dd class="text-slate-900 tabular-nums">${{ number_format($service->cost, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Client price</dt>
                        <dd class="font-semibold text-slate-900 tabular-nums">${{ number_format($service->price, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Margin</dt>
                        <dd class="font-medium text-emerald-700 tabular-nums">${{ number_format($service->margin(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-slate-100 pt-2.5">
                        <dt class="text-slate-500">Billed</dt>
                        <dd class="text-slate-900">{{ ucfirst($per) }}</dd>
                    </div>
                    @if ($service->next_due_at && $status->isOpen())
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ $product === CloudProduct::Domain ? 'Expires' : 'Renews' }}</dt>
                            <dd class="text-slate-900">{{ $service->next_due_at->format('j M Y') }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="mt-4 text-xs text-slate-500">The {{ $product->label() }} markup is {{ rtrim(rtrim(number_format($markup, 2), '0'), '.') }}% now. Orders keep the price they were placed at.</p>
            </x-card>

            <x-card title="Client">
                <p class="font-medium text-slate-900">{{ $service->user->name }}</p>
                <p class="truncate text-sm text-slate-500">{{ $service->user->email }}</p>
                @can('edit users')
                    <a href="{{ route('admin.users.edit', $service->user) }}" class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-700">View account</a>
                @endcan
            </x-card>

            <x-card title="History">
                <ol class="space-y-3 text-sm">
                    <li class="flex justify-between gap-3"><span class="text-slate-500">Ordered</span><span class="text-slate-900">{{ $service->created_at->format('j M Y, H:i') }}</span></li>
                    @if ($service->approved_at)
                        <li class="flex justify-between gap-3"><span class="text-slate-500">Approved</span><span class="text-right text-slate-900">{{ $service->approved_at->format('j M Y, H:i') }}@if ($service->approver)<span class="block text-xs text-slate-500">by {{ $service->approver->name }}</span>@endif</span></li>
                    @endif
                    @if ($service->activated_at)
                        <li class="flex justify-between gap-3"><span class="text-slate-500">Active</span><span class="text-slate-900">{{ $service->activated_at->format('j M Y, H:i') }}</span></li>
                    @endif
                    @if ($service->cancel_requested_at)
                        <li class="flex justify-between gap-3"><span class="text-slate-500">Cancel asked</span><span class="text-slate-900">{{ $service->cancel_requested_at->format('j M Y, H:i') }}</span></li>
                    @endif
                    @if ($service->cancelled_at)
                        <li class="flex justify-between gap-3"><span class="text-slate-500">Cancelled</span><span class="text-slate-900">{{ $service->cancelled_at->format('j M Y, H:i') }}</span></li>
                    @endif
                </ol>
            </x-card>
        </aside>
    </div>
</x-layouts.admin>
