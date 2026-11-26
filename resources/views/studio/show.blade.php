{{-- About Xelenic Kiosk Studio (KioskStudioPageController), with real
     screenshots of it (public/moderning_xelenic/assets/img/studio). What it
     says it does, it does: see public/nignest. --}}
@php
    $start = route('projects.create.type', 'kiosk-app');
    $builtin = $games->where('kind', 'builtin');
@endphp
<x-layouts.site title="Xelenic Kiosk Studio"
  description="Kiosk Studio by Xelenic: design touchscreen kiosk apps in your browser. Map your screens, lay them out, animate them, add games and forms, play it, then export one file that runs full screen on the kiosk."
  :styles="['css/kiosk-studio.css']" :scripts="['js/kiosk-studio.js']">
  <main class="ks">
    {{-- Hero --}}
    <section class="ks-hero">
      <div class="container">
        <div class="ks-hero-copy">
          <span class="ks-badge">
            <img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" width="20" height="20" />
            Xelenic Kiosk Studio
          </span>
          <h1>Design kiosk apps people <span class="text-accent">love to play</span></h1>
          <p class="ks-lead">Kiosk Studio is where your kiosk comes together. Map out the screens, lay out each one, animate it, add games and forms, and play it, all in your browser. Then export it as one file that runs full screen on the kiosk.</p>
          <div class="ks-actions">
            <a href="{{ $start }}" class="btn btn-primary btn-lg">Start a kiosk project</a>
            <a href="#tour" class="btn btn-ghost btn-lg">Take the tour</a>
          </div>
        </div>

        <div class="ks-hero-shot">
          @include('studio.partials.shot', ['shot' => 'editor', 'alt' => 'Kiosk Studio’s Screen Editor with a welcome screen open', 'eager' => true])
          <span class="ks-float ks-float-1" aria-hidden="true"><b><x-site.icon name="gamepad" /></b> {{ $builtin->count() }} ready-made games</span>
          <span class="ks-float ks-float-2" aria-hidden="true"><b><x-site.icon name="sparkles" /></b> {{ $animations }} animated screens</span>
          <span class="ks-float ks-float-3" aria-hidden="true"><b><x-site.icon name="timeline" /></b> Animation timeline</span>
          <span class="ks-float ks-float-4" aria-hidden="true"><b><x-site.icon name="device" /></b> Portrait or landscape</span>
        </div>
      </div>
    </section>

    {{-- At a glance (the counts are Kiosk Studio's own: js/editor-inspector.js) --}}
    <section class="ks-strip" aria-label="Kiosk Studio at a glance">
      <div class="container">
        <ul class="ks-stats">
          <li><strong>{{ $builtin->count() }}</strong><span>games built in</span></li>
          <li><strong>{{ $animations }}</strong><span>animated screen templates</span></li>
          <li><strong>14</strong><span>entrance effects</span></li>
          <li><strong>7</strong><span>animated backgrounds</span></li>
          <li><strong>1</strong><span>file to run on the kiosk</span></li>
        </ul>
      </div>
    </section>

    {{-- How it works --}}
    <section class="ks-section" id="how">
      <div class="container">
        <div class="ks-head">
          <span class="eyebrow">How it works</span>
          <h2>From idea to kiosk in four steps</h2>
        </div>
        <ol class="ks-steps">
          <li class="reveal"><span class="ks-step-num">1</span><h3>Map your screens</h3><p>Lay out the journey on the Flow Map: welcome, menu, games, prizes, thank you.</p></li>
          <li class="reveal"><span class="ks-step-num">2</span><h3>Design each one</h3><p>Drag on text, buttons, pictures and stickers, then style them to your brand.</p></li>
          <li class="reveal"><span class="ks-step-num">3</span><h3>Add games and forms</h3><p>Drop in a ready-made game, and a form to collect visitors’ details.</p></li>
          <li class="reveal"><span class="ks-step-num">4</span><h3>Play and publish</h3><p>Play it in the browser, share a link, and export it for the kiosk.</p></li>
        </ol>
      </div>
    </section>

    {{-- The tour --}}
    <section class="ks-section ks-tour" id="tour">
      <div class="container">
        <div class="ks-head">
          <span class="eyebrow">Take the tour</span>
          <h2>Everything you need to build a kiosk</h2>
          <p>Real screenshots from Kiosk Studio, with the <em>Play &amp; Win Kiosk</em> demo project open. Click any of them to see it full size.</p>
        </div>

        <article class="ks-feature reveal" id="flow-map">
          <div class="ks-feature-copy">
            <span class="ks-kicker"><b>1</b> Flow Map</span>
            <h3>See your whole kiosk at a glance</h3>
            <p>Every screen is a card on the map, and every way out of it is a line: its buttons, a game’s win and lose, a timer. Drag from a port onto a screen to link them.</p>
            <ul class="ks-points">
              <li>The start screen is marked, so you always know where the kiosk begins</li>
              <li>Win, lose and timer links for games and attract loops</li>
              <li>Auto layout, zoom and pan, however big the kiosk gets</li>
            </ul>
          </div>
          @include('studio.partials.shot', ['shot' => 'flow', 'alt' => 'The Flow Map, with fifteen linked screens of a play-and-win kiosk'])
        </article>

        <article class="ks-feature ks-feature-flip reveal" id="screen-editor">
          <div class="ks-feature-copy">
            <span class="ks-kicker"><b>2</b> Screen Editor</span>
            <h3>Lay out each screen, exactly</h3>
            <p>Drag text, buttons, pictures, stickers, shapes, form fields and a live score onto a full-size kiosk screen, then fine-tune them in the Properties panel.</p>
            <ul class="ks-points">
              <li>Portrait 1080 × 1920 or landscape 1920 × 1080, and switch any time</li>
              <li>Fonts, colours, gradients, rounded corners, shadows, rotation and opacity</li>
              <li>Live values in any text: <code>@{{score}}</code>, <code>@{{best}}</code>, <code>@{{name}}</code></li>
              <li>A dark or light editor, and undo for everything</li>
            </ul>
          </div>
          @include('studio.partials.shot', ['shot' => 'editor-light', 'alt' => 'The Screen Editor in the light theme, with a game menu button picked'])
        </article>

        <article class="ks-feature reveal" id="animation">
          <div class="ks-feature-copy">
            <span class="ks-kicker"><b>3</b> Animation timeline</span>
            <h3>Bring every screen to life</h3>
            <p>Give each item an entrance, a loop and an exit, pick an animated background, and choose how the screen arrives. The timeline shows when everything plays: drag a bar to move it, or its edge to change how long it takes.</p>
            <ul class="ks-points">
              <li>14 entrance effects, from Pop to Letter by letter and Typewriter</li>
              <li>11 loops such as Float, Pulse, Shine sweep and Heartbeat</li>
              <li>Aurora, sun rays, bubbles, stars, sparkles, confetti and snow backgrounds</li>
            </ul>
          </div>
          @include('studio.partials.shot', ['shot' => 'timeline', 'alt' => 'The animation timeline under the screen, part way through playing'])
        </article>

        <article class="ks-feature ks-feature-flip reveal" id="games">
          <div class="ks-feature-copy">
            <span class="ks-kicker"><b>4</b> Game templates</span>
            <h3>Add a game in one click</h3>
            <p>Each game arrives as a screen of its own, ready to set up: your questions, prizes, colours and timings. Developers can import their own games as a .zip, starting from the sample template.</p>
            @if ($builtin->isNotEmpty())
              <ul class="ks-game-row" aria-label="The games">
                @foreach ($builtin as $game)
                  <li><a href="{{ route('marketplace.games.show', $game) }}" style="--game: {{ $game->cssColor() }}" title="{{ $game->name }}"><span aria-hidden="true">@include('marketplace.games.partials.icon', ['game' => $game])</span><span class="sr-only">{{ $game->name }}</span></a></li>
                @endforeach
              </ul>
            @endif
            <a href="{{ route('marketplace.games.index') }}" class="ks-more">Play the games in your browser <span aria-hidden="true">→</span></a>
          </div>
          @include('studio.partials.shot', ['shot' => 'games', 'alt' => 'The game templates marketplace inside Kiosk Studio'])
        </article>

        <article class="ks-feature reveal" id="screen-templates">
          <div class="ks-feature-copy">
            <span class="ks-kicker"><b>5</b> Screen templates</span>
            <h3>Start from screens that already move</h3>
            <p>A welcome loop, a how-to-play screen for every game, a winner screen, and ads for advertising kiosks, all animated. Watch each one with its timeline, then add it to your project and make it yours.</p>
            <ul class="ks-points">
              <li>Ads for sales, new arrivals, menu deals, coupons, openings and gifts</li>
              <li>Ads move on by themselves, so a few of them make a rotating loop</li>
              <li>Or start from the Advertising kiosk project: four ads that take turns, and an offer form</li>
            </ul>
            <a href="{{ route('marketplace.animations.index') }}" class="ks-more">Explore the animation templates <span aria-hidden="true">→</span></a>
          </div>
          @include('studio.partials.shot', ['shot' => 'screen-template', 'alt' => 'A screen template’s page in Kiosk Studio, playing with its animation timeline'])
        </article>

        <article class="ks-feature ks-feature-flip reveal" id="play">
          <div class="ks-feature-copy">
            <span class="ks-kicker"><b>6</b> Play &amp; publish</span>
            <h3>Play it before it goes live</h3>
            <p>Press Play to run the kiosk from the start, or from the screen you’re on, just as visitors will. When it’s ready, it goes onto the kiosk as a single file.</p>
            <ul class="ks-points">
              <li>Forms collect visitors’ details, and Data shows what came in</li>
              <li>Share a play link from your project so anyone can try it</li>
              <li>Export one .html file with everything inside: it runs full screen, even offline</li>
              <li>Back to the start screen on its own when nobody is playing</li>
            </ul>
          </div>
          @include('studio.partials.shot', ['shot' => 'play', 'alt' => 'Playing the Spin & Win game in Kiosk Studio’s preview'])
        </article>
      </div>
    </section>

    {{-- The kiosks --}}
    <section class="ks-section ks-machines-section">
      <div class="container">
        <div class="ks-head">
          <span class="eyebrow">Built for every kiosk</span>
          <h2>Designed for the screen it runs on</h2>
          <p>Pick your kiosk when you start a project, and Kiosk Studio opens at its orientation.</p>
        </div>
        <ul class="ks-machines">
          @foreach ($machines as $machine)
            <li class="reveal">
              <img src="{{ $machine->image() }}" alt="" loading="lazy" width="160" height="160" />
              <h3>{{ $machine->label() }}</h3>
              <p>{{ $machine->screen()['size'] }}″ {{ $machine->screen()['orientation'] }} screen</p>
            </li>
          @endforeach
        </ul>
      </div>
    </section>

    {{-- Questions --}}
    <section class="ks-section">
      <div class="container ks-faq-wrap">
        <div class="ks-head ks-head-left">
          <span class="eyebrow">Questions</span>
          <h2>Good to know</h2>
          <p>Anything else? <a href="{{ route('home') }}#contact">Ask our team</a>.</p>
        </div>
        <div class="ks-faq">
          <details open>
            <summary>Do I need to install anything?</summary>
            <p>No. Kiosk Studio runs in your web browser. Start a kiosk project from your Xelenic account and it opens right there.</p>
          </details>
          <details>
            <summary>Does the kiosk need the internet?</summary>
            <p>No. You export the finished app as one .html file with everything inside it, pictures included. Open it full screen on the kiosk and it runs offline.</p>
          </details>
          <details>
            <summary>Can I use my own game?</summary>
            <p>Yes. Game templates come as a .zip. Import one in Kiosk Studio, or download the sample template to see how they’re made.</p>
          </details>
          <details>
            <summary>Portrait or landscape?</summary>
            <p>Both: 1080 × 1920 portrait or 1920 × 1080 landscape. Switch at any time and Kiosk Studio rescales your screens.</p>
          </details>
          <details>
            <summary>Can others try my kiosk before it goes live?</summary>
            <p>Yes. Your kiosk project has a play link to share, and anyone with it can play the kiosk in their browser.</p>
          </details>
        </div>
      </div>
    </section>

    {{-- Call to action --}}
    <section class="ks-section ks-cta-wrap">
      <div class="container">
        <aside class="ks-cta">
          <img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" width="64" height="64" />
          <h2>Build your kiosk with Xelenic Kiosk Studio</h2>
          <p>Start a kiosk project, pick your machine, and design it in your browser. Our team reviews it with you and takes it from there.</p>
          <div class="ks-actions">
            <a href="{{ $start }}" class="btn btn-primary btn-lg">Start a kiosk project</a>
            <a href="{{ route('home') }}#contact" class="btn btn-ghost btn-lg">Talk to our team</a>
          </div>
        </aside>
      </div>
    </section>
  </main>

  <dialog class="ks-lightbox" data-lightbox aria-label="Screenshot">
    <form method="dialog"><button class="ks-lightbox-close" aria-label="Close">×</button></form>
    <img alt="" data-lightbox-img />
  </dialog>
</x-layouts.site>
