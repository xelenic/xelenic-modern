{{-- The four Kiosk App steps, with $current the one on screen. Steps 1 and 2
     are their own pages; 3 and 4 are one form that resources/js/project-steps.js
     moves between (it updates each step's data-state). --}}
@php
    $kioskSteps = [
        1 => ['Service', 'Kiosk App'],
        2 => ['Machine', isset($machine) && $machine ? $machine->label() : 'Choose a kiosk'],
        3 => ['Your project', 'Name and screen'],
        4 => ['Budget & features', 'Payments, games, extras'],
    ];
    $links = [
        1 => route('projects.create'),
        2 => route('projects.create.type', 'kiosk-app'),
    ];
@endphp

<nav aria-label="Kiosk project steps">
    <ol class="grid grid-cols-2 gap-2 lg:grid-cols-4">
        @foreach ($kioskSteps as $number => [$title, $subtitle])
            @php($state = $number < $current ? 'complete' : ($number === $current ? 'current' : 'upcoming'))
            {{-- Steps 3 and 4 are only buttons on the form that holds them. --}}
            @php($tag = isset($links[$number]) ? 'a' : ($current >= 3 ? 'button' : 'div'))
            <li data-step-indicator="{{ $number }}" data-state="{{ $state }}" class="group">
                <{{ $tag }}
                    @if ($tag === 'a') href="{{ $links[$number] }}" @elseif ($tag === 'button') type="button" data-step-goto="{{ $number }}" @endif
                    @if ($state === 'current') aria-current="step" @endif
                    class="flex w-full items-center gap-3 rounded-xl border bg-white px-3 py-2.5 text-left shadow-xs transition hover:border-slate-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 group-data-[state=current]:border-blue-200 group-data-[state=current]:bg-blue-50/70 group-data-[state=complete]:border-slate-200 group-data-[state=upcoming]:border-dashed group-data-[state=upcoming]:border-slate-200 group-data-[state=upcoming]:bg-white/60 group-data-[state=upcoming]:shadow-none"
                >
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums transition group-data-[state=complete]:bg-emerald-50 group-data-[state=complete]:text-emerald-600 group-data-[state=current]:bg-blue-600 group-data-[state=current]:text-white group-data-[state=current]:shadow-md group-data-[state=current]:shadow-blue-600/30 group-data-[state=upcoming]:bg-slate-100 group-data-[state=upcoming]:text-slate-400">
                        <span class="group-data-[state=complete]:hidden">{{ $number }}</span>
                        <x-icon name="check" class="hidden size-4 group-data-[state=complete]:block" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-slate-900 group-data-[state=upcoming]:text-slate-500">{{ $title }}</span>
                        <span class="block truncate text-xs text-slate-500 group-data-[state=upcoming]:text-slate-400">{{ $subtitle }}</span>
                    </span>
                </{{ $tag }}>
            </li>
        @endforeach
    </ol>
</nav>
