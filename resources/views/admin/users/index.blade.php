<x-layouts.admin title="Users">
    @can('create users')
        <x-slot:actions>
            <x-button :href="route('admin.users.create')">
                <x-icon name="plus" class="size-4" />
                New user
            </x-button>
        </x-slot:actions>
    @endcan

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or email" aria-label="Search users"
                class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 shadow-xs outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <select name="role" aria-label="Filter by role"
            class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Filter</x-button>
        @if (request()->filled('search') || request()->filled('role'))
            <x-button variant="secondary" :href="route('admin.users.index')">Clear</x-button>
        @endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">User</th>
                        <th scope="col" class="px-6 py-3">Roles</th>
                        <th scope="col" class="hidden px-6 py-3 md:table-cell">Joined</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php
                            $isSelf = $user->is(auth()->user());
                            // Admin accounts can only be changed by admins (see UserController).
                            $locked = $user->hasRole('admin') && ! auth()->user()->hasRole('admin');
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$user" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">
                                            {{ $user->name }}
                                            @if ($isSelf)
                                                <span class="ml-1 text-xs font-normal text-slate-400">(you)</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($user->roles as $role)
                                        <x-badge :role="$role->name" />
                                    @empty
                                        <span class="text-slate-400">None</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="hidden px-6 py-4 whitespace-nowrap text-slate-500 md:table-cell">
                                {{ $user->created_at->format('j M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    @unless ($locked)
                                        @can('edit users')
                                            <x-button variant="secondary" size="sm" :href="route('admin.users.edit', $user)">Edit</x-button>
                                        @endcan
                                        @can('delete users')
                                            @unless ($isSelf)
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete {{ $user->name }}? This can't be undone.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-button variant="danger" size="sm">Delete</x-button>
                                                </form>
                                            @endunless
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">No users match your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $users->links('partials.pagination') }}
    </div>
</x-layouts.admin>
