{{-- Five stars, filled to a rating out of 5 (rounded to the nearest star). --}}
@props(['rating' => 0, 'size' => 'size-4'])

@php
    $filled = (int) round((float) $rating);
@endphp

<span {{ $attributes->class('inline-flex shrink-0 items-center gap-0.5') }} role="img" aria-label="{{ number_format((float) $rating, 1) }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)
        <svg @class([$size, 'text-amber-400' => $i <= $filled, 'text-slate-200' => $i > $filled]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 1.6l2.55 5.2 5.75.84-4.16 4.05.98 5.72L10 14.7l-5.12 2.7.98-5.72L1.7 7.64l5.75-.84z" />
        </svg>
    @endfor
</span>
