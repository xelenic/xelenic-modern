{{-- A screenshot of Kiosk Studio in a browser window. Clicking it shows it
     full size (kiosk-studio.js). $shot is a file in img/studio. --}}
@php($src = asset("moderning_xelenic/assets/img/studio/{$shot}.webp"))
<figure class="ks-shot {{ $class ?? '' }}">
  <div class="ks-window">
    <div class="ks-window-bar" aria-hidden="true">
      <span></span><span></span><span></span>
      <em>Kiosk Studio · Xelenic</em>
    </div>
    <button type="button" class="ks-zoom" data-zoom="{{ $src }}" data-alt="{{ $alt }}" aria-label="View larger: {{ $alt }}">
      <img src="{{ $src }}" alt="{{ $alt }}" width="2160" height="1350" @if (empty($eager)) loading="lazy" @else fetchpriority="high" @endif decoding="async" />
    </button>
  </div>
  @isset($caption)
    <figcaption>{{ $caption }}</figcaption>
  @endisset
</figure>
