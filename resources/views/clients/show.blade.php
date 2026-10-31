{{-- A client's story (ClientStoryController), in the marketing site's look:
     style.css plus client-story.css, with the home page's header and footer. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $client->name }} · Xelenic</title>
  <meta name="description" content="{{ $client->summary ?: "How Xelenic works with {$client->name}." }}" />
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
      <a href="{{ route('home') }}#clients" class="story-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        All clients
      </a>

      <header class="story-hero">
        <div>
          <span class="eyebrow">Client story</span>
          <h1>{{ $client->name }}</h1>
          @if (filled($client->summary))
            <p class="story-summary">{{ $client->summary }}</p>
          @endif
        </div>
        <div @class(['story-logo', 'is-dark' => $client->dark_logo])>
          <img src="{{ $client->logoUrl() }}" alt="{{ $client->name }} logo" />
        </div>
      </header>

      @if ($client->imageUrl() || $client->videoUrl() || $client->videoEmbedUrl())
        <div class="story-media">
          @if ($client->imageUrl())
            <figure class="story-figure">
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

      @if (filled($client->article))
        {{-- Markdown rendered with raw HTML stripped (Client::articleHtml()) --}}
        <div class="story-body">{!! $client->articleHtml() !!}</div>
      @endif

      <aside class="story-cta">
        <div>
          <h2>Ready to grow with <span class="text-accent">Xelenic?</span></h2>
          <p>Tell us what you'd like to build and our team will get back to you.</p>
        </div>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Start your project</a>
      </aside>

      @if ($others->isNotEmpty())
        <nav class="story-others" aria-label="More client stories">
          <h2>More client stories</h2>
          <ul>
            @foreach ($others as $other)
              <li>
                <a href="{{ route('clients.show', $other) }}" @class(['story-other', 'is-dark' => $other->dark_logo])>
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
</body>
</html>
