{{-- The signed-in user's menu at the right of the top bar. It's a <details>, so
     it opens without JavaScript; app.js closes it on a click elsewhere or Escape.
     `adminLink` is off inside the admin panel, which links back on its own. --}}
@props(['adminLink' => true])

@php
    $user = auth()->user();
    $role = $user->getRoleNames()->first();
@endphp

<details class="group relative" data-menu>
    <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-lg py-1 pr-2 pl-1 transition select-none hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-blue-600 [&::-webkit-details-marker]:hidden">
        <x-avatar :user="$user" size="sm" />
        <span class="hidden min-w-0 text-left sm:block">
            <span class="block max-w-40 truncate text-sm leading-tight font-medium text-slate-900">{{ $user->name }}</span>
            @if ($role)
                <span class="block text-xs leading-tight text-slate-500">{{ ucfirst($role) }}</span>
            @endif
        </span>
        <x-icon name="chevron-down" class="size-4 text-slate-400 transition group-open:rotate-180" />
    </summary>

    <div class="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-slate-200 bg-white shadow-lg">
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
            <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
        </div>

        <div class="p-1.5">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                <x-icon name="user" class="size-4 text-slate-400" />
                Profile
            </a>
            <a href="{{ route('profile.edit') }}#password" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                <x-icon name="key" class="size-4 text-slate-400" />
                Change password
            </a>
            @if ($adminLink)
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        <x-icon name="shield" class="size-4 text-slate-400" />
                        Admin panel
                    </a>
                @endcan
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 p-1.5">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                <x-icon name="logout" class="size-4 text-slate-400" />
                Log out
            </button>
        </form>
    </div>
</details>
