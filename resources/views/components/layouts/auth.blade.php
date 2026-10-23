@props(['title', 'heading', 'subheading' => null])

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
<body class="h-full bg-white font-sans text-slate-600 antialiased">
    <div class="grid min-h-full lg:grid-cols-2">
        {{-- Brand panel (large screens only) --}}
        <aside class="relative hidden overflow-hidden border-r border-slate-200 bg-linear-to-br from-blue-50 via-white to-sky-50 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 -left-24 size-[28rem] rounded-full bg-blue-200/50 blur-3xl"></div>
                <div class="absolute -right-24 bottom-0 size-[24rem] rounded-full bg-sky-200/60 blur-3xl"></div>
                <div class="absolute inset-0" style="background-image: linear-gradient(rgba(15,23,42,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.04) 1px, transparent 1px); background-size: 48px 48px;"></div>
            </div>

            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" class="size-10">
                <span class="font-display text-xl font-semibold text-slate-900">Xelenic</span>
            </a>

            <div class="relative">
                <img src="{{ asset('moderning_xelenic/assets/img/robot-still.webp') }}" alt="" width="615" height="640" class="mx-auto w-64 drop-shadow-[0_30px_40px_rgba(37,99,235,0.25)] xl:w-72">
                <h2 class="mt-10 font-display text-3xl leading-tight font-semibold text-slate-900">
                    Grow your business with <span class="text-blue-600">Xelenic</span>
                </h2>
                <p class="mt-3 max-w-md text-slate-600">
                    AI middleware, connected business systems and the tools to run them, all in one place.
                </p>
            </div>

            <p class="relative text-sm text-slate-500">&copy; {{ now()->year }} Xelenic. All rights reserved.</p>
        </aside>

        {{-- Form --}}
        <main class="flex flex-col justify-center px-4 py-12 sm:px-10 lg:px-20">
            <div class="mx-auto w-full max-w-sm">
                <a href="{{ route('home') }}" class="mb-10 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" class="size-9">
                    <span class="font-display text-lg font-semibold text-slate-900">Xelenic</span>
                </a>

                <h1 class="font-display text-2xl font-semibold text-slate-900">{{ $heading }}</h1>
                @if ($subheading)
                    <p class="mt-2 text-sm text-slate-500">{{ $subheading }}</p>
                @endif

                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
