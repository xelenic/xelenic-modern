/*
 * Kiosk Studio game templates. Each template is a function
 *   (config, layer, W, H, onEnd(score)) => { stop() }
 * Self-contained so the exported kiosk HTML can embed it.
 */
function KioskGames() {
  const EMOJI_FONT = '"Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif';
  const UI_FONT = 'system-ui,-apple-system,"Segoe UI",Roboto,sans-serif';
  const JUICE = {
    '🍎': '#e53935', '🍊': '#fb8c00', '🍌': '#fdd835', '🍓': '#e53935', '🍇': '#8e24aa', '🍉': '#ff5470',
    '🍍': '#fbc02d', '🥝': '#7cb342', '🍋': '#fff176', '🍑': '#ffab91', '🍒': '#c62828', '🥭': '#ffb300',
    '🍐': '#c0ca33', '🫐': '#3949ab', '🥥': '#f5f5f5', '🍏': '#8bc34a'
  };
  const spriteCache = {};

  function sprite(ch, size) {
    size = Math.max(8, Math.round(size));
    const key = ch + '|' + size;
    if (spriteCache[key]) return spriteCache[key];
    const c = document.createElement('canvas');
    const pad = Math.ceil(size * 0.25);
    c.width = c.height = size + pad * 2;
    const x = c.getContext('2d');
    x.font = size + 'px ' + EMOJI_FONT;
    x.textAlign = 'center';
    x.textBaseline = 'middle';
    x.fillText(ch, c.width / 2, c.height / 2 + size * 0.06);
    spriteCache[key] = c;
    return c;
  }

  function list(v, fallback) {
    const out = [];
    String(v || '').split(/[\s,]+/).filter(Boolean).forEach(function (part) {
      if (window.Intl && Intl.Segmenter) {
        for (const g of new Intl.Segmenter(undefined, { granularity: 'grapheme' }).segment(part)) out.push(g.segment);
      } else out.push(part);
    });
    return out.length ? out : fallback;
  }

  const pick = function (a) { return a[Math.floor(Math.random() * a.length)]; };
  const lerp = function (a, b, t) { return a + (b - a) * Math.max(0, Math.min(1, t)); };
  const num = function (v, d) { return v === '' || v == null || isNaN(+v) ? d : +v; };

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function label(ctx, str, x, y, size, color, align) {
    ctx.font = '900 ' + size + 'px ' + UI_FONT;
    ctx.textAlign = align || 'center';
    ctx.textBaseline = 'middle';
    ctx.lineJoin = 'round';
    ctx.lineWidth = size * 0.16;
    ctx.strokeStyle = 'rgba(0,0,0,.35)';
    ctx.strokeText(str, x, y);
    ctx.fillStyle = color || '#ffffff';
    ctx.fillText(str, x, y);
  }

  function drawSprite(ctx, img, x, y, rot, sx, sy) {
    ctx.save();
    ctx.translate(x, y);
    if (rot) ctx.rotate(rot);
    if (sx || sy) ctx.scale(sx || 1, sy || 1);
    ctx.drawImage(img, -img.width / 2, -img.height / 2);
    ctx.restore();
  }

  /* Shared shell for canvas games: loop, countdown, HUD, effects, end screen. */
  function canvasGame(layer, W, H, cfg, hooks, onEnd) {
    const k = Math.min(W, H) / 1080;
    const cv = document.createElement('canvas');
    cv.width = W;
    cv.height = H;
    cv.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;display:block;touch-action:none;';
    layer.appendChild(cv);
    const ctx = cv.getContext('2d');
    const duration = Math.max(5, num(cfg.duration, 30));
    const st = { phase: 'count', count: 3.2, left: duration, score: 0, shake: 0, flash: 0, flashColor: '#fff', floats: [], parts: [], t: 0, overT: 0, ended: false, down: false };
    const api = {
      ctx: ctx, W: W, H: H, k: k, st: st,
      add: function (n, x, y, color) {
        st.score = Math.max(0, st.score + n);
        st.floats.push({ text: (n > 0 ? '+' : '') + n, x: x, y: y, life: 1, size: 76 * k, color: color || (n >= 0 ? '#ffffff' : '#ff4d4d') });
      },
      say: function (text, x, y, color, size) {
        st.floats.push({ text: text, x: x, y: y, life: 1.3, size: (size || 96) * k, color: color || '#ffe14d' });
      },
      burst: function (x, y, color, n, speed) {
        for (let i = 0; i < (n || 14); i++) {
          const a = Math.random() * Math.PI * 2;
          const v = (speed || 700) * k * (0.3 + Math.random() * 0.7);
          st.parts.push({ x: x, y: y, vx: Math.cos(a) * v, vy: Math.sin(a) * v - 250 * k, life: 0.5 + Math.random() * 0.4, r: (7 + Math.random() * 12) * k, color: color });
        }
      },
      shake: function (v) { st.shake = Math.max(st.shake, v * k); },
      flash: function (c) { st.flash = 0.35; st.flashColor = c || '#ff0000'; },
      end: function () { if (st.phase === 'play') { st.left = 0; } }
    };

    const pt = function (e) {
      const r = cv.getBoundingClientRect();
      return { x: (e.clientX - r.left) / r.width * W, y: (e.clientY - r.top) / r.height * H };
    };
    const onDown = function (e) {
      st.down = true;
      try { cv.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
      if (hooks.down) hooks.down(pt(e));
    };
    const onMove = function (e) { if (hooks.move) hooks.move(pt(e), st.down); };
    const onUp = function (e) { st.down = false; if (hooks.up) hooks.up(pt(e)); };
    cv.addEventListener('pointerdown', onDown);
    cv.addEventListener('pointermove', onMove);
    cv.addEventListener('pointerup', onUp);
    cv.addEventListener('pointercancel', onUp);

    function hudPill(x, title, value, alignRight) {
      const w = 290 * k, h = 112 * k, y = 44 * k;
      const px = alignRight ? x - w : x;
      ctx.fillStyle = 'rgba(0,0,0,.32)';
      roundRect(ctx, px, y, w, h, h / 2);
      ctx.fill();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = 'rgba(255,255,255,.75)';
      ctx.font = '800 ' + 26 * k + 'px ' + UI_FONT;
      ctx.fillText(title, px + w / 2, y + 30 * k);
      ctx.fillStyle = '#ffffff';
      ctx.font = '900 ' + 54 * k + 'px ' + UI_FONT;
      ctx.fillText(String(value), px + w / 2, y + 74 * k);
    }

    let last = performance.now();
    let raf = 0;
    let stopped = false;
    function frame(now) {
      if (stopped) return;
      const dt = Math.min(0.05, (now - last) / 1000);
      last = now;
      st.t += dt;
      if (st.phase === 'count') {
        st.count -= dt;
        if (st.count <= 0) st.phase = 'play';
      } else if (st.phase === 'play') {
        st.left -= dt;
        hooks.update(dt, 1 - st.left / duration);
        if (st.left <= 0) { st.left = 0; st.phase = 'over'; }
      } else {
        st.overT += dt;
        if (st.overT > 2.4 && !st.ended) { st.ended = true; onEnd(Math.round(st.score)); return; }
      }
      st.parts.forEach(function (p) { p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 1600 * k * dt; p.life -= dt; });
      st.parts = st.parts.filter(function (p) { return p.life > 0; });
      st.floats.forEach(function (f) { f.y -= 170 * k * dt; f.life -= dt; });
      st.floats = st.floats.filter(function (f) { return f.life > 0; });
      st.shake = Math.max(0, st.shake - dt * 70 * k);
      st.flash = Math.max(0, st.flash - dt);

      ctx.save();
      if (st.shake) ctx.translate((Math.random() - 0.5) * st.shake, (Math.random() - 0.5) * st.shake);
      hooks.draw(ctx, dt);
      st.parts.forEach(function (p) {
        ctx.globalAlpha = Math.max(0, Math.min(1, p.life * 1.6));
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
      });
      st.floats.forEach(function (f) {
        ctx.globalAlpha = Math.max(0, Math.min(1, f.life * 1.6));
        label(ctx, f.text, f.x, f.y, f.size, f.color);
      });
      ctx.globalAlpha = 1;
      ctx.restore();
      if (st.flash) {
        ctx.globalAlpha = st.flash;
        ctx.fillStyle = st.flashColor;
        ctx.fillRect(0, 0, W, H);
        ctx.globalAlpha = 1;
      }
      hudPill(44 * k, 'SCORE', Math.round(st.score), false);
      hudPill(W - 44 * k, 'TIME', Math.ceil(st.left), true);
      if (st.phase === 'count') {
        const n = Math.ceil(st.count - 0.2);
        const frac = st.count % 1;
        ctx.fillStyle = 'rgba(0,0,0,.25)';
        ctx.fillRect(0, 0, W, H);
        label(ctx, n > 0 ? String(n) : 'GO!', W / 2, H / 2, (260 + frac * 120) * k, '#ffffff');
        if (cfg.hint) label(ctx, cfg.hint, W / 2, H / 2 + 260 * k, 54 * k, '#ffffff');
      } else if (st.phase === 'over') {
        ctx.fillStyle = 'rgba(0,0,0,' + Math.min(0.55, st.overT) + ')';
        ctx.fillRect(0, 0, W, H);
        label(ctx, "TIME'S UP!", W / 2, H / 2 - 110 * k, 130 * k, '#ffe14d');
        label(ctx, 'Score ' + Math.round(st.score), W / 2, H / 2 + 60 * k, 100 * k, '#ffffff');
      }
      raf = requestAnimationFrame(frame);
    }
    raf = requestAnimationFrame(frame);

    return {
      api: api,
      stop: function () {
        stopped = true;
        cancelAnimationFrame(raf);
        cv.remove();
      }
    };
  }

  /* ---------- Quiz (DOM) ---------- */
  function quiz(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    let qs = (cfg.questions || []).filter(function (q) { return q && q.q; });
    if (cfg.shuffle) qs = qs.slice().sort(function () { return Math.random() - 0.5; });
    if (!qs.length) qs = [{ q: 'Add questions in the inspector', answers: ['OK'], correct: 0 }];
    const time = num(cfg.time, 0);
    const pts = num(cfg.points, 100);
    const accent = cfg.accent || '#ffb703';
    const textColor = cfg.textColor || '#ffffff';
    const ansBg = cfg.answerBg || 'rgba(255,255,255,.14)';
    const root = document.createElement('div');
    root.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;box-sizing:border-box;' +
      'padding:' + 110 * k + 'px ' + 70 * k + 'px ' + 90 * k + 'px;color:' + textColor + ';font-family:' + UI_FONT + ';';
    layer.appendChild(root);
    let i = 0, score = 0, right = 0, locked = false, timeoutId = 0, raf = 0, tStart = 0, stopped = false;
    let bar = null, buttons = [];

    function show() {
      locked = false;
      const q = qs[i];
      root.innerHTML = '';
      const head = document.createElement('div');
      head.style.cssText = 'display:flex;justify-content:space-between;align-items:center;font-weight:800;font-size:' + 42 * k + 'px;';
      head.innerHTML = '<span style="opacity:.85">Question ' + (i + 1) + ' / ' + qs.length + '</span>' +
        '<span style="background:' + accent + ';color:#1b1b1b;border-radius:999px;padding:' + 10 * k + 'px ' + 30 * k + 'px">★ ' + score + '</span>';
      root.appendChild(head);
      const track = document.createElement('div');
      track.style.cssText = 'height:' + 18 * k + 'px;border-radius:99px;background:rgba(255,255,255,.18);margin-top:' + 34 * k + 'px;overflow:hidden;' + (time ? '' : 'visibility:hidden;');
      bar = document.createElement('div');
      bar.style.cssText = 'height:100%;width:100%;background:' + accent + ';border-radius:99px;';
      track.appendChild(bar);
      root.appendChild(track);
      const qEl = document.createElement('div');
      qEl.className = 'kp-in';
      qEl.style.cssText = 'flex:1;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:800;line-height:1.15;' +
        'font-size:' + num(cfg.questionSize, 76) * k + 'px;padding:' + 30 * k + 'px 0;';
      qEl.textContent = q.q;
      root.appendChild(qEl);
      const grid = document.createElement('div');
      grid.style.cssText = 'display:grid;gap:' + 28 * k + 'px;grid-template-columns:' + (W > H ? '1fr 1fr' : '1fr') + ';';
      buttons = [];
      (q.answers || []).forEach(function (a, j) {
        if (!String(a || '').trim()) return;
        const b = document.createElement('div');
        b.className = 'kp-press kp-in';
        b.style.cssText = 'display:flex;align-items:center;gap:' + 28 * k + 'px;min-height:' + 150 * k + 'px;padding:0 ' + 36 * k + 'px;box-sizing:border-box;' +
          'border-radius:' + 36 * k + 'px;background:' + ansBg + ';border:' + 5 * k + 'px solid transparent;font-weight:800;font-size:' + 48 * k + 'px;cursor:pointer;' +
          'transition:background .2s,opacity .2s;animation-delay:' + j * 0.06 + 's;animation-fill-mode:backwards;';
        b.innerHTML = '<span style="flex:none;width:' + 80 * k + 'px;height:' + 80 * k + 'px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:' + 40 * k + 'px">' + 'ABCDEF'.charAt(j) + '</span>';
        const t = document.createElement('span');
        t.textContent = a;
        b.appendChild(t);
        b.addEventListener('click', function () { choose(j); });
        b.dataset.index = j;
        grid.appendChild(b);
        buttons.push(b);
      });
      root.appendChild(grid);
      if (time) { tStart = performance.now(); tick(); }
    }
    function tick() {
      if (stopped || locked) return;
      const left = 1 - (performance.now() - tStart) / (time * 1000);
      bar.style.width = Math.max(0, left) * 100 + '%';
      if (left <= 0) choose(-1);
      else raf = requestAnimationFrame(tick);
    }
    function choose(j) {
      if (locked) return;
      locked = true;
      cancelAnimationFrame(raf);
      const c = +qs[i].correct || 0;
      buttons.forEach(function (b) {
        const idx = +b.dataset.index;
        if (idx === c) { b.style.background = '#2ecc71'; b.style.borderColor = '#ffffff'; }
        else if (idx === j) b.style.background = '#ff4d4d';
        else b.style.opacity = '.45';
      });
      if (j === c) { score += pts; right++; }
      timeoutId = setTimeout(function () { i++; if (i < qs.length) show(); else finish(); }, 1100);
    }
    function finish() {
      root.innerHTML = '';
      const card = document.createElement('div');
      card.className = 'kp-in';
      card.style.cssText = 'margin:auto;text-align:center;font-weight:800;';
      card.innerHTML = '<div style="font-size:' + 220 * k + 'px;line-height:1;font-family:' + EMOJI_FONT + '">' + (right >= qs.length / 2 ? '🎉' : '🙂') + '</div>' +
        '<div style="font-size:' + 70 * k + 'px;margin-top:' + 40 * k + 'px">' + right + ' of ' + qs.length + ' correct</div>' +
        '<div style="font-size:' + 54 * k + 'px;margin-top:' + 20 * k + 'px;color:' + accent + '">Score ' + score + '</div>';
      root.appendChild(card);
      timeoutId = setTimeout(function () { onEnd(score); }, 2000);
    }
    show();
    return {
      stop: function () { stopped = true; clearTimeout(timeoutId); cancelAnimationFrame(raf); root.remove(); }
    };
  }

  /* ---------- Catch (canvas) ---------- */
  function catchGame(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    const good = list(cfg.good, ['🍎', '🍊', '🍌', '🍓', '🍇']);
    const bad = list(cfg.bad, ['💣']);
    const catcherCh = list(cfg.catcher, ['🧺'])[0];
    const csize = num(cfg.catcherSize, 230) * k;
    const isize = num(cfg.itemSize, 130) * k;
    const points = num(cfg.points, 10);
    const penalty = num(cfg.penalty, 20);
    const catcher = { x: W / 2, tx: W / 2, y: H - csize * 0.62 - 90 * k, squash: 0 };
    let items = [];
    let acc = 0;
    let api = null;
    const hooks = {
      down: function (p) { catcher.tx = p.x; },
      move: function (p) { catcher.tx = p.x; },
      update: function (dt, prog) {
        const rate = lerp(num(cfg.spawnStart, 1.2), num(cfg.spawnEnd, 3), prog);
        const speed = lerp(num(cfg.speedStart, 520), num(cfg.speedEnd, 1100), prog) * (H / 1920);
        acc += dt * rate;
        while (acc >= 1) {
          acc -= 1;
          const isBad = Math.random() < num(cfg.badChance, 20) / 100;
          items.push({ x: isize / 2 + Math.random() * (W - isize), y: -isize, ch: pick(isBad ? bad : good), bad: isBad, vy: speed * (0.85 + Math.random() * 0.3), rot: 0, vr: (Math.random() - 0.5) * 3 });
        }
        const top = catcher.y - csize * 0.35;
        items.forEach(function (it) {
          it.y += it.vy * dt;
          it.rot += it.vr * dt;
          if (!it.done && it.y + isize * 0.3 >= top && it.y < catcher.y + csize * 0.2 && Math.abs(it.x - catcher.x) < csize * 0.55) {
            it.done = true;
            if (it.bad) {
              api.add(-penalty, it.x, top - 60 * k);
              api.shake(34);
              api.flash('#ff2d2d');
              api.burst(it.x, top, '#3a3a3a', 18);
            } else {
              api.add(points, it.x, top - 60 * k);
              api.burst(it.x, top, JUICE[it.ch] || '#ffd54f', 12);
              catcher.squash = 1;
            }
          }
        });
        items = items.filter(function (it) { return !it.done && it.y < H + isize; });
      },
      draw: function (ctx, dt) {
        catcher.x += (catcher.tx - catcher.x) * Math.min(1, dt * 14);
        catcher.x = Math.max(csize / 2, Math.min(W - csize / 2, catcher.x));
        catcher.squash = Math.max(0, catcher.squash - dt * 5);
        const g = ctx.createLinearGradient(0, 0, 0, H);
        g.addColorStop(0, cfg.bgTop || '#4fc3f7');
        g.addColorStop(1, cfg.bgBottom || '#b3e5fc');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = 'rgba(255,255,255,.55)';
        [[0.2, 0.18, 1], [0.75, 0.3, 0.8], [0.45, 0.48, 0.6]].forEach(function (c) {
          const cx = c[0] * W, cy = c[1] * H, r = 70 * k * c[2];
          ctx.beginPath();
          ctx.arc(cx, cy, r, 0, Math.PI * 2);
          ctx.arc(cx + r, cy + r * 0.2, r * 0.8, 0, Math.PI * 2);
          ctx.arc(cx - r, cy + r * 0.25, r * 0.7, 0, Math.PI * 2);
          ctx.fill();
        });
        ctx.fillStyle = cfg.ground || '#66bb6a';
        ctx.beginPath();
        ctx.ellipse(W / 2, H + 40 * k, W * 0.9, 190 * k, 0, 0, Math.PI * 2);
        ctx.fill();
        items.forEach(function (it) { drawSprite(ctx, sprite(it.ch, isize), it.x, it.y, it.rot); });
        drawSprite(ctx, sprite(catcherCh, csize), catcher.x, catcher.y, 0, 1 + catcher.squash * 0.14, 1 - catcher.squash * 0.14);
      }
    };
    const g = canvasGame(layer, W, H, Object.assign({ hint: 'Drag to catch!' }, cfg), hooks, onEnd);
    api = g.api;
    return { stop: g.stop };
  }

  /* ---------- Slice (canvas) ---------- */
  function slice(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    const fruits = list(cfg.fruits, ['🍉', '🍍', '🍊', '🍎', '🥝', '🍋', '🍑']);
    const bombCh = list(cfg.bomb, ['💣'])[0];
    const size = num(cfg.itemSize, 170) * k;
    const R = size * 0.45;
    const points = num(cfg.points, 10);
    const penalty = num(cfg.penalty, 30);
    const grav = H * 0.85;
    let items = [], halves = [], trail = [], wave = 0.4, lastPt = null;
    const combo = { n: 0, t: 0, x: 0, y: 0 };
    let api = null;

    function launch() {
      const isBomb = Math.random() < num(cfg.bombChance, 15) / 100;
      const x0 = W * (0.15 + Math.random() * 0.7);
      const peakY = H * (0.12 + Math.random() * 0.3);
      const y0 = H + size * 0.6;
      const vy = -Math.sqrt(2 * grav * (y0 - peakY));
      const flight = 2 * (-vy) / grav;
      const vx = (W / 2 - x0) * (0.2 + Math.random() * 0.6) / (flight / 2) + (Math.random() - 0.5) * 120 * k;
      items.push({ x: x0, y: y0, vx: vx, vy: vy, rot: 0, vr: (Math.random() - 0.5) * 5, ch: isBomb ? bombCh : pick(fruits), bomb: isBomb });
    }
    function cut(it, dx, dy) {
      it.cut = true;
      if (it.bomb) {
        api.add(-penalty, it.x, it.y - size * 0.6);
        api.shake(46);
        api.flash('#ffffff');
        api.burst(it.x, it.y, '#333333', 26, 1000);
        if (cfg.bombEnds) api.end();
        return;
      }
      const len = Math.hypot(dx, dy) || 1;
      const nx = -dy / len, ny = dx / len;
      const a = Math.atan2(dy, dx);
      [1, -1].forEach(function (side) {
        halves.push({ x: it.x + nx * side * 10 * k, y: it.y + ny * side * 10 * k, vx: it.vx + nx * side * 260 * k, vy: Math.min(it.vy, 0) * 0.4 + ny * side * 260 * k - 150 * k, rot: it.rot, vr: side * 4, ch: it.ch, cutA: a - it.rot, side: side });
      });
      api.add(points, it.x, it.y - size * 0.6);
      api.burst(it.x, it.y, JUICE[it.ch] || '#ffd54f', 16);
      combo.n = api.st.t - combo.t < 0.35 ? combo.n + 1 : 1;
      combo.t = api.st.t;
      combo.x = it.x;
      combo.y = it.y;
    }
    function distSeg(px, py, a, b) {
      const vx = b.x - a.x, vy = b.y - a.y;
      const l2 = vx * vx + vy * vy || 1;
      const t = Math.max(0, Math.min(1, ((px - a.x) * vx + (py - a.y) * vy) / l2));
      return Math.hypot(px - (a.x + t * vx), py - (a.y + t * vy));
    }
    const hooks = {
      down: function (p) { lastPt = p; trail = [{ x: p.x, y: p.y, t: api.st.t }]; },
      move: function (p, down) {
        if (!down || !lastPt) return;
        trail.push({ x: p.x, y: p.y, t: api.st.t });
        if (api.st.phase === 'play') {
          items.forEach(function (it) { if (!it.cut && distSeg(it.x, it.y, lastPt, p) < R) cut(it, p.x - lastPt.x, p.y - lastPt.y); });
        }
        lastPt = p;
      },
      up: function () { lastPt = null; },
      update: function (dt, prog) {
        wave -= dt;
        if (wave <= 0) {
          const n = 1 + Math.floor(Math.random() * (1 + prog * 3));
          for (let i = 0; i < n; i++) launch();
          wave = lerp(num(cfg.waveStart, 1.3), num(cfg.waveEnd, 0.7), prog);
        }
        [items, halves].forEach(function (arr) {
          arr.forEach(function (o) { o.x += o.vx * dt; o.y += o.vy * dt; o.vy += grav * dt; o.rot += o.vr * dt; });
        });
        items = items.filter(function (o) { return !o.cut && !(o.vy > 0 && o.y > H + size); });
        halves = halves.filter(function (o) { return o.y < H + size; });
        if (combo.n && api.st.t - combo.t > 0.35) {
          if (combo.n >= 3) {
            const bonus = combo.n * points;
            api.st.score += bonus;
            api.say('COMBO x' + combo.n + '  +' + bonus, W / 2, Math.max(260 * k, combo.y - 160 * k));
          }
          combo.n = 0;
        }
      },
      draw: function (ctx) {
        const g = ctx.createLinearGradient(0, 0, 0, H);
        g.addColorStop(0, cfg.bgTop || '#5d3a1a');
        g.addColorStop(1, cfg.bgBottom || '#2b1708');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = 'rgba(0,0,0,.18)';
        const plank = 180 * k;
        for (let x = plank; x < W; x += plank) ctx.fillRect(x - 3 * k, 0, 6 * k, H);
        halves.forEach(function (h) {
          const img = sprite(h.ch, size);
          ctx.save();
          ctx.translate(h.x, h.y);
          ctx.rotate(h.rot);
          ctx.rotate(h.cutA);
          ctx.beginPath();
          ctx.rect(-size, h.side > 0 ? 0 : -size, size * 2, size);
          ctx.clip();
          ctx.rotate(-h.cutA);
          ctx.drawImage(img, -img.width / 2, -img.height / 2);
          ctx.restore();
        });
        items.forEach(function (it) { drawSprite(ctx, sprite(it.ch, size), it.x, it.y, it.rot); });
        const now = api.st.t;
        trail = trail.filter(function (p) { return now - p.t < 0.14; });
        if (trail.length > 1) {
          ctx.save();
          ctx.lineCap = 'round';
          ctx.shadowColor = cfg.trail || '#ffffff';
          ctx.shadowBlur = 24 * k;
          ctx.strokeStyle = cfg.trail || '#ffffff';
          for (let i = 1; i < trail.length; i++) {
            ctx.lineWidth = (4 + 22 * (i / trail.length)) * k;
            ctx.beginPath();
            ctx.moveTo(trail[i - 1].x, trail[i - 1].y);
            ctx.lineTo(trail[i].x, trail[i].y);
            ctx.stroke();
          }
          ctx.restore();
        }
      }
    };
    const g = canvasGame(layer, W, H, Object.assign({ hint: 'Swipe to slice!' }, cfg), hooks, onEnd);
    api = g.api;
    return { stop: g.stop };
  }

  /* ---------- Spin wheel (DOM + SVG) ---------- */
  const WHEEL_FALLBACK = [
    { label: 'Prize', color: '#ef476f', weight: 1, points: 10, win: true },
    { label: 'Try again', color: '#118ab2', weight: 1, points: 0, win: false }
  ];
  function escXml(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; });
  }
  function wheelSegments(cfg) {
    const segs = (cfg.segments || []).filter(function (g) { return g && String(g.label || '').trim(); });
    return segs.length >= 2 ? segs : WHEEL_FALLBACK;
  }
  function splitLabel(label) {
    const s = String(label).trim();
    if (Array.from(s).length <= 8 || s.indexOf(' ') < 0) return [s];
    const mid = s.length / 2;
    let best = -1;
    for (let i = 0; i < s.length; i++) if (s.charAt(i) === ' ' && (best < 0 || Math.abs(i - mid) < Math.abs(best - mid))) best = i;
    return [s.slice(0, best), s.slice(best + 1)];
  }
  /* The wheel face as an SVG string. Slice i is centred at i * (360 / n) degrees, clockwise from the top. */
  function wheelSvg(cfg, D) {
    const segs = wheelSegments(cfg);
    const n = segs.length;
    const r = D / 2, cx = r, cy = r, R = r * 0.93;
    const a = 2 * Math.PI / n;
    const pt = function (ang, rad) { return [cx + rad * Math.sin(ang), cy - rad * Math.cos(ang)]; };
    let s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + D + ' ' + D + '" width="' + D + '" height="' + D + '" style="display:block;overflow:visible">';
    s += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="' + escXml(cfg.rimColor || '#ffffff') + '"/>';
    segs.forEach(function (g, i) {
      const p0 = pt(i * a - a / 2, R), p1 = pt(i * a + a / 2, R);
      s += '<path d="M' + cx + ' ' + cy + 'L' + p0[0] + ' ' + p0[1] + 'A' + R + ' ' + R + ' 0 ' + (a > Math.PI ? 1 : 0) + ' 1 ' + p1[0] + ' ' + p1[1] + 'Z" fill="' + escXml(g.color || '#888') + '"/>';
    });
    segs.forEach(function (g, i) {
      const lines = splitLabel(g.label);
      const longest = Math.max.apply(null, lines.map(function (l) { return Array.from(l).length; }));
      const chord = 2 * R * 0.6 * Math.sin(Math.min(a, Math.PI) / 2);
      const fs = Math.min(D * 0.062, chord / (longest * 0.62 + 0.6), R * 0.2);
      const y0 = cy - R * 0.68 - (lines.length - 1) * fs * 0.5;
      s += '<g transform="rotate(' + (i * 360 / n) + ' ' + cx + ' ' + cy + ')"><text x="' + cx + '" y="' + y0 + '" text-anchor="middle" dominant-baseline="middle" ' +
        'font-family="system-ui,-apple-system,Segoe UI,Roboto,sans-serif,' + EMOJI_FONT.replace(/"/g, '') + '" font-weight="900" font-size="' + fs + '" fill="' + escXml(g.textColor || cfg.labelColor || '#ffffff') + '">' +
        lines.map(function (l, j) { return '<tspan x="' + cx + '" dy="' + (j ? fs * 1.08 : 0) + '">' + escXml(l) + '</tspan>'; }).join('') + '</text></g>';
    });
    segs.forEach(function (g, i) {
      const p = pt(i * a - a / 2, (r + R) / 2);
      s += '<circle cx="' + p[0] + '" cy="' + p[1] + '" r="' + D * 0.013 + '" fill="#ffffff" stroke="rgba(0,0,0,.25)" stroke-width="' + D * 0.004 + '"/>';
    });
    s += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r * 0.14 + '" fill="' + escXml(cfg.hubColor || '#ffffff') + '" stroke="rgba(0,0,0,.18)" stroke-width="' + D * 0.012 + '"/>';
    s += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r * 0.05 + '" fill="' + escXml(cfg.pointerColor || '#ffd166') + '"/>';
    return s + '</svg>';
  }
  /* Builds the wheel screen layout; shared by the live game and the editor preview. */
  function wheelUI(cfg, W, H, live) {
    const k = Math.min(W, H) / 1080;
    const land = W > H;
    const D = land ? H * 0.8 : Math.min(W * 0.86, H * 0.5);
    const wcx = land ? W * 0.34 : W / 2;
    const wcy = land ? H / 2 + 20 * k : H * 0.47;
    const colX = land ? W * 0.72 : W / 2;
    const colW = land ? W * 0.5 : W * 0.9;
    const textColor = cfg.textColor || '#ffffff';
    const root = document.createElement('div');
    root.style.cssText = 'position:absolute;inset:0;font-family:' + UI_FONT + ';color:' + textColor + ';';
    const put = function (css, html) {
      const d = document.createElement('div');
      d.style.cssText = 'position:absolute;' + css;
      if (html != null) d.innerHTML = html;
      root.appendChild(d);
      return d;
    };
    const titleTop = land ? H * 0.16 : 110 * k;
    put('left:' + (colX - colW / 2) + 'px;width:' + colW + 'px;top:' + titleTop + 'px;text-align:center;font-weight:900;line-height:1.05;font-size:' + num(cfg.titleSize, 110) * k +
      'px;text-shadow:0 6px 20px rgba(0,0,0,.3)', escXml(cfg.title || ''));
    put('left:' + (colX - colW / 2) + 'px;width:' + colW + 'px;top:' + (titleTop + 150 * k) + 'px;text-align:center;font-weight:600;opacity:.85;font-size:' + 48 * k + 'px', escXml(cfg.subtitle || ''));
    const box = put('left:' + (wcx - D / 2) + 'px;top:' + (wcy - D / 2) + 'px;width:' + D + 'px;height:' + D + 'px;cursor:pointer;filter:drop-shadow(0 ' + 24 * k + 'px ' + 40 * k + 'px rgba(0,0,0,.35))');
    const wheelEl = document.createElement('div');
    wheelEl.style.cssText = 'width:100%;height:100%;transform:rotate(0deg);will-change:transform;';
    wheelEl.innerHTML = wheelSvg(cfg, D);
    box.appendChild(wheelEl);
    const pointer = put('left:' + (wcx - 55 * k) + 'px;top:' + (wcy - D / 2 - 50 * k) + 'px;width:' + 110 * k + 'px;height:' + 130 * k + 'px;transform-origin:50% 22%;filter:drop-shadow(0 ' + 6 * k + 'px ' + 8 * k + 'px rgba(0,0,0,.35))',
      '<svg viewBox="0 0 110 130" width="100%" height="100%"><path d="M55 126 L10 30 Q55 -8 100 30 Z" fill="' + escXml(cfg.pointerColor || '#ffd166') + '" stroke="#ffffff" stroke-width="7" stroke-linejoin="round"/><circle cx="55" cy="36" r="12" fill="#ffffff"/></svg>');
    const btnTop = land ? H * 0.6 : wcy + D / 2 + 90 * k;
    const button = put('left:' + (colX - 300 * k) + 'px;top:' + btnTop + 'px;width:' + 600 * k + 'px;height:' + 170 * k + 'px;border-radius:' + 85 * k + 'px;display:flex;align-items:center;justify-content:center;' +
      'font-weight:900;letter-spacing:' + 4 * k + 'px;font-size:' + 72 * k + 'px;cursor:pointer;background:' + (cfg.buttonColor || '#ffd166') + ';color:' + (cfg.buttonTextColor || '#3a0ca3') +
      ';box-shadow:0 ' + 12 * k + 'px 0 rgba(0,0,0,.22),0 ' + 22 * k + 'px ' + 40 * k + 'px rgba(0,0,0,.3);transition:opacity .2s', escXml(cfg.buttonText || 'SPIN'));
    if (live) button.className = 'kp-press kp-anim-pulse';
    return { root: root, box: box, wheelEl: wheelEl, pointer: pointer, button: button, D: D, k: k };
  }
  function confetti(host, W, H, colors) {
    const k = Math.min(W, H) / 1080;
    for (let i = 0; i < 110; i++) {
      const d = document.createElement('div');
      const w = (12 + Math.random() * 16) * k;
      d.style.cssText = 'position:absolute;left:' + Math.random() * W + 'px;top:' + -60 * k + 'px;width:' + w + 'px;height:' + w * 1.6 + 'px;background:' + pick(colors) + ';border-radius:' + 3 * k + 'px;pointer-events:none;';
      host.appendChild(d);
      if (d.animate) {
        d.animate([
          { transform: 'translate(0,0) rotate(0deg)' },
          { transform: 'translate(' + (Math.random() - 0.5) * 400 * k + 'px,' + (H + 120 * k) + 'px) rotate(' + (Math.random() - 0.5) * 1440 + 'deg)' }
        ], { duration: 1800 + Math.random() * 1800, delay: Math.random() * 500, easing: 'cubic-bezier(.25,.6,.4,1)', fill: 'forwards' });
      }
    }
  }
  function wheel(cfg, layer, W, H, onEnd) {
    const segs = wheelSegments(cfg);
    const n = segs.length;
    const ui = wheelUI(cfg, W, H, true);
    const k = ui.k;
    layer.appendChild(ui.root);
    const spinTime = Math.max(1.5, num(cfg.spinTime, 5));
    let rot = 0, spinning = false, done = false, raf = 0, lastIdx = null;
    const timers = [];
    function pickIndex() {
      const ws = segs.map(function (g) { return Math.max(0, num(g.weight, 1)); });
      const total = ws.reduce(function (a, b) { return a + b; }, 0);
      if (!total) return Math.floor(Math.random() * n);
      let x = Math.random() * total;
      for (let i = 0; i < n; i++) { x -= ws[i]; if (x < 0) return i; }
      return n - 1;
    }
    function angleNow() {
      const m = /matrix\(([^)]+)\)/.exec(getComputedStyle(ui.wheelEl).transform || '');
      if (!m) return 0;
      const p = m[1].split(',').map(Number);
      return Math.atan2(p[1], p[0]) * 180 / Math.PI;
    }
    function tick() {
      if (!spinning) return;
      const seg = 360 / n;
      const idx = Math.round(((-angleNow() % 360) + 360) % 360 / seg) % n;
      if (lastIdx !== null && idx !== lastIdx && ui.pointer.animate) {
        ui.pointer.animate([{ transform: 'rotate(0deg)' }, { transform: 'rotate(-24deg)' }, { transform: 'rotate(0deg)' }], { duration: 150, easing: 'ease-out' });
      }
      lastIdx = idx;
      raf = requestAnimationFrame(tick);
    }
    function spin() {
      if (spinning || done) return;
      spinning = true;
      ui.button.classList.remove('kp-anim-pulse');
      ui.button.style.opacity = '0.45';
      const i = pickIndex();
      const seg = 360 / n;
      const target = -i * seg + (Math.random() - 0.5) * seg * 0.7;
      const delta = (((target - rot) % 360) + 360) % 360;
      rot += 360 * Math.max(1, Math.round(num(cfg.turns, 6))) + delta;
      ui.wheelEl.style.transition = 'transform ' + spinTime + 's cubic-bezier(0.15, 0.6, 0.1, 1)';
      requestAnimationFrame(function () { ui.wheelEl.style.transform = 'rotate(' + rot + 'deg)'; });
      tick();
      timers.push(setTimeout(function () { finish(i); }, spinTime * 1000 + 200));
    }
    function finish(i) {
      spinning = false;
      done = true;
      cancelAnimationFrame(raf);
      const g = segs[i];
      const win = g.win !== false;
      const ov = document.createElement('div');
      ov.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;';
      const card = document.createElement('div');
      card.className = 'kp-in';
      card.style.cssText = 'text-align:center;color:#fff;font-weight:900;padding:' + 60 * k + 'px ' + 70 * k + 'px;border-radius:' + 50 * k + 'px;max-width:' + W * 0.8 + 'px;' +
        'background:' + (g.color || '#222') + ';box-shadow:0 ' + 30 * k + 'px ' + 80 * k + 'px rgba(0,0,0,.45);border:' + 8 * k + 'px solid #fff;';
      card.innerHTML = '<div style="font-size:' + 180 * k + 'px;line-height:1;font-family:' + EMOJI_FONT + '">' + (win ? '🎉' : '😅') + '</div>' +
        '<div style="font-size:' + 64 * k + 'px;margin-top:' + 30 * k + 'px;color:' + escXml(g.textColor || '#fff') + '">' + escXml(win ? (cfg.winText || 'YOU WON!') : (cfg.loseText || 'SO CLOSE!')) + '</div>' +
        '<div style="font-size:' + 100 * k + 'px;margin-top:' + 16 * k + 'px;line-height:1.1;color:' + escXml(g.textColor || '#fff') + '">' + escXml(g.label) + '</div>';
      ov.appendChild(card);
      ui.root.appendChild(ov);
      if (win) confetti(ui.root, W, H, segs.map(function (s) { return s.color; }).concat(['#ffffff', '#ffd166']));
      timers.push(setTimeout(function () { onEnd(num(g.points, 0), { win: win, vars: { prize: g.label } }); }, num(cfg.resultTime, 3) * 1000));
    }
    ui.button.addEventListener('click', spin);
    ui.box.addEventListener('click', spin);
    return {
      stop: function () {
        timers.forEach(clearTimeout);
        cancelAnimationFrame(raf);
        ui.root.remove();
      }
    };
  }

  /* ---------- Game SDK: what imported game templates get as `ctx.sdk` / `KioskGame.sdk` ---------- */
  const imageCache = {};
  const sdk = {
    version: 1,
    /* The shared canvas shell: 3-2-1 countdown, SCORE / TIME pills, particles, floating text,
       "time's up" card, and ctx.end(score) when the time runs out.
       hooks: { update(dt, progress), draw(ctx2d, dt), down(p), move(p, isDown), up(p) } */
    canvasGame: function (ctx, hooks) { return canvasGame(ctx.layer, ctx.width, ctx.height, ctx.config || {}, hooks, ctx.end); },
    sprite: sprite,
    drawSprite: drawSprite,
    label: label,
    roundRect: roundRect,
    confetti: confetti,
    list: list,
    words: function (v, fallback) {
      const a = String(v || '').split(/[\s,]+/).filter(Boolean);
      return a.length ? a : fallback;
    },
    num: num,
    pick: pick,
    lerp: lerp,
    image: function (src) {
      if (!src) return null;
      if (!imageCache[src]) { const im = new Image(); im.src = src; imageCache[src] = im; }
      const im = imageCache[src];
      return im.complete && im.naturalWidth ? im : null;
    },
    sound: function (src, volume) {
      if (!src) return;
      try {
        const a = new Audio(src);
        a.volume = volume == null ? 1 : volume;
        const p = a.play();
        if (p && p.catch) p.catch(function () { /* autoplay blocked */ });
      } catch (e) { /* ignore */ }
    }
  };

  return { quiz: quiz, catch: catchGame, slice: slice, wheel: wheel, wheelUI: wheelUI, sprite: sprite, list: list, sdk: sdk };
}
