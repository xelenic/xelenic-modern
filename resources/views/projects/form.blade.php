@php($isKiosk = $type === \App\Enums\ProjectType::KioskApp)

<x-layouts.app title="Create project">
    <div @class(['max-w-3xl' => ! $isKiosk, 'max-w-6xl' => $isKiosk])>
        {{-- Kiosk projects get a settings panel on the right (projects.kiosk-settings),
             kept in step with its preview by resources/js/kiosk-settings.js. --}}
        <form method="POST" action="{{ route('projects.store') }}" @if ($isKiosk) data-kiosk-settings @endif>
            @csrf
            <input type="hidden" name="type" value="{{ $type->value }}">

            <div @class(['space-y-6', 'xl:grid xl:grid-cols-5 xl:items-start xl:gap-6 xl:space-y-0' => $isKiosk])>
                <div class="space-y-6 xl:col-span-3">
                    {{-- The type picked on the previous step --}}
                    <div class="flex items-center gap-4 rounded-2xl border border-blue-200 bg-blue-50/60 p-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-sky-400 text-white">
                            <x-icon :name="$type->icon()" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-display font-semibold text-slate-900">{{ $type->label() }}</p>
                            <p class="text-sm text-slate-600">{{ $type->description() }}</p>
                        </div>
                        <a href="{{ route('projects.create') }}" class="shrink-0 text-sm font-medium text-blue-600 hover:text-blue-700">Change</a>
                    </div>

                    {{-- Kiosk projects pick their machine on the step before (projects.kiosk-machines) --}}
                    @if ($isKiosk)
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            @if ($machine)
                                <input type="hidden" name="details[machine]" value="{{ $machine->value }}">
                                <img src="{{ $machine->image() }}" alt="" width="80" height="80" class="size-20 shrink-0 rounded-xl border border-slate-100 bg-white object-contain p-1">
                            @else
                                <span class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                                    <x-icon name="kiosk" class="size-8" />
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium tracking-wider text-slate-500 uppercase">Kiosk machine · Step 2 of 2</p>
                                <p class="mt-1 font-display font-semibold text-slate-900">{{ $machine?->label() ?? 'Not chosen yet' }}</p>
                                <p class="text-sm text-slate-500">{{ $machine?->description() ?? "We'll recommend the right machine for your space." }}</p>
                            </div>
                            <a href="{{ route('projects.create.type', $type->value) }}" class="shrink-0 text-sm font-medium text-blue-600 hover:text-blue-700">
                                {{ $machine ? 'Change' : 'Choose one' }}
                            </a>
                        </div>
                    @endif

                    <x-card title="About your project" description="The basics, so we know where to start.">
                        <div class="space-y-5">
                            <x-form.input name="name" label="Project name" placeholder="Give your project a short name" required />
                            <x-form.textarea name="description" label="Tell us about it" hint="What do you want to achieve, and who is it for?" required />
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-form.select name="budget" label="Budget" :options="$budgets" placeholder="Choose a range" />
                                <x-form.select name="timeline" label="Timeline" :options="$timelines" placeholder="Choose a timeline" />
                            </div>
                        </div>
                    </x-card>

                    <x-card title="A few more details" description="Optional, but it helps us prepare.">
                        <div class="space-y-6">
                            @foreach ($type->questions() as $question)
                                @include('projects.question')
                            @endforeach
                        </div>
                    </x-card>
                </div>

                @if ($isKiosk)
                    <div class="xl:col-span-2">
                        @include('projects.kiosk-settings')
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-button variant="secondary" :href="route('projects.index')">Cancel</x-button>
                <x-button>Submit project</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
