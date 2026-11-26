{{-- An animation template in the grid. animations.js plays the screen in
     data-screen while the card is on screen (and replays it on hover); the
     icon shows until then. The data-* attributes are for search and filters. --}}
<li class="mk-card mk-anim-card" data-item data-tint
  data-name="{{ Str::lower($template['name'].' '.$template['blurb'].' '.App\Support\AnimationTemplates::GROUPS[$template['group']]) }}"
  data-category="{{ $template['group'] }}"
  data-order="{{ $order ?? 0 }}">
  <a href="{{ route('marketplace.animations.show', $template['slug']) }}" class="mk-card-link">
    <span class="mk-anim-cover">
      <span class="mk-anim-screen" data-screen="{{ $template['name'] }}">
        <span class="mk-anim-fallback" aria-hidden="true"><x-site.icon :name="$template['icon']" /></span>
      </span>
    </span>
    <span class="mk-card-body">
      <span class="mk-card-kicker">{{ App\Support\AnimationTemplates::GROUPS[$template['group']] }}</span>
      <span class="mk-card-title">{{ $template['name'] }}</span>
      <span class="mk-card-desc">{{ $template['blurb'] }}</span>
    </span>
  </a>
</li>
