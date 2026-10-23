@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Xelenic</title>
    <link rel="icon" href="{{ asset('moderning_xelenic/assets/img/favicon.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-600 antialiased">
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-48 left-1/3 size-[40rem] rounded-full bg-blue-100/60 blur-3xl"></div>
    </div>

    {{-- Backdrop behind the sidebar while it's open on small screens --}}
    <div data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-slate-900/40 backdrop-blur-sm sidebar-open:block lg:sidebar-open:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 sidebar-open:translate-x-0 lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" class="size-8">
                <span class="font-display text-lg font-semibold text-slate-900">Xelenic</span>
            </a>
        </div>

        <nav class="flex-1 space-y-8 overflow-y-auto px-4 py-6" aria-label="Panel">
            <div class="space-y-1">
                <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Account</p>
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">Dashboard</x-nav-link>
                <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" icon="folder">Projects</x-nav-link>
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">Profile</x-nav-link>
            </div>

            @canany(['view users', 'manage roles'])
                <div class="space-y-1">
                    <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Administration</p>
                    @can('view users')
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="users">Users</x-nav-link>
                    @endcan
                    @can('manage roles')
                        <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" icon="shield">Roles &amp; permissions</x-nav-link>
                    @endcan
                </div>
            @endcanany

            <div class="space-y-1">
                <p class="px-3 pb-2 text-xs font-medium tracking-wider text-slate-400 uppercase">Xelenic</p>
                <x-nav-link :href="route('home')" icon="globe">Website</x-nav-link>
            </div>
        </nav>

        <div class="border-t border-slate-200 p-4">
            <div class="flex items-center gap-3 px-2 py-2">
                <x-avatar :user="auth()->user()" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                    <x-icon name="logout" class="size-5 text-slate-400" />
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <div class="relative lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-10">
            <button type="button" data-sidebar-toggle class="-ml-2 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden" aria-label="Toggle menu">
                <x-icon name="menu" />
            </button>
            <h1 class="truncate font-display text-lg font-semibold text-slate-900">{{ $title }}</h1>
            @isset($actions)
                <div class="ml-auto flex shrink-0 items-center gap-3">{{ $actions }}</div>
            @endisset
        </header>

        <main class="px-4 py-8 sm:px-6 lg:px-10">
            @if (session('success'))
                <x-alert class="mb-6">{{ session('success') }}</x-alert>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
