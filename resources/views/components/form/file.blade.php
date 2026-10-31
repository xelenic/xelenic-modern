{{-- A labelled file input with its validation error. The form needs
     enctype="multipart/form-data". --}}
@props(['name', 'label', 'accept' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="file"
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'mt-2 block w-full cursor-pointer rounded-lg border bg-white text-sm text-slate-600 shadow-xs outline-none transition focus:ring-2 file:mr-4 file:cursor-pointer file:border-0 file:border-r file:border-slate-200 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100',
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
