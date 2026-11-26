{{-- The game templates marketplace (GameMarketplaceController@index): every
     game Kiosk Studio offers, to search, filter, and open for a live demo. --}}
<x-layouts.site title="Kiosk Game Templates"
  description="Branded games for kiosk screens: spin wheels, quizzes, runners, scratch cards and more. Play any demo in your browser, then make it yours in Kiosk Studio."
  :styles="['css/marketplace.css']" :scripts="['js/marketplace.js']">
  <main class="mk">
    {{-- Hero --}}
    <section class="mk-hero">
      <div class="container mk-hero-grid">
        <div>
          <span class="eyebrow">Kiosk Studio Marketplace</span>
          <h1>Kiosk Game <span class="text-accent">Templates</span></h1>
          <p class="mk-lead">Branded games that pull people to your kiosk and keep them playing. Try any of them right here, then add it to your kiosk app in Kiosk Studio and make it yours.</p>
          <ul class="mk-stats">
            <li><strong>{{ $games->count() }}</strong> {{ Str::plural('game', $games->count()) }}</li>
            @if ($demos)
              <li><strong>{{ $demos }}</strong> playable {{ Str::plural('demo', $demos) }}</li>
            @endif
            @if ($reviews)
              <li><strong>{{ $reviews }}</strong> {{ Str::plural('review', $reviews) }}</li>
            @endif
          </ul>
          <div class="mk-hero-actions">
            <a href="#games" class="btn btn-primary btn-lg">Browse the games</a>
            <a href="{{ route('projects.create.type', 'kiosk-app') }}" class="btn btn-ghost btn-lg">Open Kiosk Studio</a>
          </div>
        </div>

        {{-- A kiosk with a few of the games floating round it --}}
        <div class="mk-hero-art" aria-hidden="true">
          <div class="mk-hero-kiosk">
            <div class="mk-hero-screen">
              @foreach ($games->take(6) as $game)
                <span class="mk-hero-tile" style="--game: {{ $game->cssColor() }}; --i: {{ $loop->index }}">
                  @include('marketplace.games.partials.icon', ['game' => $game])
                </span>
              @endforeach
            </div>
            <div class="mk-hero-neck"></div>
            <div class="mk-hero-base"></div>
          </div>
          @foreach ($games->slice(6, 4)->values() as $game)
            <span class="mk-hero-float mk-hero-float-{{ $loop->iteration }}" style="--game: {{ $game->cssColor() }}">
              @include('marketplace.games.partials.icon', ['game' => $game])
            </span>
          @endforeach
        </div>
      </div>
    </section>

    {{-- The games --}}
    <section class="mk-browse" id="games">
      <div class="container">
        <div class="mk-toolbar" data-toolbar>
          <label class="mk-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <span class="sr-only">Search games</span>
            <input type="search" placeholder="Search games" autocomplete="off" data-search />
          </label>
          <div class="mk-filters" role="group" aria-label="Show">
            <button type="button" class="mk-filter is-active" aria-pressed="true" data-filter="all">All games</button>
            <button type="button" class="mk-filter" aria-pressed="false" data-filter="score">Score games</button>
            <button type="button" class="mk-filter" aria-pressed="false" data-filter="prize">Prize games</button>
          </div>
          <label class="mk-sort">
            <span>Sort</span>
            <select data-sort>
              <option value="featured">Featured</option>
              <option value="rating">Top rated</option>
              <option value="name">A to Z</option>
            </select>
          </label>
        </div>

        @if ($games->isEmpty())
          <p class="mk-empty">New games are on their way. Check back soon.</p>
        @else
          <ul class="mk-grid" data-grid>
            @foreach ($games as $game)
              @include('marketplace.games.partials.card', ['game' => $game, 'order' => $loop->index])
            @endforeach
          </ul>
          <p class="mk-empty" data-no-results hidden>No games match. Try another word, or show all games.</p>
        @endif
      </div>
    </section>

    {{-- Bespoke games --}}
    <section class="mk-band-wrap">
      <div class="container">
        <aside class="mk-band">
          <div>
            <h2>Want a game made for your brand?</h2>
            <p>Our team designs custom kiosk games around your products, prizes and campaign, and sets them up in Kiosk Studio for you.</p>
          </div>
          <div class="mk-band-actions">
            <a href="{{ route('home') }}#contact" class="btn btn-primary btn-lg">Talk to our team</a>
            <a href="{{ route('projects.create.type', 'kiosk-app') }}" class="btn btn-ghost btn-lg">Start a kiosk project</a>
          </div>
        </aside>
      </div>
    </section>
  </main>
</x-layouts.site>
