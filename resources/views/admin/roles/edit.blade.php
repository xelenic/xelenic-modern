<x-layouts.admin :title="ucfirst($role->name).' role'">
    <div class="max-w-3xl">
        @if ($role->name === 'admin')
            <x-card title="Admin" description="This role can't be edited.">
                <x-alert type="info">
                    Admins always have full access to everything, including permissions added later, so there's
                    nothing to choose here.
                </x-alert>
                <div class="mt-6">
                    <x-button variant="secondary" :href="route('admin.roles.index')">Back to roles</x-button>
                </div>
            </x-card>
        @else
            <x-card title="Role details" description="Changes apply straight away to everyone with this role.">
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf
                    @method('PUT')

                    @include('admin.roles.form')

                    <div class="mt-8 flex justify-end gap-3">
                        <x-button variant="secondary" :href="route('admin.roles.index')">Cancel</x-button>
                        <x-button>Save changes</x-button>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.admin>
