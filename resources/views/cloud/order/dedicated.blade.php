{{-- DedicServer (dedicated servers): InterServer's ready-built Rapid Deploy machines.
     $catalog is Dedicated::catalog(). --}}
@php
    $servers = $catalog['servers'];
    $server = (string) old('server', $servers[0]['id'] ?? '');
@endphp

<script type="application/json" data-catalog>@json($catalog)</script>

@if ($servers === [])
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">All our ready-to-go servers are taken right now. New ones arrive often, so please check back soon, or contact us for a custom build.</div>
@else
    <x-card title="1. Server" description="Ready-built machines, online within hours. Prices are per month.">
        <div class="space-y-2" role="radiogroup" aria-label="Server">
            @foreach ($servers as $option)
                <label class="relative flex cursor-pointer flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 has-checked:border-blue-600 has-checked:ring-1 has-checked:ring-blue-600 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-blue-600 sm:flex-row sm:items-center">
                    <input type="radio" name="server" value="{{ $option['id'] }}" class="sr-only" @checked($server === $option['id']) required>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-slate-900">{{ $option['cpu'] ?: 'Server #'.$option['id'] }}</span>
                        <span class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                            @if ($option['cores']) <span>{{ $option['cores'] }} cores</span> @endif
                            @if ($option['memory']) <span>{{ $option['memory'] }} RAM</span> @endif
                            @if ($option['disk']) <span>{{ $option['disk'] }}</span> @endif
                            @if ($option['bandwidth']) <span>{{ $option['bandwidth'] }}</span> @endif
                            @if ($option['ips']) <span>{{ $option['ips'] }} IPs</span> @endif
                        </span>
                    </span>
                    <span class="flex shrink-0 items-center justify-between gap-4 sm:flex-col sm:items-end sm:gap-0.5">
                        <span class="font-semibold text-slate-900 tabular-nums">${{ number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Dedicated, $option['cost']), 2) }}<span class="text-xs font-normal text-slate-500">/mo</span></span>
                        @if ($option['location']) <span class="text-xs text-slate-500">{{ $option['location'] }}</span> @endif
                    </span>
                </label>
            @endforeach
        </div>
        @error('server') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </x-card>

    <x-card title="2. Your server">
        <div class="space-y-5">
            <x-form.input name="hostname" label="Hostname" :value="old('hostname')" placeholder="server.yourbrand.com" required hint="The server's full name. Use a domain you own, like server.yourbrand.com." />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="password" type="password" label="Root password" required autocomplete="new-password" strength hint="At least 10 characters, with upper and lower case, a number and a symbol." />
                <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" confirms="password" />
            </div>
        </div>
    </x-card>
@endif

@push('summary')
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Server</dt><dd class="min-w-0 text-right font-medium text-slate-900" data-sum="server">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Location</dt><dd class="text-right font-medium text-slate-900" data-sum="location">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Billing</dt><dd class="text-right font-medium text-slate-900">Monthly</dd></div>
@endpush
