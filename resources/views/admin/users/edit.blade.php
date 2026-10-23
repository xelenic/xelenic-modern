<x-layouts.app title="Edit user">
    <div class="max-w-3xl">
        <x-card :title="$user->name" :description="'Joined '.$user->created_at->format('j F Y')">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                @include('admin.users.form')

                <div class="mt-8 flex justify-end gap-3">
                    <x-button variant="secondary" :href="route('admin.users.index')">Cancel</x-button>
                    <x-button>Save changes</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
