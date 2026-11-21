{{-- The sign-in pages' brand scene: the Xelenic logo builds itself in 3D from
     its own shapes (as in the home page's call to action), then the robot flies
     in to hover beside it. Styles in resources/css/brand-scene.css; the lean
     toward the mouse is resources/js/brand-scene.js. --}}
@php
    // Each part of the logo is a stack of SVG layers 2px apart in depth, so it
    // has real thickness: the rounded square at the back, the pentagon in front
    // of it and the letters in front of that.
    $parts = [
        'square' => ['layers' => 8, 'edge' => '#1b37b8', 'face' => 'url(#bs-square-grad)', 'shape' => '<rect x="63" y="107" width="403" height="368" rx="18"/>'],
        'penta' => ['layers' => 8, 'edge' => '#0079b3', 'face' => 'url(#bs-penta-grad)', 'shape' => '<polygon points="137,46 430,69 499,350 254,504 28,318"/>'],
        'x' => ['layers' => 6, 'edge' => '#9fd3f5', 'face' => 'url(#bs-letter-grad)', 'shape' => '<text x="127" y="340" textLength="166" lengthAdjust="spacingAndGlyphs">X</text>'],
        'e' => ['layers' => 6, 'edge' => '#9fd3f5', 'face' => 'url(#bs-letter-grad)', 'shape' => '<text x="303" y="341" textLength="128" lengthAdjust="spacingAndGlyphs">e</text>'],
    ];
    $sparks = [[4, 18], [92, 10], [100, 64], [60, 102], [-4, 74], [40, -6]];
@endphp

<div class="brand-scene" data-brand-scene aria-hidden="true">
    <svg class="bs-defs" width="0" height="0">
        <defs>
            <clipPath id="bs-penta-clip"><polygon points="137,46 430,69 499,350 254,504 28,318"/></clipPath>
            <linearGradient id="bs-shine-grad" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#ffffff" stop-opacity="0"/>
                <stop offset="0.5" stop-color="#ffffff" stop-opacity="0.75"/>
                <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
            </linearGradient>
            <linearGradient id="bs-square-grad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#4f7bff"/>
                <stop offset="1" stop-color="#2447f0"/>
            </linearGradient>
            <linearGradient id="bs-penta-grad" x1="0.2" y1="0" x2="0.8" y2="1">
                <stop offset="0" stop-color="#36c8ff"/>
                <stop offset="1" stop-color="#009fe3"/>
            </linearGradient>
            <linearGradient id="bs-letter-grad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#ffffff"/>
                <stop offset="1" stop-color="#e3f3ff"/>
            </linearGradient>
        </defs>
    </svg>

    <div class="bs-logo">
        <div class="bs-tilt">
            <div class="bs-3d">
                <div class="bs-glow"></div>
                <div class="bs-ring-wrap">
                    <svg class="bs-ring" viewBox="-110 -110 220 220"><circle r="104"/><circle class="bs-ring-dot" cx="104" cy="0" r="4.5"/><circle class="bs-ring-dot" cx="-104" cy="0" r="3"/></svg>
                </div>

                @foreach ($parts as $name => $part)
                    <div class="bs-part bs-{{ $name }}">
                        @for ($depth = $part['layers']; $depth >= 1; $depth--)
                            <svg class="bs-layer" viewBox="0 0 515 531" style="transform: translateZ(-{{ $depth * 2 }}px)"><g fill="{{ $part['edge'] }}">{!! $part['shape'] !!}</g></svg>
                        @endfor
                        <svg class="bs-layer bs-face" viewBox="0 0 515 531">
                            <g fill="{{ $part['face'] }}">{!! $part['shape'] !!}</g>
                            @if ($name === 'penta')
                                <g clip-path="url(#bs-penta-clip)"><rect class="bs-shine" x="-260" y="0" width="120" height="531" fill="url(#bs-shine-grad)" transform="skewX(-18)"/></g>
                            @endif
                        </svg>
                    </div>
                @endforeach

                <div class="bs-sparks">
                    @foreach ($sparks as $i => [$x, $y])
                        <span class="bs-spark" style="--sx: {{ $x }}%; --sy: {{ $y }}%; --sd: {{ $i * 0.08 }}s"><svg viewBox="-12 -12 24 24"><path d="M0 -11 Q1.98 -1.98 11 0 Q1.98 1.98 0 11 Q-1.98 1.98 -11 0 Q-1.98 -1.98 0 -11Z"/></svg></span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Three wrappers so each motion has its own transform: flying in, leaning
         toward the mouse, and hovering. --}}
    <div class="bs-robot">
        <span class="bs-robot-shadow"></span>
        <div class="bs-robot-lean">
            <div class="bs-robot-hover">
                <img src="{{ asset('moderning_xelenic/assets/img/robot-still.webp') }}" alt="" width="615" height="640" decoding="async">
            </div>
        </div>
    </div>
</div>
