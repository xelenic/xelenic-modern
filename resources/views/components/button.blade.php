{{-- A button, or a link styled as one when given an href. --}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        'px-4 py-2.5 text-sm' => $size === 'md',
        'px-3 py-1.5 text-xs' => $size === 'sm',
        'bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700 focus-visible:outline-blue-600' => $variant === 'primary',
        'border border-slate-300 bg-white text-slate-700 shadow-xs hover:bg-slate-50 focus-visible:outline-slate-400' => $variant === 'secondary',
        'bg-red-50 text-red-700 ring-1 ring-red-200 ring-inset hover:bg-red-100 focus-visible:outline-red-600' => $variant === 'danger',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>{{ $slot }}</button>
@endif
