{{-- Section links point back at the home page when shown on other pages. --}}
@php($sectionBase = request()->routeIs('home') ? '' : route('home'))
<!-- Header / Nav -->
<header class="site-header" id="siteHeader">
  <nav class="navbar container">
    <a href="{{ $sectionBase }}#home" class="brand">
      <span class="brand-mark"><img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" width="38" height="38" /></span>
      <span class="brand-name">Xelenic</span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <ul class="nav-links" id="navLinks">
      <li><a href="{{ $sectionBase }}#home" class="nav-link">Home</a></li>
      <li><a href="{{ $sectionBase }}#solutions" class="nav-link">Solutions</a></li>
      <li><a href="{{ $sectionBase }}#why-us" class="nav-link">Why Us</a></li>
      <li><a href="{{ $sectionBase }}#process" class="nav-link">Process</a></li>
      <li><a href="{{ $sectionBase }}#technology" class="nav-link">Technology</a></li>
      <li><a href="{{ $sectionBase }}#contact" class="nav-link">Contact</a></li>
    </ul>

    <a href="{{ route('register') }}" class="btn btn-primary nav-cta">Get Started</a>
  </nav>
</header>
