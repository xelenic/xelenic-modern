{{-- The page frame shared by the client panel (layouts.app) and the admin
     panel (layouts.admin): sidebar, top bar with the user's menu, and flash
     message. Each passes its own sidebar links as the `nav` slot. --}}
@props(['title', 'label' => null, 'home'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Xelenic{{ $label ? " {$label}" : '' }}</title>
    <link rel="icon" href="{{ asset('moderning_xelenic/assets/img/favicon.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-600 antialiased">
    {{-- Backdrop behind the sidebar while it's open on small screens --}}
    <div data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-slate-900/40 sidebar-open:block lg:sidebar-open:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 sidebar-open:translate-x-0 lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center px-6">
            <a href="{{ $home }}" class="flex items-center gap-3">
                <img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" class="size-8">
                <span class="font-display text-lg font-semibold text-slate-900">Xelenic</span>
                @if ($label)
                    <span class="rounded-md bg-blue-50 px-1.5 py-0.5 text-[11px] font-semibold tracking-wide text-blue-700 uppercase ring-1 ring-blue-100 ring-inset">{{ $label }}</span>
                @endif
            </a>
        </div>

        <nav class="flex-1 space-y-8 overflow-y-auto px-4 py-6" aria-label="{{ $label ?? 'Panel' }}">
            {{ $nav }}
        </nav>
    </aside>

    <div class="relative lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-10">
            <button type="button" data-sidebar-toggle class="-ml-2 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden" aria-label="Toggle menu">
                <x-icon name="menu" />
            </button>
            <h1 class="truncate font-display text-lg font-semibold text-slate-900">{{ $title }}</h1>
            <div class="ml-auto flex shrink-0 items-center gap-3">
                @isset($actions)
                    {{ $actions }}
                @endisset
                <x-user-menu :admin-link="! $label" />
            </div>
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
