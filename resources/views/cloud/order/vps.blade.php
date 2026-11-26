{{-- Vplates (cloud VPS): platform, OS, size in slices, location, billing
     period and the server's own details. $catalog is Vps::catalog(). --}}
@php
    $platforms = $catalog['platforms'];
    $platform = old('platform', array_key_first($platforms));
    $distros = $catalog['os'][$platform] ?? [];
    $distro = old('os', array_key_first($distros));
    $versions = $distros[$distro]['versions'] ?? [];
    $slices = (int) old('slices', 1);
    $period = (int) old('period', 1);
    $location = (int) old('location', array_key_first($catalog['locations']));
    $card = 'relative flex cursor-pointer flex-col rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 has-checked:border-blue-600 has-checked:ring-1 has-checked:ring-blue-600 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-blue-600 has-disabled:cursor-not-allowed has-disabled:opacity-45';
@endphp

<script type="application/json" data-catalog>@json($catalog)</script>

<x-card title="1. Platform" description="Where your server runs. KVM is a good fit for most Linux and Windows servers.">
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ($platforms as $key => $info)
            <label class="{{ $card }}">
                <input type="radio" name="platform" value="{{ $key }}" class="sr-only" @checked($platform === $key) required>
                <span class="font-semibold text-slate-900">{{ $info['name'] }}</span>
                <span class="mt-1 text-xs text-slate-500">
                    @if ($key === 'hyperv')
                        Windows · from ${{ number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Vps, $info['windows']), 2) }} a slice
                    @elseif ($key === 'kvmstorage')
                        Big disks · from ${{ number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Vps, $info['linux']), 2) }} a slice
                    @else
                        Linux or Windows · from ${{ number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Vps, $info['linux']), 2) }} a slice
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    @error('platform') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
</x-card>

<x-card title="2. Operating system">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="os" class="block text-sm font-medium text-slate-700">System</label>
            <select id="os" name="os" data-os required class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                @foreach ($distros as $key => $info)
                    <option value="{{ $key }}" @selected($distro === $key)>{{ $info['name'] }}</option>
                @endforeach
            </select>
            @error('os') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="version" class="block text-sm font-medium text-slate-700">Version</label>
            <select id="version" name="version" data-version required class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                @foreach ($versions as $key => $label)
                    <option value="{{ $key }}" @selected(old('version') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('version') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <p class="mt-3 hidden text-xs text-slate-500" data-windows-note>Windows servers need at least 2 slices.</p>
</x-card>

<x-card title="3. Size" description="Each slice adds memory, disk and bandwidth. You can grow it later.">
    <div class="flex items-center gap-4">
        <input type="range" name="slices" min="1" max="{{ $catalog['maxSlices'] }}" value="{{ $slices }}" data-slices aria-label="Slices"
            class="h-2 flex-1 cursor-pointer appearance-none rounded-full bg-slate-200 accent-blue-600">
        <output class="w-24 shrink-0 text-right font-display text-lg font-semibold text-slate-900 tabular-nums" data-slices-label>{{ $slices }} {{ $slices === 1 ? 'slice' : 'slices' }}</output>
    </div>
    <dl class="mt-5 grid grid-cols-3 gap-3 text-center">
        <div class="rounded-xl bg-slate-50 px-3 py-3">
            <dt class="text-xs text-slate-500">Memory</dt>
            <dd class="mt-1 font-semibold text-slate-900 tabular-nums" data-ram>{{ $slices * $catalog['slice']['ram'] / 1024 }} GB</dd>
        </div>
        <div class="rounded-xl bg-slate-50 px-3 py-3">
            <dt class="text-xs text-slate-500">Disk</dt>
            <dd class="mt-1 font-semibold text-slate-900 tabular-nums" data-disk>{{ $slices * $catalog['slice']['disk'] }} GB</dd>
        </div>
        <div class="rounded-xl bg-slate-50 px-3 py-3">
            <dt class="text-xs text-slate-500">Bandwidth</dt>
            <dd class="mt-1 font-semibold text-slate-900 tabular-nums" data-bandwidth>{{ number_format($slices * $catalog['slice']['bandwidth'] / 1000, 0) }} TB</dd>
        </div>
    </dl>
    @error('slices') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
</x-card>

<x-card title="4. Location">
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ($catalog['locations'] as $id => $info)
            <label class="{{ $card }}" data-location="{{ $id }}">
                <input type="radio" name="location" value="{{ $id }}" class="sr-only" @checked($location === (int) $id) @disabled(! ($info['stock'][$platform] ?? true)) required>
                <span class="font-semibold text-slate-900">{{ $info['name'] }}</span>
                <span class="mt-1 text-xs text-slate-500" data-stock>{{ ($info['stock'][$platform] ?? true) ? 'Available' : 'Sold out for this platform' }}</span>
            </label>
        @endforeach
    </div>
    @error('location') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
</x-card>

<x-card title="5. Billing" description="Pay for longer and save.">
    @include('cloud.order.periods', ['period' => $period, 'unit' => 'month'])
</x-card>

<x-card title="6. Your server">
    <div class="space-y-5">
        <x-form.input name="hostname" label="Hostname" :value="old('hostname')" placeholder="server.yourbrand.com" required hint="The server's full name. Use a domain you own, like server.yourbrand.com." />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="password" type="password" label="Root password" required autocomplete="new-password" strength hint="At least 10 characters, with upper and lower case and a number." />
            <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" confirms="password" />
        </div>
        <div data-control-panel>
            <x-form.select name="control_panel" label="Control panel (optional)" :placeholder="null" :value="old('control_panel', 'none')" :options="[
                'none' => 'None',
                'cpanel' => 'cPanel (+$'.number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Vps, $catalog['controlPanels']['cpanel']), 2).' a month)',
                'da' => 'DirectAdmin (+$'.number_format(App\Cloud\CloudSettings::price(App\Enums\CloudProduct::Vps, $catalog['controlPanels']['da']), 2).' a month)',
            ]" hint="For Linux servers. Manage websites and email in a browser." />
        </div>
    </div>
</x-card>

@push('summary')
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Platform</dt><dd class="text-right font-medium text-slate-900" data-sum="platform">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">System</dt><dd class="text-right font-medium text-slate-900" data-sum="os">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Size</dt><dd class="text-right font-medium text-slate-900" data-sum="size">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Location</dt><dd class="text-right font-medium text-slate-900" data-sum="location">—</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-slate-500">Billing</dt><dd class="text-right font-medium text-slate-900" data-sum="period">—</dd></div>
@endpush
