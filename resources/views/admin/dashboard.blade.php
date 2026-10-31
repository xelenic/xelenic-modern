<x-layouts.admin title="Overview">
    @if ($projects && $projects['newRequests'] > 0)
        <x-slot:actions>
            <x-button :href="route('admin.projects.index', ['status' => 'submitted'])">
                Review new requests
            </x-button>
        </x-slot:actions>
    @endif

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($projects)
            <x-stat label="Open projects" :value="number_format($projects['open'])" icon="folder" :hint="number_format($projects['total']).' in total'" />
            <x-stat label="New requests" :value="number_format($projects['newRequests'])" icon="chat" hint="Waiting for review" />
            <x-stat label="In progress" :value="number_format($projects['inProgress'])" icon="trending" hint="Being built now" />
        @endif
        @if ($users)
            <x-stat label="Users" :value="number_format($users['total'])" icon="users" :hint="'+'.number_format($users['newThisWeek']).' this week'" />
        @endif
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        @if ($projects)
            <x-card class="xl:col-span-2" title="Latest project requests" description="The newest projects from clients.">
                <x-slot:actions>
                    <a href="{{ route('admin.projects.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                </x-slot:actions>

                @if ($projects['latest']->isEmpty())
                    <p class="rounded-xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">No project requests yet.</p>
                @else
                    <ul class="-mx-2 divide-y divide-slate-100">
                        @foreach ($projects['latest'] as $project)
                            <li>
                                <a href="{{ route('admin.projects.show', $project) }}" class="group flex items-center gap-3 rounded-lg px-2 py-3 transition hover:bg-slate-50">
                                    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
                                        <x-icon :name="$project->type->icon()" class="size-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-900 group-hover:text-blue-700">{{ $project->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $project->user->name }} · {{ $project->type->label() }}</p>
                                    </div>
                                    <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
                                    <p class="hidden w-24 shrink-0 text-right text-xs text-slate-500 sm:block">{{ $project->created_at->diffForHumans() }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Projects by status" description="Select a status to see its projects.">
                <x-bar-list :items="$projects['byStatus']" class="-mx-2" />
            </x-card>

            <x-card class="xl:col-span-2" title="Projects by type" description="What clients are asking Xelenic for.">
                <x-bar-list :items="$projects['byType']" class="-mx-2" />
            </x-card>
        @endif

        @if ($users)
            <x-card title="New users" description="The latest sign-ups.">
                <x-slot:actions>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                </x-slot:actions>

                <ul class="divide-y divide-slate-100">
                    @foreach ($users['latest'] as $user)
                        <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <x-avatar :user="$user" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->created_at->diffForHumans() }}</p>
                            </div>
                            @foreach ($user->roles as $role)
                                <x-badge :role="$role->name" />
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>
</x-layouts.admin>
