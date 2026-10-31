{{-- Kiosk App steps 3 (project and screen) and 4 (budget and features) as
     one form. resources/js/project-steps.js shows one step at a time and
     checks a step's fields before moving on; kiosk-settings.js keeps the
     screen previews in step with the settings. --}}
@php
    // Settings fall back to the chosen machine's defaults ($kioskSettings).
    $setting = fn (string $key) => old("settings.{$key}", $kioskSettings[$key]);
    $preview = [
        'screen_size' => (string) $setting('screen_size'),
        'orientation' => (string) $setting('orientation'),
        'resolution' => (string) $setting('resolution'),
        'webgl' => (bool) $setting('webgl'),
        'game_board' => (bool) $setting('game_board'),
        'payments' => (bool) $setting('payments'),
    ];

    // After a failed submit, open the step with the first problem.
    $stepThreeFields = ['name', 'description', 'settings', 'settings.screen_size', 'settings.orientation', 'settings.resolution', 'details.machine'];
    $currentStep = $errors->isEmpty() || $errors->hasAny($stepThreeFields) ? 3 : 4;
@endphp

<x-layouts.app title="Create project">
    <div class="max-w-6xl">
        @include('projects.kiosk.steps', ['current' => $currentStep])

        <form method="POST" action="{{ route('projects.store') }}" class="mt-6" data-kiosk-settings data-steps>
            @csrf
            <input type="hidden" name="type" value="{{ $type->value }}">
            @if ($machine)
                <input type="hidden" name="details[machine]" value="{{ $machine->value }}">
            @endif

            {{-- Step 3: the project and its screen --}}
            <section data-step-panel="3" @if ($currentStep === 3) data-active @endif aria-labelledby="step-3-title">
                <div class="mb-5">
                    <h2 id="step-3-title" tabindex="-1" class="font-display text-xl font-semibold text-slate-900 outline-none">Tell us about your project</h2>
                    <p class="mt-1 text-sm text-slate-500">Name it, describe it and set up the screen.</p>
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
                                <x-form.input name="name" label="Project name" placeholder="Give your project a short name" required data-summary-source="name" />
                                <x-form.textarea name="description" label="Tell us about it" hint="What do you want to achieve, and who is it for?" required />
                            </div>
                        </x-card>
                    </div>

                    <div class="xl:col-span-2">
                        @include('projects.kiosk.screen')
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <x-button variant="secondary" :href="route('projects.create.type', $type->value)">
                        <x-icon name="arrow-right" class="size-4 rotate-180" />
                        Back
                    </x-button>
                    <x-button type="button" data-step-goto="4">
                        Continue to budget &amp; features
                        <x-icon name="arrow-right" class="size-4" />
                    </x-button>
                </div>
            </section>

            {{-- Step 4: budget, timeline, features and the extra questions --}}
            <section data-step-panel="4" @if ($currentStep === 4) data-active @endif aria-labelledby="step-4-title">
                <div class="mb-5">
                    <h2 id="step-4-title" tabindex="-1" class="font-display text-xl font-semibold text-slate-900 outline-none">Budget and features</h2>
                    <p class="mt-1 text-sm text-slate-500">Last step: what it should do, and what you'd like to spend.</p>
                </div>

                <div class="grid gap-6 xl:grid-cols-5 xl:items-start">
                    <div class="space-y-6 xl:col-span-3">
                        <x-card title="Budget and timeline" description="A rough idea is fine. It helps us suggest the right setup.">
                            @include('projects.partials.budget-timeline')
                        </x-card>

                        @include('projects.kiosk.features')

                        <x-card title="A few more details" description="Optional, but it helps us prepare.">
                            <div class="space-y-6">
                                @foreach ($type->questions() as $question)
                                    @include('projects.question')
                                @endforeach
                            </div>
                        </x-card>
                    </div>

                    {{-- What they've set up so far, kept in view while they choose features --}}
                    <aside class="xl:sticky xl:top-24 xl:col-span-2" aria-label="Your kiosk">
                        <x-card title="Your kiosk">
                            <x-slot:actions>
                                <button type="button" data-step-goto="3" class="text-sm font-medium text-blue-600 hover:text-blue-700">Edit</button>
                            </x-slot:actions>

                            <div class="mb-5 flex items-center gap-3">
                                @if ($machine)
                                    <img src="{{ $machine->image() }}" alt="" width="48" height="48" class="size-12 shrink-0 rounded-lg border border-slate-100 bg-white object-contain">
                                @endif
                                <div class="min-w-0">
                                    <p data-summary="name" class="truncate font-medium text-slate-900">{{ old('name') ?: 'Your project' }}</p>
                                    <p class="truncate text-sm text-slate-500">{{ $machine?->label() ?? 'Kiosk machine to be recommended' }}</p>
                                </div>
                            </div>

                            @include('projects.screen-preview')
                        </x-card>
                    </aside>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <x-button variant="secondary" type="button" data-step-goto="3">
                        <x-icon name="arrow-right" class="size-4 rotate-180" />
                        Back
                    </x-button>
                    <div class="flex gap-3">
                        <x-button variant="secondary" :href="route('projects.index')">Cancel</x-button>
                        <x-button>Submit project</x-button>
                    </div>
                </div>
            </section>
        </form>
    </div>
</x-layouts.app>
