{{-- Animation templates (AnimationMarketplaceController@index): Kiosk
     Studio's ready-made animated screens, each playing live. --}}
<x-layouts.site title="Animation Templates"
  description="Ready-made animated screens for kiosks: welcome loops, how-to-play screens for every game, winner screens and advertising screens. Watch each one play, then add it in Kiosk Studio."
  :styles="['css/marketplace.css']" :scripts="['js/marketplace.js', 'js/animations.js']" kiosk-player>
  <main class="mk">
    {{-- Hero, with the Welcome screen playing in a kiosk --}}
    <section class="mk-hero">
      <div class="container mk-hero-grid">
        <div>
          <span class="eyebrow">Explore Kiosk Studio</span>
          <h1>Animation <span class="text-accent">Templates</span></h1>
          <p class="mk-lead">Ready-made animated screens for your kiosk: a welcome loop that draws people in, a how-to-play screen for every game, a winner screen that celebrates, and ads for advertising kiosks that take turns on their own. Watch each one play right here, then add it to your kiosk app in Kiosk Studio.</p>
          <ul class="mk-stats">
            <li><strong>{{ $templates->count() }}</strong> animated screens</li>
            @foreach ($groups as $group)
              <li><strong>{{ $group['count'] }}</strong> {{ Str::lower($group['label']) }}</li>
            @endforeach
          </ul>
          <div class="mk-hero-actions">
            <a href="#templates" class="btn btn-primary btn-lg">Explore the screens</a>
            <a href="{{ route('marketplace.games.index') }}" class="btn btn-ghost btn-lg">Browse the games</a>
          </div>
        </div>

        <div class="mk-anim-hero" aria-hidden="true">
          <div class="mk-device mk-device-sm">
            <div class="mk-device-screen" data-tint>
              <span class="mk-anim-stage" data-screen="Welcome" data-live>
                <span class="mk-anim-fallback"><x-site.icon name="sparkles" /></span>
              </span>
            </div>
            <span class="mk-device-neck"></span>
            <span class="mk-device-base"></span>
          </div>
          <span class="mk-hero-float mk-hero-float-1" style="--game: #4b1d8f"><x-site.icon name="quiz" /></span>
          <span class="mk-hero-float mk-hero-float-2" style="--game: #8a5a00"><x-site.icon name="wheel" /></span>
          <span class="mk-hero-float mk-hero-float-3" style="--game: #a4133c"><x-site.icon name="trophy" /></span>
          <span class="mk-hero-float mk-hero-float-4" style="--game: #0b5f86"><x-site.icon name="megaphone" /></span>
        </div>
      </div>
    </section>

    {{-- The templates --}}
    <section class="mk-browse" id="templates">
      <div class="container">
        <div class="mk-toolbar" data-toolbar>
          <label class="mk-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <span class="sr-only">Search animation templates</span>
            <input type="search" placeholder="Search screens" autocomplete="off" data-search />
          </label>
          <div class="mk-filters" role="group" aria-label="Show">
            <button type="button" class="mk-filter is-active" aria-pressed="true" data-filter="all">All <span class="mk-filter-count">{{ $templates->count() }}</span></button>
            @foreach ($groups as $key => $group)
              <button type="button" class="mk-filter" aria-pressed="false" data-filter="{{ $key }}">{{ $group['label'] }} <span class="mk-filter-count">{{ $group['count'] }}</span></button>
            @endforeach
          </div>
        </div>

        <ul class="mk-grid" data-grid>
          @foreach ($templates as $template)
            @include('marketplace.animations.partials.card', ['template' => $template, 'order' => $loop->index])
          @endforeach
        </ul>
        <p class="mk-empty" data-no-results hidden>No screens match. Try another word, or show them all.</p>
      </div>
    </section>

    <section class="mk-band-wrap">
      <div class="container">
        <aside class="mk-band">
          <div>
            <h2>Pair them with a game</h2>
            <p>Every how-to-play screen opens one of Kiosk Studio’s games. Play the games in your browser, then build the whole kiosk: welcome, how to play, the game, and a winner screen.</p>
          </div>
          <div class="mk-band-actions">
            <a href="{{ route('marketplace.games.index') }}" class="btn btn-primary btn-lg">Kiosk game templates</a>
            <a href="{{ route('projects.create.type', 'kiosk-app') }}" class="btn btn-ghost btn-lg">Start a kiosk project</a>
          </div>
        </aside>
      </div>
    </section>
  </main>
</x-layouts.site>
