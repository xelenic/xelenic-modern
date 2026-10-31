{{-- The client panel: a signed-in user's own dashboard, projects and profile. --}}
@props(['title'])

<x-layouts.panel :title="$title" :home="route('dashboard')">
    <x-slot:nav>
        <div class="space-y-1">
            <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Account</p>
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">Dashboard</x-nav-link>
            <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" icon="folder">Projects</x-nav-link>
            <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">Profile</x-nav-link>
        </div>

        <div class="space-y-1">
            <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Xelenic</p>
            @can('access-admin')
                <x-nav-link :href="route('admin.dashboard')" icon="shield">Admin panel</x-nav-link>
            @endcan
            <x-nav-link :href="route('home')" icon="globe">Website</x-nav-link>
        </div>
    </x-slot:nav>

    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.panel>
