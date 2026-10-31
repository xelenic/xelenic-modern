<x-layouts.app :title="$project->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('projects.index')">All projects</x-button>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('projects.partials.overview')
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
                </dl>

                @if ($project->status->canBeCancelled())
                    <form method="POST" action="{{ route('projects.cancel', $project) }}" data-confirm="Cancel this project? You can always start a new one." class="mt-6">
                        @csrf
                        <x-button variant="danger" class="w-full">Cancel project</x-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.app>
