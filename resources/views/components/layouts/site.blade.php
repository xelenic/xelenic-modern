{{-- A page of the marketing site: style.css's look (light and dark), the
     home page's header with its mega menu, and its footer. Pages add their
     own stylesheets and scripts from public/moderning_xelenic/assets;
     kioskPlayer loads Kiosk Studio's player and screen templates before them. --}}
@props(['title', 'description' => null, 'styles' => [], 'scripts' => [], 'kioskPlayer' => false])

@php
    $asset = fn (string $path) => asset("moderning_xelenic/assets/{$path}").'?v='.@filemtime(public_path("moderning_xelenic/assets/{$path}"));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $title }} · Xelenic</title>
  @if ($description)
    <meta name="description" content="{{ $description }}" />
  @endif
  <link rel="icon" href="{{ asset('moderning_xelenic/assets/img/favicon.png') }}" type="image/png" />
  <link rel="apple-touch-icon" href="{{ asset('moderning_xelenic/assets/img/apple-touch-icon.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ $asset('css/style.css') }}" />
  @foreach ($styles as $style)
    <link rel="stylesheet" href="{{ $asset($style) }}" />
  @endforeach
</head>
<body {{ $attributes }}>
  <div class="bg-fx" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="grid-overlay"></div>
  </div>

  @include('welcome.header')

  {{ $slot }}

  @include('welcome.footer')

  <script src="{{ $asset('js/main.js') }}" defer></script>
  @if ($kioskPlayer)
    @foreach (['art.js', 'games.js', 'runtime.js', 'templates.js'] as $file)
      <script src="{{ asset("nignest/js/{$file}") }}?v={{ @filemtime(public_path("nignest/js/{$file}")) }}" defer></script>
    @endforeach
  @endif
  @foreach ($scripts as $script)
    <script src="{{ $asset($script) }}" defer></script>
  @endforeach
</body>
</html>
