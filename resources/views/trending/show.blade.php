{{-- A trending kiosk app concept (TrendingKioskAppController@show): the
     campaign playing in a kiosk, the idea, how it flows, where it works, and
     the Kiosk Studio games and screens it's built from. --}}
@php
    $recipe = $concept['recipe'];
    $start = route('projects.create.type', 'kiosk-app');
@endphp
<x-layouts.site :title="$concept['name'].' · Trending Kiosk Apps'" :description="$concept['summary'].' '.$concept['tagline']"
  :styles="['css/marketplace.css']" :scripts="['js/marketplace.js', 'js/animations.js']" kiosk-player>
  <main class="mk mk-page tk" data-tint>
    <div class="container">
      <a href="{{ route('trending.index') }}" class="mk-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        All trending kiosk apps
      </a>

      <div class="mk-detail">
        <div class="mk-detail-info">
          <div class="mk-title-row">
            <span class="mk-icon-tile" aria-hidden="true"><x-site.icon :name="$concept['icon']" /></span>
            <div>
              <span class="eyebrow">{{ $category }} · Concept</span>
              <h1>{{ $concept['name'] }}</h1>
            </div>
          </div>

          <p class="mk-lead">{{ $concept['tagline'] }}</p>

          <ul class="mk-tags">
            @if ($concept['trending'])
              <li class="tk-tag-hot"><x-site.icon name="flame" /> Trending</li>
            @endif
            @foreach ($concept['settings'] as $setting)
              <li>{{ $setting }}</li>
            @endforeach
            <li>{{ $category }}</li>
            <li>{{ $recipe['form'] ? 'Collects leads' : 'Prizes at the desk' }}</li>
          </ul>

          <div class="mk-detail-actions">
            <button type="button" class="btn btn-primary btn-lg" data-demo-start>
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>
              Play the concept
            </button>
            <a href="{{ $start }}" class="btn btn-ghost btn-lg">Build this campaign</a>
          </div>

          <dl class="mk-facts">
            <div><dt>Kiosk</dt><dd>{{ $concept['machine']->label() }}</dd></div>
            <div><dt>{{ Str::plural('Game', $gameParts->count()) }}</dt><dd>{{ $gameParts->pluck('name')->implode(', ') }}</dd></div>
            <div><dt>Collects</dt><dd>{{ $recipe['form'] ? 'Name and mobile number' : 'Nothing: prizes at the desk' }}</dd></div>
          </dl>
        </div>

        {{-- The kiosk: the campaign's first screen plays until it's started. --}}
        <div class="mk-device-wrap">
          <div class="mk-device" data-demo data-demo-src="{{ route('trending.demo', $concept['slug']) }}">
            <div class="mk-device-screen">
              <div class="mk-poster">
                <span class="mk-anim-stage" data-screen="{{ $recipe['attract'][0] }}" data-tagline="{{ $recipe['tagline'] ?? '' }}" data-live>
                  <span class="mk-anim-fallback" aria-hidden="true"><x-site.icon :name="$concept['icon']" /></span>
                </span>
                <button type="button" class="mk-poster-play" data-demo-start>
                  <span class="mk-poster-btn" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg></span>
                  <span>Tap to play the campaign</span>
                </button>
              </div>
            </div>
            <span class="mk-device-neck" aria-hidden="true"></span>
            <span class="mk-device-base" aria-hidden="true"></span>
          </div>
          <p class="mk-device-note">
            The whole campaign, as it plays on the kiosk. Leave it alone and it returns to the start.
            <a href="{{ route('trending.demo', $concept['slug']) }}" target="_blank" rel="noopener">Open full screen</a>
          </p>
        </div>
      </div>

      {{-- The idea --}}
      <section class="mk-section mk-two" aria-label="The idea">
        <div>
          <h2>The idea</h2>
          <p class="mk-lead">{{ $concept['idea'] }}</p>
        </div>
        <div>
          <h2>Great for</h2>
          <ul class="tk-goals">
            @foreach ($concept['goals'] as $goal)
              <li>{{ $goal }}</li>
            @endforeach
          </ul>
        </div>
      </section>

      {{-- How it flows --}}
      <section class="mk-section" aria-labelledby="flow-title">
        <h2 id="flow-title">How the campaign flows</h2>
        <ol class="tk-flow">
          @foreach ($flow as $step)
            <li>
              <span class="tk-flow-icon" aria-hidden="true"><x-site.icon :name="$step['icon']" /></span>
              <strong>{{ $step['title'] }}</strong>
              <span>{{ $step['text'] }}</span>
            </li>
          @endforeach
        </ol>
      </section>

      {{-- Where --}}
      <section class="mk-section mk-two" aria-label="Where it works">
        <div>
          <h2>Where it works</h2>
          <ul class="mk-tags tk-places">
            @foreach ($concept['places'] as $place)
              <li>{{ $place }}</li>
            @endforeach
          </ul>
          <p class="mk-note">{{ in_array('Outdoor', $concept['settings'], true) ? 'Indoors or out. Outdoors, put the kiosk under cover and choose a bright screen, so it’s easy to see in daylight.' : 'Made for indoor spaces where people pass by or wait.' }}</p>
        </div>
        <div>
          <h2>Recommended kiosk</h2>
          <ul class="mk-machine-row">
            <li>
              <img src="{{ $concept['machine']->image() }}" alt="" width="120" height="120" loading="lazy" />
              <span>
                <strong>{{ $concept['machine']->label() }}</strong>
                <span>{{ $concept['machine']->screen()['size'] }}″ {{ $concept['machine']->screen()['orientation'] }} screen</span>
              </span>
            </li>
          </ul>
        </div>
      </section>

      {{-- What it's made of --}}
      <section class="mk-section" aria-labelledby="parts-title">
        <div class="mk-section-head">
          <h2 id="parts-title">Built from Kiosk Studio templates</h2>
          <a href="{{ route('studio') }}">About Kiosk Studio</a>
        </div>
        <p class="mk-note mk-note-top">This concept is made entirely from ready-made templates. Open any of them to see it on its own.</p>
        <ul class="tk-parts">
          @foreach ($gameParts as $game)
            <li>
              <a href="{{ route('marketplace.games.show', $game) }}" style="--game: {{ $game->cssColor() }}">
                <span class="mk-icon-tile" aria-hidden="true">@include('marketplace.games.partials.icon', ['game' => $game])</span>
                <span><strong>{{ $game->name }}</strong><span>Game</span></span>
              </a>
            </li>
          @endforeach
          @foreach ($screenParts as $screen)
            <li>
              <a href="{{ route('marketplace.animations.show', $screen['slug']) }}">
                <span class="mk-icon-tile tk-screen-tile" aria-hidden="true"><x-site.icon :name="$screen['icon']" /></span>
                <span><strong>{{ $screen['name'] }}</strong><span>{{ App\Support\AnimationTemplates::GROUPS[$screen['group']] }} screen</span></span>
              </a>
            </li>
          @endforeach
        </ul>
        <p class="mk-note">A concept to show what’s possible. In Kiosk Studio you change the words, questions, prizes, colours and logo to make it your own.</p>
      </section>

      {{-- More --}}
      <section class="mk-section" aria-labelledby="more-title">
        <div class="mk-section-head">
          <h2 id="more-title">More trending kiosk apps</h2>
          <a href="{{ route('trending.index') }}">See all</a>
        </div>
        <ul class="mk-grid tk-grid tk-grid-3">
          @foreach ($others as $other)
            @include('trending.partials.card', ['concept' => $other, 'order' => $loop->index])
          @endforeach
        </ul>
      </section>

      <section class="mk-section">
        <aside class="mk-band">
          <div>
            <h2>Want this campaign for your brand?</h2>
            <p>We’ll make it yours: your logo and colours, your prizes and questions, on the kiosk that suits your space.</p>
          </div>
          <div class="mk-band-actions">
            <a href="{{ route('home') }}#contact" class="btn btn-primary btn-lg">Talk to our team</a>
            <a href="{{ $start }}" class="btn btn-ghost btn-lg">Start a kiosk project</a>
          </div>
        </aside>
      </section>
    </div>
  </main>
</x-layouts.site>
