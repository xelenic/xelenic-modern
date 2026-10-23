{{-- An on/off switch that always submits 1 or 0. --}}
@props(['name', 'label', 'description' => null, 'checked' => false])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '-', $key));
    $on = (bool) old($key, $checked);
@endphp

<div class="flex items-start justify-between gap-4">
    <div>
        <label for="{{ $id }}" class="text-sm font-medium text-slate-900">{{ $label }}</label>
        @if ($description)
            <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
        @endif
    </div>
    <label for="{{ $id }}" class="relative mt-0.5 inline-flex shrink-0 cursor-pointer">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1" @checked($on) {{ $attributes->except('id')->class('peer sr-only') }}>
        <span aria-hidden="true" class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-blue-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-blue-600"></span>
        <span aria-hidden="true" class="pointer-events-none absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
    </label>
</div>
