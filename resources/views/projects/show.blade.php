<x-layouts.app :title="$project->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('projects.index')">All projects</x-button>
    </x-slot:actions>

    @php
        $designEditable = $project->canChangeKioskDesign();
    @endphp

    @include('projects.partials.header')

    <div class="mt-8 grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 lg:col-span-2">
            @include('projects.partials.tabs')
        </div>

        <aside class="space-y-6">
            @if ($project->canBeShared())
                @include('projects.partials.play-link')
            @endif

            <x-card title="What happens next">
                <div class="flex items-start gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <x-icon name="chat" class="size-4" />
                    </span>
                    <p class="text-sm text-slate-600">{{ $project->status->description() }}</p>
                </div>

                @if ($project->status->canBeCancelled())
                    <form method="POST" action="{{ route('projects.cancel', $project) }}" data-confirm="Cancel this project? You can always start a new one." class="mt-6 border-t border-slate-100 pt-5">
                        @csrf
                        <x-button variant="danger" class="w-full">Cancel project</x-button>
                    </form>
                @endif
            </x-card>

            <x-card title="Questions?">
                <p class="text-sm text-slate-600">Talk to the Xelenic team about anything in this project.</p>
                <x-button variant="secondary" :href="route('home').'#contact'" class="mt-4 w-full">Contact us</x-button>
            </x-card>
        </aside>
    </div>

    {{-- Changing the kiosk design: Kiosk Studio opens with the saved design
         (data-studio-source), and Save design sends it here in the background
         without leaving the studio (data-studio-stay). --}}
    @if ($project->canChangeKioskDesign())
        <form method="POST" action="{{ route('projects.kiosk-design.update', $project) }}" data-kiosk-studio data-studio-stay
            data-studio-url="{{ asset('nignest/index.html') }}?embed=1"
            data-studio-max="{{ \App\Support\KioskDesign::MAX_LENGTH }}"
            data-studio-orientation="{{ $project->kioskSettings()['orientation'] ?? $project->kioskDesign()['orientation'] ?? 'portrait' }}"
            @if (\App\Support\KioskDesign::exists($project)) data-studio-source="{{ route('projects.kiosk-design', $project) }}" @endif>
            @csrf
            @method('PUT')
            <input type="hidden" name="kiosk_design" data-studio-input>
            <input type="hidden" name="kiosk_data" data-studio-entries>
        </form>

        <x-kiosk-studio :title="'Design for '.$project->name" action="Save design" back="Back to the project" />
    @endif
</x-layouts.app>
