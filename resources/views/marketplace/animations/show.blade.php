{{-- An animation template's page (AnimationMarketplaceController@show): the
     screen playing large in a kiosk, its animation timeline and contents
     (worked out from the screen itself by animations.js), how it links, and
     the game it goes with. --}}
<x-layouts.site :title="$template['name'].' · Animation Templates'"
  :description="$template['description']"
  :styles="['css/marketplace.css']" :scripts="['js/animations.js']" kiosk-player>
  <main class="mk mk-page" data-tint>
    <div class="container">
      <a href="{{ route('marketplace.animations.index') }}" class="mk-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        All animation templates
      </a>

      <div class="mk-detail">
        <div class="mk-detail-info">
          <div class="mk-title-row">
            <span class="mk-icon-tile" aria-hidden="true"><x-site.icon :name="$template['icon']" /></span>
            <div>
              <span class="eyebrow">{{ $group }} screen</span>
              <h1>{{ $template['name'] }}</h1>
            </div>
          </div>

          <p class="mk-lead">{{ $template['description'] }}</p>

          <ul class="mk-tags" data-chips>
            <li>Animated</li>
            <li>{{ $group }}</li>
          </ul>

          <div class="mk-detail-actions">
            <button type="button" class="btn btn-primary btn-lg" data-replay>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
              Replay
            </button>
            <a href="{{ route('projects.create.type', 'kiosk-app') }}" class="btn btn-ghost btn-lg">Use it in Kiosk Studio</a>
          </div>

          <dl class="mk-facts" data-facts>
            <div><dt>Made by</dt><dd>Kiosk Studio</dd></div>
            <div><dt>Section</dt><dd>{{ $group }}</dd></div>
            @if ($game)
              <div><dt>Opens</dt><dd><a href="{{ route('marketplace.games.show', $game) }}">{{ $game->name }} game</a></dd></div>
            @endif
          </dl>
        </div>

        <div class="mk-device-wrap">
          <div class="mk-device">
            <div class="mk-device-screen">
              <span class="mk-anim-stage" data-screen="{{ $template['name'] }}" data-live data-main>
                <span class="mk-anim-fallback" aria-hidden="true"><x-site.icon :name="$template['icon']" /></span>
              </span>
            </div>
            <span class="mk-device-neck" aria-hidden="true"></span>
            <span class="mk-device-base" aria-hidden="true"></span>
          </div>
          <p class="mk-device-note">The screen as it plays on a portrait kiosk. Your logo, words and colours go in its place.</p>
        </div>
      </div>

      {{-- Filled in from the screen by animations.js --}}
      <section class="mk-section" aria-labelledby="timeline-title" data-timeline-section hidden>
        <div class="mk-section-head">
          <h2 id="timeline-title">Animation timeline</h2>
          <button type="button" class="mk-link-btn" data-replay>Replay</button>
        </div>
        <p class="mk-note mk-note-top">Each item’s entrance, in order, and the loop it keeps playing after.</p>
        <div class="mk-timeline" data-timeline></div>
      </section>

      <section class="mk-section mk-two" aria-label="About this screen">
        <div>
          <h2>How it links</h2>
          <p class="mk-lead">{{ $template['links'] }}</p>
        </div>
        <div data-counts-section hidden>
          <h2>What’s on it</h2>
          <ul class="mk-counts" data-counts></ul>
        </div>
      </section>

      {{-- The game it goes with --}}
      @if ($game)
        <section class="mk-section" aria-labelledby="game-title">
          <h2 id="game-title">Goes with this game</h2>
          <a href="{{ route('marketplace.games.show', $game) }}" class="mk-pair" style="--game: {{ $game->cssColor() }}">
            <span class="mk-icon-tile" aria-hidden="true">@include('marketplace.games.partials.icon', ['game' => $game])</span>
            <span>
              <strong>{{ $game->name }}</strong>
              <span>{{ $game->description }}. Play the demo in your browser.</span>
            </span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
        </section>
      @elseif ($games->isNotEmpty())
        <section class="mk-section" aria-labelledby="games-title">
          <div class="mk-section-head">
            <h2 id="games-title">Works with every game</h2>
            <a href="{{ route('marketplace.games.index') }}">All games</a>
          </div>
          <ul class="mk-game-row">
            @foreach ($games as $item)
              <li>
                <a href="{{ route('marketplace.games.show', $item) }}" style="--game: {{ $item->cssColor() }}" title="{{ $item->name }}">
                  <span class="mk-icon-tile" aria-hidden="true">@include('marketplace.games.partials.icon', ['game' => $item])</span>
                  <span>{{ $item->name }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      {{-- The kiosks an ad is made for --}}
      @if ($machines)
        <section class="mk-section" aria-labelledby="machines-title">
          <div class="mk-section-head">
            <h2 id="machines-title">Made for advertising kiosks</h2>
            <a href="{{ route('studio') }}">About Kiosk Studio</a>
          </div>
          <ul class="mk-machine-row">
            @foreach ($machines as $machine)
              <li>
                <img src="{{ $machine->image() }}" alt="" width="120" height="120" loading="lazy" />
                <span>
                  <strong>{{ $machine->label() }}</strong>
                  <span>{{ $machine->screen()['size'] }}″ {{ $machine->screen()['orientation'] }} screen</span>
                </span>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      <section class="mk-section" aria-labelledby="more-title">
        <div class="mk-section-head">
          <h2 id="more-title">More animation templates</h2>
          <a href="{{ route('marketplace.animations.index') }}">See all</a>
        </div>
        <ul class="mk-grid mk-grid-4">
          @foreach ($others as $other)
            @include('marketplace.animations.partials.card', ['template' => $other, 'order' => $loop->index])
          @endforeach
        </ul>
      </section>
    </div>
  </main>
</x-layouts.site>
