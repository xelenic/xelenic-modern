<x-layouts.admin title="New user">
    <div class="max-w-3xl">
        <x-card title="Account details" description="They can change their name, email and password later from their profile.">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                @include('admin.users.form', ['user' => null])

                <div class="mt-8 flex justify-end gap-3">
                    <x-button variant="secondary" :href="route('admin.users.index')">Cancel</x-button>
                    <x-button>Create user</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
