<x-layouts.app title="New role">
    <div class="max-w-3xl">
        <x-card title="Role details" description="Name the role and choose what it allows.">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf

                @include('admin.roles.form', ['role' => null])

                <div class="mt-8 flex justify-end gap-3">
                    <x-button variant="secondary" :href="route('admin.roles.index')">Cancel</x-button>
                    <x-button>Create role</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
