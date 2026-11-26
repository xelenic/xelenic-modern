{{-- A game's picture: its cover photo, else its icon on its own colour with
     a little confetti. --}}
@if ($game->thumbnailUrl())
  <img class="mk-art-photo" src="{{ $game->thumbnailUrl() }}" alt="" loading="lazy" />
@else
  <span class="mk-art" aria-hidden="true">
    <span class="mk-art-dot mk-art-dot-1"></span>
    <span class="mk-art-dot mk-art-dot-2"></span>
    <span class="mk-art-dot mk-art-dot-3"></span>
    <span class="mk-art-ring"></span>
    @include('marketplace.games.partials.icon', ['game' => $game, 'class' => 'mk-art-icon'])
  </span>
@endif
