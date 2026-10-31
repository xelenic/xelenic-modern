<x-layouts.admin :title="$project->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('admin.projects.index')">All projects</x-button>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('projects.partials.overview')
        </div>

        <div class="space-y-6">
            <x-card title="Status" description="The client sees changes straight away.">
                <div class="flex items-center gap-2">
                    <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
                    <span class="text-xs text-slate-500">updated {{ $project->updated_at->diffForHumans() }}</span>
                </div>

                <form method="POST" action="{{ route('admin.projects.status', $project) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <x-form.select name="status" label="Change status" :options="$statuses" :value="$project->status->value" :placeholder="null" />
                    <x-button class="w-full">Update status</x-button>
                </form>

                <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5 text-sm">
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-slate-900">{{ $project->created_at->format('j F Y, g:i a') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Client">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$project->user" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $project->user->name }}</p>
                        <a href="mailto:{{ $project->user->email }}" class="block truncate text-sm text-blue-600 hover:text-blue-700">{{ $project->user->email }}</a>
                    </div>
                </div>

                <dl class="mt-5 space-y-4 border-t border-slate-100 pt-5 text-sm">
                    <div>
                        <dt class="text-slate-500">Roles</dt>
                        <dd class="mt-1.5 flex flex-wrap gap-1.5">
                            @forelse ($project->user->roles as $role)
                                <x-badge :role="$role->name" />
                            @empty
                                <span class="text-slate-500">None</span>
                            @endforelse
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Member since</dt>
                        <dd class="mt-1 text-slate-900">{{ $project->user->created_at->format('j F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Projects</dt>
                        <dd class="mt-1 text-slate-900">
                            <a href="{{ route('admin.projects.index', ['search' => $project->user->email]) }}" class="text-blue-600 hover:text-blue-700">
                                {{ $project->user->projects_count }} {{ Str::plural('project', $project->user->projects_count) }}
                            </a>
                        </dd>
                    </div>
                </dl>

                {{-- Admin accounts can only be changed by admins (see UserController) --}}
                @can('edit users')
                    @unless ($project->user->hasRole('admin') && ! auth()->user()->hasRole('admin'))
                        <x-button variant="secondary" :href="route('admin.users.edit', $project->user)" class="mt-5 w-full">Edit user</x-button>
                    @endunless
                @endcan
            </x-card>
        </div>
    </div>
</x-layouts.admin>
