<x-layouts.app title="Dashboard">
    @php
        $hasProjects = $counts['total'] > 0;
        $firstName = Str::before($user->name, ' ');

        // "5 waiting for review and 2 being built", for the banner.
        $summary = collect([
            $counts['review'] ? $counts['review'].' waiting for review' : null,
            $counts['progress'] ? $counts['progress'].' being built' : null,
            $counts['completed'] ? $counts['completed'].' completed' : null,
        ])->filter()->join(', ', ' and ');

        // The numbers in the banner: [label, value, dot colour].
        $stats = [
            ['All projects', $counts['total'], 'bg-blue-500'],
            ['Under review', $counts['review'], 'bg-amber-400'],
            ['In progress', $counts['progress'], 'bg-violet-400'],
            ['Completed', $counts['completed'], 'bg-emerald-400'],
        ];

        $steps = ['Submitted', 'In review', 'In progress', 'Completed'];
        $dot = fn ($status) => match ($status->color()) {
            'amber' => 'bg-amber-500 ring-amber-100',
            'blue' => 'bg-blue-500 ring-blue-100',
            'violet' => 'bg-violet-500 ring-violet-100',
            'emerald' => 'bg-emerald-500 ring-emerald-100',
            default => 'bg-slate-400 ring-slate-100',
        };
    @endphp

    {{-- Banner: who's here, where things stand, and the numbers. A soft,
         solid blue, with the robot on a brand-blue disc. --}}
    <section class="relative overflow-hidden rounded-3xl border border-blue-100 bg-[#e8f1ff] shadow-sm">
        <svg class="pointer-events-none absolute inset-0 h-full w-full text-blue-600/[0.08]" aria-hidden="true">
            <defs>
                <pattern id="dashboard-dots" width="22" height="22" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.4" fill="currentColor" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dashboard-dots)" />
        </svg>

        <div class="relative flex items-center gap-8 p-6 sm:p-8 lg:p-10">
            <div class="min-w-0 flex-1">
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-blue-700 shadow-xs ring-1 ring-blue-100 ring-inset">
                    <x-icon name="calendar" class="size-3.5" />
                    {{ now()->format('l, j F Y') }}
                </p>
                <h2 class="mt-4 font-display text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                    {{ $hasProjects ? 'Welcome back, '.$firstName : 'Welcome to Xelenic, '.$firstName }}
                </h2>
                <p class="mt-3 max-w-xl text-base text-slate-600">
                    @if ($hasProjects)
                        You have {{ $counts['total'] }} {{ Str::plural('project', $counts['total']) }} with Xelenic{{ $summary ? ': '.$summary : '' }}.
                    @else
                        Kiosk apps, cloud services, engineers for your team and custom software. Tell us what you need and we'll take it from there.
                    @endif
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <x-button :href="$hasProjects ? route('projects.create') : '#services'">
                        <x-icon name="plus" class="size-4" />
                        {{ $hasProjects ? 'New project' : 'Start a project' }}
                    </x-button>
                    @if ($hasProjects)
                        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs ring-1 ring-blue-200 transition ring-inset hover:bg-blue-50 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            All projects
                            <x-icon name="arrow-right" class="size-4" />
                        </a>
                    @endif
                </div>
            </div>

            {{-- The robot, on a blue disc with rings round it. --}}
            <div class="relative hidden size-56 shrink-0 items-center justify-center lg:flex xl:size-64" aria-hidden="true">
                <span class="absolute inset-0 rounded-full border border-blue-200/70"></span>
                <span class="absolute inset-6 rounded-full border border-blue-200"></span>
                <span class="absolute inset-12 rounded-full bg-blue-600"></span>
                <img src="{{ asset('moderning_xelenic/assets/img/robot-still.webp') }}" alt="" width="615" height="640" class="relative w-40 drop-shadow-[0_16px_20px_rgba(30,64,175,0.28)] xl:w-44">
            </div>
        </div>

        @if ($hasProjects)
            <dl class="relative grid grid-cols-2 border-t border-blue-100 bg-white/70 sm:grid-cols-4">
                @foreach ($stats as $i => [$label, $value, $color])
                    <a href="{{ route('projects.index') }}" @class([
                        'group px-6 py-5 transition hover:bg-white sm:px-8',
                        'border-l border-blue-100' => $i % 2 === 1,
                        'sm:border-l sm:border-blue-100' => $i > 0,
                        'border-t border-blue-100 sm:border-t-0' => $i > 1,
                    ])>
                        <dt class="flex items-center gap-2 text-sm text-slate-500">
                            <span class="size-2 rounded-full {{ $color }}"></span>
                            {{ $label }}
                        </dt>
                        <dd class="mt-1.5 font-display text-3xl font-semibold text-slate-900 tabular-nums">{{ $value }}</dd>
                    </a>
                @endforeach
            </dl>
        @endif
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-8 lg:col-span-2">
            @if ($hasProjects)
                {{-- Their latest projects, and how far along each is. --}}
                <x-card title="Your projects" description="What you've asked the Xelenic team to build.">
                    <x-slot:actions>
                        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700">
                            View all
                            <x-icon name="arrow-right" class="size-3.5" />
                        </a>
                    </x-slot:actions>

                    <ul class="-mx-3 -my-2 divide-y divide-slate-100">
                        @foreach ($projects as $project)
                            @php
                                $step = $project->status->step();
                                $design = $project->kioskDesign();
                            @endphp
                            <li class="group relative flex gap-4 rounded-xl px-3 py-4 transition hover:bg-slate-50">
                                <x-dashboard.type-icon :type="$project->type" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ route('projects.show', $project) }}" class="block truncate font-medium text-slate-900 after:absolute after:inset-0 after:rounded-xl group-hover:text-blue-700">{{ $project->name }}</a>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $project->type->label() }} · Started {{ $project->created_at->format('j M Y') }}</p>
                                        </div>
                                        <x-badge :color="$project->status->color()" class="shrink-0">{{ $project->status->label() }}</x-badge>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                                        @if ($step)
                                            {{-- Submitted, reviewed, being built, done. --}}
                                            <div class="flex items-center gap-2.5" title="Step {{ $step }} of 4: {{ $steps[$step - 1] }}">
                                                <span aria-hidden="true" class="flex w-28 gap-1">
                                                    @for ($i = 1; $i <= 4; $i++)
                                                        <span @class(['h-1.5 flex-1 rounded-full', 'bg-blue-600' => $i <= $step, 'bg-slate-200' => $i > $step])></span>
                                                    @endfor
                                                </span>
                                                <span class="text-xs text-slate-500">Step {{ $step }} of 4</span>
                                            </div>
                                        @endif
                                        @if ($design)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                                <x-icon name="cube" class="size-3.5 text-slate-400" />
                                                {{ $design['screens'] }} {{ Str::plural('screen', $design['screens']) }} designed
                                            </span>
                                        @endif
                                        @if ($project->isShared())
                                            <a href="{{ $project->playUrl() }}" target="_blank" rel="noopener" class="relative z-10 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset hover:bg-emerald-100">
                                                <x-icon name="play" class="size-3" />
                                                Play
                                            </a>
                                        @endif
                                        <span class="ml-auto hidden text-xs text-slate-400 sm:inline">Updated {{ $project->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-card>

                <section>
                    <h2 class="font-display text-lg font-semibold text-slate-900">More from Xelenic</h2>
                    <p class="mt-1 text-sm text-slate-500">Anything else we can build or run for you.</p>
                    <x-dashboard.services compact class="mt-4" />
                </section>
            @else
                <x-card id="services" class="scroll-mt-24" title="Start a new project" description="Choose a service, answer a few questions, and our team will get back to you.">
                    <x-dashboard.services />
                </x-card>
            @endif
        </div>

        <aside class="space-y-6">
            {{-- Kiosk Studio: back into their latest design, or a first one. --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <img src="{{ asset('nignest/img/xelenic-mark.png') }}" alt="" class="size-9">
                    <div>
                        <h2 class="font-display text-sm font-semibold text-slate-900">Kiosk Studio</h2>
                        <p class="text-xs text-slate-500">Design your kiosk app yourself</p>
                    </div>
                </div>
                <div class="p-5">
                    @if ($kioskProject)
                        @php
                            $design = $kioskProject->kioskDesign();
                        @endphp
                        <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Latest design</p>
                        <p class="mt-1 truncate font-medium text-slate-900">{{ $design['name'] }}</p>
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $design['screens'] }} {{ Str::plural('screen', $design['screens']) }} · {{ ucfirst($design['orientation']) }} · {{ $kioskProject->name }}</p>
                        <div class="mt-5 grid grid-cols-2 gap-2">
                            <x-button size="sm" :href="route('projects.show', [$kioskProject, 'tab' => 'design'])">
                                <x-icon name="cube" class="size-3.5" />
                                Open design
                            </x-button>
                            @if ($kioskProject->isShared())
                                <x-button variant="secondary" size="sm" :href="$kioskProject->playUrl()" target="_blank" rel="noopener">
                                    <x-icon name="play" class="size-3.5" />
                                    Play
                                </x-button>
                            @else
                                <x-button variant="secondary" size="sm" :href="route('projects.show', $kioskProject)">
                                    <x-icon name="link" class="size-3.5" />
                                    Share to play
                                </x-button>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-slate-600">Lay out your kiosk's screens, add games and forms, and play it in your browser before the kiosk arrives.</p>
                        <x-button size="sm" class="mt-5 w-full" :href="route('projects.create.type', App\Enums\ProjectType::KioskApp)">
                            <x-icon name="kiosk" class="size-3.5" />
                            Start a kiosk project
                        </x-button>
                    @endif
                </div>
            </section>

            @if ($hasProjects)
                {{-- What changed lately, newest first. --}}
                <x-card title="Latest updates">
                    <ol class="relative space-y-5 before:absolute before:top-2 before:bottom-2 before:left-[5px] before:w-px before:bg-slate-200">
                        @foreach ($updates as $project)
                            <li class="relative flex gap-4">
                                <span class="relative mt-1.5 size-[11px] shrink-0 rounded-full ring-4 {{ $dot($project->status) }}"></span>
                                <div class="min-w-0">
                                    <a href="{{ route('projects.show', $project) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-blue-700">{{ $project->name }}</a>
                                    {{-- What the newest status means; the rest just say what it is. --}}
                                    @if ($loop->first)
                                        <p class="mt-0.5 text-sm text-slate-500">{{ $project->status->description() }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-400">{{ $project->status->label() }} · {{ $project->updated_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            @endif

            {{-- A way to reach the team. --}}
            <section class="rounded-2xl bg-blue-600 p-6 text-white shadow-sm">
                <span class="flex size-10 items-center justify-center rounded-xl bg-white/15">
                    <x-icon name="chat" class="size-5" />
                </span>
                <h2 class="mt-4 font-display text-lg font-semibold">Need a hand?</h2>
                <p class="mt-1 text-sm text-blue-100">Questions about a project, or not sure what to ask for? Talk to the Xelenic team.</p>
                <a href="{{ route('home') }}#contact" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Contact us
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            </section>
        </aside>
    </div>
</x-layouts.app>
