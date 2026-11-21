{{-- Everything a client told us about a project, in tabs, for the client's page
     (projects.show) and the team's (admin.projects.show). A kiosk project gets
     its machine, screen, Kiosk Studio design and that design's data set; every
     project its overview, and details when there are any. Each tab is a link to ?tab=…, which
     resources/js/tabs.js switches in place. The client's page sets
     $designEditable when they can still change their design, and holds the
     form that saves it. --}}
@use('App\Enums\ActivityType')
@use('App\Enums\ProjectType')
@use('App\Models\ProjectActivity')
@use('App\Support\KioskDesign')
@use('App\Support\KioskSettings')

@php
    $designEditable ??= false;
    $machine = $project->kioskMachine();
    $kioskSettings = $project->kioskSettings();
    $design = $project->kioskDesign();
    // A design whose file has gone from storage can't be downloaded or
    // opened, though the client can design it again.
    $designMissing = $design && ! KioskDesign::exists($project);
    $answers = $project->answers();

    // [label, icon, a shorter label so they all fit on a phone]
    $tabs = array_filter([
        'overview' => ['Overview', 'dashboard', 'Overview'],
        'machine' => $project->type === ProjectType::KioskApp ? ['Kiosk machine', 'kiosk', 'Machine'] : null,
        'screen' => $kioskSettings ? ['Screen', 'monitor', 'Screen'] : null,
        'design' => $design || $designEditable ? ['App design', 'cube', 'Design'] : null,
        'data' => $design ? ['Data', 'table', 'Data'] : null,
        'details' => $project->budget || $project->timeline || $answers ? ['Details', 'article', 'Details'] : null,
        'log' => ['Log', 'clock', 'Log'],
    ]);
    $active = match (true) {
        $errors->has('kiosk_design') && isset($tabs['design']) => 'design',
        isset($tabs[request('tab')]) => request('tab'),
        default => 'overview',
    };
    $tabUrl = fn (string $tab) => request()->fullUrlWithQuery(['tab' => $tab]);

    // The client downloads their own design; the team, from the admin panel.
    $designUrl = request()->routeIs('admin.*')
        ? route('admin.projects.kiosk-design', $project)
        : route('projects.kiosk-design', $project);

    if ($kioskSettings) {
        $dimensions = KioskSettings::dimensions($kioskSettings['screen_size'], $kioskSettings['orientation'], $kioskSettings['resolution']);
        $resolution = (KioskSettings::RESOLUTIONS[$kioskSettings['resolution']] ?? $kioskSettings['resolution'])." · {$dimensions['width_px']} × {$dimensions['height_px']}";
        // The screen preview shows the size and resolution; this is the rest.
        $features = collect(KioskSettings::summary($kioskSettings))->except(['Screen', 'Resolution']);
    }

    // The design's data set: what its forms collect, and what they collected
    // while it was played in Kiosk Studio (newest first).
    if (isset($tabs['data'])) {
        $fields = KioskDesign::fields($project);
        $entries = array_reverse(KioskDesign::entries($project));
        $columns = KioskDesign::columns($entries);
        $dataUrl = request()->routeIs('admin.*')
            ? route('admin.projects.kiosk-data', $project)
            : route('projects.kiosk-data', $project);
    }

    // The log, newest first, by day. A project from before the log began
    // still starts with its submission.
    $activities = $project->activities()->with('user')->latest('created_at')->latest('id')->limit(200)->get();
    if ($activities->count() < 200 && ! $activities->contains('type', ActivityType::Created)) {
        $activities->push((new ProjectActivity(['type' => ActivityType::Created, 'summary' => 'Project submitted', 'created_at' => $project->created_at]))
            ->setRelation('user', $project->user));
    }
    $logDays = $activities->groupBy(fn (ProjectActivity $activity) => match (true) {
        $activity->created_at->isToday() => 'Today',
        $activity->created_at->isYesterday() => 'Yesterday',
        default => $activity->created_at->format('l, j F Y'),
    });
    // The client sees "You" and "The Xelenic team"; the team sees names.
    $onAdmin = request()->routeIs('admin.*');
    $who = fn (ProjectActivity $activity) => match (true) {
        ! $activity->user => $onAdmin ? 'A removed account' : 'The Xelenic team',
        $activity->user->is($project->user) => $onAdmin ? $activity->user->name.' (client)' : 'You',
        default => $onAdmin ? $activity->user->name : 'The Xelenic team',
    };
    $tone = fn (string $tone) => match ($tone) {
        'added' => 'bg-emerald-500',
        'removed' => 'bg-red-500',
        'renamed' => 'bg-amber-500',
        'edited' => 'bg-blue-500',
        default => 'bg-slate-400',
    };

    // At a glance: [icon, label, value, the tab with more].
    $facts = array_filter([
        isset($tabs['machine']) ? ['kiosk', 'Kiosk machine', $machine?->label() ?? 'To be recommended by Xelenic', 'machine'] : null,
        $kioskSettings ? ['monitor', 'Screen', KioskSettings::sizeLabel($kioskSettings['screen_size'], $kioskSettings['orientation']), 'screen'] : null,
        $kioskSettings ? ['image', 'Resolution', $resolution, 'screen'] : null,
        isset($tabs['design']) ? ['cube', 'App design', $design ? $design['name'].' · '.$design['screens'].' '.Str::plural('screen', $design['screens']) : 'Not designed yet', 'design'] : null,
        isset($tabs['data']) ? ['table', 'Collected data', count($fields).' '.Str::plural('field', count($fields)).' · '.count($entries).' '.Str::plural('entry', count($entries)), 'data'] : null,
        $project->budget ? ['wallet', 'Budget', $project->budget, 'details'] : null,
        $project->timeline ? ['calendar', 'Timeline', $project->timeline, 'details'] : null,
    ]);
@endphp

<div data-tabs>
    <nav role="tablist" aria-label="Project" class="flex gap-5 overflow-x-auto border-b border-slate-200 sm:gap-6">
        @foreach ($tabs as $key => [$label, $icon, $short])
            <a href="{{ $tabUrl($key) }}" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                aria-selected="{{ $key === $active ? 'true' : 'false' }}" tabindex="{{ $key === $active ? 0 : -1 }}" data-tab="{{ $key }}"
                class="-mb-px flex shrink-0 items-center gap-2 border-b-2 border-transparent px-0.5 pb-3 text-sm font-medium whitespace-nowrap text-slate-500 transition hover:border-slate-300 hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 aria-selected:border-blue-600 aria-selected:text-blue-700">
                <x-icon :name="$icon" class="hidden size-4 sm:block" />
                <span class="sm:hidden">{{ $short }}</span>
                <span class="hidden sm:inline">{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Overview --}}
    <section role="tabpanel" id="panel-overview" aria-labelledby="tab-overview" tabindex="0" class="mt-6 space-y-6 outline-none" @if ($active !== 'overview') hidden @endif>
        <x-card title="About the project">
            <p class="text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $project->description }}</p>
        </x-card>

        @if ($facts)
            <x-card title="At a glance">
                <dl class="grid gap-3 sm:grid-cols-2">
                    @foreach ($facts as [$icon, $label, $value, $tab])
                        <div class="flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <x-icon :name="$icon" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                                <dd class="mt-0.5 truncate text-sm font-semibold text-slate-900" title="{{ $value }}">{{ $value }}</dd>
                            </div>
                            <a href="{{ $tabUrl($tab) }}" data-tab-link="{{ $tab }}" class="shrink-0 text-xs font-medium text-blue-600 hover:text-blue-700">
                                View<span class="sr-only"> {{ Str::lower($label) }}</span>
                            </a>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        @endif
    </section>

    {{-- Kiosk machine --}}
    @isset($tabs['machine'])
        <section role="tabpanel" id="panel-machine" aria-labelledby="tab-machine" tabindex="0" class="mt-6 outline-none" @if ($active !== 'machine') hidden @endif>
            <x-card title="Kiosk machine">
                @if ($machine)
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="mx-auto flex size-48 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4 sm:mx-0">
                            <img src="{{ $machine->image() }}" alt="{{ $machine->label() }}" width="160" height="160" class="max-h-full w-auto object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="font-display text-xl font-semibold text-slate-900">{{ $machine->label() }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $machine->description() }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <x-badge color="slate">{{ $machine->screen()['size'] }}″ screen</x-badge>
                                <x-badge color="slate">{{ KioskSettings::ORIENTATIONS[$machine->screen()['orientation']] }}</x-badge>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center py-6 text-center">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                            <x-icon name="kiosk" class="size-6" />
                        </span>
                        <p class="mt-3 font-medium text-slate-900">No machine picked yet</p>
                        <p class="mt-1 text-sm text-slate-500">Xelenic will recommend one for this space.</p>
                    </div>
                @endif
            </x-card>
        </section>
    @endisset

    {{-- Screen --}}
    @isset($tabs['screen'])
        <section role="tabpanel" id="panel-screen" aria-labelledby="tab-screen" tabindex="0" class="mt-6 outline-none" @if ($active !== 'screen') hidden @endif>
            <x-card title="Kiosk settings" description="The screen as it will be mounted, to scale.">
                @include('projects.screen-preview', ['preview' => $kioskSettings])

                @if ($features->isNotEmpty())
                    <dl class="mt-6 grid gap-x-6 gap-y-5 border-t border-slate-100 pt-6 sm:grid-cols-2">
                        @foreach ($features as $label => $value)
                            <div>
                                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </x-card>
        </section>
    @endisset

    {{-- App design --}}
    @isset($tabs['design'])
        <section role="tabpanel" id="panel-design" aria-labelledby="tab-design" tabindex="0" class="mt-6 outline-none" @if ($active !== 'design') hidden @endif>
            <x-card title="Kiosk app design" description="Designed in Kiosk Studio, our kiosk engine.">
                <div class="flex flex-col gap-4 rounded-xl border border-slate-200 p-5 sm:flex-row sm:items-center">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <x-icon name="monitor" class="size-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        @if ($design)
                            <p class="truncate font-medium text-slate-900">{{ $design['name'] }}</p>
                            <p class="text-sm text-slate-500">{{ $design['screens'] }} {{ Str::plural('screen', $design['screens']) }} · {{ ucfirst($design['orientation']) }}</p>
                            @if ($designMissing)
                                <p class="mt-1 text-sm text-amber-700">The design file is missing.{{ $designEditable ? ' Design it again in Kiosk Studio.' : '' }}</p>
                            @endif
                        @else
                            <p class="font-medium text-slate-900">No design yet</p>
                            <p class="text-sm text-slate-500">Lay out your screens in Kiosk Studio and save them to this project.</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($project->isShared() && ! $designMissing)
                            <x-button variant="secondary" size="sm" :href="$project->playUrl()" target="_blank" rel="noopener">
                                <x-icon name="play" class="size-3.5" />
                                Play
                            </x-button>
                        @endif
                        @if ($design && ! $designMissing)
                            <x-button variant="secondary" size="sm" :href="$designUrl">Download</x-button>
                        @endif
                        @if ($designEditable)
                            <x-button type="button" size="sm" data-studio-open>{{ $design && ! $designMissing ? 'Edit in Kiosk Studio' : 'Design in Kiosk Studio' }}</x-button>
                        @elseif (! $designMissing)
                            <x-button size="sm" :href="asset('nignest/index.html').'?src='.urlencode($designUrl)" target="_blank" rel="noopener">Open in Kiosk Studio</x-button>
                        @endif
                    </div>
                </div>

                @if ($designEditable)
                    <p class="mt-4 text-sm text-slate-500">You can change the design until the project is completed. Our team sees each saved version straight away.</p>
                @endif

                @error('kiosk_design')
                    <p class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </x-card>
        </section>
    @endisset

    {{-- Data --}}
    @isset($tabs['data'])
        <section role="tabpanel" id="panel-data" aria-labelledby="tab-data" tabindex="0" class="mt-6 space-y-6 outline-none" @if ($active !== 'data') hidden @endif>
            <x-card title="Data set" description="What the kiosk app's forms collect, as set up in Kiosk Studio.">
                @if ($fields)
                    <div class="-mx-6 overflow-x-auto border-y border-slate-100">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-medium text-slate-500">
                                <tr>
                                    <th scope="col" class="px-6 py-2.5">Field</th>
                                    <th scope="col" class="px-4 py-2.5">Shown as</th>
                                    <th scope="col" class="px-4 py-2.5">Type</th>
                                    <th scope="col" class="px-4 py-2.5">Required</th>
                                    <th scope="col" class="px-6 py-2.5">Screen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($fields as $field)
                                    <tr>
                                        <td class="px-6 py-3 font-mono text-xs font-medium whitespace-nowrap text-slate-900">{{ $field['field'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $field['label'] ?: '—' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ ucfirst($field['type']) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $field['required'] ? 'Yes' : 'No' }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-slate-600">{{ $field['screen'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Every entry also records when it was sent, from which screen, and the score at the time.</p>
                @else
                    <div class="flex flex-col items-center py-6 text-center">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                            <x-icon name="table" class="size-6" />
                        </span>
                        <p class="mt-3 font-medium text-slate-900">No forms in this design</p>
                        <p class="mt-1 max-w-sm text-sm text-slate-500">Add an input field to a screen in Kiosk Studio to collect names, emails or anything else.</p>
                    </div>
                @endif
            </x-card>

            <x-card title="Collected data" description="Entries sent from the app's forms while it was played in Kiosk Studio, saved with the design.">
                @if ($entries)
                    <x-slot:actions>
                        <x-button variant="secondary" size="sm" :href="$dataUrl">Download CSV</x-button>
                    </x-slot:actions>

                    <div class="-mx-6 overflow-x-auto border-y border-slate-100">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-medium text-slate-500">
                                <tr>
                                    {{-- What every entry records, then the form's own fields by name --}}
                                    @foreach ($columns as $column)
                                        @php
                                            $recorded = ['time' => 'Sent', 'screen' => 'Screen', 'score' => 'Score'][$column] ?? null;
                                        @endphp
                                        <th scope="col" @class(['px-4 py-2.5 whitespace-nowrap first:pl-6 last:pr-6', 'font-mono' => ! $recorded])>{{ $recorded ?? $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach (array_slice($entries, 0, 50) as $entry)
                                    <tr>
                                        @foreach ($columns as $column)
                                            @php
                                                $value = $entry[$column] ?? null;
                                                if ($column === 'time' && is_string($value) && strtotime($value)) {
                                                    $value = \Illuminate\Support\Carbon::parse($value)->format('j M Y, H:i');
                                                }
                                            @endphp
                                            <td class="max-w-56 truncate px-4 py-3 whitespace-nowrap text-slate-700 first:pl-6 last:pr-6" title="{{ $value }}">{{ $value ?? '—' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">
                        {{ count($entries) > 50 ? 'Showing the latest 50 of '.count($entries).' entries.' : count($entries).' '.Str::plural('entry', count($entries)).'.' }}
                        Entries made in another browser arrive when the design is saved there.
                    </p>
                @else
                    <div class="flex flex-col items-center py-6 text-center">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                            <x-icon name="article" class="size-6" />
                        </span>
                        <p class="mt-3 font-medium text-slate-900">No entries yet</p>
                        <p class="mt-1 max-w-sm text-sm text-slate-500">Play the design in Kiosk Studio and send a form. Entries are saved here with the design.</p>
                    </div>
                @endif
            </x-card>
        </section>
    @endisset

    {{-- Details --}}
    @isset($tabs['details'])
        <section role="tabpanel" id="panel-details" aria-labelledby="tab-details" tabindex="0" class="mt-6 outline-none" @if ($active !== 'details') hidden @endif>
            <x-card title="Project details">
                <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Budget</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $project->budget ?? 'Not given' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Timeline</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $project->timeline ?? 'Not given' }}</dd>
                    </div>
                    @foreach ($answers as $question => $answer)
                        <div>
                            <dt class="text-sm text-slate-500">{{ $question }}</dt>
                            <dd class="mt-1 text-sm font-medium break-words text-slate-900">{{ $answer }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        </section>
    @endisset

    {{-- Log: everything that happened to the project, newest first, as a
         vertical timeline by day. Kiosk Studio saves list what changed. --}}
    <section role="tabpanel" id="panel-log" aria-labelledby="tab-log" tabindex="0" class="mt-6 outline-none" @if ($active !== 'log') hidden @endif>
        <x-card title="Log" :description="$project->type === ProjectType::KioskApp
            ? 'Every change to this project, newest first: each Kiosk Studio save and what it changed, status updates and the play link.'
            : 'Every change to this project, newest first.'">
            @foreach ($logDays as $day => $entries)
                <h3 @class(['flex items-center gap-3 text-xs font-semibold tracking-wide text-slate-400 uppercase', 'mt-8' => ! $loop->first])>
                    {{ $day }}
                    <span class="h-px flex-1 bg-slate-100"></span>
                </h3>

                <ol class="relative mt-5 ml-4 space-y-7 border-l border-slate-200">
                    @foreach ($entries as $activity)
                        <li class="relative pl-8">
                            <span class="absolute top-0 -left-4 flex size-8 items-center justify-center rounded-full ring-4 ring-white {{ $activity->type->tint() }}" aria-hidden="true">
                                <x-icon :name="$activity->type->icon()" class="size-4" />
                            </span>

                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                                <p class="font-medium text-slate-900">{{ $activity->summary }}</p>
                                <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->format('j M Y, H:i') }}" class="text-xs text-slate-400 tabular-nums">
                                    {{ $activity->created_at->format('H:i') }} · {{ $activity->created_at->diffForHumans() }}
                                </time>
                            </div>
                            <p class="mt-0.5 text-sm text-slate-500">by {{ $who($activity) }}</p>

                            @if ($activity->changes)
                                <dl class="mt-3 space-y-2.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 text-sm">
                                    @foreach ($activity->changes as $change)
                                        <div class="flex flex-col gap-1.5 sm:flex-row sm:gap-3">
                                            <dt class="flex shrink-0 items-center gap-2 text-slate-500 sm:w-36 sm:pt-0.5">
                                                <span class="size-1.5 shrink-0 rounded-full {{ $tone($change['tone'] ?? 'info') }}"></span>
                                                {{ $change['label'] ?? '' }}
                                            </dt>
                                            <dd class="flex min-w-0 flex-wrap gap-1.5">
                                                @foreach ($change['items'] ?? [] as $item)
                                                    <span class="max-w-full truncate rounded-md bg-white px-2 py-0.5 text-slate-700 ring-1 ring-slate-200">{{ $item }}</span>
                                                @endforeach
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </x-card>
    </section>
</div>
