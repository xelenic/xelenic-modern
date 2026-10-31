<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Xelenic | AI Middleware &amp; Business Technology Solutions</title>
  <meta name="description" content="Xelenic delivers AI Middleware, Business Solutions (CRM, ERP, POS, Automation, Payroll), AI-powered Kiosk Apps, and technology-driven marketing strategy to help your business grow." />
  <link rel="icon" href="{{ asset('moderning_xelenic/assets/img/favicon.png') }}" type="image/png" />
  <link rel="apple-touch-icon" href="{{ asset('moderning_xelenic/assets/img/apple-touch-icon.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('moderning_xelenic/assets/css/style.css') }}" />
  <script>
    // Phones (narrow screens, or touch screens held sideways) get the "lite"
    // page: no 3D and no scroll-driven animation, just the clean responsive
    // layout with gentle 2D motion. So do visitors saving data and devices
    // with very little memory. Decided once, before anything renders.
    (function () {
      var lite = false;
      try {
        var connection = navigator.connection;
        lite = window.matchMedia('(max-width: 720px), (pointer: coarse) and (max-height: 500px)').matches
          || Boolean(connection && connection.saveData)
          || (navigator.deviceMemory !== undefined && navigator.deviceMemory <= 2);
      } catch (err) { /* very old browser: full page */ }

      if (lite) {
        document.documentElement.classList.add('is-lite');
        return;
      }

      // The full page needs the 3D libraries and the robot, so start on them
      // now rather than when the end of the page is reached.
      function hint(rel, href, attributes) {
        var link = document.createElement('link');
        link.rel = rel;
        link.href = href;
        Object.keys(attributes || {}).forEach(function (name) { link.setAttribute(name, attributes[name]); });
        document.head.appendChild(link);
      }
      hint('preconnect', 'https://unpkg.com', { crossorigin: '' });
      hint('preconnect', 'https://cdnjs.cloudflare.com', { crossorigin: '' });
      hint('preload', @json(asset('moderning_xelenic/assets/robot/robot.glb')), { as: 'fetch', crossorigin: '' });
    })();
  </script>
</head>
<body>

  <!-- Animated background -->
  <div class="bg-fx" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="grid-overlay"></div>
  </div>
  <canvas id="bg-canvas" aria-hidden="true"></canvas>
  <div class="robot-layer" aria-hidden="true">
    <canvas id="hero-robot-canvas" class="hero-robot-canvas"></canvas>
  </div>

  @include('welcome.header')

  <main>
    @include('welcome.hero')
    @include('welcome.clients')
    @include('welcome.value-strip')
    @include('welcome.solutions')
    @include('welcome.why-us')
    @include('welcome.process')
    @include('welcome.technology')
    @include('welcome.cta')
    @include('welcome.contact')
  </main>

  @include('welcome.footer')

  <!-- Robot sounds on/off (robot-sound.js); aria-pressed reflects the state -->
  <button type="button" class="sound-toggle" aria-pressed="true" aria-label="Robot sounds">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M4 9.5h3.5L12 5.5v13l-4.5-4H4z"/>
      <path class="sound-wave" d="M15.5 9.5a3.5 3.5 0 0 1 0 5"/>
      <path class="sound-wave sound-wave-2" d="M18 7a7 7 0 0 1 0 10"/>
      <path class="sound-off" d="M16 9.5l5 5M21 9.5l-5 5"/>
    </svg>
  </button>

  <a href="#home" class="back-to-top" id="backToTop" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </a>

  <script>
    // Load the scripts in order. The lite (phone) page skips GSAP, three.js,
    // the 3D robot, its sounds and the 3D background entirely.
    (function () {
      // The site's assets live in public/moderning_xelenic/assets; three-robot.js
      // also reads this to find the robot model.
      var base = window.XELENIC_ASSET_BASE = @json(asset('moderning_xelenic/assets').'/');
      var lite = document.documentElement.classList.contains('is-lite');
      var full = [
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js',
        'https://unpkg.com/three@0.144.0/build/three.min.js',
        'https://unpkg.com/three@0.144.0/examples/js/loaders/GLTFLoader.js',
        'https://unpkg.com/three@0.144.0/examples/js/libs/meshopt_decoder.js',
      ];
      var scripts = (lite ? [] : full).concat([
        base + 'js/main.js',
      ], lite ? [] : [
        base + 'js/value-strip.js',
      ], [
        base + 'js/clients.js',
        base + 'js/logo-art.js',
      ], lite ? [] : [
        base + 'js/frame-budget.js',
        base + 'js/three-bg.js',
        base + 'js/robot-sound.js',
      ], [
        base + 'js/story.js',
      ], lite ? [] : [
        base + 'js/three-robot.js',
      ], [
        base + 'js/solution-cards.js',
        base + 'js/tech.js',
      ], lite ? [] : [
        base + 'js/cta.js',
      ]);
      scripts.forEach(function (src) {
        var el = document.createElement('script');
        el.src = src;
        el.async = false; // run in this order, as they load
        document.body.appendChild(el);
      });
    })();
  </script>
</body>
</html>
