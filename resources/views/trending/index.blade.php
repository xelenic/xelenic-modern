{{-- Trending kiosk apps (TrendingKioskAppController@index): concept
     campaigns for brand promotion and event crowd engagement, each
     playable, built from Kiosk Studio's templates. --}}
@php
    $start = route('projects.create.type', 'kiosk-app');
    $hero = $concepts->where('trending', true)->take(3)->values();
@endphp
<x-layouts.site title="Trending Kiosk Apps"
  description="Concept kiosk apps for brand promotions, product launches, store openings and crowd engagement at indoor and outdoor events. Play every one in your browser, then make it yours."
  :styles="['css/marketplace.css']" :scripts="['js/marketplace.js', 'js/animations.js']" kiosk-player>
  <main class="mk tk">
    {{-- Hero --}}
    <section class="mk-hero">
      <div class="container mk-hero-grid">
        <div>
          <span class="eyebrow">Trending Kiosk Apps</span>
          <h1>Kiosk campaigns that <span class="text-accent">draw a crowd</span></h1>
          <p class="mk-lead">Concept kiosk apps for promoting your company and your brand, and for engaging the crowd at indoor and outdoor events. Each one is a complete campaign: an eye-catching loop, a game, a prize and a way to collect details. Play them right here, then make one yours.</p>
          <ul class="mk-stats">
            <li><strong>{{ $concepts->count() }}</strong> campaign concepts</li>
            <li><strong>{{ $concepts->filter(fn ($c) => in_array('Outdoor', $c['settings'], true))->count() }}</strong> for outdoor events</li>
            <li><strong>{{ $categories->count() }}</strong> kinds of campaign</li>
          </ul>
          <div class="mk-hero-actions">
            <a href="#concepts" class="btn btn-primary btn-lg">See the concepts</a>
            <a href="{{ $start }}" class="btn btn-ghost btn-lg">Start your campaign</a>
          </div>
        </div>

        {{-- Three trending concepts, playing --}}
        <div class="tk-hero-stack" aria-hidden="true">
          @foreach ($hero as $item)
            <div class="tk-hero-device tk-hero-device-{{ $loop->iteration }}">
              <div class="mk-device mk-device-sm">
                <div class="mk-device-screen" data-tint>
                  <span class="mk-anim-stage" data-screen="{{ $item['recipe']['attract'][0] }}" data-tagline="{{ $item['recipe']['tagline'] ?? '' }}" data-live>
                    <span class="mk-anim-fallback"><x-site.icon :name="$item['icon']" /></span>
                  </span>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- How a campaign works --}}
    <section class="tk-how">
      <div class="container">
        <ol class="tk-how-steps">
          <li><span class="tk-how-icon" aria-hidden="true"><x-site.icon name="megaphone" /></span><strong>Attract</strong><span>Your brand and offers loop on the screen.</span></li>
          <li><span class="tk-how-icon" aria-hidden="true"><x-site.icon name="gamepad" /></span><strong>Play</strong><span>A quick game anyone can pick up.</span></li>
          <li><span class="tk-how-icon" aria-hidden="true"><x-site.icon name="trophy" /></span><strong>Win</strong><span>An instant prize, with confetti.</span></li>
          <li><span class="tk-how-icon" aria-hidden="true"><x-site.icon name="clipboard" /></span><strong>Collect</strong><span>Details to claim it: leads for you.</span></li>
        </ol>
      </div>
    </section>

    {{-- The concepts --}}
    <section class="mk-browse" id="concepts">
      <div class="container">
        <div class="mk-toolbar" data-toolbar>
          <label class="mk-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <span class="sr-only">Search concepts</span>
            <input type="search" placeholder="Search: mall, festival, restaurant…" autocomplete="off" data-search />
          </label>
          <div class="mk-filters" role="group" aria-label="Show">
            <button type="button" class="mk-filter is-active" aria-pressed="true" data-filter="all">All <span class="mk-filter-count">{{ $concepts->count() }}</span></button>
            @foreach ($categories as $key => $category)
              <button type="button" class="mk-filter" aria-pressed="false" data-filter="{{ $key }}">{{ $category['label'] }} <span class="mk-filter-count">{{ $category['count'] }}</span></button>
            @endforeach
          </div>
        </div>

        <ul class="mk-grid tk-grid" data-grid>
          @foreach ($concepts as $concept)
            @include('trending.partials.card', ['concept' => $concept, 'order' => $loop->index])
          @endforeach
        </ul>
        <p class="mk-empty" data-no-results hidden>No concepts match. Try another word, or show them all.</p>
      </div>
    </section>

    <section class="mk-band-wrap">
      <div class="container">
        <aside class="mk-band">
          <div>
            <h2>Have a campaign in mind?</h2>
            <p>Tell us about your brand, your event and your audience. We’ll shape a kiosk campaign around them, with your games, prizes and look, and set it up in Kiosk Studio.</p>
          </div>
          <div class="mk-band-actions">
            <a href="{{ route('home') }}#contact" class="btn btn-primary btn-lg">Talk to our team</a>
            <a href="{{ route('studio') }}" class="btn btn-ghost btn-lg">About Kiosk Studio</a>
          </div>
        </aside>
      </div>
    </section>
  </main>
</x-layouts.site>
