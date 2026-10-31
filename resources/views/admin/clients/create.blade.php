<x-layouts.admin title="Add client">
    <form method="POST" action="{{ route('admin.clients.store') }}" enctype="multipart/form-data">
        @csrf

        @include('admin.clients.form')

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" :href="route('admin.clients.index')">Cancel</x-button>
            <x-button>Add client</x-button>
        </div>
    </form>
</x-layouts.admin>
