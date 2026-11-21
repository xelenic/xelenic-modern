{{-- Kiosk App step 3, the project and its screen. "Continue to design" then
     opens Kiosk Studio for step 4 over the page, and the form is sent from
     there, with or without a design (resources/js/kiosk-studio.js).
     kiosk-settings.js keeps the screen preview in step with the settings. --}}
@php
    // Settings fall back to the chosen machine's defaults ($kioskSettings).
    $setting = fn (string $key) => old("settings.{$key}", $kioskSettings[$key]);
    $preview = [
        'screen_size' => (string) $setting('screen_size'),
        'orientation' => (string) $setting('orientation'),
        'resolution' => (string) $setting('resolution'),
        // The form doesn't ask about these, so the preview doesn't show them.
        'webgl' => false,
        'game_board' => false,
        'payments' => false,
    ];
@endphp

<x-layouts.app title="Create project">
    <div class="max-w-6xl">
        @include('projects.kiosk.steps', ['current' => 3])

        <form method="POST" action="{{ route('projects.store') }}" class="mt-6" data-kiosk-settings
            data-kiosk-studio data-studio-url="{{ asset('nignest/index.html') }}?embed=1" data-studio-max="{{ \App\Support\KioskDesign::MAX_LENGTH }}">
            @csrf
            <input type="hidden" name="type" value="{{ $type->value }}">
            @if ($machine)
                <input type="hidden" name="details[machine]" value="{{ $machine->value }}">
            @endif
            <input type="hidden" name="kiosk_design" value="{{ old('kiosk_design') }}" data-studio-input>
            <input type="hidden" name="kiosk_data" data-studio-entries>

            <div class="mb-5">
                <h2 class="font-display text-xl font-semibold text-slate-900">Tell us about your project</h2>
                <p class="mt-1 text-sm text-slate-500">Name it, describe it and set up the screen. Next, you can design the app in Kiosk Studio.</p>
            </div>

            <div class="grid gap-6 xl:grid-cols-5 xl:items-start">
                <div class="space-y-6 xl:col-span-3">
                    <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        @if ($machine)
                            <img src="{{ $machine->image() }}" alt="" width="80" height="80" class="size-20 shrink-0 rounded-xl border border-slate-100 bg-white object-contain p-1">
                        @else
                            <span class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                                <x-icon name="kiosk" class="size-8" />
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium tracking-wider text-slate-500 uppercase">Kiosk machine</p>
                            <p class="mt-1 font-display font-semibold text-slate-900">{{ $machine?->label() ?? 'Not chosen yet' }}</p>
                            <p class="text-sm text-slate-500">{{ $machine?->description() ?? "We'll recommend the right machine for your space." }}</p>
                        </div>
                        <a href="{{ route('projects.create.type', $type->value) }}" class="shrink-0 text-sm font-medium text-blue-600 hover:text-blue-700">
                            {{ $machine ? 'Change' : 'Choose one' }}
                        </a>
                    </div>

                    <x-card title="About your project" description="The basics, so we know where to start.">
                        <div class="space-y-5">
                            <x-form.input name="name" label="Project name" placeholder="Give your project a short name" required />
                            <x-form.textarea name="description" label="Tell us about it" hint="What do you want to achieve, and who is it for?" required />
                        </div>
                    </x-card>
                </div>

                <div class="xl:col-span-2">
                    @include('projects.kiosk.screen')
                </div>
            </div>

            @error('kiosk_design')
                <p class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
            @enderror

            <div class="mt-6 flex items-center justify-between gap-3">
                <x-button variant="secondary" :href="route('projects.create.type', $type->value)">
                    <x-icon name="arrow-right" class="size-4 rotate-180" />
                    Back
                </x-button>
                <x-button type="button" data-studio-open>
                    Continue to design
                    <x-icon name="arrow-right" class="size-4" />
                </x-button>
            </div>
        </form>
    </div>

    {{-- Step 4: Kiosk Studio, over the whole page --}}
    <x-kiosk-studio title="Step 4 of 4 · Design your kiosk app (optional)" action="Submit project" skip="Skip design & submit project" back="Back to the form" />
</x-layouts.app>
