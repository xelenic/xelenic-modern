{{-- Our clients (ClientStoryController@index): the brands we work with, their
     stories first, then every logo. Clients, their logos and stories are
     managed in Admin → Clients. --}}
@php
    // The first stories as large rows, the rest in a grid.
    $rows = $stories->take(3);
    $more = $stories->slice(3)->values();
    $excerpt = fn ($client, $length = 190) => Str::limit(trim(strip_tags($client->summary ?: $client->articleHtml())), $length);
@endphp
<x-layouts.site title="Our Clients"
  description="The brands that trust Xelenic with their kiosks, software and digital growth. See who we work with and read their stories."
  :styles="['css/client-story.css', 'css/clients.css']" :scripts="['js/clients-page.js']">
  <main class="cl">
    {{-- Hero, with a wall of logos --}}
    <section class="cl-hero">
      <div class="container cl-hero-grid">
        <div>
          <span class="eyebrow">Our Clients</span>
          <h1>Brands that grow <span class="text-accent">with Xelenic</span></h1>
          <p class="cl-lead">From global names to ambitious local brands, these are the companies that trust us with their kiosks, software and digital growth.</p>
          <ul class="cl-stats">
            <li><strong>{{ $clients->count() }}</strong> {{ Str::plural('brand', $clients->count()) }}</li>
            @if ($stories->isNotEmpty())
              <li><strong>{{ $stories->count() }}</strong> {{ Str::plural('client story', $stories->count()) }}</li>
            @endif
          </ul>
          <div class="cl-actions">
            <a href="#brands" class="btn btn-primary btn-lg">See our clients</a>
            <a href="{{ route('home') }}#contact" class="btn btn-ghost btn-lg">Work with us</a>
          </div>
        </div>

        @if ($clients->isNotEmpty())
          {{-- Logos drift up in three columns --}}
          <div class="cl-hero-wall" aria-hidden="true">
            @foreach ($clients->take(12)->chunk((int) ceil(min(12, $clients->count()) / 3)) as $column)
              <div class="cl-wall-col cl-wall-col-{{ $loop->iteration }}">
                @foreach ([1, 2] as $copy)
                  @foreach ($column as $client)
                    <span @class(['cl-wall-tile', 'is-dark' => $client->dark_logo])><img src="{{ $client->logoUrl() }}" alt="" loading="lazy" /></span>
                  @endforeach
                @endforeach
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </section>

    @if ($clients->isEmpty())
      <section class="cl-section">
        <div class="container"><p class="cl-empty">Our client list is on its way.</p></div>
      </section>
    @else
      {{-- Stories --}}
      @if ($stories->isNotEmpty())
        <section class="cl-section" aria-labelledby="stories-title">
          <div class="container">
            <div class="cl-head">
              <span class="eyebrow">Client stories</span>
              <h2 id="stories-title">What we’ve built together</h2>
            </div>

            <div class="cl-features">
              @foreach ($rows as $client)
                <a href="{{ route('clients.show', $client) }}" @class(['cl-feature reveal', 'is-flipped' => $loop->even])>
                  <span class="cl-feature-media">
                    @if ($client->imageUrl())
                      <img src="{{ $client->imageUrl() }}" alt="" loading="lazy" @class(['is-portrait' => $client->imageIsPortrait()]) />
                    @else
                      <span @class(['cl-feature-logo', 'is-dark' => $client->dark_logo])><img src="{{ $client->logoUrl() }}" alt="" /></span>
                    @endif
                  </span>
                  <span class="cl-feature-body">
                    <span @class(['cl-logo-chip', 'is-dark' => $client->dark_logo])><img src="{{ $client->logoUrl() }}" alt="{{ $client->name }}" /></span>
                    <span class="cl-feature-kicker">Client story</span>
                    <span class="cl-feature-title">{{ $client->name }}</span>
                    <span class="cl-feature-text">{{ $excerpt($client, 260) }}</span>
                    @include('clients.partials.story-meta', ['client' => $client])
                    <span class="cl-read">Read the story <span aria-hidden="true">→</span></span>
                  </span>
                </a>
              @endforeach
            </div>

            @if ($more->isNotEmpty())
              <ul class="cl-stories">
                @foreach ($more as $client)
                  <li class="reveal">
                    <a href="{{ route('clients.show', $client) }}" class="cl-story">
                      <span class="cl-story-media">
                        @if ($client->imageUrl())
                          <img src="{{ $client->imageUrl() }}" alt="" loading="lazy" />
                        @else
                          <span @class(['cl-feature-logo', 'is-dark' => $client->dark_logo])><img src="{{ $client->logoUrl() }}" alt="" /></span>
                        @endif
                        <span @class(['cl-logo-chip cl-logo-chip-sm', 'is-dark' => $client->dark_logo])><img src="{{ $client->logoUrl() }}" alt="" /></span>
                      </span>
                      <span class="cl-story-body">
                        <span class="cl-story-title">{{ $client->name }}</span>
                        <span class="cl-story-text">{{ $excerpt($client) }}</span>
                        @include('clients.partials.story-meta', ['client' => $client])
                        <span class="cl-read">Read the story <span aria-hidden="true">→</span></span>
                      </span>
                    </a>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        </section>
      @endif

      {{-- Every brand --}}
      <section class="cl-section" id="brands" aria-labelledby="brands-title">
        <div class="container">
          <div class="cl-head cl-head-row">
            <div>
              <span class="eyebrow">Every brand</span>
              <h2 id="brands-title">The companies we work with</h2>
            </div>
            <label class="cl-search">
              <x-site.icon name="search" class="cl-search-icon" />
              <span class="sr-only">Find a client</span>
              <input type="search" placeholder="Find a client" autocomplete="off" data-client-search />
            </label>
          </div>

          <ul class="cl-grid" data-client-grid>
            @foreach ($clients as $client)
              <li data-client="{{ Str::lower($client->name) }}">
                <a href="{{ route('clients.show', $client) }}" class="cl-card" aria-label="{{ $client->name }}{{ $client->hasStory() ? ': read the story' : '' }}">
                  <span @class(['cl-card-logo', 'is-dark' => $client->dark_logo])>
                    <img src="{{ $client->logoUrl() }}" alt="{{ $client->name }}" loading="lazy" decoding="async" />
                  </span>
                  <span class="cl-card-foot">
                    <span class="cl-card-name">{{ $client->name }}</span>
                    @if ($client->hasStory())
                      <span class="cl-card-story"><x-site.icon name="article" /> Story</span>
                    @endif
                  </span>
                </a>
              </li>
            @endforeach
          </ul>
          <p class="cl-empty" data-client-none hidden>No client by that name.</p>
        </div>
      </section>
    @endif

    {{-- Call to action --}}
    <section class="cl-section cl-cta-wrap">
      <div class="container">
        <aside class="cl-cta">
          <span class="cl-cta-icon" aria-hidden="true"><x-site.icon name="handshake" /></span>
          <div>
            <h2>Become our next success story</h2>
            <p>Tell us about your brand and what you’d like to build: kiosks, games, software or a campaign. We’ll take it from there.</p>
          </div>
          <div class="cl-actions">
            <a href="{{ route('home') }}#contact" class="btn btn-primary btn-lg">Talk to our team</a>
            <a href="{{ route('trending.index') }}" class="btn btn-ghost btn-lg">See campaign ideas</a>
          </div>
        </aside>
      </div>
    </section>
  </main>
</x-layouts.site>
