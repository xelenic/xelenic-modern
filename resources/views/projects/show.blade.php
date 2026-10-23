<x-layouts.app :title="$project->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('projects.index')">All projects</x-button>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <div class="flex items-center gap-4">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-500/20">
                        <x-icon :name="$project->type->icon()" class="size-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">{{ $project->type->label() }}</p>
                        <h2 class="font-display text-xl font-semibold text-slate-900">{{ $project->name }}</h2>
                    </div>
                </div>
                <p class="mt-6 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $project->description }}</p>
            </x-card>

            @if ($machine = $project->kioskMachine())
                <x-card title="Kiosk machine">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <img src="{{ $machine->image() }}" alt="{{ $machine->label() }}" width="160" height="160" class="mx-auto size-40 shrink-0 rounded-xl border border-slate-100 bg-white object-contain p-2 sm:mx-0">
                        <div>
                            <p class="font-display text-lg font-semibold text-slate-900">{{ $machine->label() }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $machine->description() }}</p>
                        </div>
                    </div>
                </x-card>
            @elseif ($project->type === \App\Enums\ProjectType::KioskApp)
                <x-card title="Kiosk machine">
                    <p class="text-sm text-slate-500">No machine picked yet: Xelenic will recommend one for this space.</p>
                </x-card>
            @endif

            @if ($kioskSettings = $project->kioskSettings())
                <x-card title="Kiosk settings">
                    @include('projects.screen-preview', ['preview' => $kioskSettings])

                    <dl class="mt-6 grid gap-x-6 gap-y-5 border-t border-slate-100 pt-6 sm:grid-cols-2">
                        @foreach (\App\Support\KioskSettings::summary($kioskSettings) as $label => $value)
                            <div>
                                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endif

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
                    @foreach ($project->answers() as $question => $answer)
                        <div>
                            <dt class="text-sm text-slate-500">{{ $question }}</dt>
                            <dd class="mt-1 text-sm font-medium break-words text-slate-900">{{ $answer }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Status">
                <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
                <p class="mt-3 text-sm text-slate-500">{{ $project->status->description() }}</p>

                <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5 text-sm">
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-slate-900">{{ $project->created_at->format('j F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Last updated</dt>
                        <dd class="mt-1 text-slate-900">{{ $project->updated_at->diffForHumans() }}</dd>
                    </div>
                    @unless ($isOwner)
                        <div>
                            <dt class="text-slate-500">Requested by</dt>
                            <dd class="mt-1 text-slate-900">
                                {{ $project->user->name }}
                                <span class="block break-all text-slate-500">{{ $project->user->email }}</span>
                            </dd>
                        </div>
                    @endunless
                </dl>

                @if ($isOwner && $project->status->canBeCancelled())
                    <form method="POST" action="{{ route('projects.cancel', $project) }}" data-confirm="Cancel this project? You can always start a new one." class="mt-6">
                        @csrf
                        <x-button variant="danger" class="w-full">Cancel project</x-button>
                    </form>
                @endif
            </x-card>

            @can('manage projects')
                <x-card title="Update status" description="The client sees the new status straight away.">
                    <form method="POST" action="{{ route('projects.status', $project) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <x-form.select name="status" label="Status" :options="$statuses" :value="$project->status->value" :placeholder="null" />
                        <x-button class="w-full">Update status</x-button>
                    </form>
                </x-card>
            @endcan
        </div>
    </div>
</x-layouts.app>
