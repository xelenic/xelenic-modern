{{-- A labelled select. `options` is a list (each option is its own value) or
     value => label pairs; pass :placeholder="null" for no empty choice. --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => 'Choose one', 'hint' => null])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '-', $key));
    $error = $errors->first($key);
    $selected = (string) old($key, $value);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-2 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none transition focus:ring-2',
            'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20' => ! $error,
            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $error,
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            @php($optionValue = array_is_list($options) ? $optionLabel : $optionValue)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 text-xs text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
