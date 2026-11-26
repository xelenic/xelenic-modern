{{-- A 0–5 star rating, filled to the tenth (marketplace.css .mk-stars). --}}
@php($rating = max(0, min(5, (float) $rating)))
<span class="mk-stars {{ $class ?? '' }}" style="--fill: {{ round($rating / 5 * 100, 1) }}%" role="img" aria-label="{{ $rating ? 'Rated '.rtrim(rtrim(number_format($rating, 1), '0'), '.').' out of 5' : 'Not rated yet' }}"></span>
