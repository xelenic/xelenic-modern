/* Kiosk Studio's sticker art: colourful SVG illustrations for every game
   piece and sticker, used instead of emoji. Stickers and game items are still
   stored as the emoji character they stand for (so older designs, templates
   and uploaded games keep working); the player, the games and the editor draw
   the illustration in its place.

   KioskGames() (games.js) calls this, and an exported kiosk carries its
   source along with the games and the player, so it works offline. Load it
   before games.js. */
function KioskArt() {
  /* ---- Building blocks ---- */
  const lin = function (id, a, b, x2, y2) {
    return '<linearGradient id="' + id + '" x1="0" y1="0" x2="' + (x2 == null ? 0 : x2) + '" y2="' + (y2 == null ? 1 : y2) + '"><stop offset="0" stop-color="' + a + '"/><stop offset="1" stop-color="' + b + '"/></linearGradient>';
  };
  const rad = function (id, a, b, cx, cy) {
    return '<radialGradient id="' + id + '" cx="' + (cx == null ? 0.36 : cx) + '" cy="' + (cy == null ? 0.3 : cy) + '" r=".8"><stop offset="0" stop-color="' + a + '"/><stop offset="1" stop-color="' + b + '"/></radialGradient>';
  };
  const svg = function (defs, body) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' + (defs ? '<defs>' + defs + '</defs>' : '') + body + '</svg>';
  };
  // A soft highlight.
  const shine = function (cx, cy, rx, ry, rot, o) {
    return '<ellipse cx="' + cx + '" cy="' + cy + '" rx="' + rx + '" ry="' + ry + '" fill="#fff" opacity="' + (o == null ? 0.45 : o) + '"' + (rot ? ' transform="rotate(' + rot + ' ' + cx + ' ' + cy + ')"' : '') + '/>';
  };
  const leaf = function (d, c) { return '<path d="' + d + '" fill="' + (c || '#4caf50') + '"/>'; };
  const STAR = 'M32 6L39.1 24.3L58.6 25.3L43.4 37.7L48.5 56.7L32 46L15.5 56.7L20.6 37.7L5.4 25.3L24.9 24.3Z';
  const tiny = function (x, y, s, c) { // a four-point twinkle
    return '<path transform="translate(' + x + ' ' + y + ') scale(' + s + ')" d="M0-10L2.1-2.1L10 0L2.1 2.1L0 10L-2.1 2.1L-10 0L-2.1-2.1Z" fill="' + (c || '#ffd54a') + '"/>';
  };
  // A round yellow face, with features on top.
  const face = function (features, extra) {
    return svg(rad('f', '#ffe77a', '#f5a524'),
      (extra || '') + '<circle cx="32" cy="34" r="27" fill="url(#f)"/>' + shine(22, 19, 9, 5, -30, 0.4) + features);
  };
  const eyes = '<ellipse cx="23" cy="29" rx="3.2" ry="4.4" fill="#5b3000"/><ellipse cx="41" cy="29" rx="3.2" ry="4.4" fill="#5b3000"/>';
  const grinMouth = '<path d="M17 38h30c0 9-7 15-15 15s-15-6-15-15z" fill="#7a3a00"/><path d="M19.5 38h25v3.2h-25z" fill="#fff"/><path d="M24 49.5c2.5-3 13.5-3 16 0-2.4 2-5 3-8 3s-5.6-1-8-3z" fill="#e2464a"/>';

  /* ---- The illustrations, by name ---- */
  const A = {
    // Fruit
    apple: svg(rad('a', '#ff6b6b', '#c3161c'),
      '<path d="M32 19c-5-4-14-4-19 2-6 7-4 19 2 27 4 6 9 9 13 7 2.5-1 5.5-1 8 0 4 2 9-1 13-7 6-8 8-20 2-27-5-6-14-6-19-2z" fill="url(#a)"/>' +
      '<path d="M32 20c0-5 1.5-9 4.5-12" stroke="#6d3b12" stroke-width="3.2" stroke-linecap="round" fill="none"/>' +
      leaf('M35 14c4-6 12-7 16-3-4 6-11 7-16 3z', '#43a047') + shine(20, 27, 4, 7.5, 20)),
    greenApple: svg(rad('a', '#c5f26b', '#4e9a1c'),
      '<path d="M32 19c-5-4-14-4-19 2-6 7-4 19 2 27 4 6 9 9 13 7 2.5-1 5.5-1 8 0 4 2 9-1 13-7 6-8 8-20 2-27-5-6-14-6-19-2z" fill="url(#a)"/>' +
      '<path d="M32 20c0-5 1.5-9 4.5-12" stroke="#6d3b12" stroke-width="3.2" stroke-linecap="round" fill="none"/>' +
      leaf('M35 14c4-6 12-7 16-3-4 6-11 7-16 3z', '#2e7d32') + shine(20, 27, 4, 7.5, 20)),
    orange: svg(rad('a', '#ffc04d', '#f0700a'),
      '<circle cx="32" cy="35" r="24" fill="url(#a)"/>' +
      '<g fill="#e0630a" opacity=".45"><circle cx="24" cy="44" r="1.2"/><circle cx="40" cy="45" r="1.2"/><circle cx="45" cy="34" r="1.2"/><circle cx="34" cy="52" r="1.2"/><circle cx="19" cy="35" r="1.2"/></g>' +
      '<circle cx="32" cy="13" r="2.2" fill="#8a5a10"/>' + leaf('M33 12c3-6 10-7 14-4-4 5-10 6-14 4z', '#43a047') + shine(23, 26, 6, 8.5, 25)),
    banana: svg(lin('a', '#fff27a', '#f5b800', 1, 1),
      '<path d="M11 13c-3 21 9 39 31 44 7 2 12-1 11-6-19-3-31-18-31-36 0-6-10-8-11-2z" fill="url(#a)"/>' +
      '<path d="M15 20c1 16 10 28 26 33" stroke="#e0a000" stroke-width="2.4" fill="none" stroke-linecap="round" opacity=".55"/>' +
      '<path d="M10 14c-1-5 2-8 6-7l1 5z" fill="#6d4c17"/><path d="M52 50c3 0 4 3 2 5l-4-2z" fill="#6d4c17"/>' + shine(17, 28, 2.4, 9, -18, 0.55)),
    strawberry: svg(lin('a', '#ff5a6e', '#c4102a'),
      '<path d="M32 59C17 51 9 37 11 26c2-8 10-10 21-10s19 2 21 10c2 11-6 25-21 33z" fill="url(#a)"/>' +
      '<g fill="#ffe082">' + [[22, 27], [32, 25], [42, 27], [18, 36], [28, 34], [38, 34], [46, 36], [24, 43], [34, 42], [42, 44], [29, 50], [36, 51]].map(function (p) { return '<ellipse cx="' + p[0] + '" cy="' + p[1] + '" rx="1.3" ry="2"/>'; }).join('') + '</g>' +
      '<path d="M32 18l-8-7 3 8-9-2 7 6c5 1 9 1 14 0l7-6-9 2 3-8z" fill="#43a047"/>' + shine(20, 31, 3.5, 7, 15)),
    grapes: svg(rad('a', '#b57bff', '#5b1fa8'),
      '<path d="M32 8c0 5-1 8-3 10" stroke="#6d4c17" stroke-width="3" stroke-linecap="round" fill="none"/>' + leaf('M33 12c4-5 12-5 15-1-4 5-11 5-15 1z', '#43a047') +
      [[22, 23], [32, 21], [42, 23], [17, 33], [27, 32], [37, 32], [47, 33], [22, 42], [32, 42], [42, 42], [27, 51], [37, 51], [32, 59]].map(function (p) {
        return '<circle cx="' + p[0] + '" cy="' + p[1] + '" r="6.4" fill="url(#a)"/><circle cx="' + (p[0] - 2) + '" cy="' + (p[1] - 2.2) + '" r="1.6" fill="#fff" opacity=".55"/>';
      }).join('')),
    watermelon: svg(lin('r', '#ff6b7d', '#e8213f'),
      '<path d="M4 24h56a28 28 0 0 1-56 0z" fill="#2e9b45"/><path d="M7.5 24h49a24.5 24.5 0 0 1-49 0z" fill="#e8f5c8"/>' +
      '<path d="M10.5 24h43a21.5 21.5 0 0 1-43 0z" fill="url(#r)"/>' +
      '<g fill="#2b1b17">' + [[21, 31], [32, 34], [43, 31], [26, 40], [38, 40], [32, 46]].map(function (p) { return '<ellipse cx="' + p[0] + '" cy="' + p[1] + '" rx="1.6" ry="2.6"/>'; }).join('') + '</g>' +
      '<path d="M4 24h56" stroke="#1f7a33" stroke-width="2"/>'),
    kiwi: svg('',
      '<circle cx="32" cy="32" r="27" fill="#8d6234"/><circle cx="32" cy="32" r="23.5" fill="#8bc34a"/><circle cx="32" cy="32" r="18" fill="#a5d95a"/>' +
      '<ellipse cx="32" cy="32" rx="7" ry="5.5" fill="#f4f8d8"/>' +
      Array.from({ length: 12 }, function (_, i) { const a = i * Math.PI / 6; return '<ellipse cx="' + (32 + 11 * Math.cos(a)).toFixed(1) + '" cy="' + (32 + 11 * Math.sin(a)).toFixed(1) + '" rx="1.1" ry="2" fill="#1d1d1d" transform="rotate(' + (i * 30 + 90) + ' ' + (32 + 11 * Math.cos(a)).toFixed(1) + ' ' + (32 + 11 * Math.sin(a)).toFixed(1) + ')"/>'; }).join('')),
    pineapple: svg(lin('a', '#ffd54a', '#f08a00'),
      '<path d="M32 22l-6-14 6 6 2-11 3 11 7-7-5 15z" fill="#3a9a45"/><path d="M32 24l-12-8 8 9z" fill="#2e7d32"/><path d="M32 24l12-8-8 9z" fill="#2e7d32"/>' +
      '<ellipse cx="32" cy="42" rx="16" ry="19" fill="url(#a)"/>' +
      '<g stroke="#c26a00" stroke-width="1.6" opacity=".7" fill="none"><path d="M20 30l22 24M16 40l16 18M26 24l20 22M22 54l20-26M17 46l18-22M28 58l17-20"/></g>' + shine(25, 35, 3.5, 7, 10, 0.35)),
    lemon: svg(rad('a', '#fff59d', '#f2c200'),
      '<path d="M8 33c4-12 16-19 28-17 9 1 15 6 20 11 2 1 2 4 0 5-4 12-16 19-28 17-9-1-15-6-20-11-2-1-2-4 0-5z" fill="url(#a)"/>' + shine(26, 24, 9, 4, -12)),
    peach: svg(rad('a', '#ffd3a8', '#f2705b', 0.4, 0.35),
      '<path d="M32 17c-14-4-24 6-24 20 0 12 10 21 24 21s24-9 24-21c0-14-10-24-24-20z" fill="url(#a)"/>' +
      '<path d="M32 18c-4 10-4 26 1 38" stroke="#e2584a" stroke-width="2" fill="none" opacity=".5"/>' + leaf('M33 17c3-7 11-9 15-5-4 6-10 7-15 5z', '#43a047') + shine(21, 31, 4, 7, 20)),
    cherries: svg(rad('a', '#ff5f6d', '#a10f22'),
      '<path d="M21 42C24 28 30 16 40 8M43 42c-1-12-1-22-3-34" stroke="#4e7d1f" stroke-width="2.6" fill="none" stroke-linecap="round"/>' + leaf('M40 8c5-3 13-2 15 3-5 3-12 2-15-3z', '#43a047') +
      '<circle cx="20" cy="46" r="12" fill="url(#a)"/><circle cx="44" cy="46" r="12" fill="url(#a)"/>' + shine(15, 41, 2.5, 4, 25) + shine(39, 41, 2.5, 4, 25)),
    coconut: svg(rad('a', '#a06a3a', '#4e2c12'),
      '<circle cx="32" cy="34" r="25" fill="url(#a)"/><path d="M9 34a23 23 0 0 0 46 0z" fill="#fffaf0"/><path d="M13 34a19 19 0 0 0 38 0z" fill="#f4ead8"/>' +
      '<path d="M7 34h50" stroke="#3a1f0a" stroke-width="3"/><g fill="#3a1f0a"><circle cx="24" cy="20" r="2"/><circle cx="32" cy="16" r="2"/><circle cx="40" cy="20" r="2"/></g>'),
    mango: svg(lin('a', '#ffe14d', '#ff5a2e', 1, 1),
      '<path d="M15 22c7-10 22-12 32-4 10 9 10 25 1 34-9 8-25 8-33-1-8-9-6-21 0-29z" fill="url(#a)"/>' + leaf('M30 15c2-7 10-10 15-7-3 6-10 8-15 7z', '#43a047') + shine(23, 29, 4, 8, 30)),
    pear: svg(rad('a', '#e8f07a', '#9bb51e'),
      '<path d="M32 12c-6 0-8 6-8 11 0 5-9 9-9 20 0 10 7 17 17 17s17-7 17-17c0-11-9-15-9-20 0-5-2-11-8-11z" fill="url(#a)"/>' +
      '<path d="M32 13c0-3 1-6 3-8" stroke="#6d3b12" stroke-width="2.6" stroke-linecap="round" fill="none"/>' + leaf('M34 9c3-4 9-4 12-1-3 4-9 4-12 1z', '#43a047') + shine(25, 41, 3.5, 7, 15)),
    blueberries: svg(rad('a', '#6f8cff', '#1f2f8f'),
      [[22, 40, 13], [42, 40, 13], [32, 24, 12]].map(function (b) {
        return '<circle cx="' + b[0] + '" cy="' + b[1] + '" r="' + b[2] + '" fill="url(#a)"/><path transform="translate(' + b[0] + ' ' + (b[1] - b[2] * 0.45) + ')" d="M0-3.5L1.2-1 3.5-1 1.6.6 2.2 3 0 1.7-2.2 3-1.6.6-3.5-1-1.2-1z" fill="#16206a"/>' + shine(b[0] - 5, b[1] - 3, 2, 3.5, 25, 0.35);
      }).join('')),

    // Game pieces
    bomb: svg(rad('a', '#5b5f6e', '#101218'),
      '<circle cx="28" cy="38" r="21" fill="url(#a)"/><rect x="34" y="12" width="12" height="9" rx="2" transform="rotate(35 40 16)" fill="#6b7080"/>' +
      '<path d="M44 12c3-4 6-5 9-4" stroke="#a1887f" stroke-width="2.6" fill="none" stroke-linecap="round"/>' +
      '<path transform="translate(55 7)" d="M0-7L1.8-2 7-2.2 2.8.9 4.4 6 0 3 -4.4 6 -2.8.9 -7-2.2 -1.8-2z" fill="#ffb300"/><circle cx="55" cy="7" r="2.2" fill="#fff59d"/>' + shine(19, 29, 5, 8, 30, 0.3)),
    star: svg(lin('a', '#fff176', '#f59e0b'),
      '<path d="' + STAR + '" fill="url(#a)" stroke="#e08a00" stroke-width="2" stroke-linejoin="round"/>' +
      '<path d="M32 13l4.4 11.6 12.4.6-9.6 7.8" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/>'),
    glowingStar: svg(rad('g', '#fff8c4', 'rgba(255,214,79,0)', 0.5, 0.5) + lin('a', '#fff176', '#f59e0b'),
      '<circle cx="32" cy="34" r="30" fill="url(#g)"/><path transform="translate(32 34) scale(.78) translate(-32 -34)" d="' + STAR + '" fill="url(#a)" stroke="#e08a00" stroke-width="2.4" stroke-linejoin="round"/>' +
      tiny(10, 12, 0.55, '#fff') + tiny(54, 14, 0.45, '#fff') + tiny(52, 54, 0.4, '#fff')),
    coin: svg(rad('a', '#ffe680', '#d98e04'),
      '<circle cx="32" cy="32" r="27" fill="#c27c00"/><circle cx="32" cy="30" r="27" fill="url(#a)"/><circle cx="32" cy="30" r="20" fill="none" stroke="#e8a317" stroke-width="2.6"/>' +
      '<path d="M32 19L34.8 26.1L42.5 26.6L36.6 31.5L38.5 38.9L32 34.8L25.5 38.9L27.4 31.5L21.5 26.6L29.2 26.1Z" fill="#f4b400"/>' + shine(21, 17, 6, 3.5, -30, 0.55)),
    gem: svg(lin('a', '#9ff3ff', '#1e88e5') + lin('b', '#5ad1ff', '#0d47a1'),
      '<path d="M14 22l9-11h18l9 11L32 56z" fill="url(#b)"/><path d="M14 22h36L32 56z" fill="url(#a)"/>' +
      '<path d="M23 11l-4 11 13 34 13-34-4-11M19 22l13-11 13 11" fill="none" stroke="#e1f8ff" stroke-width="1.4" opacity=".7"/>' + '<path d="M23 11h9l-9 11z" fill="#fff" opacity=".45"/>'),
    cactus: svg(lin('a', '#66bb6a', '#2e7d32', 1, 0),
      '<path d="M26 58V18a6 6 0 0 1 12 0v40z" fill="url(#a)"/><path d="M26 40h-8a6 6 0 0 1-6-6v-8a3.5 3.5 0 0 1 7 0v7h7zM38 34h7v-9a3.5 3.5 0 0 1 7 0v9a6 6 0 0 1-6 6h-8z" fill="url(#a)"/>' +
      '<g stroke="#c8e6c9" stroke-width="1.4" stroke-linecap="round"><path d="M29 22l-2-1M35 28l2-1M29 34l-2-1M35 44l2-1M29 50l-2-1M14 30l-2-1M50 30l2-1"/></g>' +
      '<path d="M14 58h36" stroke="#d4a373" stroke-width="5" stroke-linecap="round"/>'),
    rock: svg(lin('a', '#b0bec5', '#546e7a', 1, 1),
      '<path d="M8 50l6-20 14-12 16 4 12 14 2 14z" fill="url(#a)"/><path d="M14 30l12 6 2-18M28 36l16-14M28 36l2 14M44 22l2 18 12 10" fill="none" stroke="#eceff1" stroke-width="1.4" opacity=".5"/><path d="M6 52h52" stroke="#455a64" stroke-width="3" stroke-linecap="round"/>'),
    crate: svg(lin('a', '#e0a060', '#a0602a'),
      '<rect x="9" y="11" width="46" height="44" rx="4" fill="url(#a)"/><rect x="9" y="11" width="46" height="44" rx="4" fill="none" stroke="#7a4518" stroke-width="3"/>' +
      '<path d="M9 23h46M9 43h46" stroke="#7a4518" stroke-width="2.4"/><path d="M13 43l38-20" stroke="#7a4518" stroke-width="5"/><path d="M13 43l38-20" stroke="#d08c4c" stroke-width="2.4"/>'),
    hamster: svg(rad('a', '#ffc779', '#d97a1e'),
      '<circle cx="16" cy="18" r="7" fill="#d97a1e"/><circle cx="48" cy="18" r="7" fill="#d97a1e"/><circle cx="16" cy="18" r="3.5" fill="#ff9aa2"/><circle cx="48" cy="18" r="3.5" fill="#ff9aa2"/>' +
      '<ellipse cx="32" cy="36" rx="24" ry="22" fill="url(#a)"/><ellipse cx="32" cy="44" rx="13" ry="10" fill="#fff4e0"/>' +
      '<circle cx="23" cy="32" r="3.3" fill="#2b1b10"/><circle cx="41" cy="32" r="3.3" fill="#2b1b10"/><circle cx="24" cy="31" r="1" fill="#fff"/><circle cx="42" cy="31" r="1" fill="#fff"/>' +
      '<ellipse cx="32" cy="39" rx="2.6" ry="1.8" fill="#e5737f"/><ellipse cx="17" cy="41" rx="4" ry="2.6" fill="#ff9aa2" opacity=".7"/><ellipse cx="47" cy="41" rx="4" ry="2.6" fill="#ff9aa2" opacity=".7"/>'),
    mouse: svg(rad('a', '#e0e4ea', '#8f98a6'),
      '<circle cx="15" cy="20" r="11" fill="#9aa3b0"/><circle cx="49" cy="20" r="11" fill="#9aa3b0"/><circle cx="15" cy="20" r="6.5" fill="#ffb3c1"/><circle cx="49" cy="20" r="6.5" fill="#ffb3c1"/>' +
      '<ellipse cx="32" cy="38" rx="19" ry="19" fill="url(#a)"/>' +
      '<circle cx="25" cy="35" r="3" fill="#20242c"/><circle cx="39" cy="35" r="3" fill="#20242c"/><circle cx="26" cy="34" r="1" fill="#fff"/><circle cx="40" cy="34" r="1" fill="#fff"/>' +
      '<circle cx="32" cy="45" r="3" fill="#ff7a93"/><path d="M27 45l-12-2M27 47l-12 2M37 45l12-2M37 47l12 2" stroke="#6b7280" stroke-width="1.2" stroke-linecap="round"/>'),
    rabbit: svg(rad('a', '#ffffff', '#cfd4dc'),
      '<ellipse cx="23" cy="16" rx="6" ry="15" fill="#e8ebf0"/><ellipse cx="41" cy="16" rx="6" ry="15" fill="#e8ebf0"/><ellipse cx="23" cy="17" rx="3" ry="11" fill="#ffb3c1"/><ellipse cx="41" cy="17" rx="3" ry="11" fill="#ffb3c1"/>' +
      '<ellipse cx="32" cy="42" rx="19" ry="17" fill="url(#a)"/>' +
      '<circle cx="25" cy="39" r="3" fill="#20242c"/><circle cx="39" cy="39" r="3" fill="#20242c"/><circle cx="26" cy="38" r="1" fill="#fff"/><circle cx="40" cy="38" r="1" fill="#fff"/>' +
      '<path d="M29.5 46h5L32 49z" fill="#ff7a93"/><path d="M32 49v3" stroke="#9aa3b0" stroke-width="1.4"/><ellipse cx="19" cy="47" rx="3.5" ry="2.2" fill="#ffb3c1" opacity=".7"/><ellipse cx="45" cy="47" rx="3.5" ry="2.2" fill="#ffb3c1" opacity=".7"/>'),
    hammer: svg(lin('h', '#cf8a4a', '#8a5222', 1, 0) + lin('s', '#eceff1', '#78909c'),
      '<path d="M30 30l6 6-20 21a4.2 4.2 0 0 1-6-6z" fill="url(#h)"/>' +
      '<path d="M26 21l17-17 8 8-17 17z" fill="url(#s)"/><path d="M41 10l14 14-5 5-14-14z" fill="#607d8b"/><path d="M26 21l8 8" stroke="#455a64" stroke-width="2"/>' + shine(36, 11, 6, 2, -45, 0.5)),
    basketball: svg(rad('a', '#ffab40', '#e65100'),
      '<circle cx="32" cy="32" r="27" fill="url(#a)"/><g fill="none" stroke="#3e2111" stroke-width="2.4"><path d="M5 32h54M32 5v54"/><path d="M13 13c8 9 8 29 0 38M51 13c-8 9-8 29 0 38"/></g>' + shine(21, 19, 7, 4, -30, 0.35)),
    runner: svg(lin('s', '#4f8bff', '#2a5bd7'),
      // Faces left, like the emoji it stands for (the runner game mirrors it).
      '<circle cx="30" cy="11" r="7" fill="#f5c08a"/><path d="M25 8c2-5 10-5 12 0-4-1-8-1-12 0z" fill="#3b2a20"/>' +
      '<path d="M28 20c6-1 10 3 10 9l-1 10H27l-3-11c-1-5 1-8 4-8z" fill="url(#s)"/>' +
      '<path d="M27 24l-10 6-5-4" stroke="#f5c08a" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
      '<path d="M37 25l9 7 4 9" stroke="#f5c08a" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
      '<path d="M30 38l-6 11-11 2" stroke="#1e2a44" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
      '<path d="M34 38l9 9-1 11" stroke="#1e2a44" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
      '<path d="M8 50l7-1 1 5H9zM39 57l7 0 1 4h-8z" fill="#ff5252"/>'),
    basket: svg(lin('a', '#d9a066', '#8a5a2b'),
      '<path d="M16 28C16 12 48 12 48 28" stroke="#8a5a2b" stroke-width="4" fill="none" stroke-linecap="round"/>' +
      '<path d="M7 28h50l-5 24a6 6 0 0 1-6 5H18a6 6 0 0 1-6-5z" fill="url(#a)"/><path d="M7 28h50v5H7z" fill="#7a4a1f"/>' +
      '<g stroke="#7a4a1f" stroke-width="1.6" opacity=".7"><path d="M10 40h44M11 47h42M20 33l2 23M32 33v24M44 33l-2 23"/></g>'),
    dizzy: svg(lin('a', '#fff176', '#f59e0b'),
      '<path d="M10 40c4-16 30-22 40-8 6 9-2 20-12 18" stroke="#ffd54a" stroke-width="3.5" fill="none" stroke-linecap="round"/>' +
      '<path transform="translate(40 26) scale(.75)" d="M0-14L4.1-4.5 14-4.3 6.3 1.9 9 11.6 0 6 -9 11.6 -6.3 1.9 -14-4.3 -4.1-4.5Z" fill="url(#a)" stroke="#e08a00" stroke-width="1.6" stroke-linejoin="round"/>' + tiny(16, 20, 0.5) + tiny(48, 50, 0.4)),
    alarm: svg(rad('a', '#ff6b6b', '#c62828'),
      '<circle cx="15" cy="13" r="8" fill="#ffb300"/><circle cx="49" cy="13" r="8" fill="#ffb300"/><path d="M18 52l-5 7M46 52l5 7" stroke="#8d6e63" stroke-width="4" stroke-linecap="round"/>' +
      '<circle cx="32" cy="35" r="23" fill="url(#a)"/><circle cx="32" cy="35" r="17.5" fill="#fffdf6"/>' +
      '<path d="M32 23v12l8 5" stroke="#263238" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="32" cy="35" r="2.2" fill="#c62828"/>'),
    smile: face(eyes + '<path d="M20 40c4 7 20 7 24 0" stroke="#7a3a00" stroke-width="3.6" fill="none" stroke-linecap="round"/>'),
    grin: face(eyes + grinMouth),
    sweat: face('<path d="M17 30c2-3 7-3 9 0M38 30c2-3 7-3 9 0" stroke="#5b3000" stroke-width="3" fill="none" stroke-linecap="round"/>' + grinMouth +
      '<path d="M52 8c3 5 5 8 5 11a5 5 0 0 1-10 0c0-3 2-6 5-11z" fill="#4fc3f7"/>' + shine(50, 16, 1.2, 2.4, 0, 0.8)),
    cool: face('<path d="M10 25h44v3c0 6-3 10-9 10s-9-4-10-9h-6c-1 5-4 9-10 9s-9-4-9-10z" fill="#1d2330"/><path d="M14 28c0 4 2 6 5 6" stroke="#5b6b85" stroke-width="2" fill="none" stroke-linecap="round"/>' +
      '<path d="M22 45c6 4 14 4 20-1" stroke="#7a3a00" stroke-width="3.6" fill="none" stroke-linecap="round"/>'),
    starEyes: face('<path transform="translate(23 29) scale(.36)" d="M0-14L4.1-4.5 14-4.3 6.3 1.9 9 11.6 0 6 -9 11.6 -6.3 1.9 -14-4.3 -4.1-4.5Z" fill="#ff3d57"/><path transform="translate(41 29) scale(.36)" d="M0-14L4.1-4.5 14-4.3 6.3 1.9 9 11.6 0 6 -9 11.6 -6.3 1.9 -14-4.3 -4.1-4.5Z" fill="#ff3d57"/>' + grinMouth),
    partyFace: face(eyes + '<path d="M22 41c5 6 15 6 20 0" stroke="#7a3a00" stroke-width="3.6" fill="none" stroke-linecap="round"/><ellipse cx="17" cy="40" rx="4" ry="2.6" fill="#ff8a80" opacity=".7"/><ellipse cx="47" cy="40" rx="4" ry="2.6" fill="#ff8a80" opacity=".7"/>',
      '<path d="M37 10L54 2l-4 18z" fill="#7c4dff"/><path d="M40 9l4 6M46 6l3 6" stroke="#ffd54a" stroke-width="2.4"/><circle cx="54" cy="2.5" r="2.8" fill="#ff4081"/>' + tiny(8, 10, 0.45, '#29b6f6') + tiny(58, 34, 0.4, '#ff4081')),
    oops: face('<path d="M17 29c3 3 7 3 9 0M38 29c3 3 7 3 9 0" stroke="#5b3000" stroke-width="3" fill="none" stroke-linecap="round"/><ellipse cx="32" cy="44" rx="5" ry="4" fill="#7a3a00"/><ellipse cx="17" cy="38" rx="4" ry="2.6" fill="#ff8a80" opacity=".7"/><ellipse cx="47" cy="38" rx="4" ry="2.6" fill="#ff8a80" opacity=".7"/>'),
    party: svg(lin('a', '#ffd54a', '#ff8f00', 1, 1),
      '<path d="M8 58l12-36 22 22z" fill="url(#a)"/><path d="M12 46l6 6M15 37l12 12M18 28l15 15" stroke="#e8453c" stroke-width="3.4"/>' +
      '<path d="M27 21c-2-7 1-12 7-14M40 28c6-4 12-3 16 1M33 24c4-8 10-11 16-9" stroke="#29b6f6" stroke-width="2.8" fill="none" stroke-linecap="round"/>' +
      '<g><rect x="45" y="7" width="5" height="5" rx="1" fill="#ff4081" transform="rotate(20 47 9)"/><rect x="54" y="20" width="5" height="5" rx="1" fill="#7c4dff" transform="rotate(-15 56 22)"/><circle cx="30" cy="8" r="2.6" fill="#66bb6a"/><circle cx="56" cy="40" r="2.6" fill="#ffd54a"/><rect x="38" y="15" width="4" height="4" rx="1" fill="#ffd54a"/></g>'),
    pointer: svg(lin('a', '#ffd8b0', '#f3a56b'),
      '<path d="M26 8a4.5 4.5 0 0 1 9 0v20l3-1a4 4 0 0 1 5 3l2-1a4 4 0 0 1 5 3c3 0 5 2 5 5v10c0 9-7 15-15 15h-6c-7 0-11-4-14-9l-7-12a4 4 0 0 1 7-4l6 8z" fill="url(#a)" stroke="#d98a4e" stroke-width="1.6"/>' +
      '<path d="M35 28v8M43 30v7M50 33v6" stroke="#d98a4e" stroke-width="1.6" stroke-linecap="round"/>' + shine(29, 12, 1.6, 4, 0, 0.6)),
    sparkles: svg(lin('a', '#fff59d', '#ffb300'),
      '<path transform="translate(26 34) scale(2.1)" d="M0-10L2.1-2.1L10 0L2.1 2.1L0 10L-2.1 2.1L-10 0L-2.1-2.1Z" fill="url(#a)"/>' + tiny(48, 14, 1, '#ffd54a') + tiny(50, 48, 0.75, '#ffca28')),

    // Stickers
    gift: svg(lin('a', '#ff5c7a', '#d81b45') + lin('r', '#ffe082', '#ffb300'),
      '<rect x="9" y="27" width="46" height="31" rx="4" fill="url(#a)"/><rect x="6" y="19" width="52" height="11" rx="3" fill="#ff7a93"/>' +
      '<rect x="28" y="19" width="8" height="39" fill="url(#r)"/><path d="M32 19c-8-12-20-11-18-4 1 4 10 5 18 4zM32 19c8-12 20-11 18-4-1 4-10 5-18 4z" fill="url(#r)" stroke="#e09a00" stroke-width="1.4"/>' + shine(18, 36, 3, 6, 0, 0.3)),
    trophy: svg(lin('a', '#ffe680', '#e8a000', 1, 1),
      '<path d="M17 12H8v6c0 8 6 13 12 13M47 12h9v6c0 8-6 13-12 13" stroke="#e8a000" stroke-width="4" fill="none"/>' +
      '<path d="M16 7h32v15c0 10-7 18-16 18s-16-8-16-18z" fill="url(#a)"/><path d="M28 39h8v8h-8z" fill="#d18e00"/>' +
      '<rect x="18" y="47" width="28" height="11" rx="2.5" fill="#6d4c41"/><rect x="23" y="51" width="18" height="3.5" rx="1" fill="#ffd54a"/>' +
      '<path transform="translate(32 21) scale(.5)" d="M0-14L4.1-4.5 14-4.3 6.3 1.9 9 11.6 0 6 -9 11.6 -6.3 1.9 -14-4.3 -4.1-4.5Z" fill="#fff8d0"/>' + shine(22, 15, 2.4, 6, 0, 0.5)),
    brain: svg(lin('a', '#ffb3c7', '#f06292', 1, 1),
      '<path d="M31 10c-4-4-12-3-14 2-6 0-10 6-8 11-4 3-4 10 0 13-1 6 4 11 10 11 2 4 8 6 12 3z" fill="url(#a)"/><path d="M33 10c4-4 12-3 14 2 6 0 10 6 8 11 4 3 4 10 0 13 1 6-4 11-10 11-2 4-8 6-12 3z" fill="url(#a)"/>' +
      '<g fill="none" stroke="#c2185b" stroke-width="2" stroke-linecap="round" opacity=".75"><path d="M32 10v40M17 13c3 2 4 6 2 9M10 24c4 0 7 3 7 6M11 37c3-2 8-1 9 2M20 47c0-4 3-6 6-6M47 13c-3 2-4 6-2 9M54 24c-4 0-7 3-7 6M53 37c-3-2-8-1-9 2M44 47c0-4-3-6-6-6M24 22c3 1 5 4 4 7M40 22c-3 1-5 4-4 7"/></g>'),
    ferrisWheel: svg('',
      '<path d="M32 32L18 60M32 32l14 28M14 60h36" stroke="#5c6bc0" stroke-width="3.6" stroke-linecap="round"/>' +
      '<circle cx="32" cy="30" r="22" fill="none" stroke="#7986cb" stroke-width="3"/><g stroke="#9fa8da" stroke-width="1.8">' +
      Array.from({ length: 8 }, function (_, i) { const a = i * Math.PI / 4; return '<path d="M32 30L' + (32 + 22 * Math.cos(a)).toFixed(1) + ' ' + (30 + 22 * Math.sin(a)).toFixed(1) + '"/>'; }).join('') + '</g>' +
      ['#ef476f', '#ffd166', '#06d6a0', '#118ab2', '#ff8fab', '#8338ec', '#fb5607', '#3a86ff'].map(function (c, i) { const a = i * Math.PI / 4; return '<rect x="' + (32 + 22 * Math.cos(a) - 4.5).toFixed(1) + '" y="' + (30 + 22 * Math.sin(a) - 1).toFixed(1) + '" width="9" height="7" rx="2" fill="' + c + '"/>'; }).join('') +
      '<circle cx="32" cy="30" r="4" fill="#ffd166"/>'),
    cards: svg('',
      '<rect x="12" y="10" width="28" height="40" rx="4" fill="#3949ab" transform="rotate(-14 26 30)"/><rect x="15" y="13" width="22" height="34" rx="2.5" fill="none" stroke="#9fa8da" stroke-width="1.6" transform="rotate(-14 26 30)"/>' +
      '<rect x="24" y="14" width="28" height="40" rx="4" fill="#fff" stroke="#cfd8dc" stroke-width="1.4" transform="rotate(10 38 34)"/>' +
      '<g transform="rotate(10 38 34)"><path d="M38 44c-6-4-9-7-9-11a4.5 4.5 0 0 1 9-1 4.5 4.5 0 0 1 9 1c0 4-3 7-9 11z" fill="#e53935"/><text x="28" y="23" font-family="Arial,sans-serif" font-weight="900" font-size="8" fill="#e53935">A</text></g>'),
    bricks: svg('',
      '<g stroke="#f8d7c8" stroke-width="1.6"><rect x="5" y="9" width="26" height="13" rx="2" fill="#d9534f"/><rect x="33" y="9" width="26" height="13" rx="2" fill="#e8705a"/>' +
      '<rect x="5" y="24" width="13" height="13" rx="2" fill="#e8705a"/><rect x="20" y="24" width="26" height="13" rx="2" fill="#c9443f"/><rect x="48" y="24" width="11" height="13" rx="2" fill="#d9534f"/>' +
      '<rect x="5" y="39" width="26" height="13" rx="2" fill="#c9443f"/><rect x="33" y="39" width="26" height="13" rx="2" fill="#d9534f"/></g>' + shine(14, 13, 6, 1.6, 0, 0.35)),
    ticket: svg(lin('a', '#ff7eb3', '#e91e63', 1, 1),
      '<path d="M5 21v-5a3 3 0 0 1 3-3h48a3 3 0 0 1 3 3v5a7 7 0 0 0 0 14v5a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3v-5a7 7 0 0 0 0-14z" fill="url(#a)" transform="rotate(-10 32 30)"/>' +
      '<g transform="rotate(-10 32 30)"><path d="M42 15v30" stroke="#fff" stroke-width="2" stroke-dasharray="3 3" opacity=".7"/><path transform="translate(24 30) scale(.6)" d="M0-14L4.1-4.5 14-4.3 6.3 1.9 9 11.6 0 6 -9 11.6 -6.3 1.9 -14-4.3 -4.1-4.5Z" fill="#ffd54a"/></g>'),
    gamepad: svg(lin('a', '#7c6cff', '#4b39d6'),
      '<path d="M18 18h28c9 0 14 7 15 16l1 9c1 7-7 11-12 6l-6-6H20l-6 6c-5 5-13 1-12-6l1-9c1-9 6-16 15-16z" fill="url(#a)"/>' +
      '<path d="M16 26v10M11 31h10" stroke="#fff" stroke-width="4" stroke-linecap="round"/><circle cx="44" cy="27" r="3" fill="#ffd166"/><circle cx="51" cy="32" r="3" fill="#ef476f"/><circle cx="44" cy="37" r="3" fill="#06d6a0"/><circle cx="37" cy="32" r="3" fill="#4fc3f7"/>' + shine(22, 21, 7, 2, 0, 0.3)),
    megaphone: svg(lin('a', '#ff6b6b', '#d62839'),
      '<path d="M8 26v12a3 3 0 0 0 3 3h6l24 12V11L17 23h-6a3 3 0 0 0-3 3z" fill="url(#a)"/><path d="M17 23v18" stroke="#fff" stroke-width="3"/><path d="M15 41l4 12h6l-3-12z" fill="#8d6e63"/>' +
      '<path d="M47 24c3 3 3 13 0 16M53 18c6 7 6 21 0 28" stroke="#ffd166" stroke-width="3.2" fill="none" stroke-linecap="round"/>'),
    coffee: svg(lin('a', '#ffffff', '#dfe6ee'),
      '<path d="M24 6c-3 4 3 6 0 10M32 4c-3 4 3 6 0 10M40 6c-3 4 3 6 0 10" stroke="#b0bec5" stroke-width="2.4" fill="none" stroke-linecap="round"/>' +
      '<ellipse cx="32" cy="54" rx="26" ry="5" fill="#cfd8dc"/><path d="M12 22h40l-3 24a8 8 0 0 1-8 7H23a8 8 0 0 1-8-7z" fill="url(#a)"/><path d="M52 27c7 0 9 10 1 14" stroke="#dfe6ee" stroke-width="4" fill="none"/>' +
      '<ellipse cx="32" cy="22" rx="20" ry="4" fill="#6d4c41"/><path d="M15 30h34" stroke="#26a69a" stroke-width="4"/>'),
    iceCream: svg(lin('c', '#f6c27a', '#d18a3c'),
      '<path d="M18 32h28L32 61z" fill="url(#c)"/><path d="M22 36l14 14M28 34l12 12M36 34l-14 14M42 36l-12 12" stroke="#b06a28" stroke-width="1.4"/>' +
      '<path d="M14 32c-3-8 4-13 9-12 0-7 6-11 12-9 6-3 13 2 12 9 5 0 9 6 6 12z" fill="#fff0f5"/><path d="M20 26c4-2 8 1 12-1s8-1 11 1" stroke="#ffb3c7" stroke-width="2.4" fill="none" stroke-linecap="round"/>' +
      '<circle cx="35" cy="9" r="4.5" fill="#e53935"/><path d="M35 5c1-3 3-4 5-4" stroke="#558b2f" stroke-width="1.6" fill="none"/>'),
    question: svg(lin('a', '#ff6b6b', '#d62839'),
      '<path d="M20 22c0-8 6-13 13-13s13 5 13 12c0 10-12 11-12 20" stroke="url(#a)" stroke-width="9" fill="none" stroke-linecap="round" stroke-linejoin="round"/><circle cx="34" cy="54" r="5.5" fill="#d62839"/>' +
      '<path d="M24 20c1-4 4-6 8-6" stroke="#fff" stroke-width="2.6" fill="none" stroke-linecap="round" opacity=".55"/>'),
    sneaker: svg(lin('a', '#4f8bff', '#2a5bd7'),
      '<path d="M6 44c0-6 2-14 6-18l6 4c4 3 9 4 14 3l10-2c5 0 9 3 12 6l4 4c2 2 1 6-2 6H8a2 2 0 0 1-2-2z" fill="url(#a)"/><path d="M5 46h53v5a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3z" fill="#f5f7fa"/>' +
      '<path d="M22 31l4 5M27 30l3 5M32 29l2 5" stroke="#fff" stroke-width="2" stroke-linecap="round"/><path d="M12 26c2-4 3-8 3-12l6 3c0 4-1 9-3 13z" fill="#2a5bd7"/><path d="M40 40c4-1 8 0 11 3" stroke="#ffd166" stroke-width="3" fill="none" stroke-linecap="round"/>'),
    burger: svg(lin('b', '#ffc56e', '#e08a2c'),
      '<path d="M8 26c0-12 11-19 24-19s24 7 24 19z" fill="url(#b)"/><g fill="#fff8e1"><ellipse cx="22" cy="15" rx="2" ry="1.2"/><ellipse cx="32" cy="12" rx="2" ry="1.2"/><ellipse cx="42" cy="15" rx="2" ry="1.2"/><ellipse cx="28" cy="20" rx="2" ry="1.2"/><ellipse cx="38" cy="20" rx="2" ry="1.2"/></g>' +
      '<path d="M6 28c4 4 8-2 12 2s8-2 12 2 8-2 12 2 8-2 12 2l2-6H4z" fill="#66bb6a"/><path d="M8 33h48l-6 6-6-4H8z" fill="#ffd54a"/>' +
      '<rect x="7" y="36" width="50" height="9" rx="4.5" fill="#6d3b1f"/><path d="M9 48h46c0 6-5 9-11 9H20c-6 0-11-3-11-9z" fill="url(#b)"/>'),
    cocktail: svg(lin('a', '#ffd54a', '#ff5252'),
      '<path d="M40 4L33 26" stroke="#ff4081" stroke-width="3" stroke-linecap="round"/><path d="M12 22h40c0 13-8 22-20 22S12 35 12 22z" fill="url(#a)"/><path d="M12 22h40" stroke="#fff" stroke-width="2" opacity=".7"/>' +
      '<path d="M32 44v12M22 59h20" stroke="#b0bec5" stroke-width="3.4" stroke-linecap="round"/>' +
      '<circle cx="48" cy="20" r="7" fill="#c6ff00" stroke="#7cb342" stroke-width="2"/><path d="M48 14v12M42 20h12M44 16l8 8M52 16l-8 8" stroke="#7cb342" stroke-width="1"/>' +
      '<path d="M8 14c5-7 15-7 20 0z" fill="#29b6f6"/><path d="M18 14v10" stroke="#8d6e63" stroke-width="1.8"/>'),
    tag: svg(lin('a', '#ffd54a', '#ff9800', 1, 1),
      '<path d="M8 12a4 4 0 0 1 4-4h20l26 26a4 4 0 0 1 0 6L40 58a4 4 0 0 1-6 0L8 32z" fill="url(#a)"/><circle cx="19" cy="19" r="4.5" fill="#fff8e1"/>' +
      '<path d="M19 19C12 12 6 10 3 12" stroke="#8d6e63" stroke-width="2" fill="none"/><path d="M27 37l14-14M30 26h0M38 34h0" stroke="#c45a00" stroke-width="3.2" stroke-linecap="round"/>'),
    money: svg(lin('a', '#8ee28e', '#2e9b45'),
      '<path d="M6 22c-5-6-4-13 2-14 2 5 6 8 10 9z" fill="#fff" stroke="#cfd8dc" stroke-width="1.2"/><path d="M58 22c5-6 4-13-2-14-2 5-6 8-10 9z" fill="#fff" stroke="#cfd8dc" stroke-width="1.2"/>' +
      '<rect x="8" y="20" width="48" height="30" rx="3.5" fill="url(#a)" transform="rotate(-6 32 35)"/><g transform="rotate(-6 32 35)"><rect x="12" y="24" width="40" height="22" rx="2" fill="none" stroke="#e8f5e9" stroke-width="1.6"/><circle cx="32" cy="35" r="7.5" fill="#e8f5e9"/><path d="M34.6 32.2c-.6-1-1.6-1.5-2.7-1.5-1.6 0-2.7.8-2.7 1.9s1.1 1.7 2.7 2 2.7.8 2.7 2-1.1 1.9-2.7 1.9c-1.2 0-2.2-.5-2.8-1.4M32 29.3v11.4" stroke="#2e7d32" stroke-width="1.8" fill="none" stroke-linecap="round"/></g>'),
    fire: svg(lin('o', '#ffb300', '#ff3d00') + lin('y', '#fff59d', '#ffc107'),
      '<path d="M32 4c4 10 18 16 18 32a18 18 0 0 1-36 0c0-8 4-13 8-17 0 6 3 9 6 9-3-9 0-18 4-24z" fill="url(#o)"/>' +
      '<path d="M32 58a10 10 0 0 1-10-10c0-6 5-9 10-15 5 6 10 9 10 15a10 10 0 0 1-10 10z" fill="url(#y)"/>'),
    bolt: svg(lin('a', '#fff176', '#ffb300', 1, 1),
      '<path d="M37 3L12 36h16l-5 25 25-34H32z" fill="url(#a)" stroke="#f08a00" stroke-width="2" stroke-linejoin="round"/><path d="M34 9L19 31" stroke="#fff" stroke-width="2.4" stroke-linecap="round" opacity=".55"/>'),
    heart: svg(rad('a', '#ff7a8a', '#d1102c', 0.32, 0.25),
      '<path d="M32 56C14 44 5 34 5 23 5 14 12 8 20 8c5 0 9 3 12 7 3-4 7-7 12-7 8 0 15 6 15 15 0 11-9 21-27 33z" fill="url(#a)"/>' + shine(18, 18, 5, 3.4, -35, 0.6)),
    thumbsUp: svg(lin('a', '#ffd95a', '#f5a524'),
      '<rect x="6" y="28" width="12" height="28" rx="3" fill="#3f6fd8"/><path d="M18 30l10-14c2-4 2-8 2-11 4-1 8 2 8 7 0 4-1 7-3 10h15c4 0 7 4 6 8l-4 20c-1 4-4 6-8 6H18z" fill="url(#a)"/>' +
      '<path d="M40 31h12M40 40h11M40 48h9" stroke="#d98a1e" stroke-width="2" stroke-linecap="round"/>'),
    moneyBag: svg(rad('a', '#ffe9a6', '#d4a23a'),
      '<path d="M24 14l-5-9h26l-5 9z" fill="#d4a23a"/><path d="M24 15c-11 7-17 18-17 28 0 9 7 15 25 15s25-6 25-15c0-10-6-21-17-28z" fill="url(#a)"/><path d="M23 15h18" stroke="#8d5b17" stroke-width="3.4" stroke-linecap="round"/>' +
      '<path d="M37.5 33.5c-1.2-2-3.2-3-5.5-3-3.3 0-5.5 1.7-5.5 4s2.2 3.4 5.5 4 5.5 1.7 5.5 4-2.2 4-5.5 4c-2.4 0-4.4-1-5.6-3M32 27.5v24" stroke="#8d5b17" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'),
    target: svg('',
      '<circle cx="30" cy="34" r="25" fill="#e53935"/><circle cx="30" cy="34" r="19" fill="#fff"/><circle cx="30" cy="34" r="13" fill="#e53935"/><circle cx="30" cy="34" r="7" fill="#fff"/><circle cx="30" cy="34" r="3" fill="#e53935"/>' +
      '<path d="M31 33L54 10" stroke="#37474f" stroke-width="3" stroke-linecap="round"/><path d="M54 10l1-7 3 4 4 1-6 3z" fill="#29b6f6"/>'),
    balloon: svg(rad('a', '#ff7a8a', '#d1102c'),
      '<path d="M32 48c-2 4 2 6 0 10" stroke="#9e9e9e" stroke-width="1.8" fill="none"/><ellipse cx="32" cy="25" rx="18" ry="21" fill="url(#a)"/><path d="M29 46h6l-3 3z" fill="#b71c1c"/>' + shine(24, 15, 4, 7, 25, 0.55)),
    rocket: svg(lin('b', '#ffffff', '#cfd8e3', 1, 0),
      '<path d="M26 46c-6 4-8 10-8 16 6 0 12-2 16-8" fill="#ff9100"/><path d="M28 48c-3 3-4 6-4 9 3 0 6-1 8-4" fill="#ffd54a"/>' +
      '<path d="M18 38l-8 2 6-12 8-2zM26 46l-2 8 12-6 2-8z" fill="#e53935"/>' +
      '<path d="M50 6c-14 2-26 14-30 30l8 8c16-4 28-16 30-30 0-5-3-8-8-8z" fill="url(#b)"/><path d="M50 6c5 0 8 3 8 8l-1 5-12-12z" fill="#e53935"/>' +
      '<circle cx="40" cy="24" r="5.5" fill="#29b6f6" stroke="#90a4ae" stroke-width="2.4"/>'),
    stopwatch: svg(lin('a', '#7aa7ff', '#3d5afe'),
      '<rect x="27" y="3" width="10" height="6" rx="2" fill="#546e7a"/><path d="M32 9v4M48 14l3-3" stroke="#546e7a" stroke-width="3.4" stroke-linecap="round"/>' +
      '<circle cx="32" cy="37" r="24" fill="url(#a)"/><circle cx="32" cy="37" r="18" fill="#f7f9ff"/><path d="M32 37V25" stroke="#e53935" stroke-width="3.4" stroke-linecap="round"/><circle cx="32" cy="37" r="2.6" fill="#263238"/>' +
      '<path d="M32 21v2M48 37h-2M32 53v-2M18 37h-2" stroke="#90a4ae" stroke-width="2" stroke-linecap="round"/>'),
    phone: svg(lin('s', '#7c4dff', '#00b8d4', 1, 1),
      '<rect x="15" y="3" width="34" height="58" rx="7" fill="#263238"/><rect x="18" y="9" width="28" height="44" rx="3" fill="url(#s)"/><rect x="27" y="5.5" width="10" height="2" rx="1" fill="#546e7a"/><circle cx="32" cy="57" r="2" fill="#546e7a"/>' +
      '<rect x="22" y="14" width="9" height="9" rx="2" fill="#fff" opacity=".85"/><rect x="33" y="14" width="9" height="9" rx="2" fill="#ffd166" opacity=".9"/><rect x="22" y="26" width="9" height="9" rx="2" fill="#ff8fab" opacity=".9"/><rect x="33" y="26" width="9" height="9" rx="2" fill="#fff" opacity=".85"/>'),
    pin: svg(rad('a', '#ff6b6b', '#c62828'),
      '<ellipse cx="32" cy="58" rx="10" ry="3" fill="#000" opacity=".18"/><path d="M32 58C20 44 12 34 12 24a20 20 0 0 1 40 0c0 10-8 20-20 34z" fill="url(#a)"/><circle cx="32" cy="24" r="8" fill="#fff"/>' + shine(22, 15, 3, 5, 25, 0.45)),
    check: svg(lin('a', '#5be37d', '#1e9e47'),
      '<rect x="6" y="6" width="52" height="52" rx="14" fill="url(#a)"/><path d="M18 33l9 9 19-20" stroke="#fff" stroke-width="7" fill="none" stroke-linecap="round" stroke-linejoin="round"/>' + shine(18, 14, 7, 3, -10, 0.35)),
    cross: svg(lin('a', '#ff6b6b', '#d62839'),
      '<rect x="6" y="6" width="52" height="52" rx="14" fill="url(#a)"/><path d="M21 21l22 22M43 21L21 43" stroke="#fff" stroke-width="7" stroke-linecap="round"/>' + shine(18, 14, 7, 3, -10, 0.35)),
    puzzle: svg(lin('a', '#69e08f', '#22a35a', 1, 1),
      '<path d="M13 19h11A6 6 0 1 1 36 19h11v11A6 6 0 1 1 47 42v11H36A6 6 0 1 0 24 53H13V42A6 6 0 1 0 13 30z" fill="url(#a)" stroke="#1b8a4b" stroke-width="1.6" stroke-linejoin="round"/>' + shine(21, 25, 4.5, 2.4, 0, 0.35)),
    arrowUp: svg(lin('a', '#ffffff', '#dfe6ee'),
      '<path d="M32 6L8 32h14v24h20V32h14z" fill="url(#a)" stroke="#90a4ae" stroke-width="2" stroke-linejoin="round"/>')
  };

  /* ---- Which emoji each one stands for ---- */
  const BY_CHAR = {
    '🍎': 'apple', '🍏': 'greenApple', '🍊': 'orange', '🍌': 'banana', '🍓': 'strawberry', '🍇': 'grapes', '🍉': 'watermelon', '🥝': 'kiwi',
    '🍍': 'pineapple', '🍋': 'lemon', '🍑': 'peach', '🍒': 'cherries', '🥥': 'coconut', '🥭': 'mango', '🍐': 'pear', '🫐': 'blueberries',
    '💣': 'bomb', '⭐': 'star', '🌟': 'glowingStar', '🪙': 'coin', '💎': 'gem', '🌵': 'cactus', '🪨': 'rock', '📦': 'crate',
    '🐹': 'hamster', '🐭': 'mouse', '🐰': 'rabbit', '🔨': 'hammer', '🏀': 'basketball', '🏃': 'runner', '🧺': 'basket', '💫': 'dizzy',
    '⏰': 'alarm', '🙂': 'smile', '😀': 'grin', '😅': 'sweat', '😎': 'cool', '🤩': 'starEyes', '🥳': 'partyFace', '🙈': 'oops',
    '🎉': 'party', '👆': 'pointer', '✨': 'sparkles', '⬆': 'arrowUp', '⬆️': 'arrowUp',
    '🎁': 'gift', '🏆': 'trophy', '🧠': 'brain', '🎡': 'ferrisWheel', '🃏': 'cards', '🧱': 'bricks', '🎟️': 'ticket', '🎟': 'ticket',
    '🎮': 'gamepad', '📣': 'megaphone', '☕': 'coffee', '☕️': 'coffee', '🍦': 'iceCream', '❓': 'question', '👟': 'sneaker', '🍔': 'burger',
    '🍹': 'cocktail', '🏷️': 'tag', '🏷': 'tag', '💸': 'money', '🔥': 'fire', '⚡': 'bolt', '⚡️': 'bolt', '❤️': 'heart', '❤': 'heart',
    '👍': 'thumbsUp', '💰': 'moneyBag', '🎯': 'target', '🎈': 'balloon', '🚀': 'rocket', '⏱️': 'stopwatch', '⏱': 'stopwatch',
    '📱': 'phone', '📍': 'pin', '✅': 'check', '❌': 'cross', '🧩': 'puzzle'
  };

  // People-friendly names, for the editor.
  const LABELS = {
    greenApple: 'Green apple', glowingStar: 'Shining star', ferrisWheel: 'Ferris wheel', iceCream: 'Ice cream', thumbsUp: 'Thumbs up',
    moneyBag: 'Money bag', starEyes: 'Star eyes', partyFace: 'Party face', arrowUp: 'Arrow', cards: 'Playing cards', party: 'Party popper'
  };

  const urls = {};
  const url = function (name) {
    if (!A[name]) return null;
    return urls[name] || (urls[name] = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(A[name]));
  };
  const nameOf = function (ch) {
    if (ch == null) return null;
    const s = String(ch).trim();
    return A[s] ? s : (BY_CHAR[s] || BY_CHAR[s.replace(/️/g, '')] || null);
  };
  // Emoji in a run of text, longest first, so "☕️" wins over "☕".
  const keys = Object.keys(BY_CHAR).sort(function (a, b) { return b.length - a.length; });
  const pattern = new RegExp('(' + keys.map(function (k) { return k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')', 'g');

  // One decoded image per illustration, shared by everything on the page.
  const images = KioskArt.images || (KioskArt.images = {});
  const image = function (name) {
    if (!name || !A[name] || typeof Image === 'undefined') return null;
    if (!images[name]) {
      const img = new Image();
      img.decoding = 'async';
      img.src = url(name);
      images[name] = img;
    }
    return images[name];
  };

  return {
    names: Object.keys(A),
    image: function (ch) { return image(nameOf(ch)); },
    /* Calls fn(img) once the illustration is ready to draw. */
    ready: function (ch, fn) {
      const img = image(nameOf(ch));
      if (!img) return false;
      if (img.complete && img.naturalWidth) fn(img);
      else img.addEventListener('load', function () { fn(img); }, { once: true });
      return true;
    },
    /* An <img> tag for HTML strings, or null. */
    html: function (ch, size, css) {
      const u = this.url(ch);
      return u ? '<img src="' + u + '" alt="" draggable="false" style="display:block;width:' + size + 'px;height:' + size + 'px;margin:0 auto;' + (css || '') + '">' : null;
    },
    svg: function (ch) { const n = nameOf(ch); return n ? A[n] : null; },
    url: function (ch) { const n = nameOf(ch); return n ? url(n) : null; },
    has: function (ch) { return !!nameOf(ch); },
    name: nameOf,
    label: function (ch) {
      const n = nameOf(ch);
      if (!n) return 'Sticker';
      return LABELS[n] || n.charAt(0).toUpperCase() + n.slice(1);
    },
    /* Text split into [string, string or {art: url}, …] pieces. */
    split: function (text) {
      const out = [];
      let last = 0;
      String(text == null ? '' : text).replace(pattern, function (m, _, at) {
        if (at > last) out.push(text.slice(last, at));
        out.push({ art: url(BY_CHAR[m]), ch: m });
        last = at + m.length;
        return m;
      });
      if (last < String(text == null ? '' : text).length) out.push(String(text).slice(last));
      return out;
    },
    /* The text without its emoji (for plain-text places). */
    strip: function (text) { return String(text == null ? '' : text).replace(pattern, '').replace(/\s{2,}/g, ' ').trim(); },
    /* The emoji each sticker stands for, by name (for storing). */
    charOf: function (name) {
      for (const k in BY_CHAR) if (BY_CHAR[k] === name && !/️/.test(k)) return k;
      return null;
    }
  };
}
