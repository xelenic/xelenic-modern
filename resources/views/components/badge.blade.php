{{-- A pill. Pass a role name to label and color it by role, or a color and a slot. --}}
@props(['role' => null, 'color' => null])

@php
    $color ??= match ($role) {
        'admin' => 'blue',
        'manager' => 'violet',
        'user' => 'slate',
        default => 'emerald',
    };

    $colors = match ($color) {
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {$colors}") }}>
    {{ $slot->isEmpty() ? ucfirst($role) : $slot }}
</span>
