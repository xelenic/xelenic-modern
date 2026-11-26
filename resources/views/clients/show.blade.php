{{-- A client's story (ClientStoryController), in the marketing site's look:
     style.css plus client-story.css, with the home page's header and footer. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $client->name }} · Xelenic</title>
  <meta name="description" content="{{ $client->summary ?: "{$client->name} is one of the brands that trust Xelenic." }}" />
  <link rel="icon" href="{{ asset('moderning_xelenic/assets/img/favicon.png') }}" type="image/png" />
  <link rel="apple-touch-icon" href="{{ asset('moderning_xelenic/assets/img/apple-touch-icon.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('moderning_xelenic/assets/css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('moderning_xelenic/assets/css/client-story.css') }}" />
</head>
<body>
  <div class="bg-fx" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="grid-overlay"></div>
  </div>

  @include('welcome.header')

  <main class="story">
    <article class="container">
      <a href="{{ route('clients.index') }}" class="story-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        All clients
      </a>

      <header class="story-hero">
        <div>
          <span class="eyebrow">{{ $client->hasStory() ? 'Client story' : 'Our client' }}</span>
          <h1>{{ $client->name }}</h1>
          @if (filled($client->summary))
            <p class="story-summary">{{ $client->summary }}</p>
          @else
            <p class="story-summary">{{ $client->name }} is one of the brands that trust Xelenic with their kiosks, software and digital growth.</p>
          @endif
        </div>
        <div @class(['story-logo', 'is-dark' => $client->dark_logo])>
          <img src="{{ $client->logoUrl() }}" alt="{{ $client->name }} logo" />
        </div>
      </header>

      @php
        // A portrait featured photo is shown whole beside the article (or on
        // its own, centred); a landscape one runs wide above it.
        $portrait = $client->imageUrl() && $client->imageIsPortrait();
        $beside = $portrait && filled($client->article);
      @endphp

      @if ($beside)
        <div class="story-split">
          <figure class="story-figure is-portrait">
            <img src="{{ $client->imageUrl() }}" alt="" />
          </figure>
          {{-- Markdown rendered with raw HTML stripped (Client::articleHtml()) --}}
          <div class="story-body">{!! $client->articleHtml() !!}</div>
        </div>
      @endif

      @if ((! $beside && $client->imageUrl()) || $client->videoUrl() || $client->videoEmbedUrl())
        <div class="story-media">
          @if (! $beside && $client->imageUrl())
            <figure @class(['story-figure', 'is-portrait' => $portrait])>
              <img src="{{ $client->imageUrl() }}" alt="" />
            </figure>
          @endif

          @if ($client->videoUrl())
            <div class="story-video">
              <video src="{{ $client->videoUrl() }}" controls playsinline preload="metadata"></video>
            </div>
          @elseif ($embed = $client->videoEmbedUrl())
            <div class="story-video">
              <iframe
                src="{{ $embed }}"
                title="{{ $client->name }} video"
                loading="lazy"
                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
              ></iframe>
            </div>
          @endif
        </div>
      @endif

      @if (! $beside && filled($client->article))
        {{-- Markdown rendered with raw HTML stripped (Client::articleHtml()) --}}
        <div class="story-body">{!! $client->articleHtml() !!}</div>
      @endif

      {{-- The gallery, in its order: photos (they open large, see
           client-story.js), then videos and YouTube or Vimeo links. --}}
      @php
        $photos = $client->media->filter->isImage()->values();
        $videos = $client->media->reject->isImage()->values();
      @endphp
      @if ($photos->isNotEmpty())
        <section class="story-gallery" aria-labelledby="story-photos">
          <h2 id="story-photos">Photos</h2>
          <ul class="story-photos">
            @foreach ($photos as $photo)
              <li>
                <figure>
                  <a href="{{ $photo->fileUrl() }}" class="story-photo" data-lightbox @if ($photo->caption) data-caption="{{ $photo->caption }}" @endif aria-label="{{ $photo->caption ?: 'Photo '.($loop->iteration) }}: view larger">
                    <img src="{{ $photo->fileUrl() }}" alt="{{ $photo->caption ?? '' }}" loading="lazy" />
                  </a>
                  @if ($photo->caption)
                    <figcaption>{{ $photo->caption }}</figcaption>
                  @endif
                </figure>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      @if ($videos->isNotEmpty())
        <section class="story-gallery" aria-labelledby="story-videos">
          <h2 id="story-videos">Videos</h2>
          <ul class="story-videos">
            @foreach ($videos as $video)
              <li>
                <figure>
                  <div class="story-video">
                    @if ($video->isVideo())
                      <video src="{{ $video->fileUrl() }}" controls playsinline preload="metadata"></video>
                    @else
                      <iframe
                        src="{{ $video->embedUrl() }}"
                        title="{{ $video->caption ?: $client->name.' video' }}"
                        loading="lazy"
                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                      ></iframe>
                    @endif
                  </div>
                  @if ($video->caption)
                    <figcaption>{{ $video->caption }}</figcaption>
                  @endif
                </figure>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      <aside class="story-cta">
        <div>
          <h2>Ready to grow with <span class="text-accent">Xelenic?</span></h2>
          <p>Tell us what you'd like to build and our team will get back to you.</p>
        </div>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Start your project</a>
      </aside>

      @if ($others->isNotEmpty())
        <nav class="story-others" aria-label="More of our clients">
          <h2>More of our clients</h2>
          <ul>
            @foreach ($others as $other)
              <li>
                <a href="{{ route('clients.show', $other) }}" @class(['story-other', 'is-dark' => $other->dark_logo]) aria-label="{{ $other->name }}">
                  <img src="{{ $other->logoUrl() }}" alt="{{ $other->name }}" loading="lazy" />
                </a>
              </li>
            @endforeach
          </ul>
        </nav>
      @endif
    </article>
  </main>

  @include('welcome.footer')

  <script src="{{ asset('moderning_xelenic/assets/js/main.js') }}" defer></script>
  <script src="{{ asset('moderning_xelenic/assets/js/client-story.js') }}" defer></script>
</body>
</html>
