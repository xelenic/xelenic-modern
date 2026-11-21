{{-- What Xelenic offers. On a dashboard with no projects yet it's a row of
     options to choose from, then Continue opens that service's form (the
     project picker reads ?type=…). Once there are projects, a compact row of
     links instead. --}}
@props(['compact' => false])

@use('App\Enums\ProjectType')

@php
    // Each with its icon tile's colour: light, and solid when hovered or chosen.
    $services = [
        ['title' => 'Kiosk Engine', 'summary' => 'Self-service kiosks', 'icon' => 'kiosk', 'type' => ProjectType::KioskApp,
            'tint' => 'bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-has-checked/option:bg-blue-600'],
        // No project type of its own yet, so it goes to the picker.
        ['title' => 'Cloud Services', 'summary' => 'Hosting and integrations', 'icon' => 'cloud', 'type' => null,
            'tint' => 'bg-sky-50 text-sky-600 group-hover:bg-sky-600 group-has-checked/option:bg-sky-600'],
        ['title' => 'Hire Our Engineers', 'summary' => 'Engineers for your team', 'icon' => 'users', 'type' => ProjectType::HireEngineers,
            'tint' => 'bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-has-checked/option:bg-amber-500'],
        ['title' => 'Custom Development', 'summary' => 'Outsource your build', 'icon' => 'code', 'type' => ProjectType::SoftwareSolution,
            'tint' => 'bg-violet-50 text-violet-600 group-hover:bg-violet-600 group-has-checked/option:bg-violet-600'],
    ];
@endphp

@if ($compact)
    <div {{ $attributes->class('grid gap-3 sm:grid-cols-2') }}>
        @foreach ($services as $service)
            <a href="{{ $service['type'] ? route('projects.create.type', $service['type']) : route('projects.create') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs transition hover:border-slate-300 hover:bg-slate-50">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg transition group-hover:text-white {{ $service['tint'] }}">
                    <x-icon :name="$service['icon']" class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-900">{{ $service['title'] }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500">{{ $service['summary'] }}</span>
                </span>
                <x-icon name="arrow-right" class="size-4 shrink-0 text-slate-300 transition group-hover:text-slate-500" />
            </a>
        @endforeach
    </div>
@else
    <form method="GET" action="{{ route('projects.create') }}" {{ $attributes->class('group/services') }}>
        <fieldset>
            <legend class="sr-only">Choose a service</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($services as $service)
                    <label class="group/option flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3.5 pr-4 shadow-xs transition hover:border-slate-300 has-checked:border-blue-600 has-checked:ring-1 has-checked:ring-blue-600 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-blue-600">
                        <input type="radio" name="type" value="{{ $service['type']?->value }}" class="sr-only" required>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg transition group-has-checked/option:text-white {{ $service['tint'] }}">
                            <x-icon :name="$service['icon']" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold whitespace-nowrap text-slate-900">{{ $service['title'] }}</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $service['summary'] }}</span>
                        </span>
                        {{-- The radio dot. --}}
                        <span aria-hidden="true" class="flex size-4 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white transition group-has-checked/option:border-blue-600 group-has-checked/option:bg-blue-600">
                            <span class="size-1.5 rounded-full bg-white opacity-0 group-has-checked/option:opacity-100"></span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center">
            <p class="flex-1 text-sm text-slate-500">
                <span class="group-has-checked/services:hidden">Choose a service to continue.</span>
                <span class="hidden group-has-checked/services:inline">Next, a few quick questions about your project.</span>
            </p>
            <x-button>
                Continue
                <x-icon name="arrow-right" class="size-4" />
            </x-button>
        </div>
    </form>
@endif
