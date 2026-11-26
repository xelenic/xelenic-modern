{{-- The marketing site's icons: duotone line drawings (a soft fill under a
     stroke) in currentColor, drawn on a 24px grid. The site uses these, never
     emoji. Games are named after their kind (quiz, catch, slice…); anything
     unknown falls back to the game controller. --}}
@props(['name'])

@php
    // The soft fill under a shape.
    $soft = 'fill="currentColor" fill-opacity=".22"';
    $solid = 'fill="currentColor" stroke="none"';

    $icons = [
        // Games
        'quiz' => "<path {$soft} d=\"M12 3a6 6 0 0 0-3.7 10.7c.7.6 1.2 1.4 1.2 2.3v.5h5v-.5c0-.9.5-1.7 1.2-2.3A6 6 0 0 0 12 3z\"/><path d=\"M9.5 19h5M10.5 21.5h3\"/><path d=\"M10.4 8.6a1.7 1.7 0 1 1 2.4 1.6c-.5.3-.8.7-.8 1.2v.4\"/><circle {$solid} cx=\"12\" cy=\"13.6\" r=\".85\"/>",
        'catch' => "<path {$soft} d=\"M3 10.5h18l-1.8 8.4A2 2 0 0 1 17.2 20.5H6.8a2 2 0 0 1-2-1.6z\"/><path d=\"M7.5 10.5L11 4.5M16.5 10.5L13 4.5M2.5 10.5h19\"/><path d=\"M9 13.5v4M12 13.5v4M15 13.5v4\"/>",
        'slice' => "<path {$soft} d=\"M2.5 8.5a9.5 9.5 0 0 0 19 0z\"/><path d=\"M5.3 8.5a6.7 6.7 0 0 0 13.4 0\"/><circle {$solid} cx=\"9\" cy=\"11\" r=\".9\"/><circle {$solid} cx=\"12\" cy=\"13\" r=\".9\"/><circle {$solid} cx=\"15\" cy=\"11\" r=\".9\"/>",
        'wheel' => "<circle {$soft} cx=\"12\" cy=\"13\" r=\"8.5\"/><path d=\"M12 4.5v17M3.5 13h17M6 7l12 12M18 7L6 19\"/><circle {$solid} cx=\"12\" cy=\"13\" r=\"2\"/><path {$solid} d=\"M9.8 1.8h4.4L12 5z\"/>",
        'runner' => "<circle {$soft} cx=\"15.5\" cy=\"4.5\" r=\"2.2\"/><path d=\"M13.5 8l-2.7 5.2 3.6 2.6-.9 5.4\"/><path d=\"M8 11l3-3.2 3.3.9 2.2 3 2.8.8\"/><path d=\"M10.8 13.2L8.6 16.5H5\"/>",
        'memory' => "<rect {$soft} x=\"3\" y=\"5.5\" width=\"10\" height=\"14\" rx=\"2\" transform=\"rotate(-9 8 12.5)\"/><rect x=\"11\" y=\"4.5\" width=\"10\" height=\"14\" rx=\"2\" transform=\"rotate(9 16 11.5)\"/><path {$solid} d=\"M16 8.8l1.3 2.7-1.3 2.7-1.3-2.7z\"/>",
        'whack' => "<path {$soft} d=\"M13.4 3.6l7 7-3 3-7-7z\"/><path d=\"M12.2 9.3L3.9 17.6a1.6 1.6 0 0 0 2.3 2.3l8.3-8.3\"/><path d=\"M19.5 16.5l2 .3M18 19l1.3 1.6\"/>",
        'stack' => "<rect {$soft} x=\"4\" y=\"15.5\" width=\"16\" height=\"5\" rx=\"1.2\"/><rect x=\"6.5\" y=\"10\" width=\"12\" height=\"5\" rx=\"1.2\"/><rect {$soft} x=\"8\" y=\"4.5\" width=\"10\" height=\"5\" rx=\"1.2\"/>",
        'hoop' => "<circle {$soft} cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M3 12h18M12 3v18M5.7 5.7a9 9 0 0 1 0 12.6M18.3 5.7a9 9 0 0 0 0 12.6\"/>",
        'scratch' => "<path {$soft} d=\"M3 8.5V6.5a1.5 1.5 0 0 1 1.5-1.5h15A1.5 1.5 0 0 1 21 6.5v2a3.5 3.5 0 0 0 0 7v2a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5v-2a3.5 3.5 0 0 0 0-7z\"/><path d=\"M15 5v2.5M15 11v2M15 16.5V19\"/><path {$solid} d=\"M8.5 8.8l.9 2 2 .9-2 .9-.9 2-.9-2-2-.9 2-.9z\"/>",
        'gamepad' => "<path {$soft} d=\"M7.5 7h9a5 5 0 0 1 4.8 6.3l-.7 2.6a2.6 2.6 0 0 1-4.4 1.1L14.4 15H9.6L7.8 17a2.6 2.6 0 0 1-4.4-1.1l-.7-2.6A5 5 0 0 1 7.5 7z\"/><path d=\"M8 10v3.5M6.25 11.75h3.5\"/><circle {$solid} cx=\"15.5\" cy=\"10.5\" r=\"1\"/><circle {$solid} cx=\"17.8\" cy=\"12.8\" r=\"1\"/>",

        // Screens and campaigns
        'sparkles' => "<path {$soft} d=\"M10.5 3l1.9 5.1 5.1 1.9-5.1 1.9-1.9 5.1-1.9-5.1-5.1-1.9 5.1-1.9z\"/><path {$solid} d=\"M18.5 14.5l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9z\"/>",
        'trophy' => "<path {$soft} d=\"M7 3.5h10v5.5a5 5 0 0 1-10 0z\"/><path d=\"M7 5.5H4.5v1.2a3.3 3.3 0 0 0 3.2 3.3M17 5.5h2.5v1.2a3.3 3.3 0 0 1-3.2 3.3M12 14v3.5M8.5 20.5h7M9.5 17.5h5\"/>",
        'megaphone' => "<path {$soft} d=\"M3 10.5v3a1 1 0 0 0 1 1h2.5L13 19.5v-15L6.5 9.5H4a1 1 0 0 0-1 1z\"/><path d=\"M16.5 9.2a3.8 3.8 0 0 1 0 5.6M19.3 6.6a7.6 7.6 0 0 1 0 10.8\"/>",
        'tag' => "<path {$soft} d=\"M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z\"/><circle cx=\"7.5\" cy=\"7.5\" r=\"1.5\"/><path d=\"M10 15l5-5\"/>",
        'bag' => "<path {$soft} d=\"M5 8h14l-1.1 11.3a1.5 1.5 0 0 1-1.5 1.2H7.6a1.5 1.5 0 0 1-1.5-1.2z\"/><path d=\"M9 10V6.5a3 3 0 0 1 6 0V10\"/><path {$solid} d=\"M12 12.3l.7 1.6 1.6.7-1.6.7-.7 1.6-.7-1.6-1.6-.7 1.6-.7z\"/>",
        'burger' => "<path {$soft} d=\"M4 11a8 8 0 0 1 16 0z\"/><path d=\"M4 11a8 8 0 0 1 16 0zM3 14.5h18M4.5 17.5h15l-.6 1.7a1.5 1.5 0 0 1-1.4 1H6.5a1.5 1.5 0 0 1-1.4-1z\"/><circle {$solid} cx=\"9.5\" cy=\"7.5\" r=\".7\"/><circle {$solid} cx=\"13.5\" cy=\"6.8\" r=\".7\"/>",
        'coupon' => "<path {$soft} d=\"M3 8.5V6.5a1.5 1.5 0 0 1 1.5-1.5h15A1.5 1.5 0 0 1 21 6.5v2a3.5 3.5 0 0 0 0 7v2a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5v-2a3.5 3.5 0 0 0 0-7z\"/><path d=\"M9.5 14.5l5-5\"/><circle {$solid} cx=\"9.7\" cy=\"9.7\" r=\"1\"/><circle {$solid} cx=\"14.3\" cy=\"14.3\" r=\"1\"/>",
        'party' => "<path {$soft} d=\"M3.5 20.5L8.3 7.7l8 8z\"/><path d=\"M13 7.5c.4-1.8 1.8-3 3.6-3.1M16.5 11c1.6-.6 3-.3 4 .8M11.5 3v1.6M20.5 6.5l-1.3.6M16 2.5l.5 1.3\"/>",
        'cocktail' => "<path {$soft} d=\"M4.5 4.5h15L12 12.5z\"/><path d=\"M4.5 4.5h15L12 12.5zM12 12.5v7M8.5 20.5h7M7 7.5h10\"/><path d=\"M15.5 4.5L18 2\"/>",
        'gift' => "<rect {$soft} x=\"3.5\" y=\"8\" width=\"17\" height=\"4.5\" rx=\"1\"/><path d=\"M3.5 9a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v2.5a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1zM5 12.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-7.5M12 8v13\"/><path d=\"M12 8C10.2 4.5 6.5 4.3 6.5 6.4S9.3 8 12 8zM12 8c1.8-3.5 5.5-3.7 5.5-1.6S14.7 8 12 8z\"/>",
        'tent' => "<path {$soft} d=\"M12 5L3 20.5h18z\"/><path d=\"M12 5L3 20.5h18zM12 5V2M12 2l3.5 1.2L12 4.4M12 11l-3.2 9.5M12 11l3.2 9.5\"/>",
        'family' => "<circle {$soft} cx=\"8\" cy=\"7\" r=\"2.8\"/><circle {$soft} cx=\"17\" cy=\"9.5\" r=\"2.2\"/><path d=\"M3 20a5 5 0 0 1 10 0M13.2 20a3.9 3.9 0 0 1 7.8 0\"/>",
        'clipboard' => "<rect {$soft} x=\"5\" y=\"4.5\" width=\"14\" height=\"16.5\" rx=\"2\"/><path d=\"M15 4.5h2a2 2 0 0 1 2 2V19a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6.5a2 2 0 0 1 2-2h2\"/><rect x=\"9\" y=\"3\" width=\"6\" height=\"3\" rx=\"1\"/><path d=\"M8.5 11h7M8.5 14.5h7M8.5 18h4\"/>",
        'loop' => "<path d=\"M4 12a8 8 0 0 1 13.7-5.6L20 8.5M20 4v4.5h-4.5\"/><path d=\"M20 12a8 8 0 0 1-13.7 5.6L4 15.5M4 20v-4.5h4.5\"/>",
        'timeline' => "<path d=\"M3.5 6h9M3.5 12h17M3.5 18h6\"/><circle {$soft} cx=\"16\" cy=\"6\" r=\"2.5\"/><circle {$soft} cx=\"13\" cy=\"18\" r=\"2.5\"/>",
        'device' => "<rect {$soft} x=\"6\" y=\"2.5\" width=\"12\" height=\"19\" rx=\"2.5\"/><path d=\"M10.5 18.5h3\"/>",
        'code' => "<rect {$soft} x=\"2.5\" y=\"4\" width=\"19\" height=\"16\" rx=\"2.5\"/><rect x=\"2.5\" y=\"4\" width=\"19\" height=\"16\" rx=\"2.5\"/><path d=\"M9.5 9.5L7 12l2.5 2.5M14.5 9.5L17 12l-2.5 2.5M12.8 8.5l-1.6 7\"/>",
        'cloud' => "<path {$soft} d=\"M7 18.5a4.5 4.5 0 0 1-.6-9 6 6 0 0 1 11.5 1.6A3.8 3.8 0 0 1 17.5 18.5z\"/><path d=\"M7 18.5a4.5 4.5 0 0 1-.6-9 6 6 0 0 1 11.5 1.6A3.8 3.8 0 0 1 17.5 18.5z\"/>",
        'globe' => "<circle {$soft} cx=\"12\" cy=\"12\" r=\"9\"/><circle cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M3 12h18M12 3c2.5 2.6 3.7 5.6 3.7 9s-1.2 6.4-3.7 9c-2.5-2.6-3.7-5.6-3.7-9S9.5 5.6 12 3z\"/>",
        'rocket' => "<path {$soft} d=\"M14.5 3.5c3.4-.6 6 .1 6 .1s.7 2.6.1 6c-.6 3.3-3.3 6.4-6.6 8.4l-4-4c2-3.3 5.1-6 8.5-6.6\"/><path d=\"M10 14l-4-4c2.6-4.6 6.9-7.2 14.5-6.5.7 7.6-1.9 11.9-6.5 14.5zM10 14l-3 3M6.5 10.5H3.5l2.6-2.6a2 2 0 0 1 1.7-.6l2.2.3M13.5 17.5v3l2.6-2.6a2 2 0 0 0 .6-1.7l-.3-2.2\"/><circle {$solid} cx=\"15.5\" cy=\"8.5\" r=\"1.4\"/>",
        'book' => "<path {$soft} d=\"M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z\"/><path d=\"M4 20.5V5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5 2.5 2.5 0 0 0 6.5 23H20v-5M8.5 8h7M8.5 11.5h5\"/>",
        'chat' => "<path {$soft} d=\"M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 4v-4h0A1.5 1.5 0 0 1 4 14.5z\"/><path d=\"M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 4v-4A1.5 1.5 0 0 1 4 14.5zM8.5 8.5h7M8.5 11.5h4\"/>",
        'upload' => "<path {$soft} d=\"M4 15.5v3A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5v-3z\"/><path d=\"M4 15.5v3A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5v-3M12 15V3.5M7.5 8L12 3.5 16.5 8\"/>",
        'pin' => "<path {$soft} d=\"M12 21.5s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12z\"/><path d=\"M12 21.5s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12z\"/><circle cx=\"12\" cy=\"9.5\" r=\"2.5\"/>",
        'clock' => "<circle {$soft} cx=\"12\" cy=\"12\" r=\"9\"/><circle cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M12 7v5l3.5 2\"/>",
        'briefcase' => "<rect {$soft} x=\"3\" y=\"7\" width=\"18\" height=\"13\" rx=\"2.5\"/><rect x=\"3\" y=\"7\" width=\"18\" height=\"13\" rx=\"2.5\"/><path d=\"M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7M3 12.5h18M10.5 12.5v2h3v-2\"/>",
        'check' => "<circle {$soft} cx=\"12\" cy=\"12\" r=\"9\"/><circle cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M8 12.5l2.7 2.7L16.5 9.5\"/>",
        'mail' => "<rect {$soft} x=\"3\" y=\"5\" width=\"18\" height=\"14\" rx=\"2.5\"/><rect x=\"3\" y=\"5\" width=\"18\" height=\"14\" rx=\"2.5\"/><path d=\"M4 7l8 6 8-6\"/>",
        'phone' => "<path {$soft} d=\"M5 3.5h3l1.8 4.5-2.3 1.4a11 11 0 0 0 5.1 5.1l1.4-2.3 4.5 1.8v3a2 2 0 0 1-2 2A16 16 0 0 1 3 5.5a2 2 0 0 1 2-2z\"/><path d=\"M5 3.5h3l1.8 4.5-2.3 1.4a11 11 0 0 0 5.1 5.1l1.4-2.3 4.5 1.8v3a2 2 0 0 1-2 2A16 16 0 0 1 3 5.5a2 2 0 0 1 2-2z\"/>",
        'article' => "<rect {$soft} x=\"4\" y=\"3\" width=\"16\" height=\"18\" rx=\"2.5\"/><rect x=\"4\" y=\"3\" width=\"16\" height=\"18\" rx=\"2.5\"/><path d=\"M8 8h8M8 12h8M8 16h5\"/>",
        'image' => "<rect {$soft} x=\"3\" y=\"4\" width=\"18\" height=\"16\" rx=\"2.5\"/><rect x=\"3\" y=\"4\" width=\"18\" height=\"16\" rx=\"2.5\"/><circle cx=\"9\" cy=\"9.5\" r=\"1.8\"/><path d=\"M21 15.5l-5-5-9 9\"/>",
        'handshake' => "<path {$soft} d=\"M2.5 11.5l4-4 3.5 1.5 2-1.5 3 0 6.5 4-3.5 5.5-2 1.5-2.5-1-2.5 1.5-5-4.5z\"/><path d=\"M2.5 11.5l4-4 3.5 1.5M21.5 11.5l-4-4h-4.3L9.5 10.6a1.6 1.6 0 0 0 2 2.4l2.3-1.6 4.7 4.1M18.5 15.5l-1.7 1.8M15.6 13.4l-2 2.3M8 14.5l2.3 2.1M10.6 12.8l2.4 2.2M5.6 13.6l1.7 1.6\"/>",
        'search' => "<circle {$soft} cx=\"11\" cy=\"11\" r=\"7\"/><circle cx=\"11\" cy=\"11\" r=\"7\"/><path d=\"M20.5 20.5L16 16\"/>",
        'play' => "<circle {$soft} cx=\"12\" cy=\"12\" r=\"9\"/><circle cx=\"12\" cy=\"12\" r=\"9\"/><path {$solid} d=\"M10 8.5v7l6-3.5z\"/>",
        'flame' => "<path {$soft} d=\"M12 2.5c.8 3.4 5 5.4 5 10.2a5 5 0 0 1-10 0c0-2.1 1-3.6 2.1-4.7.3 1.6 1.2 2.6 2 2.6-.9-3-.2-5.8.9-8.1z\"/><path {$solid} d=\"M12 20.2a2.3 2.3 0 0 1-2.3-2.3c0-1.4 1.2-2.2 2.3-3.5 1.1 1.3 2.3 2.1 2.3 3.5a2.3 2.3 0 0 1-2.3 2.3z\"/>",
    ];
@endphp

<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>{!! $icons[$name] ?? $icons['gamepad'] !!}</svg>
