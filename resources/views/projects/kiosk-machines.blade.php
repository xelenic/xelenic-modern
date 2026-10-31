{{-- Kiosk App step 2: pick the hardware. --}}
<x-layouts.app title="Create project">
    <div class="max-w-6xl">
        @include('projects.kiosk.steps', ['current' => 2])

        <div class="mt-8 mb-8">
            <h2 class="font-display text-2xl font-semibold text-slate-900">Which kiosk machine do you need?</h2>
            <p class="mt-2 text-slate-500">Pick the hardware that fits your space. You'll tell us about the software next.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($machines as $machine)
                <a href="{{ route('projects.create.type', [$type->value, 'machine' => $machine->value]) }}"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-600/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <div class="aspect-square overflow-hidden border-b border-slate-100 bg-white p-3">
                        <img src="{{ $machine->image() }}" alt="{{ $machine->label() }}" width="400" height="400" loading="lazy" class="size-full object-contain transition duration-300 group-hover:scale-105">
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="font-display font-semibold text-slate-900">{{ $machine->label() }}</h3>
                        <p class="mt-1 flex-1 text-sm text-slate-500">{{ $machine->description() }}</p>
                        <span class="mt-4 flex items-center gap-1 text-sm font-medium text-blue-600">
                            Choose this kiosk
                            <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                        </span>
                    </div>
                </a>
            @endforeach

            <a href="{{ route('projects.create.type', [$type->value, 'skip' => 1]) }}"
                class="group flex flex-col overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-white transition hover:border-blue-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <div class="flex aspect-square items-center justify-center border-b border-dashed border-slate-200 bg-slate-50">
                    <span class="flex size-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <x-icon name="chat" class="size-8" />
                    </span>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <h3 class="font-display font-semibold text-slate-900">Not sure yet?</h3>
                    <p class="mt-1 flex-1 text-sm text-slate-500">Skip this step and we'll recommend the right machine for your space.</p>
                    <span class="mt-4 flex items-center gap-1 text-sm font-medium text-blue-600">
                        Help me choose
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                    </span>
                </div>
            </a>
        </div>
    </div>
</x-layouts.app>
