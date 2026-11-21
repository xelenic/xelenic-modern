@php
    $steps = ['Choose a service', 'Share the details', 'Hear from our team'];
@endphp

<x-layouts.app title="Create project">
    <div class="max-w-6xl">
        {{-- Header --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-8 shadow-xs sm:px-10">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100 ring-inset">
                        <span class="size-1.5 rounded-full bg-blue-500"></span>
                        New project
                    </span>
                    <h2 class="mt-4 font-display text-3xl font-semibold tracking-tight text-slate-900 sm:text-[2.25rem] sm:leading-tight">What would you like to build?</h2>
                    <p class="mt-3 text-slate-500">Pick the option that fits best. You'll answer a few quick questions next, and the Xelenic team will get back to you.</p>
                </div>

                {{-- Three equal columns; one line runs behind the circles, blue where you are. --}}
                <ol class="relative grid w-full max-w-sm shrink-0 grid-cols-3 lg:w-[22rem]" aria-label="How it works">
                    <span aria-hidden="true" class="absolute top-4 right-[16.67%] left-[16.67%] h-px bg-slate-200"></span>
                    <span aria-hidden="true" class="absolute top-4 left-[16.67%] h-px w-1/3 bg-blue-600"></span>
                    @foreach ($steps as $step)
                        <li class="relative flex flex-col items-center text-center" @if ($loop->first) aria-current="step" @endif>
                            <span @class([
                                'flex size-8 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                'bg-blue-600 text-white ring-4 ring-blue-50' => $loop->first,
                                'bg-white text-slate-500 ring-1 ring-slate-200' => ! $loop->first,
                            ])>{{ $loop->iteration }}</span>
                            <span @class([
                                'mt-2.5 text-xs whitespace-nowrap',
                                'font-semibold text-slate-900' => $loop->first,
                                'text-slate-500' => ! $loop->first,
                            ])>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- Services --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($types as $type)
                <a href="{{ route('projects.create.type', $type) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-blue-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <div class="flex items-start justify-between">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <x-icon :name="$type->icon()" class="size-6" />
                        </span>
                        <span class="font-display text-sm font-medium text-slate-300 tabular-nums">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <h3 class="mt-5 font-display text-lg font-semibold text-slate-900">{{ $type->label() }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ $type->description() }}</p>

                    <ul class="mt-5 flex-1 space-y-2 border-t border-slate-100 pt-5">
                        @foreach ($type->highlights() as $highlight)
                            <li class="flex items-start gap-2 text-sm text-slate-600">
                                <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-blue-600" />
                                {{ $highlight }}
                            </li>
                        @endforeach
                    </ul>

                    <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600">
                        Get started
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Help --}}
        <div class="mt-6 flex flex-col gap-4 rounded-2xl border border-dashed border-slate-300 bg-white/60 p-5 sm:flex-row sm:items-center">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                <x-icon name="chat" class="size-5" />
            </span>
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-900">Not sure which one fits?</p>
                <p class="text-sm text-slate-500">Pick the closest match and describe your idea. We'll take it from there.</p>
            </div>
            <x-button variant="secondary" :href="route('home').'#contact'">Talk to our team</x-button>
        </div>
    </div>
</x-layouts.app>
