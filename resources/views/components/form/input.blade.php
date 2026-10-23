{{-- A labelled input with its validation error. Use `bag` for forms that
     validate into a named error bag (Fortify's profile and password forms). --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'bag' => 'default', 'hint' => null])

@php
    // "details[kiosk_count]" is "details.kiosk_count" to old() and $errors.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '-', $key));
    $error = $errors->getBag($bag)->first($key);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($key, $value) }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-2 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-2',
            'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20' => ! $error,
            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $error,
        ]) }}
    >
    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 text-xs text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
