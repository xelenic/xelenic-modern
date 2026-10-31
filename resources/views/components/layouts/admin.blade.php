{{-- The admin panel (/admin): the overview, every client's projects, users
     and roles. Links only show for people with the permission behind them. --}}
@props(['title'])

<x-layouts.panel :title="$title" label="Admin" :home="route('admin.dashboard')">
    <x-slot:nav>
        <div class="space-y-1">
            <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Manage</p>
            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="dashboard">Overview</x-nav-link>
            @can('manage projects')
                <x-nav-link :href="route('admin.projects.index')" :active="request()->routeIs('admin.projects.*')" icon="folder">Projects</x-nav-link>
            @endcan
            @can('manage clients')
                <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')" icon="star">Clients</x-nav-link>
            @endcan
            @can('view users')
                <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="users">Users</x-nav-link>
            @endcan
            @can('manage roles')
                <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" icon="shield">Roles &amp; permissions</x-nav-link>
            @endcan
        </div>

        <div class="space-y-1">
            <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Switch to</p>
            <x-nav-link :href="route('dashboard')" icon="switch">Client panel</x-nav-link>
            <x-nav-link :href="route('home')" icon="globe">Website</x-nav-link>
        </div>
    </x-slot:nav>

    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.panel>
