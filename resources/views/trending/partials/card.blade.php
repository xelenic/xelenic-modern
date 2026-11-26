{{-- A concept in the grid. Its first attract screen plays live in a mini
     kiosk (animations.js); the data-* attributes are for search and the
     filters (marketplace.js). --}}
<li class="mk-card mk-anim-card tk-card" data-item data-tint
  data-name="{{ Str::lower($concept['name'].' '.$concept['summary'].' '.implode(' ', $concept['places']).' '.implode(' ', $concept['settings'])) }}"
  data-category="{{ $concept['category'] }}"
  data-order="{{ $order ?? 0 }}">
  <a href="{{ route('trending.show', $concept['slug']) }}" class="mk-card-link">
    <span class="mk-anim-cover tk-cover">
      <span class="tk-cover-side">
        @if ($concept['trending'])
          <span class="tk-hot"><x-site.icon name="flame" /> Trending</span>
        @endif
        <span class="tk-cover-games" aria-label="Games: {{ collect($concept['recipe']['games'])->map(fn ($kind) => $games->get($kind)?->name ?? $kind)->implode(', ') }}">
          @foreach ($concept['recipe']['games'] as $kind)
            <span style="--game: {{ $games->get($kind)?->cssColor() ?? '#3b3f5c' }}" aria-hidden="true"><x-site.icon :name="$games->get($kind)?->siteIcon() ?? $kind" /></span>
          @endforeach
        </span>
        <span class="tk-cover-setting">{{ implode(' · ', $concept['settings']) }}</span>
      </span>
      <span class="mk-anim-screen" data-screen="{{ $concept['recipe']['attract'][0] }}" data-tagline="{{ $concept['recipe']['tagline'] ?? '' }}">
        <span class="mk-anim-fallback" aria-hidden="true"><x-site.icon :name="$concept['icon']" /></span>
      </span>
    </span>
    <span class="mk-card-body">
      <span class="mk-card-kicker">{{ App\Support\KioskConcepts::CATEGORIES[$concept['category']] }}</span>
      <span class="mk-card-title">{{ $concept['name'] }}</span>
      <span class="mk-card-desc">{{ $concept['summary'] }}</span>
      <span class="mk-card-meta">
        <span class="tk-play">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>
          Play the concept
        </span>
        <span class="mk-control">{{ $concept['places'][0] }}</span>
      </span>
    </span>
  </a>
</li>
