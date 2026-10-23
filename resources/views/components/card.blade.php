@props(['title' => null, 'description' => null])

<section {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-6 shadow-sm') }}>
    @if ($title)
        <header class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h2 class="font-display text-base font-semibold text-slate-900">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}
</section>
