{{-- A game in the marketplace grid. The data-* attributes are for the
     search, filters and sorting (marketplace.js). --}}
@php($guide = $game->guide())
<li class="mk-card" style="--game: {{ $game->cssColor() }}"
  data-item
  data-name="{{ Str::lower($game->name.' '.$game->description) }}"
  data-category="{{ $guide['ends'] ?? 'other' }}"
  data-rating="{{ $game->averageRating() }}"
  data-reviews="{{ $game->reviewsCount() }}"
  data-order="{{ $order ?? 0 }}">
  <a href="{{ route('marketplace.games.show', $game) }}" class="mk-card-link">
    <span class="mk-cover">
      @include('marketplace.games.partials.art', ['game' => $game])
      @if ($game->hasDemo())
        <span class="mk-chip mk-chip-demo">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>
          Live demo
        </span>
      @endif
      @if (($guide['ends'] ?? null) === 'prize')
        <span class="mk-chip mk-chip-prize">Prize game</span>
      @endif
    </span>
    <span class="mk-card-body">
      <span class="mk-card-title">{{ $game->name }}</span>
      <span class="mk-card-desc">{{ $game->description }}</span>
      <span class="mk-card-meta">
        <span class="mk-rating">
          @include('marketplace.games.partials.stars', ['rating' => $game->averageRating()])
          <span>{{ $game->reviewsCount() ? number_format($game->averageRating(), 1).' ('.$game->reviewsCount().')' : 'New' }}</span>
        </span>
        @if ($guide)
          <span class="mk-control">{{ $guide['controls'] }} to play</span>
        @endif
      </span>
    </span>
  </a>
</li>
