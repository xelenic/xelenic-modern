<x-layouts.app title="Projects">
    <x-slot:actions>
        <x-button :href="route('projects.create')">
            <x-icon name="plus" class="size-4" />
            Create project
        </x-button>
    </x-slot:actions>

    @can('manage projects')
        <div class="mb-6 inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-xs">
            <a href="{{ route('projects.index') }}" @class([
                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                'bg-blue-50 text-blue-700' => ! $showAll,
                'text-slate-600 hover:text-slate-900' => $showAll,
            ])>My projects</a>
            <a href="{{ route('projects.index', ['scope' => 'all']) }}" @class([
                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                'bg-blue-50 text-blue-700' => $showAll,
                'text-slate-600 hover:text-slate-900' => ! $showAll,
            ])>All projects</a>
        </div>
    @endcan

    @if ($projects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <x-icon name="folder" class="size-6" />
            </span>
            <h2 class="mt-4 font-display text-lg font-semibold text-slate-900">
                {{ $showAll ? 'No projects yet' : "You don't have any projects yet" }}
            </h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                Tell us what you'd like to build and the Xelenic team will get back to you.
            </p>
            <x-button :href="route('projects.create')" class="mt-6">
                <x-icon name="plus" class="size-4" />
                Create project
            </x-button>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex items-center gap-2 text-xs font-medium text-slate-500">
                            <span class="rounded-lg bg-blue-50 p-1.5 text-blue-600">
                                <x-icon :name="$project->type->icon()" class="size-4" />
                            </span>
                            {{ $project->type->label() }}
                        </span>
                        <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
                    </div>

                    <h2 class="mt-4 font-display text-base font-semibold text-slate-900 group-hover:text-blue-700">{{ $project->name }}</h2>
                    <p class="mt-2 line-clamp-2 flex-1 text-sm text-slate-500">{{ $project->description }}</p>

                    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-slate-100 pt-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <x-icon name="calendar" class="size-3.5" />
                            {{ $project->created_at->format('j M Y') }}
                        </span>
                        @if ($project->budget)
                            <span class="flex items-center gap-1.5">
                                <x-icon name="wallet" class="size-3.5" />
                                {{ $project->budget }}
                            </span>
                        @endif
                        @if ($showAll)
                            <span class="ml-auto font-medium text-slate-700">{{ $project->user->name }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $projects->links('partials.pagination') }}
        </div>
    @endif
</x-layouts.app>
