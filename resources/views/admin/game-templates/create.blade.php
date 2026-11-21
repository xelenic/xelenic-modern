<x-layouts.admin title="Add game template">
    <form method="POST" action="{{ route('admin.game-templates.store') }}" enctype="multipart/form-data">
        @csrf

        @include('admin.game-templates.form')

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" :href="route('admin.game-templates.index')">Cancel</x-button>
            <x-button>Add template</x-button>
        </div>
    </form>
</x-layouts.admin>
