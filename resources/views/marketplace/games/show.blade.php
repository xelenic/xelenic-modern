{{-- A game's store page (GameMarketplaceController@show): a live demo in a
     kiosk, how it plays, its pictures and write-up, and reviews. --}}
@php
    $guide = $game->guide();
    $studio = route('projects.create.type', 'kiosk-app');
    $rating = $game->averageRating();
    $count = $game->reviewsCount();
@endphp
<x-layouts.site :title="$game->name.' · Kiosk Game Templates'"
  :description="$game->description ?: $game->name.', a kiosk game template from Kiosk Studio by Xelenic.'"
  :styles="['css/marketplace.css']" :scripts="['js/marketplace.js']">
  <main class="mk mk-page" style="--game: {{ $game->cssColor() }}">
    <div class="container">
      <a href="{{ route('marketplace.games.index') }}" class="mk-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        All games
      </a>

      @if (session('status'))
        <p class="mk-flash" role="status">{{ session('status') }}</p>
      @endif

      {{-- About the game, and the game itself --}}
      <div class="mk-detail">
        <div class="mk-detail-info">
          <div class="mk-title-row">
            <span class="mk-icon-tile" aria-hidden="true">
              @include('marketplace.games.partials.icon', ['game' => $game])
            </span>
            <div>
              <span class="eyebrow">{{ $game->isBuiltin() ? 'Kiosk Studio game' : 'Game template' }}</span>
              <h1>{{ $game->name }}</h1>
            </div>
          </div>

          @if ($game->description)
            <p class="mk-lead">{{ rtrim($game->description, '.!') }}{{ Str::endsWith($game->description, '!') ? '!' : '.' }}</p>
          @endif

          <a href="#reviews" class="mk-rating mk-rating-lg">
            @include('marketplace.games.partials.stars', ['rating' => $rating])
            @if ($count)
              <span><strong>{{ number_format($rating, 1) }}</strong> · {{ $count }} {{ Str::plural('review', $count) }}</span>
            @else
              <span>No reviews yet · be the first</span>
            @endif
          </a>

          @if ($guide)
            <ul class="mk-tags">
              <li>{{ $guide['controls'] }} to play</li>
              <li>{{ $guide['ends'] === 'prize' ? 'Ends with a prize' : 'Ends with a score' }}</li>
              @foreach ($guide['good_for'] as $use)
                <li>{{ $use }}</li>
              @endforeach
            </ul>
          @endif

          <div class="mk-detail-actions">
            @if ($game->hasDemo())
              <button type="button" class="btn btn-primary btn-lg" data-demo-start>
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>
                Play the demo
              </button>
            @endif
            <a href="{{ $studio }}" @class(['btn btn-lg', 'btn-ghost' => $game->hasDemo(), 'btn-primary' => ! $game->hasDemo()])>Use it in Kiosk Studio</a>
          </div>

          <dl class="mk-facts">
            <div><dt>Made by</dt><dd>{{ $game->author ?: 'Kiosk Studio' }}</dd></div>
            <div><dt>Version</dt><dd>{{ $game->version }}</dd></div>
            @if ($game->updated_at)
              <div><dt>Updated</dt><dd>{{ $game->updated_at->format('j M Y') }}</dd></div>
            @endif
          </dl>
        </div>

        {{-- The kiosk: the demo loads when it's started. --}}
        <div class="mk-device-wrap">
          <div class="mk-device" data-demo @if ($game->hasDemo()) data-demo-src="{{ route('marketplace.games.demo', $game) }}" @endif>
            <div class="mk-device-screen">
              <div class="mk-poster">
                @include('marketplace.games.partials.art', ['game' => $game])
                @if ($game->hasDemo())
                  <button type="button" class="mk-poster-play" data-demo-start>
                    <span class="mk-poster-btn" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg></span>
                    <span>Tap to play {{ $game->name }}</span>
                  </button>
                @else
                  <a href="{{ $studio }}" class="mk-poster-play">
                    <span>Try it in Kiosk Studio</span>
                  </a>
                @endif
              </div>
            </div>
            <span class="mk-device-neck" aria-hidden="true"></span>
            <span class="mk-device-base" aria-hidden="true"></span>
          </div>
          @if ($game->hasDemo())
            <p class="mk-device-note">
              Plays like it would on a kiosk: tap, swipe and drag with your mouse or finger.
              <a href="{{ route('marketplace.games.demo', $game) }}" target="_blank" rel="noopener">Open full screen</a>
            </p>
          @endif
        </div>
      </div>

      {{-- How it plays --}}
      @if ($guide)
        <section class="mk-section" aria-labelledby="how-title">
          <h2 id="how-title">How to play</h2>
          <ol class="mk-steps">
            @foreach ($guide['steps'] as $step)
              <li><span class="mk-step-num">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
            @endforeach
          </ol>
          <p class="mk-note">In Kiosk Studio you set the prizes, questions, colours, timings and your logo, then link it to your own start and result screens.</p>
        </section>
      @endif

      @if ($game->screenshotUrls())
        <section class="mk-section" aria-labelledby="shots-title">
          <h2 id="shots-title">Screenshots</h2>
          <ul class="mk-shots">
            @foreach ($game->screenshotUrls() as $url)
              <li><a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="{{ $game->name }} screenshot {{ $loop->iteration }}" loading="lazy" /></a></li>
            @endforeach
          </ul>
        </section>
      @endif

      @if (filled($game->details))
        <section class="mk-section" aria-labelledby="about-title">
          <h2 id="about-title">About this game</h2>
          <div class="mk-prose">{!! $game->detailsHtml() !!}</div>
        </section>
      @endif

      {{-- Reviews --}}
      <section class="mk-section" id="reviews" aria-labelledby="reviews-title">
        <h2 id="reviews-title">Ratings and reviews</h2>
        <div class="mk-reviews">
          <div class="mk-summary">
            <p class="mk-summary-score">{{ $count ? number_format($rating, 1) : '–' }}</p>
            @include('marketplace.games.partials.stars', ['rating' => $rating, 'class' => 'mk-stars-lg'])
            <p class="mk-summary-count">{{ $count ? $count.' '.Str::plural('review', $count) : 'No reviews yet' }}</p>
            <ul class="mk-bars">
              @foreach ($breakdown as $stars => $total)
                <li>
                  <span>{{ $stars }}</span>
                  <span class="mk-bar"><span style="width: {{ $count ? round($total / $count * 100) : 0 }}%"></span></span>
                  <span class="mk-bar-count">{{ $total }}</span>
                </li>
              @endforeach
            </ul>
          </div>

          <div class="mk-review-side">
            {{-- Write one --}}
            <div class="mk-review-form" id="review">
              @auth
                <form method="POST" action="{{ route('marketplace.games.review', $game) }}">
                  @csrf
                  @method('PUT')
                  <h3>{{ $mine ? 'Your review' : 'Rate this game' }}</h3>
                  <fieldset class="mk-star-input">
                    <legend class="sr-only">Your rating</legend>
                    @for ($i = 5; $i >= 1; $i--)
                      <input type="radio" name="rating" id="rating-{{ $i }}" value="{{ $i }}" @checked((int) old('rating', $mine?->rating) === $i) required />
                      <label for="rating-{{ $i }}" title="{{ $i }} {{ Str::plural('star', $i) }}"><span class="sr-only">{{ $i }} {{ Str::plural('star', $i) }}</span></label>
                    @endfor
                  </fieldset>
                  @error('rating') <p class="mk-error">{{ $message }}</p> @enderror
                  <label for="comment" class="sr-only">Comment</label>
                  <textarea id="comment" name="comment" rows="3" maxlength="1000" placeholder="What did you like? How did you use it? (optional)">{{ old('comment', $mine?->comment) }}</textarea>
                  @error('comment') <p class="mk-error">{{ $message }}</p> @enderror
                  <div class="mk-review-actions">
                    <button type="submit" class="btn btn-primary">{{ $mine ? 'Update review' : 'Post review' }}</button>
                  </div>
                </form>
                @if ($mine)
                  <form method="POST" action="{{ route('marketplace.games.review.destroy', $game) }}" class="mk-review-remove">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Remove my review</button>
                  </form>
                @endif
              @else
                <h3>Played it?</h3>
                <p>Sign in to rate this game and tell others how it went.</p>
                <a href="{{ route('marketplace.games.review.start', $game) }}" class="btn btn-primary">Sign in to review</a>
              @endauth
            </div>

            {{-- What people said --}}
            @if ($reviews->isNotEmpty())
              <ul class="mk-review-list">
                @foreach ($reviews as $review)
                  <li class="mk-review">
                    <span class="mk-avatar" aria-hidden="true">{{ $review->user->initials() }}</span>
                    <div>
                      <p class="mk-review-head">
                        <strong>{{ $review->user->name }}</strong>
                        @include('marketplace.games.partials.stars', ['rating' => $review->rating])
                        <time datetime="{{ $review->updated_at->toIso8601String() }}">{{ $review->updated_at->diffForHumans() }}</time>
                      </p>
                      @if ($review->comment)
                        <p class="mk-review-text">{{ $review->comment }}</p>
                      @endif
                    </div>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        </div>
      </section>

      {{-- More --}}
      @if ($others->isNotEmpty())
        <section class="mk-section" aria-labelledby="more-title">
          <div class="mk-section-head">
            <h2 id="more-title">More games</h2>
            <a href="{{ route('marketplace.games.index') }}">See all</a>
          </div>
          <ul class="mk-grid mk-grid-4">
            @foreach ($others as $other)
              @include('marketplace.games.partials.card', ['game' => $other, 'order' => $loop->index])
            @endforeach
          </ul>
        </section>
      @endif
    </div>
  </main>
</x-layouts.site>
