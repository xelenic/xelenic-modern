{{-- Kiosk step 4: rendering, game board, payments and extras. Uses $preview
     and $setting from projects.kiosk-form. --}}
@use('App\Support\KioskSettings')

<x-card title="Kiosk features" description="Switch on what the kiosk should do. Our team will confirm everything with you.">
    <div class="divide-y divide-slate-100">
        {{-- Rendering --}}
        <section class="pb-6">
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
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
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
                <legend class="text-sm font-medium text-slate-900">Extras</legend>
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
