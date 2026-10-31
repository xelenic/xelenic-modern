<x-layouts.admin :title="'Edit '.$client->name">
    @if ($client->is_published && $client->hasStory())
        <x-slot:actions>
            <x-button variant="secondary" :href="route('clients.show', $client)" target="_blank">
                <x-icon name="eye" class="size-4" />
                View story
            </x-button>
        </x-slot:actions>
    @endif

    <form method="POST" action="{{ route('admin.clients.update', $client) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.clients.form')

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" :href="route('admin.clients.index')">Cancel</x-button>
            <x-button>Save changes</x-button>
        </div>
    </form>
</x-layouts.admin>
