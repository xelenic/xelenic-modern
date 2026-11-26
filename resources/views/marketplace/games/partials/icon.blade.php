{{-- A game's icon: the picture from its package if it has one, else its
     SVG icon (never Kiosk Studio's emoji). --}}
@if ($game->iconImage())<img src="{{ $game->iconImage() }}" alt="" class="{{ $class ?? '' }}" />@else<x-site.icon :name="$game->siteIcon()" class="{{ $class ?? '' }}" />@endif
