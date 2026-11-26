{{-- Domains: search for a name (CloudController@checkDomain), then the
     registrant's contact details, which the registry checks before the order
     reaches the team. Registered for a year. --}}
@php
    $domain = old('domain');
    [$first, $last] = array_pad(explode(' ', (string) $user->name, 2), 2, '');
@endphp

<x-card title="1. Find your domain" description="Search for the name you want. We'll tell you straight away if it's free.">
    <div class="flex flex-col gap-3 sm:flex-row" data-domain-search>
        <div class="min-w-0 flex-1">
            <label for="domain" class="sr-only">Domain</label>
            <input id="domain" name="domain" type="text" value="{{ $domain }}" placeholder="yourbrand.com" required autocomplete="off" autocapitalize="none" spellcheck="false" data-domain
                @class([
                    'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-2',
                    'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20' => ! $errors->has('domain'),
                    'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $errors->has('domain'),
                ])>
        </div>
        <x-button type="button" variant="secondary" data-domain-check>
            <x-icon name="search" class="size-4" />
            Check
        </x-button>
    </div>
    @error('domain') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="mt-4 hidden rounded-xl px-4 py-3 text-sm" data-domain-result role="status" aria-live="polite"></div>

    <div class="mt-4 hidden" data-privacy>
        <x-form.toggle name="privacy" label="Whois privacy" :checked="(bool) old('privacy', true)" description="Hide your name, email and address from the public domain directory." />
    </div>
</x-card>

{{-- Shown once a name is free (or straight away after a failed submit). --}}
<div @class(['space-y-6', 'hidden' => ! $domain]) data-contact>
    <x-card title="2. Registrant" description="Who the domain is registered to. Registries require real, reachable details.">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="first_name" label="First name" :value="$first" required autocomplete="given-name" />
            <x-form.input name="last_name" label="Last name" :value="$last" required autocomplete="family-name" />
            <div class="sm:col-span-2">
                <x-form.input name="org_name" label="Organisation (optional)" autocomplete="organization" />
            </div>
            <x-form.input name="email" type="email" label="Email" :value="$user->email" required autocomplete="email" />
            <x-form.input name="phone" type="tel" label="Phone" :value="$user->phone ?? null" required autocomplete="tel" placeholder="+94.771234567" hint="+country code, a dot, then the number." />
            <div class="sm:col-span-2">
                <x-form.input name="address1" label="Address" required autocomplete="address-line1" />
            </div>
            <div class="sm:col-span-2">
                <x-form.input name="address2" label="Address line 2 (optional)" autocomplete="address-line2" />
            </div>
            <x-form.input name="city" label="City" required autocomplete="address-level2" />
            <x-form.input name="state" label="State or province" required autocomplete="address-level1" />
            <x-form.input name="postal_code" label="Postal code" required autocomplete="postal-code" />
            <x-form.input name="country" label="Country code" required autocomplete="country" maxlength="2" placeholder="LK" hint="Two letters, like LK, US or GB." class="uppercase" />
        </div>
    </x-card>
</div>

@push('summary')
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Domain</dt><dd class="min-w-0 truncate text-right font-medium text-slate-900" data-sum="domain">{{ $domain ?: '—' }}</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Term</dt><dd class="text-right font-medium text-slate-900">1 year</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Whois privacy</dt><dd class="text-right font-medium text-slate-900" data-sum="privacy">—</dd></div>
@endpush
