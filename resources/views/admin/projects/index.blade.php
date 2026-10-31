<x-layouts.admin title="Projects">
    @php
        // Tabs and filters keep each other's choices.
        $query = fn (array $changes) => array_filter([
            'status' => $status?->value,
            'type' => $type?->value,
            'search' => request('search'),
            ...$changes,
        ]);
    @endphp

    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        @foreach ([null, ...\App\Enums\ProjectStatus::cases()] as $tab)
            @php($active = $status === $tab)
            <a href="{{ route('admin.projects.index', $query(['status' => $tab?->value])) }}" @if ($active) aria-current="page" @endif @class([
                'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => $active,
                'bg-white text-slate-600 ring-1 ring-slate-200 ring-inset hover:bg-slate-50 hover:text-slate-900' => ! $active,
            ])>
                {{ $tab?->label() ?? 'All' }}
                <span @class([
                    'rounded-full px-1.5 text-xs tabular-nums',
                    'bg-white/20' => $active,
                    'bg-slate-100 text-slate-500' => ! $active,
                ])>{{ $tab ? ($statusCounts[$tab->value] ?? 0) : $statusCounts->sum() }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.projects.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status->value }}">
        @endif
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by project, client name or email" aria-label="Search projects"
                class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 shadow-xs outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <select name="type" aria-label="Filter by type"
            class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected($type?->value === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Filter</x-button>
        @if ($status || $type || request()->filled('search'))
            <x-button variant="secondary" :href="route('admin.projects.index')">Clear</x-button>
        @endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Project</th>
                        <th scope="col" class="px-6 py-3">Client</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="hidden px-6 py-3 lg:table-cell">Budget</th>
                        <th scope="col" class="hidden px-6 py-3 md:table-cell">Submitted</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($projects as $project)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
                                        <x-icon :name="$project->type->icon()" class="size-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.projects.show', $project) }}" class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $project->name }}</a>
                                        <p class="truncate text-slate-500">{{ $project->type->label() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="truncate text-slate-900">{{ $project->user->name }}</p>
                                <p class="truncate text-slate-500">{{ $project->user->email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
                            </td>
                            <td class="hidden px-6 py-4 whitespace-nowrap text-slate-500 lg:table-cell">{{ $project->budget ?? '—' }}</td>
                            <td class="hidden px-6 py-4 whitespace-nowrap text-slate-500 md:table-cell">{{ $project->created_at->format('j M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <x-button variant="secondary" size="sm" :href="route('admin.projects.show', $project)">View</x-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">No projects match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $projects->links('partials.pagination') }}
    </div>
</x-layouts.admin>
