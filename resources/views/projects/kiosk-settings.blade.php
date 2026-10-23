{{-- The Kiosk App settings panel (App\Support\KioskSettings). Values start
     from $kioskSettings, the chosen machine's defaults. --}}
@use('App\Support\KioskSettings')

@php
    $setting = fn (string $key) => old("settings.{$key}", $kioskSettings[$key]);
    $preview = [
        'screen_size' => (string) $setting('screen_size'),
        'orientation' => (string) $setting('orientation'),
        'resolution' => (string) $setting('resolution'),
        'webgl' => (bool) $setting('webgl'),
        'game_board' => (bool) $setting('game_board'),
        'payments' => (bool) $setting('payments'),
    ];
@endphp

<x-card title="Kiosk settings" description="Shape the kiosk around your space. Our team will confirm everything with you.">
    <div class="divide-y divide-slate-100">
        {{-- Screen --}}
        <section class="space-y-4 pb-6">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <x-icon name="monitor" class="size-4 text-blue-600" />
                Screen
            </h3>

            @include('projects.screen-preview')

            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="settings[screen_size]" label="Screen size" :options="KioskSettings::sizeOptions()" :value="$preview['screen_size']" :placeholder="null" />
                <x-form.select name="settings[resolution]" label="Resolution" :options="KioskSettings::resolutionOptions()" :value="$preview['resolution']" :placeholder="null" />
            </div>

            <fieldset>
                <legend class="text-sm font-medium text-slate-700">Orientation</legend>
                <div class="mt-2 grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (KioskSettings::ORIENTATIONS as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="settings[orientation]" value="{{ $value }}" class="peer sr-only" @checked($preview['orientation'] === $value)>
                            <span class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:outline-2 peer-focus-visible:outline-blue-600">
                                <x-icon :name="$value" class="size-4" />
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('settings.orientation')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>
        </section>

        {{-- Rendering --}}
        <section class="py-6">
            <x-form.toggle
                name="settings[webgl]"
                label="WebGL rendering"
                description="Smooth 3D graphics and animations, drawn by the kiosk's graphics chip. Leave off for simple, low-power screens."
                :checked="$preview['webgl']"
            />
        </section>

        {{-- Game board --}}
        <section class="space-y-4 py-6">
            <x-form.toggle
                name="settings[game_board]"
                label="Game board"
                description="Interactive games that draw people in and reward them for playing."
                :checked="$preview['game_board']"
            />
            <div data-reveal="game_board" class="space-y-4" @unless ($preview['game_board']) hidden @endunless>
                <fieldset>
                    <legend class="text-xs font-medium tracking-wider text-slate-500 uppercase">Games</legend>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach (KioskSettings::GAMES as $game)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <x-form.checkbox name="settings[games][]" :value="$game" :label="$game" :checked="in_array($game, (array) $setting('games'))" />
                            </div>
                        @endforeach
                    </div>
                </fieldset>
                <x-form.toggle name="settings[leaderboard]" label="Leaderboard" description="Show the top scores on screen." :checked="(bool) $setting('leaderboard')" />
            </div>
        </section>

        {{-- Payments --}}
        <section class="space-y-4 py-6">
            <x-form.toggle
                name="settings[payments]"
                label="Payment integration"
                description="Take payments on the kiosk itself."
                :checked="$preview['payments']"
            />
            <fieldset data-reveal="payments" @unless ($preview['payments']) hidden @endunless>
                <legend class="text-xs font-medium tracking-wider text-slate-500 uppercase">Payment methods</legend>
                <div class="mt-3 grid gap-2">
                    @foreach (KioskSettings::PAYMENT_METHODS as $method)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <x-form.checkbox name="settings[payment_methods][]" :value="$method" :label="$method" :checked="in_array($method, (array) $setting('payment_methods'))" />
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </section>

        {{-- Extras --}}
        <section class="pt-6">
            <fieldset>
                <legend class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <x-icon name="puzzle" class="size-4 text-blue-600" />
                    Extras
                </legend>
                <p class="mt-0.5 text-xs text-slate-500">Hardware and software add-ons.</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach (KioskSettings::ADD_ONS as $addOn)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <x-form.checkbox name="settings[add_ons][]" :value="$addOn" :label="$addOn" :checked="in_array($addOn, (array) $setting('add_ons'))" />
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </section>
    </div>
</x-card>
