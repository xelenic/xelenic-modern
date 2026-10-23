@props(['href', 'icon', 'title'])

<a href="{{ $href }}" {{ $attributes->class('group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 hover:bg-blue-50/40') }}>
    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="flex items-center gap-1 text-sm font-medium text-slate-900">
            {{ $title }}
            <x-icon name="arrow-right" class="size-3.5 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-blue-600" />
        </span>
        <span class="mt-1 block text-xs text-slate-500">{{ $slot }}</span>
    </span>
</a>
