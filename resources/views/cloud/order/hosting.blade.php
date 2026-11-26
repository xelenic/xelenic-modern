{{-- HostBuckets (web hosting): a plan, the website's domain and a billing period.
     $catalog is Hosting::catalog(). --}}
@php
    $plans = $catalog['plans'];
    $plan = (int) old('plan', $plans[0]['id'] ?? 0);
    $period = (int) old('period', 12);
@endphp

<script type="application/json" data-catalog>@json($catalog)</script>

@if ($plans === [])
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">No hosting plans are on offer right now. Please check back soon.</div>
@else
    <x-card title="1. Plan" description="Prices are per month.">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($plans as $option)
                <label class="relative flex cursor-pointer flex-col rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 has-checked:border-blue-600 has-checked:ring-1 has-checked:ring-blue-600 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-blue-600">
                    <input type="radio" name="plan" value="{{ $option['id'] }}" class="sr-only" @checked($plan === $option['id']) required>
                    <span class="flex items-start justify-between gap-3">
                        <span class="font-semibold text-slate-900">{{ $option['name'] }}</span>
                        <span class="shrink-0 font-semibold text-slate-900 tabular-nums">${{ number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Hosting, $option['cost']), 2) }}<span class="text-xs font-normal text-slate-500">/mo</span></span>
                    </span>
                    <span class="mt-1 text-xs font-medium text-violet-700">{{ $option['panel'] }}</span>
                    @if ($option['description'])
                        <span class="mt-2 text-xs leading-relaxed text-slate-500">{{ $option['description'] }}</span>
                    @endif
                </label>
            @endforeach
        </div>
        @error('plan') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </x-card>

    <x-card title="2. Website">
        <x-form.input name="domain" label="Domain" :value="old('domain')" placeholder="yourbrand.com" required data-domain
            hint="The website's address. Don't have one yet? Order a domain too and point it here." />
    </x-card>

    <x-card title="3. Billing" description="Pay for longer and save.">
        @include('cloud.order.periods', ['period' => $period, 'unit' => 'month'])
    </x-card>
@endif

@push('summary')
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Plan</dt><dd class="text-right font-medium text-slate-900" data-sum="plan">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Website</dt><dd class="min-w-0 truncate text-right font-medium text-slate-900" data-sum="domain">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Billing</dt><dd class="text-right font-medium text-slate-900" data-sum="period">—</dd></div>
@endpush
