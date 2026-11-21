/*
 * Kiosk Studio runtime (the "Player").
 * Renders a project and plays it. Self-contained on purpose: the exported kiosk
 * HTML embeds this function's source (plus KioskGames) so it runs offline.
 */
function KioskRuntime() {
  const FONTS = {
    sans: 'system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
    rounded: '"Arial Rounded MT Bold", "Nunito", "Varela Round", system-ui, sans-serif',
    display: 'Impact, Haettenschweiler, "Arial Narrow Bold", system-ui, sans-serif',
    serif: 'Georgia, "Times New Roman", serif',
    mono: 'ui-monospace, Menlo, Consolas, monospace'
  };
  const EMOJI_FONT = '"Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif';
  const GAMES = KioskGames();

  function injectStyle() {
    if (document.getElementById('kp-style')) return;
    const s = document.createElement('style');
    s.id = 'kp-style';
    s.textContent = [
      '@keyframes kp-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}',
      '@keyframes kp-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5%)}}',
      '@keyframes kp-spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}',
      '@keyframes kp-wiggle{0%,100%{transform:rotate(0)}25%{transform:rotate(-6deg)}75%{transform:rotate(6deg)}}',
      '@keyframes kp-in{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}',
      '@keyframes kp-shake{0%,100%{translate:0}25%{translate:-14px}75%{translate:14px}}',
      '.kp-anim-pulse{animation:kp-pulse 1.6s ease-in-out infinite}',
      '.kp-anim-float{animation:kp-float 3s ease-in-out infinite}',
      '.kp-anim-spin{animation:kp-spin 6s linear infinite}',
      '.kp-anim-wiggle{animation:kp-wiggle 1.2s ease-in-out infinite}',
      '.kp-in{animation:kp-in .35s ease-out}',
      '.kp-press{transition:filter .08s, scale .08s}',
      '.kp-press:active{scale:.96;filter:brightness(.9)}',
      '.kp-bad{animation:kp-shake .3s;border-color:#ff3b30 !important}',
      /* Entrance effects use the individual translate / scale / opacity / filter properties,
         so they combine with looping effects, which use transform. */
      '@keyframes kp-e-fade{from{opacity:0}}',
      '@keyframes kp-e-up{from{opacity:0;translate:0 90px}}',
      '@keyframes kp-e-down{from{opacity:0;translate:0 -90px}}',
      '@keyframes kp-e-left{from{opacity:0;translate:140px 0}}',
      '@keyframes kp-e-right{from{opacity:0;translate:-140px 0}}',
      '@keyframes kp-e-zoom{from{opacity:0;scale:.3}}',
      '@keyframes kp-e-pop{0%{opacity:0;scale:.2}55%{opacity:1;scale:1.14}75%{scale:.95}90%{scale:1.03}100%{scale:1}}',
      '@keyframes kp-e-bounce{0%{opacity:0;translate:0 -500px}50%{opacity:1;translate:0 0}66%{translate:0 -70px}80%{translate:0 0}90%{translate:0 -20px}100%{translate:0 0}}',
      '@keyframes kp-e-jelly{0%{opacity:0;scale:.3 1.5}40%{opacity:1;scale:1.25 .75}60%{scale:.88 1.12}80%{scale:1.05 .95}100%{scale:1 1}}',
      '@keyframes kp-e-blur{from{opacity:0;filter:blur(28px);scale:1.2}}',
      '@keyframes kp-e-flip{from{opacity:0;transform:perspective(1400px) rotateX(-95deg)}}',
      '@keyframes kp-e-spin{from{opacity:0;scale:.2;transform:rotate(-540deg)}}',
      '@keyframes kp-ch-up{from{opacity:0;transform:translateY(.7em) scale(.3)}}',
      '@keyframes kp-ch-type{from{opacity:0}}',
      /* Exit effects end hidden (visibility) so a gone button can no longer be tapped. */
      '@keyframes kp-x-fade{to{opacity:0;visibility:hidden}}',
      '@keyframes kp-x-up{to{opacity:0;translate:0 -110px;visibility:hidden}}',
      '@keyframes kp-x-down{to{opacity:0;translate:0 110px;visibility:hidden}}',
      '@keyframes kp-x-left{to{opacity:0;translate:-160px 0;visibility:hidden}}',
      '@keyframes kp-x-right{to{opacity:0;translate:160px 0;visibility:hidden}}',
      '@keyframes kp-x-zoom{to{opacity:0;scale:.3;visibility:hidden}}',
      '@keyframes kp-x-grow{to{opacity:0;scale:1.7;visibility:hidden}}',
      '@keyframes kp-x-pop{0%{scale:1}30%{scale:1.15;opacity:1}100%{scale:0;opacity:0;visibility:hidden}}',
      '@keyframes kp-x-fall{0%{translate:0 0}25%{translate:0 -50px}100%{translate:0 900px;opacity:0;visibility:hidden}}',
      '@keyframes kp-x-blur{to{opacity:0;filter:blur(28px);scale:1.2;visibility:hidden}}',
      '@keyframes kp-x-flip{to{opacity:0;transform:perspective(1400px) rotateX(95deg);visibility:hidden}}',
      '@keyframes kp-x-spin{to{opacity:0;scale:.2;transform:rotate(540deg);visibility:hidden}}',
      '@keyframes kp-heartbeat{0%,100%{transform:scale(1)}14%{transform:scale(1.14)}28%{transform:scale(1)}42%{transform:scale(1.09)}56%{transform:scale(1)}}',
      '@keyframes kp-swing{0%,100%{transform:rotate(-7deg)}50%{transform:rotate(7deg)}}',
      '@keyframes kp-breathe{0%,100%{transform:scale(1);filter:opacity(1)}50%{transform:scale(.93);filter:opacity(.6)}}',
      '@keyframes kp-blink{0%,100%{filter:opacity(1)}50%{filter:opacity(.2)}}',
      '@keyframes kp-glow{0%,100%{filter:drop-shadow(0 0 0 rgba(255,255,255,0))}50%{filter:drop-shadow(0 0 30px rgba(255,255,255,.95))}}',
      '@keyframes kp-tada{0%,50%,100%{transform:scale(1) rotate(0)}5%,10%{transform:scale(.92) rotate(-4deg)}15%,25%,35%,45%{transform:scale(1.1) rotate(4deg)}20%,30%,40%{transform:scale(1.1) rotate(-4deg)}}',
      '@keyframes kp-shine{0%{transform:translateX(-130%)}45%,100%{transform:translateX(130%)}}',
      '.kp-shine{overflow:hidden}',
      '.kp-shine::after{content:"";position:absolute;inset:0;pointer-events:none;border-radius:inherit;' +
        'background:linear-gradient(105deg,transparent 30%,rgba(255,255,255,.6) 47%,rgba(255,255,255,.2) 55%,transparent 70%);' +
        'transform:translateX(-130%);animation:kp-shine 3s ease-in-out var(--kp-loop-delay,0s) infinite}',
      /* Screen transitions */
      '@keyframes kp-tr-slide{from{opacity:0;transform:translateX(12%)}}',
      '@keyframes kp-tr-zoom{from{opacity:0;transform:scale(1.12)}}',
      '.kp-tr-rise{animation:kp-in .35s ease-out}',
      '.kp-tr-fade{animation:kp-e-fade .5s ease-out}',
      '.kp-tr-slide{animation:kp-tr-slide .45s cubic-bezier(.2,.8,.2,1)}',
      '.kp-tr-zoom{animation:kp-tr-zoom .5s cubic-bezier(.2,.8,.2,1)}',
      /* Animated screen backgrounds */
      '@keyframes kp-fx-drift{from{transform:translate(0,0)}to{transform:translate(var(--dx),var(--dy))}}',
      '@keyframes kp-fx-rise{0%{transform:translate(0,0)}50%{transform:translate(var(--sway),calc(var(--rise) * .5))}100%{transform:translate(0,var(--rise))}}',
      '@keyframes kp-fx-fall{from{transform:translate(0,0) rotate(0deg)}to{transform:translate(var(--sway),var(--fall)) rotate(var(--rot))}}',
      '@keyframes kp-fx-twinkle{0%,100%{opacity:.15;transform:scale(.5)}50%{opacity:1;transform:scale(1)}}',
      '@keyframes kp-fx-sparkle{0%,100%{opacity:0;transform:scale(0) rotate(0deg)}50%{opacity:1;transform:scale(1) rotate(90deg)}}'
    ].join('\n');
    document.head.appendChild(s);
  }

  function num(v, d) { return v === '' || v == null || isNaN(+v) ? d : +v; }

  /* name -> [keyframes, easing] */
  const ENTER = {
    fade: ['kp-e-fade', 'ease-out'],
    up: ['kp-e-up', 'cubic-bezier(.2,.8,.2,1)'],
    down: ['kp-e-down', 'cubic-bezier(.2,.8,.2,1)'],
    left: ['kp-e-left', 'cubic-bezier(.2,.8,.2,1)'],
    right: ['kp-e-right', 'cubic-bezier(.2,.8,.2,1)'],
    zoom: ['kp-e-zoom', 'cubic-bezier(.2,.8,.2,1)'],
    pop: ['kp-e-pop', 'ease-out'],
    bounce: ['kp-e-bounce', 'ease-in-out'],
    jelly: ['kp-e-jelly', 'ease-out'],
    blur: ['kp-e-blur', 'ease-out'],
    flip: ['kp-e-flip', 'cubic-bezier(.2,.8,.2,1)'],
    spin: ['kp-e-spin', 'cubic-bezier(.2,.8,.2,1)']
  };
  const LOOPS = {
    pulse: 'kp-pulse 1.6s ease-in-out', float: 'kp-float 3s ease-in-out', wiggle: 'kp-wiggle 1.2s ease-in-out',
    spin: 'kp-spin 6s linear', heartbeat: 'kp-heartbeat 1.6s ease-in-out', swing: 'kp-swing 2.2s ease-in-out',
    breathe: 'kp-breathe 3.2s ease-in-out', blink: 'kp-blink 1.4s ease-in-out', glow: 'kp-glow 2.2s ease-in-out',
    tada: 'kp-tada 2.8s ease-in-out'
  };
  const EXIT = {
    fade: ['kp-x-fade', 'ease-in'], up: ['kp-x-up', 'cubic-bezier(.5,0,.8,.3)'], down: ['kp-x-down', 'cubic-bezier(.5,0,.8,.3)'],
    left: ['kp-x-left', 'cubic-bezier(.5,0,.8,.3)'], right: ['kp-x-right', 'cubic-bezier(.5,0,.8,.3)'], zoom: ['kp-x-zoom', 'ease-in'],
    grow: ['kp-x-grow', 'ease-in'], pop: ['kp-x-pop', 'ease-in'], fall: ['kp-x-fall', 'ease-in'], blur: ['kp-x-blur', 'ease-in'],
    flip: ['kp-x-flip', 'ease-in'], spin: ['kp-x-spin', 'ease-in']
  };

  /* The three phases of an object's animation timeline, in seconds from when the screen opens:
     start (entrance) -> meanwhile (loop) -> exit. The editor's timeline uses the same numbers. */
  function phases(o) {
    const textual = o.type === 'text' || o.type === 'button';
    let enter = o.enter && o.enter !== 'none' ? o.enter : '';
    if ((enter === 'letters' || enter === 'typewriter') && !textual) enter = 'fade';
    if (enter && enter !== 'letters' && enter !== 'typewriter' && !ENTER[enter]) enter = '';
    const delay = Math.max(0, num(o.enterDelay, 0));
    const dur = Math.max(0.1, num(o.enterDuration, 0.7));
    const enterEnd = enter ? delay + dur + (enter === 'letters' ? 0.6 : 0) : 0;
    const exit = o.exit && o.exit !== 'none' && EXIT[o.exit] ? o.exit : '';
    const exitDuration = Math.max(0.1, num(o.exitDuration, 0.6));
    const exitAt = exit ? Math.max(0, num(o.exitAt, enterEnd + 3)) : null;
    return {
      enter: enter, enterStart: enter ? delay : 0, enterDuration: dur, enterEnd: enterEnd, loopStart: enterEnd,
      exit: exit, exitAt: exitAt, exitDuration: exitDuration, exitEnd: exit ? exitAt + exitDuration : null
    };
  }

  function segChars(text) {
    if (window.Intl && Intl.Segmenter) {
      return Array.from(new Intl.Segmenter(undefined, { granularity: 'grapheme' }).segment(text), function (x) { return x.segment; });
    }
    return Array.from(text);
  }

  /* Letter-by-letter and typewriter effects: one span per character, each with its own delay.
     Letters are grouped per word so words never break in the middle. */
  function splitChars(el, mode, delay, dur) {
    const chars = segChars(el.textContent);
    el.textContent = '';
    const wrap = document.createElement('span');
    wrap.style.cssText = 'display:block;width:100%';
    const count = chars.filter(function (c) { return c.trim(); }).length || 1;
    const step = dur / count;
    let i = 0, word = null;
    chars.forEach(function (c) {
      if (c === '\n') { word = null; wrap.appendChild(document.createElement('br')); return; }
      if (!c.trim()) { word = null; wrap.appendChild(document.createTextNode(c)); return; }
      if (!word) { word = document.createElement('span'); word.style.whiteSpace = 'nowrap'; wrap.appendChild(word); }
      const s = document.createElement('span');
      s.textContent = c;
      const d = (delay + i * step).toFixed(3) + 's';
      if (mode === 'letters') {
        s.style.display = 'inline-block';
        s.style.animation = 'kp-ch-up .6s cubic-bezier(.2,.9,.3,1.3) ' + d + ' backwards';
      } else s.style.animation = 'kp-ch-type .01s linear ' + d + ' backwards';
      word.appendChild(s);
      i++;
    });
    el.appendChild(wrap);
    return delay + dur + (mode === 'letters' ? 0.6 : 0);
  }

  function applyAnim(el, o) {
    let enter = o.enter && o.enter !== 'none' ? o.enter : '';
    const delay = Math.max(0, num(o.enterDelay, 0));
    const dur = Math.max(0.1, num(o.enterDuration, 0.7));
    const list = [];
    let loopAt = 0;
    if (enter === 'letters' || enter === 'typewriter') {
      if ((o.type === 'text' || o.type === 'button') && el.textContent) {
        loopAt = splitChars(el, enter, delay, dur);
        if (o.type === 'button') list.push('kp-e-fade .3s ease-out ' + delay + 's backwards');
        enter = '';
      } else enter = 'fade';
    }
    if (ENTER[enter]) {
      list.push(ENTER[enter][0] + ' ' + dur + 's ' + ENTER[enter][1] + ' ' + delay + 's backwards');
      loopAt = delay + dur;
    }
    if (o.anim && o.anim !== 'none') {
      if (o.anim === 'shine') {
        el.classList.add('kp-shine');
        el.style.setProperty('--kp-loop-delay', loopAt + 's');
      } else if (LOOPS[o.anim]) {
        list.push(LOOPS[o.anim] + ' ' + loopAt + 's infinite');
        if (o.anim === 'swing') el.style.transformOrigin = '50% 0';
      }
    }
    const ph = phases(o);
    if (ph.exit) list.push(EXIT[ph.exit][0] + ' ' + ph.exitDuration + 's ' + EXIT[ph.exit][1] + ' ' + ph.exitAt + 's forwards');
    if (list.length) el.style.animation = list.join(', ');
  }

  /* When an object's entrance finishes, in seconds (used by the editor's replay). */
  function animEnd(o) {
    const p = phases(o);
    return Math.max(p.enterEnd, p.exitEnd || 0);
  }

  /* ---------- Animated screen backgrounds ---------- */
  function rng(seed) {
    let s = 2166136261;
    String(seed).split('').forEach(function (c) { s = Math.imul(s ^ c.charCodeAt(0), 16777619) >>> 0; });
    return function () { s = (Math.imul(s, 1664525) + 1013904223) >>> 0; return s / 4294967296; };
  }
  const FX_COLORS = ['#ffd166', '#ef476f', '#06d6a0', '#118ab2', '#ffffff', '#f72585', '#8338ec'];
  function hasFx(sc) {
    return !!(sc.effect && sc.effect !== 'none' && !(sc.type === 'game' && sc.game && (sc.game.kind === 'catch' || sc.game.kind === 'slice')));
  }
  function renderFx(kind, seed, W, H) {
    if (!kind || kind === 'none') return null;
    const R = rng(seed + ':' + kind);
    const k = Math.min(W, H) / 1080;
    const between = function (a, b) { return a + R() * (b - a); };
    const root = document.createElement('div');
    root.className = 'kp-fx';
    root.style.cssText = 'position:absolute;inset:0;overflow:hidden;pointer-events:none;';
    const add = function (css, text) {
      const d = document.createElement('div');
      d.style.cssText = 'position:absolute;' + css;
      if (text) d.textContent = text;
      root.appendChild(d);
    };
    let i;
    if (kind === 'aurora') {
      ['rgba(255,107,214,.55)', 'rgba(107,213,255,.5)', 'rgba(255,230,107,.4)', 'rgba(131,56,236,.55)'].forEach(function (c) {
        const s = Math.max(W, H) * between(0.6, 0.9), d = between(12, 20);
        add('left:' + between(-0.3, 0.7) * W + 'px;top:' + between(-0.2, 0.8) * H + 'px;width:' + s + 'px;height:' + s + 'px;border-radius:50%;' +
          'background:radial-gradient(circle, ' + c + ' 0%, transparent 62%);--dx:' + between(-0.35, 0.35) * W + 'px;--dy:' + between(-0.25, 0.25) * H + 'px;' +
          'animation:kp-fx-drift ' + d + 's ease-in-out ' + (-R() * d) + 's infinite alternate');
      });
    } else if (kind === 'rays') {
      const s = Math.max(W, H) * 1.8;
      add('left:' + (W / 2 - s / 2) + 'px;top:' + (H * 0.35 - s / 2) + 'px;width:' + s + 'px;height:' + s + 'px;border-radius:50%;' +
        'background:repeating-conic-gradient(rgba(255,255,255,.14) 0deg 8deg, transparent 8deg 20deg);' +
        '-webkit-mask-image:radial-gradient(circle, #000 10%, transparent 60%);mask-image:radial-gradient(circle, #000 10%, transparent 60%);' +
        'animation:kp-spin 70s linear infinite');
    } else if (kind === 'bubbles') {
      for (i = 0; i < 22; i++) {
        const s = between(30, 150) * k, d = between(9, 18);
        add('left:' + between(0, W - s) + 'px;top:' + (H + s) + 'px;width:' + s + 'px;height:' + s + 'px;border-radius:50%;' +
          'background:radial-gradient(circle at 32% 30%, rgba(255,255,255,.8), rgba(255,255,255,.15) 40%, rgba(255,255,255,.06) 70%);' +
          'border:' + 2 * k + 'px solid rgba(255,255,255,.35);--rise:' + -(H + s * 3) + 'px;--sway:' + between(-60, 60) * k + 'px;' +
          'animation:kp-fx-rise ' + d + 's ease-in-out ' + (-R() * d) + 's infinite');
      }
    } else if (kind === 'stars') {
      for (i = 0; i < 60; i++) {
        const s = between(4, 12) * k, d = between(1.6, 4);
        add('left:' + R() * W + 'px;top:' + R() * H + 'px;width:' + s + 'px;height:' + s + 'px;border-radius:50%;background:#fff;' +
          'box-shadow:0 0 ' + s * 2 + 'px rgba(255,255,255,.9);animation:kp-fx-twinkle ' + d + 's ease-in-out ' + (-R() * d) + 's infinite');
      }
    } else if (kind === 'sparkles') {
      for (i = 0; i < 18; i++) {
        const s = between(30, 90) * k, d = between(2, 4.5);
        add('left:' + R() * (W - s) + 'px;top:' + R() * (H - s) + 'px;width:' + s + 'px;height:' + s + 'px;font-size:' + s + 'px;line-height:1;text-align:center;' +
          'color:' + (R() < 0.5 ? '#ffffff' : '#ffe14d') + ';text-shadow:0 0 ' + 12 * k + 'px rgba(255,255,255,.8);' +
          'animation:kp-fx-sparkle ' + d + 's ease-in-out ' + (-R() * d) + 's infinite', '✦');
      }
    } else if (kind === 'confetti' || kind === 'snow') {
      const snow = kind === 'snow';
      for (i = 0; i < (snow ? 70 : 45); i++) {
        const w = (snow ? between(8, 20) : between(14, 26)) * k, d = snow ? between(8, 16) : between(5, 10);
        add('left:' + R() * W + 'px;top:' + -60 * k + 'px;width:' + w + 'px;height:' + (snow ? w : w * 1.6) + 'px;' +
          'border-radius:' + (snow ? '50%' : 3 * k + 'px') + ';background:' + (snow ? 'rgba(255,255,255,' + between(0.55, 0.95) + ')' : FX_COLORS[Math.floor(R() * FX_COLORS.length)]) + ';' +
          '--fall:' + (H + 120 * k) + 'px;--sway:' + between(-120, 120) * k + 'px;--rot:' + (snow ? 0 : between(-720, 720)) + 'deg;' +
          'animation:kp-fx-fall ' + d + 's linear ' + (-R() * d) + 's infinite');
      }
    }
    return root;
  }

  function interp(str, vars) {
    return String(str == null ? '' : str).replace(/\{\{\s*([\w.]+)\s*\}\}/g, function (_, k) {
      return vars && vars[k] != null ? vars[k] : '';
    });
  }

  function resolve(src, assets) {
    if (!src) return '';
    if (src.indexOf('asset:') === 0) return (assets && assets[src.slice(6)]) || '';
    return src;
  }

  function bgCss(sc, assets) {
    const base = sc.bg2 ? 'linear-gradient(180deg, ' + (sc.bg || '#222') + ', ' + sc.bg2 + ')' : (sc.bg || '#222');
    const img = resolve(sc.bgImage, assets);
    return img ? 'url("' + img + '") center/cover no-repeat, ' + base : base;
  }

  /* Render one object as a DOM element. mode: 'play' | 'edit' | 'thumb' */
  function renderObj(o, ctx, mode) {
    const el = document.createElement(o.type === 'input' ? 'input' : 'div');
    const s = el.style;
    s.position = 'absolute';
    s.left = o.x + 'px';
    s.top = o.y + 'px';
    s.width = o.w + 'px';
    s.height = o.h + 'px';
    s.boxSizing = 'border-box';
    s.opacity = o.opacity == null ? 1 : o.opacity;
    if (o.rotation) s.rotate = o.rotation + 'deg';
    const font = FONTS[o.font] || FONTS.sans;
    function textLike() {
      s.display = 'flex';
      s.alignItems = 'center';
      s.justifyContent = o.align === 'left' ? 'flex-start' : o.align === 'right' ? 'flex-end' : 'center';
      s.textAlign = o.align || 'center';
      s.fontFamily = font;
      s.fontSize = (o.fontSize || 48) + 'px';
      s.fontWeight = o.bold ? '800' : '500';
      s.fontStyle = o.italic ? 'italic' : 'normal';
      s.color = o.color || '#ffffff';
      s.lineHeight = '1.15';
      s.whiteSpace = 'pre-wrap';
      s.wordBreak = 'break-word';
    }
    switch (o.type) {
      case 'text':
        textLike();
        if (o.shadow) s.textShadow = '0 4px 16px rgba(0,0,0,.35)';
        el.textContent = interp(o.text, ctx.vars);
        break;
      case 'button':
        textLike();
        s.background = o.bg2 ? 'linear-gradient(180deg,' + (o.bg || '#ff7a1a') + ',' + o.bg2 + ')' : (o.bg || '#ff7a1a');
        s.borderRadius = (o.radius == null ? 40 : o.radius) + 'px';
        s.padding = '0 24px';
        s.cursor = 'pointer';
        if (o.borderWidth) s.border = o.borderWidth + 'px solid ' + (o.borderColor || '#ffffff');
        if (o.shadow !== false) s.boxShadow = '0 10px 0 rgba(0,0,0,.22), 0 18px 34px rgba(0,0,0,.25)';
        el.textContent = interp(o.text, ctx.vars);
        if (mode === 'play') el.classList.add('kp-press');
        break;
      case 'image': {
        const src = resolve(o.src, ctx.assets);
        s.borderRadius = (o.radius || 0) + 'px';
        if (src) {
          s.backgroundImage = 'url("' + src + '")';
          s.backgroundSize = o.fit || 'contain';
          s.backgroundPosition = 'center';
          s.backgroundRepeat = 'no-repeat';
        } else if (mode !== 'play') {
          s.background = 'repeating-linear-gradient(45deg,rgba(255,255,255,.12) 0 14px,rgba(255,255,255,.05) 14px 28px)';
          s.border = '4px dashed rgba(255,255,255,.45)';
          s.display = 'flex';
          s.alignItems = 'center';
          s.justifyContent = 'center';
          s.color = 'rgba(255,255,255,.7)';
          s.font = '600 40px ' + FONTS.sans;
          el.textContent = 'Image';
        }
        break;
      }
      case 'emoji':
        s.display = 'flex';
        s.alignItems = 'center';
        s.justifyContent = 'center';
        s.fontSize = Math.min(o.w, o.h) * 0.82 + 'px';
        s.lineHeight = '1';
        s.fontFamily = EMOJI_FONT;
        el.textContent = o.emoji || '⭐';
        break;
      case 'shape':
        s.background = o.bg2 ? 'linear-gradient(180deg,' + (o.bg || '#fff') + ',' + o.bg2 + ')' : (o.bg || '#ffffff');
        s.borderRadius = o.shape === 'circle' ? '50%' : (o.radius || 0) + 'px';
        if (o.borderWidth) s.border = o.borderWidth + 'px solid ' + (o.borderColor || '#ffffff');
        break;
      case 'input':
        el.type = o.inputType || 'text';
        el.placeholder = o.placeholder || '';
        el.name = o.field || '';
        el.autocomplete = 'off';
        s.fontSize = (o.fontSize || 44) + 'px';
        s.fontFamily = font;
        s.padding = '0 32px';
        s.borderRadius = (o.radius == null ? 24 : o.radius) + 'px';
        s.border = '4px solid ' + (o.borderColor || 'rgba(255,255,255,.4)');
        s.background = o.bg || '#ffffff';
        s.color = o.color || '#222222';
        s.outline = 'none';
        if (mode !== 'play') {
          el.readOnly = true;
          el.tabIndex = -1;
          s.pointerEvents = 'none';
        }
        break;
    }
    if (mode === 'play' || mode === 'anim') applyAnim(el, o);
    return el;
  }

  /* ---------- Game templates imported from .zip packages ----------
     A package is { manifest, code, assets }. Its game.js calls
       KioskGame.define({ start: function (ctx) { ...; return { stop: function () {} }; } })
     or, for a preset of a built-in game, the manifest has "extends": "catch" (etc.). */
  const BUILTIN_GAMES = ['quiz', 'catch', 'slice', 'wheel'];
  function loadTemplate(pkg) {
    const m = pkg.manifest;
    if (m.extends && BUILTIN_GAMES.indexOf(m.extends) >= 0) return { run: GAMES[m.extends], pkg: pkg };
    let def = null;
    try {
      new Function('KioskGame', String(pkg.code || ''))({ define: function (d) { def = d; }, sdk: GAMES.sdk, version: 1 });
    } catch (e) {
      console.error('[Kiosk] Game template "' + m.id + '" could not load:', e);
      return null;
    }
    if (def && def.builtin && BUILTIN_GAMES.indexOf(def.builtin) >= 0) return { run: GAMES[def.builtin], pkg: pkg };
    if (!def || typeof def.start !== 'function') {
      console.error('[Kiosk] Game template "' + m.id + '" must call KioskGame.define({ start: function (ctx) { ... } })');
      return null;
    }
    return { def: def, pkg: pkg };
  }

  /* Create a running player inside `root`. opts: { start, kiosk, onScreen(screen), onSubmit(record) } */
  function createPlayer(root, project, opts) {
    opts = opts || {};
    injectStyle();
    const P = project;
    const W = P.width;
    const H = P.height;
    const assets = {};
    (P.assets || []).forEach(function (a) { assets[a.id] = a.src; });
    const byId = {};
    P.screens.forEach(function (s) { byId[s.id] = s; });
    const startId = byId[P.startScreen] ? P.startScreen : P.screens[0].id;
    let best = 0;
    const templates = {};
    (P.gameTemplates || []).forEach(function (pkg) {
      if (pkg && pkg.manifest && pkg.manifest.id) {
        const t = loadTemplate(pkg);
        if (t) templates[pkg.manifest.id] = t;
      }
    });
    const freshVars = function () { return Object.assign({ score: 0 }, P.vars || {}, { best: best }); };
    let vars = freshVars();
    let cur = null;
    let back = [];
    let timers = [];
    let game = null;
    let idleTimer = null;
    let destroyed = false;

    const vp = document.createElement('div');
    vp.style.cssText = 'position:absolute;inset:0;overflow:hidden;background:#000;touch-action:none;' +
      'user-select:none;-webkit-user-select:none;-webkit-tap-highlight-color:transparent;';
    const stage = document.createElement('div');
    stage.style.cssText = 'position:absolute;left:0;top:0;width:' + W + 'px;height:' + H + 'px;transform-origin:0 0;overflow:hidden;';
    vp.appendChild(stage);
    if (getComputedStyle(root).position === 'static') root.style.position = 'relative';
    root.appendChild(vp);

    function fit() {
      const r = vp.getBoundingClientRect();
      if (!r.width || !r.height) return;
      const k = Math.min(r.width / W, r.height / H);
      stage.style.transform = 'translate(' + (r.width - W * k) / 2 + 'px,' + (r.height - H * k) / 2 + 'px) scale(' + k + ')';
    }
    let ro = null;
    if (window.ResizeObserver) { ro = new ResizeObserver(fit); ro.observe(vp); }
    else window.addEventListener('resize', fit);
    fit();

    function clearScreen() {
      timers.forEach(clearTimeout);
      timers = [];
      if (game) { try { game.stop(); } catch (e) { /* ignore */ } game = null; }
      stage.innerHTML = '';
    }

    function go(id, push) {
      if (destroyed) return;
      const sc = byId[id];
      if (!sc) return;
      if (push !== false && cur) back.push(cur.id);
      clearScreen();
      cur = sc;
      stage.style.background = bgCss(sc, assets);
      const layer = document.createElement('div');
      layer.className = 'kp-tr-' + (sc.transition || 'rise');
      layer.style.cssText = 'position:absolute;inset:0;';
      stage.appendChild(layer);
      if (hasFx(sc)) layer.appendChild(renderFx(sc.effect, sc.id, W, H));
      if (sc.type === 'game') startGame(sc, layer);
      else {
        (sc.objects || []).forEach(function (o) {
          if (o.hidden) return;
          const el = renderObj(o, { vars: vars, assets: assets }, 'play');
          el.dataset.oid = o.id;
          if (o.action && o.action.type && o.action.type !== 'none') {
            el.style.cursor = 'pointer';
            if (o.type !== 'button') el.classList.add('kp-press');
            el.addEventListener('click', function (e) { e.stopPropagation(); run(o.action, layer); });
          }
          layer.appendChild(el);
        });
      }
      if (sc.timer && sc.timer.seconds > 0 && sc.timer.target) {
        timers.push(setTimeout(function () { go(sc.timer.target); }, sc.timer.seconds * 1000));
      }
      if (opts.onScreen) opts.onScreen(sc);
      armIdle();
    }

    function run(a, layer) {
      switch (a.type) {
        case 'goto': go(a.target); break;
        case 'back': if (back.length) go(back.pop(), false); break;
        case 'restart': reset(); break;
        case 'submit': {
          const data = {};
          let bad = null;
          layer.querySelectorAll('input').forEach(function (inp, i) {
            const o = (cur.objects || []).find(function (x) { return x.id === inp.dataset.oid; }) || {};
            const v = inp.value.trim();
            const invalid = (o.required && !v) || (o.inputType === 'email' && v && !/^\S+@\S+\.\S+$/.test(v));
            if (invalid && !bad) bad = inp;
            data[inp.name || 'field' + (i + 1)] = v;
          });
          if (bad) {
            bad.classList.remove('kp-bad');
            void bad.offsetWidth;
            bad.classList.add('kp-bad');
            bad.focus();
            return;
          }
          Object.assign(vars, data);
          const rec = Object.assign({ time: new Date().toISOString(), screen: cur.name, score: vars.score }, data);
          try {
            const k = 'kiosk.submissions.' + (P.id || 'project');
            const arr = JSON.parse(localStorage.getItem(k) || '[]');
            arr.push(rec);
            localStorage.setItem(k, JSON.stringify(arr));
          } catch (e) { /* storage unavailable */ }
          if (opts.onSubmit) opts.onSubmit(rec);
          if (a.target) go(a.target);
          break;
        }
      }
    }

    function startGame(sc, layer) {
      const g = sc.game || {};
      let ended = false;
      const onEnd = function (score, extra) {
        if (ended || destroyed) return;
        ended = true;
        extra = extra || {};
        score = Math.round(+score || 0);
        vars.score = score;
        best = Math.max(best, score);
        vars.best = best;
        if (extra.vars) Object.assign(vars, extra.vars);
        const win = typeof extra.win === 'boolean' ? extra.win : score >= (+g.threshold || 0);
        const target = win ? (g.win || g.lose) : (g.lose || g.win);
        if (target) go(target);
      };
      const cfg = Object.assign({}, g.config || {});
      try {
        if (BUILTIN_GAMES.indexOf(g.kind) >= 0) { game = GAMES[g.kind](cfg, layer, W, H, onEnd); return; }
        const t = templates[g.kind];
        if (!t) { gameMessage(layer, 'This screen uses the game template "' + g.kind + '", which is not in this project.'); return; }
        if (t.run) { game = t.run(cfg, layer, W, H, onEnd); return; }
        game = t.def.start({
          layer: layer, width: W, height: H, config: cfg, assets: t.pkg.assets || {},
          sdk: GAMES.sdk, end: onEnd, manifest: t.pkg.manifest
        }) || null;
      } catch (e) {
        console.error('[Kiosk] Game "' + g.kind + '" stopped with an error:', e);
        gameMessage(layer, 'Sorry, this game could not start.');
      }
    }

    function gameMessage(layer, text) {
      const d = document.createElement('div');
      d.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:80px;box-sizing:border-box;' +
        'text-align:center;color:#fff;font:700 54px ' + FONTS.sans + ';background:rgba(0,0,0,.45);';
      d.textContent = text;
      layer.appendChild(d);
    }

    function reset() {
      vars = freshVars();
      back = [];
      go(startId, false);
    }

    function armIdle() {
      clearTimeout(idleTimer);
      const t = +P.idleTimeout || 0;
      if (!t || !cur || cur.id === startId) return;
      idleTimer = setTimeout(reset, t * 1000);
    }
    vp.addEventListener('pointerdown', armIdle, true);
    vp.addEventListener('keydown', armIdle, true);

    if (opts.kiosk) {
      document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
      document.addEventListener('gesturestart', function (e) { e.preventDefault(); });
      let askedFs = false;
      vp.addEventListener('pointerdown', function () {
        if (askedFs || !document.documentElement.requestFullscreen) return;
        askedFs = true;
        document.documentElement.requestFullscreen().catch(function () { /* not allowed */ });
      });
    }

    go(opts.start && byId[opts.start] ? opts.start : startId, false);

    return {
      go: go,
      reset: reset,
      get vars() { return vars; },
      destroy: function () {
        destroyed = true;
        clearScreen();
        clearTimeout(idleTimer);
        if (ro) ro.disconnect();
        vp.remove();
      }
    };
  }

  return {
    FONTS: FONTS, EMOJI_FONT: EMOJI_FONT, injectStyle: injectStyle, interp: interp, resolve: resolve, bgCss: bgCss,
    renderObj: renderObj, renderFx: renderFx, hasFx: hasFx, animEnd: animEnd, phases: phases, createPlayer: createPlayer
  };
}
