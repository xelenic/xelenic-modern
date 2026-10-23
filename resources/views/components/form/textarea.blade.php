@props(['name', 'label', 'value' => null, 'rows' => 5, 'hint' => null])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '-', $key));
    $error = $errors->first($key);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-2 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-2',
            'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20' => ! $error,
            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $error,
        ]) }}
    >{{ old($key, $value) }}</textarea>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 text-xs text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
