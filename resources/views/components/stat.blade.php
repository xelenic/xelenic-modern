@props(['label', 'value', 'icon'])

<div {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm') }}>
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
            <x-icon :name="$icon" class="size-4" />
        </span>
    </div>
    <p class="mt-3 font-display text-3xl font-semibold text-slate-900">{{ $value }}</p>
</div>
