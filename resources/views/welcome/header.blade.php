{{-- The site's top menu. KioskStudio opens a mega menu (style.css "Mega
     menu", main.js). Section links point back at the home page when shown
     on other pages. --}}
@php
    $sectionBase = request()->routeIs('home') ? '' : route('home');
    $studio = route('projects.create.type', 'kiosk-app');

    // Line icons for the mega menu (24×24, stroked).
    $icons = [
        'game' => '<rect x="2" y="7" width="20" height="11" rx="5.5"/><path d="M7 10.5v4M5 12.5h4"/><circle cx="15.5" cy="11.5" r=".9" fill="currentColor"/><circle cx="18" cy="14" r=".9" fill="currentColor"/>',
        'sparkles' => '<path d="M11 3l1.9 5.1L18 10l-5.1 1.9L11 17l-1.9-5.1L4 10l5.1-1.9z"/><path d="M18.5 14.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8z"/>',
        'studio' => '<rect x="5" y="2.5" width="14" height="14" rx="2.2"/><path d="M12 16.5v4M8.5 21h7"/><path d="M8.5 7h7M8.5 10.5h4.5"/>',
        'trending' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'megaphone' => '<path d="M3 10.5v3a1 1 0 001 1h2.5L12 19V5L6.5 9.5H4a1 1 0 00-1 1z"/><path d="M15.5 9a4 4 0 010 6"/><path d="M18.5 6a8 8 0 010 12"/>',
        'tag' => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L3 13V3h10l7.6 7.6a2 2 0 010 2.8z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
        'code' => '<path d="M8 8l-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14"/>',
        'team' => '<circle cx="9" cy="8" r="3.3"/><path d="M2.8 20a6.2 6.2 0 0112.4 0"/><path d="M15.5 4.9a3.3 3.3 0 010 6.3M17.6 14.2A6.2 6.2 0 0121.2 20"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
    ];

    // KioskStudio's mega menu: three groups of three.
    $groups = [
        ['title' => 'Create', 'tone' => '#8b5cf6', 'items' => [
            ['label' => 'Kiosk Game Templates', 'text' => 'Runners, memory games, scratch-and-win and more, ready to brand.', 'href' => route('marketplace.games.index'), 'icon' => 'game', 'tone' => '#8b5cf6'],
            ['label' => 'Explore Animation Templates', 'text' => 'Animated screens that draw people to your kiosk.', 'href' => route('marketplace.animations.index'), 'icon' => 'sparkles', 'tone' => '#ec4899'],
            ['label' => 'Xelenic Kiosk Studios', 'text' => 'Design, preview and share kiosk apps in your browser.', 'href' => route('studio'), 'icon' => 'studio', 'tone' => '#3b82f6'],
        ]],
        ['title' => 'Grow', 'tone' => '#f59e0b', 'items' => [
            ['label' => 'Trending Kiosk App', 'text' => 'Campaign concepts for brands and events. Play them now.', 'href' => route('trending.index'), 'icon' => 'trending', 'tone' => '#f97316', 'badge' => 'Hot'],
            ['label' => 'Advertisements', 'text' => 'Animated ad screens that take turns on your kiosk.', 'href' => route('marketplace.animations.index', ['group' => 'ads']).'#templates', 'icon' => 'megaphone', 'tone' => '#f59e0b'],
            ['label' => 'Sales and Marketing Promotions', 'text' => 'Games and offers that turn passers-by into customers.', 'href' => $sectionBase.'#marketing', 'icon' => 'tag', 'tone' => '#ef4444'],
        ]],
        ['title' => 'Team', 'tone' => '#10b981', 'items' => [
            ['label' => 'Developer Career', 'text' => 'Build kiosks, AI and cloud products with us. We’re hiring.', 'href' => route('careers'), 'icon' => 'code', 'tone' => '#06b6d4'],
            ['label' => 'Hire Our Engineers', 'text' => 'Kiosk, web and AI engineers who join your team.', 'href' => route('projects.create.type', 'hire-engineers'), 'icon' => 'team', 'tone' => '#10b981'],
            ['label' => 'Our Clients', 'text' => 'The brands we build for, and their stories.', 'href' => route('clients.index'), 'icon' => 'star', 'tone' => '#eab308'],
        ]],
    ];
@endphp
<!-- Header / Nav -->
<header class="site-header" id="siteHeader">
  <nav class="navbar container" aria-label="Main">
    <a href="{{ $sectionBase }}#home" class="brand">
      <span class="brand-mark"><img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" width="38" height="38" /></span>
      <span class="brand-name">Xelenic</span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navLinks">
      <span></span><span></span><span></span>
    </button>

    <ul class="nav-links" id="navLinks">
      <li><a href="{{ $sectionBase }}#home" class="nav-link">Home</a></li>

      <li class="has-mega" data-mega>
        <button type="button" class="nav-link mega-toggle" aria-expanded="false" aria-controls="kioskStudioMenu">
          KioskStudio
          <svg class="mega-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>

        <div class="mega" id="kioskStudioMenu">
          <div class="mega-panel">
            <div class="mega-groups">
              @foreach ($groups as $group)
                <div class="mega-group" style="--group-tone: {{ $group['tone'] }}">
                  <p class="mega-group-title">{{ $group['title'] }}</p>
                  <ul>
                    @foreach ($group['items'] as $item)
                      <li>
                        <a href="{{ $item['href'] }}" class="mega-item" style="--tone: {{ $item['tone'] }}">
                          <span class="mega-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['icon']] !!}</svg>
                          </span>
                          <span class="mega-text">
                            <span class="mega-title">
                              {{ $item['label'] }}
                              @isset($item['badge'])<span class="mega-badge">{{ $item['badge'] }}</span>@endisset
                              <svg class="mega-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </span>
                            <span class="mega-desc">{{ $item['text'] }}</span>
                          </span>
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endforeach
            </div>

            <div class="mega-foot">
              <p>New to kiosks? We'll help you choose the right screens, games and setup.</p>
              <a href="{{ $sectionBase }}#contact" class="mega-foot-link">
                Talk to our team
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </a>
            </div>

            <a href="{{ route('studio') }}" class="mega-feature">
              <span class="mega-art" aria-hidden="true"><span class="mega-stage">
                <span class="mega-kiosk">
                  <span class="mega-kiosk-screen">
                    <span class="mega-tile"></span><span class="mega-tile"></span><span class="mega-tile"></span><span class="mega-tile"></span>
                  </span>
                  <span class="mega-kiosk-neck"></span>
                  <span class="mega-kiosk-base"></span>
                </span>
                <span class="mega-wheel"></span>
                <span class="mega-coin"></span>
                <span class="mega-spark mega-spark-1"></span>
                <span class="mega-spark mega-spark-2"></span>
              </span></span>
              <span class="mega-feature-eyebrow">Xelenic Kiosk Studio</span>
              <span class="mega-feature-title">Design your kiosk app in the browser</span>
              <span class="mega-feature-text">Pick a game or an animated screen, add your brand, and preview it live.</span>
              <span class="mega-feature-cta">
                Discover Kiosk Studio
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </span>
            </a>
          </div>
        </div>
      </li>

      <li><a href="{{ route('cloud.index') }}" class="nav-link">Clouds</a></li>
      <li><a href="{{ $sectionBase }}#solutions" class="nav-link">Solutions</a></li>
      <li><a href="{{ $sectionBase }}#ai-middleware" class="nav-link">AI Middlewares</a></li>
    </ul>

    <a href="{{ route('register') }}" class="btn btn-primary nav-cta">Get Started</a>
  </nav>
</header>
