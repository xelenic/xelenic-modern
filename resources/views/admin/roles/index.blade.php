<x-layouts.app title="Roles & permissions">
    <x-slot:actions>
        <x-button :href="route('admin.roles.create')">
            <x-icon name="plus" class="size-4" />
            New role
        </x-button>
    </x-slot:actions>

    <p class="mb-6 max-w-2xl text-sm text-slate-500">
        A role is a bundle of permissions. Give someone a role from the
        <a href="{{ route('admin.users.index') }}" class="font-medium text-blue-600 hover:text-blue-700">Users</a>
        page and they can do everything the role allows.
    </p>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            <x-card class="flex flex-col">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-900">{{ ucfirst($role->name) }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</p>
                    </div>
                    <x-badge :role="$role->name">
                        @if ($role->name === 'admin')
                            Full access
                        @else
                            {{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
                        @endif
                    </x-badge>
                </div>

                <div class="mt-5 flex flex-1 flex-wrap content-start gap-1.5">
                    @if ($role->name === 'admin')
                        <p class="text-sm text-slate-500">Passes every permission check, including ones added later.</p>
                    @else
                        @forelse ($role->permissions->sortBy('name') as $permission)
                            <x-badge color="slate">{{ $permission->name }}</x-badge>
                        @empty
                            <p class="text-sm text-slate-500">No extra permissions: basic account access only.</p>
                        @endforelse
                    @endif
                </div>

                <div class="mt-6 flex items-center gap-2 border-t border-slate-100 pt-4">
                    <x-button variant="secondary" size="sm" :href="route('admin.roles.edit', $role)">
                        {{ $role->name === 'admin' ? 'View' : 'Edit' }}
                    </x-button>
                    @if (in_array($role->name, $protectedRoles))
                        <span class="ml-auto text-xs text-slate-400">Built-in role</span>
                    @else
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Delete the {{ $role->name }} role? Users who have it will lose its permissions.">
                            @csrf
                            @method('DELETE')
                            <x-button variant="danger" size="sm">Delete</x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
</x-layouts.app>
