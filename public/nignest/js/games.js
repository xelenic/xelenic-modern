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
      /* End the round early; title replaces "TIME'S UP!" on the end card. */
      end: function (title) { if (st.phase === 'play') { st.left = 0; if (title) st.overTitle = String(title); } }
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
        label(ctx, st.overTitle || "TIME'S UP!", W / 2, H / 2 - 110 * k, 130 * k, '#ffe14d');
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
        if (cfg.bombEnds) api.end('BOOM!');
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

  /* ---------- Runner (canvas) ----------
     The runner keeps running to the right; tap to jump over obstacles and
     grab the coins. It speeds up and obstacles come closer as time runs out. */
  function runner(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    const runnerCh = list(cfg.runner, ['🏃'])[0];
    const obstacles = list(cfg.obstacles, ['🌵', '🪨', '📦']);
    const coins = list(cfg.coins, ['🪙', '⭐', '💎']);
    const rsize = num(cfg.runnerSize, 210) * k;
    const osize = num(cfg.itemSize, 150) * k;
    const csize = osize * 0.78;
    const points = num(cfg.points, 10);
    const clearPoints = num(cfg.clearPoints, 5);
    const penalty = num(cfg.penalty, 20);
    const mirror = cfg.faceRight !== false ? -1 : 1;
    const groundY = H * (W > H ? 0.8 : 0.77);
    const G = 5200 * k;                         // gravity
    const JUMP = Math.sqrt(2 * G * 520 * k);    // a jump rises about 520 px
    const hero = { x: Math.max(rsize, W * 0.22), h: 0, vy: 0, jumps: 0, inv: 0, tilt: 0 };
    let items = [];   // { kind: 'block' | 'coin', x, y, ch, size, … }
    let wait = 0.6;   // seconds to the next obstacle
    let scroll = 0;
    let speed = num(cfg.speedStart, 650) * k;
    let api = null;

    function jump() {
      if (!api || api.st.phase !== 'play') return;
      const max = cfg.doubleJump === false ? 1 : 2;
      if (hero.h > 0 && hero.jumps >= max) return;
      if (hero.h <= 0) hero.jumps = 0;
      hero.jumps++;
      hero.vy = JUMP * (hero.jumps > 1 ? 0.85 : 1);
      hero.tilt = -0.25;
      if (hero.jumps > 1) api.burst(hero.x, groundY - hero.h - rsize * 0.2, 'rgba(255,255,255,.85)', 8, 320);
    }
    function spawn(prog) {
      const x = W + osize;
      const pair = prog > 0.4 && Math.random() < 0.22;
      items.push({ kind: 'block', x: x, ch: pick(obstacles), size: osize });
      if (pair) items.push({ kind: 'block', x: x + osize * 0.85, ch: pick(obstacles), size: osize });
      if (Math.random() < num(cfg.coinChance, 70) / 100) {
        const mid = x + (pair ? osize * 0.42 : 0);
        if (Math.random() < 0.6) {
          // An arc over the obstacle: jump for it.
          [[-1, 0.62], [0, 0.95], [1, 0.62]].forEach(function (c) {
            items.push({ kind: 'coin', x: mid + c[0] * 250 * k, y: groundY - osize * 0.5 - c[1] * 440 * k, ch: pick(coins), size: csize, bob: Math.random() * 6 });
          });
        } else {
          // A row on the ground before it.
          for (let i = 0; i < 3; i++) items.push({ kind: 'coin', x: mid - (620 + i * 150) * k, y: groundY - csize * 0.62, ch: pick(coins), size: csize, bob: Math.random() * 6 });
        }
      }
      wait = lerp(num(cfg.gapStart, 1.7), num(cfg.gapEnd, 1), prog) * (0.85 + Math.random() * 0.45) + (pair ? 0.25 : 0);
    }
    const hooks = {
      down: jump,
      update: function (dt, prog) {
        speed = lerp(num(cfg.speedStart, 650), num(cfg.speedEnd, 1200), prog) * k;
        wait -= dt;
        if (wait <= 0) spawn(prog);
        // The jump.
        if (hero.h > 0 || hero.vy > 0) {
          hero.vy -= G * dt;
          hero.h += hero.vy * dt;
          if (hero.h <= 0) {
            hero.h = 0;
            hero.vy = 0;
            hero.jumps = 0;
            api.burst(hero.x, groundY, 'rgba(255,255,255,.7)', 6, 260);
          }
        }
        hero.inv = Math.max(0, hero.inv - dt);
        const hy = groundY - rsize * 0.45 - hero.h;
        items.forEach(function (it) {
          it.x -= speed * dt;
          if (it.done) return;
          if (it.kind === 'coin') {
            if (Math.hypot(it.x - hero.x, it.y - hy) < (rsize + it.size) * 0.4) {
              it.done = true;
              api.add(points, it.x, it.y - 60 * k, '#ffe14d');
              api.burst(it.x, it.y, '#ffd54f', 10, 500);
            }
            return;
          }
          const oy = groundY - it.size * 0.42;
          if (!it.hit && hero.inv <= 0 && Math.abs(it.x - hero.x) < (rsize * 0.28 + it.size * 0.3) && Math.abs(oy - hy) < (rsize * 0.36 + it.size * 0.3)) {
            it.hit = true;
            hero.inv = 1.2;
            if (penalty) api.add(-penalty, hero.x, hy - rsize * 0.6);
            api.shake(34);
            api.flash('#ff2d2d');
            api.burst(it.x, oy, '#6d4c41', 14);
            if (cfg.crashEnds) api.end('CRASH!');
          } else if (!it.hit && !it.cleared && it.x + it.size * 0.3 < hero.x - rsize * 0.28) {
            it.cleared = true;
            if (clearPoints) api.add(clearPoints, it.x, oy - it.size * 0.8, '#ffffff');
          }
        });
        items = items.filter(function (it) { return !(it.kind === 'coin' && it.done) && it.x > -osize * 2; });
      },
      draw: function (ctx, dt) {
        const st = api ? api.st : { phase: 'count', t: 0 };
        const moving = st.phase !== 'over';
        if (moving) scroll += speed * dt;
        hero.tilt += (0 - hero.tilt) * Math.min(1, dt * 6);
        // Sky, sun and clouds.
        const g = ctx.createLinearGradient(0, 0, 0, groundY);
        g.addColorStop(0, cfg.bgTop || '#5ec8f8');
        g.addColorStop(1, cfg.bgBottom || '#fff1c1');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, groundY + 2);
        ctx.fillStyle = 'rgba(255,255,255,.55)';
        ctx.beginPath();
        ctx.arc(W * 0.8, groundY * 0.28, 110 * k, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,.7)';
        [[0.1, 0.2, 1], [0.55, 0.12, 0.75], [0.9, 0.42, 0.9]].forEach(function (c, i) {
          const span = W + 400 * k;
          const cx = ((c[0] * span - scroll * (0.08 + i * 0.03)) % span + span) % span - 200 * k;
          const cy = c[1] * groundY, r = 60 * k * c[2];
          ctx.beginPath();
          ctx.arc(cx, cy, r, 0, Math.PI * 2);
          ctx.arc(cx + r, cy + r * 0.2, r * 0.8, 0, Math.PI * 2);
          ctx.arc(cx - r, cy + r * 0.25, r * 0.7, 0, Math.PI * 2);
          ctx.fill();
        });
        // Far hills, scrolling slowly.
        ctx.fillStyle = cfg.ground || '#7cb342';
        ctx.globalAlpha = 0.35;
        ctx.beginPath();
        ctx.moveTo(0, groundY);
        for (let x = 0; x <= W; x += 20 * k) ctx.lineTo(x, groundY - 150 * k - Math.sin((x + scroll * 0.3) / (260 * k)) * 70 * k);
        ctx.lineTo(W, groundY);
        ctx.fill();
        ctx.globalAlpha = 1;
        // Ground, with stripes that move at running speed.
        ctx.fillStyle = cfg.ground || '#7cb342';
        ctx.fillRect(0, groundY, W, H - groundY);
        const soil = ctx.createLinearGradient(0, groundY, 0, H);
        soil.addColorStop(0, 'rgba(0,0,0,0)');
        soil.addColorStop(1, 'rgba(0,0,0,.28)');
        ctx.fillStyle = soil;
        ctx.fillRect(0, groundY, W, H - groundY);
        ctx.fillStyle = 'rgba(255,255,255,.28)';
        ctx.fillRect(0, groundY, W, 16 * k);
        ctx.fillStyle = 'rgba(0,0,0,.12)';
        const gap = 160 * k;
        for (let x = -(scroll % gap); x < W; x += gap) ctx.fillRect(x, groundY + 70 * k, 80 * k, 14 * k);
        // Obstacles and coins.
        items.forEach(function (it) {
          if (it.kind === 'coin') {
            drawSprite(ctx, sprite(it.ch, it.size), it.x, it.y + Math.sin(st.t * 5 + it.bob) * 10 * k, 0, Math.max(0.25, Math.abs(Math.cos(st.t * 3 + it.bob))), 1);
          } else {
            ctx.fillStyle = 'rgba(0,0,0,.18)';
            ctx.beginPath();
            ctx.ellipse(it.x, groundY + 6 * k, it.size * 0.4, 14 * k, 0, 0, Math.PI * 2);
            ctx.fill();
            drawSprite(ctx, sprite(it.ch, it.size), it.x, groundY - it.size * 0.42, it.hit ? 0.5 : 0);
          }
        });
        // The runner: a shadow that shrinks as it jumps, a bob while it runs, a blink after a crash.
        const sh = Math.max(0.3, 1 - hero.h / (700 * k));
        ctx.fillStyle = 'rgba(0,0,0,.2)';
        ctx.beginPath();
        ctx.ellipse(hero.x, groundY + 6 * k, rsize * 0.32 * sh, 16 * k * sh, 0, 0, Math.PI * 2);
        ctx.fill();
        const bob = hero.h <= 0 && moving ? Math.abs(Math.sin(st.t * 14)) * 12 * k : 0;
        if (!(hero.inv > 0 && Math.floor(hero.inv * 12) % 2)) {
          drawSprite(ctx, sprite(runnerCh, rsize), hero.x, groundY - rsize * 0.45 - hero.h - bob, hero.h > 0 ? hero.tilt * mirror : 0, mirror, 1);
        }
      }
    };
    const onKey = function (e) {
      if (e.code === 'Space' || e.code === 'ArrowUp') { e.preventDefault(); jump(); }
    };
    window.addEventListener('keydown', onKey);
    const g = canvasGame(layer, W, H, Object.assign({ hint: 'Tap to jump!' }, cfg), hooks, onEnd);
    api = g.api;
    return {
      stop: function () {
        window.removeEventListener('keydown', onKey);
        g.stop();
      }
    };
  }

  /* ---------- Memory match (DOM) ----------
     Cards face down in a grid; turn two over to find a matching pair. Matched
     pairs score, and finding them all early adds a bonus for the seconds left.
     memoryBoard() draws the board, for the game and the editor's preview. */
  function memorySymbols(cfg) {
    const all = list(cfg.symbols, ['🍎', '🍊', '🍌', '🍓', '🍇', '🍉', '🍒', '🥝']);
    const unique = all.filter(function (s, i) { return all.indexOf(s) === i; });
    const pool = unique.length >= 2 ? unique : ['🍎', '🍊'];
    const pairs = Math.max(2, Math.min(18, Math.round(num(cfg.pairs, 8)), pool.length));
    return pool.slice(0, pairs);
  }
  function memoryLayout(n, W, H, k) {
    const top = 290 * k, bottom = 60 * k, side = 50 * k, gap = 22 * k;
    const aw = W - side * 2, ah = H - top - bottom;
    let best = null;
    for (let cols = 2; cols <= Math.min(n, 9); cols++) {
      const rows = Math.ceil(n / cols);
      const cw = Math.min(330 * k, (aw - gap * (cols - 1)) / cols, ((ah - gap * (rows - 1)) / rows) * 0.8);
      if (!best || cw > best.cw + 0.5) best = { cols: cols, rows: rows, cw: cw };
    }
    best.ch = best.cw / 0.8;
    best.gap = gap;
    best.x0 = (W - (best.cols * best.cw + (best.cols - 1) * gap)) / 2;
    best.y0 = top + (ah - (best.rows * best.ch + (best.rows - 1) * gap)) / 2;
    return best;
  }
  function memoryBoard(cfg, W, H, deck) {
    const k = Math.min(W, H) / 1080;
    const L = memoryLayout(deck.length, W, H, k);
    const text = cfg.textColor || '#ffffff';
    const root = document.createElement('div');
    root.style.cssText = 'position:absolute;inset:0;font-family:' + UI_FONT + ';user-select:none;-webkit-user-select:none;';
    function pill(title, left, value) {
      const p = document.createElement('div');
      p.style.cssText = 'position:absolute;top:' + 44 * k + 'px;' + left + 'width:' + 290 * k + 'px;height:' + 112 * k + 'px;border-radius:' + 56 * k + 'px;' +
        'background:rgba(0,0,0,.32);color:' + text + ';display:flex;flex-direction:column;justify-content:center;text-align:center;font-weight:900;';
      p.innerHTML = '<div style="font-size:' + 26 * k + 'px;opacity:.75">' + title + '</div><div style="font-size:' + 54 * k + 'px;line-height:1.1"></div>';
      p.lastChild.textContent = value;
      root.appendChild(p);
      return p.lastChild;
    }
    const score = pill('SCORE', 'left:' + 44 * k + 'px;', '0');
    const pairs = pill('PAIRS', 'left:' + (W / 2 - 145 * k) + 'px;', '0 / ' + deck.length / 2);
    const time = pill('TIME', 'right:' + 44 * k + 'px;', String(Math.max(5, num(cfg.duration, 60))));
    const banner = document.createElement('div');
    banner.style.cssText = 'position:absolute;left:0;right:0;top:' + 180 * k + 'px;text-align:center;font-weight:900;font-size:' + 48 * k + 'px;color:' + text +
      ';text-shadow:0 ' + 4 * k + 'px ' + 14 * k + 'px rgba(0,0,0,.35);transition:opacity .4s;';
    root.appendChild(banner);
    const radius = 26 * k;
    const face = 'position:absolute;inset:0;border-radius:' + radius + 'px;backface-visibility:hidden;-webkit-backface-visibility:hidden;display:flex;align-items:center;justify-content:center;' +
      'box-sizing:border-box;border:' + 6 * k + 'px solid rgba(255,255,255,.55);box-shadow:0 ' + 10 * k + 'px ' + 24 * k + 'px rgba(0,0,0,.28);font-family:' + EMOJI_FONT + ';line-height:1;';
    const cards = deck.map(function (sym, i) {
      const r = Math.floor(i / L.cols);
      const inRow = Math.min(L.cols, deck.length - r * L.cols);
      const c = i % L.cols;
      const x = L.x0 + (L.cols - inRow) * (L.cw + L.gap) / 2 + c * (L.cw + L.gap);
      const el = document.createElement('div');
      el.style.cssText = 'position:absolute;left:' + x + 'px;top:' + (L.y0 + r * (L.ch + L.gap)) + 'px;width:' + L.cw + 'px;height:' + L.ch + 'px;perspective:' + 1400 * k + 'px;cursor:pointer;';
      const inner = document.createElement('div');
      inner.style.cssText = 'position:relative;width:100%;height:100%;transform-style:preserve-3d;transition:transform .42s cubic-bezier(.3,.7,.35,1.15);';
      const back = document.createElement('div');
      back.style.cssText = face + 'font-size:' + L.cw * 0.42 + 'px;background:' + (cfg.backColor || '#4361ee') +
        ' repeating-linear-gradient(45deg, rgba(255,255,255,.1) 0 ' + 14 * k + 'px, transparent ' + 14 * k + 'px ' + 28 * k + 'px);';
      back.textContent = list(cfg.cardBack, [''])[0] || '';
      const front = document.createElement('div');
      front.style.cssText = face + 'transform:rotateY(180deg);font-size:' + L.cw * 0.58 + 'px;background:' + (cfg.faceColor || '#ffffff') + ';transition:background .3s,border-color .3s;';
      front.textContent = sym;
      inner.appendChild(back);
      inner.appendChild(front);
      el.appendChild(inner);
      root.appendChild(el);
      return { el: el, inner: inner, front: front, symbol: sym, x: x + L.cw / 2, y: L.y0 + r * (L.ch + L.gap) + L.ch / 2, open: false, matched: false };
    });
    return { root: root, cards: cards, score: score, pairs: pairs, time: time, banner: banner, k: k };
  }
  function memory(cfg, layer, W, H, onEnd) {
    const symbols = memorySymbols(cfg);
    const deck = symbols.concat(symbols);
    for (let i = deck.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); const t = deck[i]; deck[i] = deck[j]; deck[j] = t; }
    const b = memoryBoard(cfg, W, H, deck);
    const k = b.k;
    layer.appendChild(b.root);
    const duration = Math.max(5, num(cfg.duration, 60));
    const points = num(cfg.points, 20);
    const penalty = num(cfg.penalty, 0);
    const peek = Math.max(0, num(cfg.peek, 2));
    const flipBack = Math.max(0.3, num(cfg.flipBack, 0.8));
    const timers = [];
    let score = 0, found = 0, first = null, locked = true, playing = false, done = false, left = duration, last = 0, raf = 0;

    const turn = function (card, up) { card.open = up; card.inner.style.transform = up ? 'rotateY(180deg)' : ''; };
    const later = function (fn, s) { timers.push(setTimeout(fn, s * 1000)); };
    function float(textValue, x, y, color) {
      const f = document.createElement('div');
      f.style.cssText = 'position:absolute;left:' + (x - 200 * k) + 'px;top:' + (y - 50 * k) + 'px;width:' + 400 * k + 'px;text-align:center;pointer-events:none;' +
        'font-weight:900;font-size:' + 72 * k + 'px;color:' + color + ';-webkit-text-stroke:' + 3 * k + 'px rgba(0,0,0,.35);';
      f.textContent = textValue;
      b.root.appendChild(f);
      if (f.animate) f.animate([{ transform: 'translateY(0)', opacity: 1 }, { transform: 'translateY(' + -140 * k + 'px)', opacity: 0 }], { duration: 1100, easing: 'ease-out', fill: 'forwards' });
      later(function () { f.remove(); }, 1.2);
    }
    function setScore(n) { score = Math.max(0, n); b.score.textContent = String(score); }
    function tick(now) {
      if (!playing) return;
      left = Math.max(0, left - Math.min(0.1, (now - last) / 1000));
      last = now;
      b.time.textContent = String(Math.ceil(left));
      if (left <= 0) { finish(false); return; }
      raf = requestAnimationFrame(tick);
    }
    function start() {
      b.banner.textContent = 'Find the pairs!';
      later(function () { b.banner.style.opacity = '0'; }, 1.4);
      locked = false;
      playing = true;
      last = performance.now();
      raf = requestAnimationFrame(tick);
    }
    function choose(card) {
      if (locked || done || card.open || card.matched) return;
      turn(card, true);
      if (!first) { first = card; return; }
      const a = first;
      first = null;
      if (a.symbol === card.symbol) {
        a.matched = card.matched = true;
        found++;
        setScore(score + points);
        b.pairs.textContent = found + ' / ' + deck.length / 2;
        later(function () {
          [a, card].forEach(function (c) {
            c.front.style.borderColor = cfg.matchColor || '#06d6a0';
            c.front.style.background = cfg.matchColor || '#06d6a0';
            if (c.el.animate) c.el.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.12)' }, { transform: 'scale(1)' }], { duration: 380, easing: 'ease-out' });
          });
          if (points) float('+' + points, card.x, card.y, '#ffe14d');
        }, 0.35);
        if (found === deck.length / 2) later(function () { finish(true); }, 0.7);
      } else {
        locked = true;
        if (penalty) { setScore(score - penalty); later(function () { float('-' + penalty, card.x, card.y, '#ff4d4d'); }, 0.35); }
        later(function () {
          [a, card].forEach(function (c) {
            if (c.el.animate) c.el.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(' + -12 * k + 'px)' }, { transform: 'translateX(' + 12 * k + 'px)' }, { transform: 'translateX(0)' }], { duration: 260 });
          });
        }, 0.4);
        later(function () { turn(a, false); turn(card, false); locked = false; }, 0.4 + flipBack);
      }
    }
    function finish(all) {
      if (done) return;
      done = true;
      playing = false;
      locked = true;
      cancelAnimationFrame(raf);
      const bonus = all ? Math.ceil(left) * num(cfg.timeBonus, 5) : 0;
      if (bonus) setScore(score + bonus);
      if (!all) b.cards.forEach(function (c) { if (!c.matched) turn(c, true); });
      const ov = document.createElement('div');
      ov.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;';
      const card = document.createElement('div');
      card.className = 'kp-in';
      card.style.cssText = 'text-align:center;color:#fff;font-weight:900;font-family:' + UI_FONT + ';';
      card.innerHTML = '<div style="font-size:' + 200 * k + 'px;line-height:1;font-family:' + EMOJI_FONT + '">' + (all ? '🎉' : '⏰') + '</div>' +
        '<div style="font-size:' + 96 * k + 'px;margin-top:' + 30 * k + 'px;color:#ffe14d">' + (all ? 'ALL PAIRS FOUND!' : "TIME'S UP!") + '</div>' +
        (bonus ? '<div style="font-size:' + 48 * k + 'px;margin-top:' + 16 * k + 'px;opacity:.9">Time bonus +' + bonus + '</div>' : '') +
        '<div style="font-size:' + 90 * k + 'px;margin-top:' + 20 * k + 'px">Score ' + score + '</div>';
      ov.appendChild(card);
      later(function () {
        b.root.appendChild(ov);
        if (all) confetti(b.root, W, H, ['#ffd166', '#ef476f', '#06d6a0', '#118ab2', '#ffffff']);
      }, all ? 0.5 : 1);
      later(function () { onEnd(score); }, all ? 3.2 : 3.4);
    }

    b.cards.forEach(function (c) { c.el.addEventListener('click', function () { choose(c); }); });
    if (peek > 0) {
      // Everyone gets a look first.
      b.banner.textContent = 'Remember the cards!';
      b.cards.forEach(function (c) { c.inner.style.transition = 'none'; turn(c, true); });
      later(function () {
        b.cards.forEach(function (c, i) {
          c.inner.style.transition = '';
          later(function () { turn(c, false); }, i * 0.03);
        });
        later(start, 0.5 + b.cards.length * 0.03);
      }, peek);
    } else start();

    return {
      stop: function () {
        done = true;
        playing = false;
        timers.forEach(clearTimeout);
        cancelAnimationFrame(raf);
        b.root.remove();
      }
    };
  }

  const backOut = function (t) { const c = 1.70158; t -= 1; return 1 + (c + 1) * t * t * t + c * t * t; };
  function pickWeighted(segs) {
    const ws = segs.map(function (g) { return Math.max(0, num(g.weight, 1)); });
    const total = ws.reduce(function (a, b) { return a + b; }, 0);
    if (!total) return Math.floor(Math.random() * segs.length);
    let x = Math.random() * total;
    for (let i = 0; i < segs.length; i++) { x -= ws[i]; if (x < 0) return i; }
    return segs.length - 1;
  }

  /* ---------- Whack Attack (canvas) ----------
     Critters pop out of holes in the grass; tap them before they duck back.
     Bombs cost points, stars are worth more, and it gets quicker. */
  function whackLayout(W, H, k) {
    const land = W > H;
    const cols = land ? 5 : 3, rows = land ? 2 : 4;
    const top = 300 * k, bottom = 110 * k, side = 60 * k;
    const cw = (W - side * 2) / cols, ch = (H - top - bottom) / rows;
    const rx = Math.min(cw * 0.36, 150 * k);
    const holes = [];
    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) holes.push({ x: side + cw * (c + 0.5), y: top + ch * (r + 0.74), rx: rx, ry: rx * 0.36 });
    }
    return holes;
  }
  function drawHole(ctx, o, cfg, k, critter) {
    ctx.fillStyle = cfg.moundColor || '#8b5a2b';
    ctx.beginPath();
    ctx.ellipse(o.x, o.y + o.ry * 0.25, o.rx * 1.3, o.ry * 1.7, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,.12)';
    ctx.beginPath();
    ctx.ellipse(o.x, o.y - o.ry * 0.55, o.rx * 1.1, o.ry * 0.6, 0, Math.PI, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = cfg.holeColor || '#2b1a0e';
    ctx.beginPath();
    ctx.ellipse(o.x, o.y, o.rx, o.ry, 0, 0, Math.PI * 2);
    ctx.fill();
    if (critter) {
      // The critter shows above the hole and inside it, never below its rim.
      ctx.save();
      ctx.beginPath();
      ctx.rect(o.x - o.rx * 3, o.y - o.rx * 6, o.rx * 6, o.rx * 6);
      ctx.ellipse(o.x, o.y, o.rx, o.ry, 0, 0, Math.PI * 2);
      ctx.clip();
      critter();
      ctx.restore();
    }
    ctx.strokeStyle = cfg.moundColor || '#8b5a2b';
    ctx.lineWidth = 12 * k;
    ctx.beginPath();
    ctx.ellipse(o.x, o.y, o.rx, o.ry, 0, 0.1, Math.PI - 0.1);
    ctx.stroke();
  }
  function whack(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    const good = list(cfg.critters, ['🐹', '🐭', '🐰']);
    const bad = list(cfg.bad, ['💣']);
    const bonus = list(cfg.bonus, ['⭐']);
    const hammer = list(cfg.hammer, ['🔨'])[0];
    const size = num(cfg.itemSize, 190) * k;
    const holes = whackLayout(W, H, k);
    let acc = 0.5, api = null, swing = null, streak = 0;
    const hooks = {
      down: function (p) {
        swing = { x: p.x, y: p.y, t: 0 };
        if (!api || api.st.phase !== 'play') return;
        const o = holes.find(function (o) {
          const it = o.item;
          if (!it || it.hit || it.rise < 0.35) return false;
          const top = o.y + size * 0.55 - size * 0.95 * it.rise - size * 0.6;
          return Math.abs(p.x - o.x) < Math.max(o.rx, size * 0.55) && p.y > top && p.y < o.y + o.ry * 1.8;
        });
        if (!o) { streak = 0; return; }
        const it = o.item;
        it.hit = 0.001;
        const cy = o.y + size * 0.55 - size * 0.95 * it.rise;
        if (it.type === 'bad') {
          streak = 0;
          api.add(-num(cfg.penalty, 20), o.x, cy - size * 0.6);
          api.shake(36);
          api.flash('#ff2d2d');
          api.burst(o.x, cy, '#3a3a3a', 18);
          return;
        }
        streak++;
        api.add(it.type === 'bonus' ? num(cfg.bonusPoints, 30) : num(cfg.points, 10), o.x, cy - size * 0.6, it.type === 'bonus' ? '#ffe14d' : '#ffffff');
        api.burst(o.x, cy, it.type === 'bonus' ? '#ffe14d' : '#ffffff', 12, 650);
        api.shake(10);
        if (streak % 5 === 0) api.say(streak + ' IN A ROW!', W / 2, 240 * k);
      },
      update: function (dt, prog) {
        const up = lerp(num(cfg.upStart, 1.1), num(cfg.upEnd, 0.55), prog);
        acc += dt * lerp(num(cfg.rateStart, 1.2), num(cfg.rateEnd, 2.6), prog);
        while (acc >= 1) {
          acc -= 1;
          const free = holes.filter(function (o) { return !o.item; });
          if (!free.length) break;
          const r = Math.random() * 100, badPct = num(cfg.badChance, 15);
          const type = r < badPct ? 'bad' : r < badPct + num(cfg.bonusChance, 8) ? 'bonus' : 'good';
          pick(free).item = { type: type, ch: pick(type === 'bad' ? bad : type === 'bonus' ? bonus : good), t: 0, up: up * (type === 'bonus' ? 0.75 : 1), rise: 0, hit: 0 };
        }
        const R = 0.16;
        holes.forEach(function (o) {
          const it = o.item;
          if (!it) return;
          it.t += dt;
          if (it.hit) {
            it.hit += dt;
            if (it.hit > 0.25) it.rise = Math.max(0, it.rise - dt * 7);
            if (it.hit > 0.5) o.item = null;
          } else if (it.t < R) it.rise = backOut(it.t / R);
          else if (it.t < R + it.up) it.rise = 1;
          else if (it.t < R * 2 + it.up) it.rise = 1 - (it.t - R - it.up) / R;
          else {
            o.item = null;
            if (it.type === 'good') streak = 0;
          }
        });
      },
      draw: function (ctx, dt) {
        const g = ctx.createLinearGradient(0, 0, 0, H);
        g.addColorStop(0, cfg.bgTop || '#8bd450');
        g.addColorStop(1, cfg.bgBottom || '#3d8b37');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = 'rgba(255,255,255,.07)';
        for (let x = 0; x < W; x += 360 * k) ctx.fillRect(x, 0, 180 * k, H);
        const t = api ? api.st.t : 0;
        holes.forEach(function (o) {
          const it = o.item;
          drawHole(ctx, o, cfg, k, it && it.rise > 0 ? function () {
            const y = o.y + size * 0.55 - size * 0.95 * it.rise;
            const wob = it.hit ? 0 : Math.sin(t * 9 + o.x) * 0.06;
            drawSprite(ctx, sprite(it.ch, size), o.x, y + (it.hit ? size * 0.1 : 0), wob, it.hit ? 1.18 : 1, it.hit ? 0.78 : 1);
            if (it.hit && it.type !== 'bad') drawSprite(ctx, sprite('💫', size * 0.45), o.x + Math.sin(it.hit * 14) * size * 0.2, y - size * 0.55);
          } : null);
        });
        if (swing) {
          swing.t += dt;
          const a = swing.t < 0.07 ? lerp(0.9, -0.5, swing.t / 0.07) : -0.5;
          ctx.globalAlpha = Math.max(0, 1 - Math.max(0, swing.t - 0.15) * 6);
          drawSprite(ctx, sprite(hammer, size * 0.9), swing.x + size * 0.32, swing.y - size * 0.28, a);
          ctx.globalAlpha = 1;
          if (swing.t > 0.35) swing = null;
        }
      }
    };
    const g = canvasGame(layer, W, H, Object.assign({ hint: 'Tap the critters!' }, cfg), hooks, onEnd);
    api = g.api;
    return { stop: g.stop };
  }

  /* ---------- Tower Stack (canvas) ----------
     A block slides across above the tower; tap to drop it. The part that
     overhangs falls off, so the next block is narrower. A perfect drop keeps
     the full width and scores a bonus. */
  function stackColors(cfg) {
    const c = String(cfg.colors || '').match(/#[0-9a-fA-F]{3,8}\b/g);
    return c && c.length ? c : ['#ff006e', '#fb5607', '#ffbe0b', '#8ac926', '#1982c4', '#6a4c93'];
  }
  function stackBlock(ctx, x, y, w, bh, color, k) {
    if (w <= 0) return;
    const r = Math.min(14 * k, w / 2);
    ctx.fillStyle = color;
    roundRect(ctx, x, y, w, bh - 5 * k, r);
    ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,.3)';
    roundRect(ctx, x + 6 * k, y + 6 * k, Math.max(0, w - 12 * k), bh * 0.2, Math.min(8 * k, Math.max(0, w - 12 * k) / 2));
    ctx.fill();
    ctx.fillStyle = 'rgba(0,0,0,.18)';
    ctx.fillRect(x + r, y + bh * 0.7, Math.max(0, w - r * 2), bh * 0.14);
  }
  function stack(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    const colors = stackColors(cfg);
    const bh = num(cfg.blockHeight, 110) * k;
    const baseW = Math.min(W * 0.9, num(cfg.startWidth, 600) * k);
    const groundY = H - 170 * k;
    const tol = 16 * k;
    const tower = [{ x: (W - baseW) / 2, w: baseW, c: colors[0] }];
    const stars = [];
    for (let i = 0; i < 46; i++) stars.push({ x: Math.random() * W, y: Math.random() * H, r: (2 + Math.random() * 4) * k, a: 0.3 + Math.random() * 0.6 });
    let cur = null, pieces = [], rings = [], cam = 0, dir = 1, api = null;
    const levelY = function (n) { return groundY - (n + 1) * bh; };
    function next() {
      const top = tower[tower.length - 1];
      dir = -dir;
      cur = { x: dir > 0 ? -top.w * 0.25 : W - top.w * 0.75, w: top.w, c: colors[tower.length % colors.length], dir: dir };
    }
    function drop() {
      if (!api || api.st.phase !== 'play' || !cur) return;
      const top = tower[tower.length - 1];
      const y = levelY(tower.length);
      const left = Math.max(cur.x, top.x), right = Math.min(cur.x + cur.w, top.x + top.w);
      const overlap = right - left;
      if (overlap < 8 * k) {
        pieces.push({ x: cur.x, w: cur.w, y: y, vx: cur.dir * 160 * k, vy: 0, rot: 0, vr: cur.dir * 2.5, c: cur.c });
        api.shake(30);
        api.flash('#ff2d2d');
        if (cfg.missEnds !== false) { cur = null; api.end('MISSED!'); return; }
        next();
        return;
      }
      if (Math.abs(cur.x - top.x) <= tol) {
        tower.push({ x: top.x, w: top.w, c: cur.c });
        api.add(num(cfg.points, 10) + num(cfg.perfectBonus, 10), W / 2, y + cam - 50 * k, '#ffe14d');
        api.say('PERFECT!', W / 2, y + cam - 170 * k);
        rings.push({ x: top.x, w: top.w, y: y, t: 0 });
      } else {
        tower.push({ x: left, w: overlap, c: cur.c });
        const cutLeft = cur.x < top.x;
        pieces.push({ x: cutLeft ? cur.x : right, w: cur.w - overlap, y: y, vx: (cutLeft ? -1 : 1) * 180 * k, vy: -200 * k, rot: 0, vr: (cutLeft ? -1 : 1) * 3, c: cur.c });
        api.add(num(cfg.points, 10), left + overlap / 2, y + cam - 50 * k);
      }
      api.burst(left + overlap / 2, y + cam + bh, 'rgba(255,255,255,.85)', 8, 320);
      next();
    }
    next();
    const hooks = {
      down: drop,
      update: function (dt, prog) {
        if (!cur) return;
        cur.x += cur.dir * lerp(num(cfg.speedStart, 600), num(cfg.speedEnd, 1300), prog) * k * dt;
        const lo = -cur.w * 0.25, hi = W - cur.w * 0.75;
        if (cur.x < lo) { cur.x = lo; cur.dir = 1; }
        if (cur.x > hi) { cur.x = hi; cur.dir = -1; }
      },
      draw: function (ctx, dt) {
        cam += (Math.max(0, H * 0.45 - levelY(tower.length)) - cam) * Math.min(1, dt * 4);
        const g = ctx.createLinearGradient(0, 0, 0, H);
        g.addColorStop(0, cfg.bgTop || '#0f172a');
        g.addColorStop(1, cfg.bgBottom || '#3a86ff');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, H);
        // Stars drift down as the tower climbs.
        ctx.fillStyle = '#ffffff';
        stars.forEach(function (s) {
          ctx.globalAlpha = s.a * (0.6 + 0.4 * Math.sin((api ? api.st.t : 0) * 2 + s.x));
          ctx.beginPath();
          ctx.arc(s.x, ((s.y + cam * 0.3) % H + H) % H, s.r, 0, Math.PI * 2);
          ctx.fill();
        });
        ctx.globalAlpha = 1;
        const gy = groundY + cam;
        if (gy < H) {
          ctx.fillStyle = 'rgba(0,0,0,.32)';
          ctx.fillRect(0, gy, W, H - gy);
        }
        tower.forEach(function (b, i) {
          const y = levelY(i) + cam;
          if (y < H + bh && y > -bh) stackBlock(ctx, b.x, y, b.w, bh, b.c, k);
        });
        rings.forEach(function (r) {
          r.t += dt;
          const grow = r.t * 140 * k;
          ctx.globalAlpha = Math.max(0, 1 - r.t * 2);
          ctx.strokeStyle = '#ffffff';
          ctx.lineWidth = 6 * k;
          roundRect(ctx, r.x - grow, r.y + cam - grow, r.w + grow * 2, bh + grow * 2, 18 * k);
          ctx.stroke();
          ctx.globalAlpha = 1;
        });
        rings = rings.filter(function (r) { return r.t < 0.5; });
        pieces.forEach(function (p) {
          p.vy += 2600 * k * dt;
          p.x += p.vx * dt;
          p.y += p.vy * dt;
          p.rot += p.vr * dt;
          ctx.save();
          ctx.translate(p.x + p.w / 2, p.y + cam + bh / 2);
          ctx.rotate(p.rot);
          stackBlock(ctx, -p.w / 2, -bh / 2, p.w, bh, p.c, k);
          ctx.restore();
        });
        pieces = pieces.filter(function (p) { return p.y + cam < H + 400 * k; });
        if (cur) {
          const y = levelY(tower.length) + cam;
          ctx.globalAlpha = 0.25;
          ctx.fillStyle = cur.c;
          ctx.fillRect(cur.x, y + bh, cur.w, 6 * k);
          ctx.globalAlpha = 1;
          stackBlock(ctx, cur.x, y, cur.w, bh, cur.c, k);
        }
        label(ctx, String(tower.length - 1), W / 2, 250 * k, 110 * k, 'rgba(255,255,255,.92)');
      }
    };
    const g = canvasGame(layer, W, H, Object.assign({ hint: 'Tap to drop!' }, cfg), hooks, onEnd);
    api = g.api;
    return { stop: g.stop };
  }

  /* ---------- Hoop Shot (canvas) ----------
     Swipe up to throw; the ball arcs up and has to drop through the rim.
     A shot that doesn't touch the rim is worth a bonus. After each basket
     the hoop moves, and later in the round it keeps moving. */
  function hoopLayout(W, H, k, ballSize) {
    const bsize = ballSize * k;
    return { bsize: bsize, startX: W / 2, startY: H - 300 * k, hoopY: H * (W > H ? 0.36 : 0.3), rimW: bsize * 1.75, floorY: H - 150 * k };
  }
  function drawHoop(ctx, L, x, cfg, k, netStretch, part) {
    const rimW = L.rimW, y = L.hoopY;
    const bw = rimW * 1.6, bhh = rimW * 1.05;
    if (part !== 'front') {
      ctx.fillStyle = 'rgba(0,0,0,.35)';
      ctx.fillRect(x - 9 * k, y + 10 * k, 18 * k, L.floorY - y);
      ctx.fillStyle = cfg.boardColor || '#ffffff';
      roundRect(ctx, x - bw / 2, y - bhh * 0.88, bw, bhh, 18 * k);
      ctx.fill();
      ctx.strokeStyle = 'rgba(0,0,0,.18)';
      ctx.lineWidth = 6 * k;
      ctx.stroke();
      ctx.strokeStyle = cfg.rimColor || '#f97316';
      ctx.lineWidth = 8 * k;
      ctx.strokeRect(x - rimW * 0.42, y - bhh * 0.5, rimW * 0.84, bhh * 0.44);
      ctx.strokeStyle = 'rgba(0,0,0,.3)';
      ctx.lineWidth = 12 * k;
      ctx.beginPath();
      ctx.ellipse(x, y, rimW / 2, rimW * 0.12, 0, Math.PI, Math.PI * 2);
      ctx.stroke();
      return;
    }
    // Net, then the front of the rim.
    const nh = rimW * 0.62 * (1 + netStretch * 0.35);
    ctx.strokeStyle = cfg.netColor || '#ffffff';
    ctx.lineWidth = 4 * k;
    for (let i = 0; i <= 6; i++) {
      const f = i / 6 - 0.5;
      ctx.beginPath();
      ctx.moveTo(x + f * rimW, y);
      ctx.lineTo(x + f * rimW * 0.6, y + nh);
      ctx.stroke();
    }
    for (let j = 1; j <= 3; j++) {
      const f = j / 3.4;
      const half = rimW / 2 * (1 - f * 0.4);
      ctx.beginPath();
      for (let i = 0; i <= 6; i++) {
        const xx = x - half + (half * 2) * i / 6;
        const yy = y + nh * f + (i % 2 ? 8 * k : 0);
        if (i) ctx.lineTo(xx, yy); else ctx.moveTo(xx, yy);
      }
      ctx.stroke();
    }
    ctx.strokeStyle = cfg.rimColor || '#f97316';
    ctx.lineWidth = 13 * k;
    ctx.beginPath();
    ctx.ellipse(x, y, rimW / 2, rimW * 0.12, 0, 0, Math.PI);
    ctx.stroke();
  }
  function hoop(cfg, layer, W, H, onEnd) {
    const k = Math.min(W, H) / 1080;
    const L = hoopLayout(W, H, k, num(cfg.ballSize, 170));
    const ballCh = list(cfg.ball, ['🏀'])[0];
    const G = 4200 * k, pr = 9 * k;
    const vNeed = Math.sqrt(2 * G * (L.startY - L.hoopY + L.bsize * 0.9));
    const rim = { x: W / 2, target: W / 2 };
    let ball = null, api = null, swipe = null, net = 0, streak = 0, shots = 0, trail = [];
    const scaleAt = function (y) { return lerp(1, 0.78, (L.startY - y) / (L.startY - L.hoopY)); };
    function newBall() { ball = { x: L.startX, y: L.startY, vx: 0, vy: 0, rot: 0, spin: 0, flying: false, scored: false, touched: false, pop: 0 }; trail = []; }
    newBall();
    const hooks = {
      down: function (p) { if (ball && !ball.flying) swipe = { x: p.x, y: p.y }; },
      up: function (p) {
        const s = swipe;
        swipe = null;
        if (!s || !ball || ball.flying || !api || api.st.phase !== 'play') return;
        const dx = p.x - s.x, dy = p.y - s.y;
        if (dy > -50 * k) return;
        const power = Math.max(0.55, Math.min(1.25, -dy / (H * 0.25)));
        ball.vy = -vNeed * power;
        ball.vx = Math.max(-1600 * k, Math.min(1600 * k, dx / -dy * vNeed * power * 0.5));
        ball.spin = ball.vx / (L.bsize * 0.5) * 0.5 - 4;
        ball.flying = true;
        shots++;
      },
      update: function (dt, prog) {
        rim.x += ((cfg.hoopMoves !== false && prog > 0.35 ? W / 2 + Math.sin(api.st.t * lerp(0.9, 1.8, prog)) * W * 0.22 : rim.target) - rim.x) * Math.min(1, dt * 3);
        if (!ball.flying) return;
        const py = ball.y;
        ball.vy += G * dt;
        ball.x += ball.vx * dt;
        ball.y += ball.vy * dt;
        ball.rot += ball.spin * dt;
        trail.push({ x: ball.x, y: ball.y, s: scaleAt(ball.y) });
        if (trail.length > 10) trail.shift();
        const rb = L.bsize * 0.45 * scaleAt(ball.y);
        [rim.x - L.rimW / 2, rim.x + L.rimW / 2].forEach(function (rx) {
          const dx = ball.x - rx, dy = ball.y - L.hoopY, d = Math.hypot(dx, dy), min = rb + pr;
          if (d < min && d > 0) {
            const nx = dx / d, ny = dy / d, vn = ball.vx * nx + ball.vy * ny;
            ball.x = rx + nx * min;
            ball.y = L.hoopY + ny * min;
            if (vn < 0) { ball.vx -= 1.55 * vn * nx; ball.vy -= 1.55 * vn * ny; }
            ball.touched = true;
          }
        });
        if (!ball.scored && ball.vy > 0 && py < L.hoopY && ball.y >= L.hoopY && Math.abs(ball.x - rim.x) < L.rimW / 2 - pr) {
          ball.scored = true;
          streak++;
          net = 1;
          const clean = !ball.touched;
          api.add(num(cfg.points, 20) + (clean ? num(cfg.swishBonus, 10) : 0), rim.x, L.hoopY - 120 * k, '#ffe14d');
          api.say(streak >= 3 ? 'ON FIRE!' : clean ? 'SWISH!' : 'NICE!', W / 2, L.hoopY + L.rimW * 1.2, streak >= 3 ? '#ff7b00' : '#ffffff');
          api.burst(rim.x, L.hoopY + 40 * k, cfg.rimColor || '#f97316', 16, 700);
          rim.target = W * (0.28 + Math.random() * 0.44);
        }
        if (ball.y > H + L.bsize || ball.x < -L.bsize * 2 || ball.x > W + L.bsize * 2) {
          if (!ball.scored) streak = 0;
          newBall();
        }
      },
      draw: function (ctx, dt) {
        const t = api ? api.st.t : 0;
        net = Math.max(0, net - dt * 2.5);
        const g = ctx.createLinearGradient(0, 0, 0, H);
        g.addColorStop(0, cfg.bgTop || '#1e1b4b');
        g.addColorStop(1, cfg.bgBottom || '#4338ca');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, H);
        // Arena lights.
        [0.15, 0.5, 0.85].forEach(function (f, i) {
          const lx = W * f, ly = H * 0.06;
          const rg = ctx.createRadialGradient(lx, ly, 0, lx, ly, 420 * k);
          rg.addColorStop(0, 'rgba(255,255,255,' + (0.22 + 0.06 * Math.sin(t * 2 + i)) + ')');
          rg.addColorStop(1, 'rgba(255,255,255,0)');
          ctx.fillStyle = rg;
          ctx.fillRect(lx - 420 * k, ly - 420 * k, 840 * k, 840 * k);
        });
        ctx.fillStyle = cfg.floor || '#9a3412';
        ctx.fillRect(0, L.floorY, W, H - L.floorY);
        ctx.fillStyle = 'rgba(255,255,255,.2)';
        ctx.fillRect(0, L.floorY, W, 8 * k);
        drawHoop(ctx, L, rim.x, cfg, k, net, 'back');
        const fire = streak >= 3;
        trail.forEach(function (p, i) {
          ctx.globalAlpha = (i / trail.length) * 0.35;
          ctx.fillStyle = fire ? '#ff7b00' : '#ffffff';
          ctx.beginPath();
          ctx.arc(p.x, p.y, L.bsize * 0.4 * p.s * (i / trail.length), 0, Math.PI * 2);
          ctx.fill();
        });
        ctx.globalAlpha = 1;
        ball.pop = Math.min(1, ball.pop + dt * 5);
        const s = scaleAt(ball.y) * backOut(ball.pop);
        const rb = L.bsize * 0.45 * scaleAt(ball.y);
        const inHoop = ball.flying && ball.vy > 0 && ball.y > L.hoopY - rb * 1.2;
        const drawBall = function () {
          if (!ball.flying) {
            ctx.fillStyle = 'rgba(0,0,0,.25)';
            ctx.beginPath();
            ctx.ellipse(ball.x, L.startY + L.bsize * 0.48, L.bsize * 0.4, 14 * k, 0, 0, Math.PI * 2);
            ctx.fill();
          }
          const bob = ball.flying ? 0 : Math.sin(t * 4) * 8 * k;
          drawSprite(ctx, sprite(ballCh, L.bsize), ball.x, ball.y - bob, ball.rot, s, s);
        };
        if (inHoop) drawBall();
        drawHoop(ctx, L, rim.x, cfg, k, net, 'front');
        if (!inHoop) drawBall();
        if (!ball.flying && shots < 2) {
          ctx.globalAlpha = 0.6 + 0.4 * Math.sin(t * 5);
          label(ctx, '⬆', ball.x, L.startY - L.bsize * 0.95 - Math.abs(Math.sin(t * 3)) * 30 * k, 90 * k, '#ffffff');
          label(ctx, 'SWIPE UP', ball.x, L.startY + L.bsize * 0.85, 44 * k, '#ffffff');
          ctx.globalAlpha = 1;
        }
      }
    };
    const g = canvasGame(layer, W, H, Object.assign({ hint: 'Swipe up to shoot!' }, cfg), hooks, onEnd);
    api = g.api;
    return { stop: g.stop };
  }

  /* ---------- Scratch & Win (DOM + canvas) ----------
     A card covered in foil; rub it to scratch the foil off. Once enough is
     gone the prize under it is revealed. Prizes and their chances are the
     same list as the Spin Wheel's (cfg.segments). */
  function scratchUI(cfg, W, H, seg) {
    const k = Math.min(W, H) / 1080;
    const land = W > H;
    const text = cfg.textColor || '#ffffff';
    const root = document.createElement('div');
    root.style.cssText = 'position:absolute;inset:0;font-family:' + UI_FONT + ';color:' + text + ';user-select:none;-webkit-user-select:none;';
    const put = function (css, html) {
      const d = document.createElement('div');
      d.style.cssText = 'position:absolute;' + css;
      if (html != null) d.textContent = html;
      root.appendChild(d);
      return d;
    };
    put('left:' + 60 * k + 'px;right:' + 60 * k + 'px;top:' + (land ? 60 : 190) * k + 'px;text-align:center;font-weight:900;letter-spacing:' + 3 * k + 'px;font-size:' + 104 * k +
      'px;line-height:1.05;text-shadow:0 ' + 6 * k + 'px ' + 24 * k + 'px rgba(0,0,0,.3);', cfg.title || 'SCRATCH & WIN');
    put('left:' + 80 * k + 'px;right:' + 80 * k + 'px;top:' + (land ? 190 : 330) * k + 'px;text-align:center;font-weight:600;opacity:.88;font-size:' + 46 * k + 'px;',
      cfg.subtitle || 'Scratch the card to reveal your prize!');
    const cw = Math.min(W * 0.84, (land ? 1000 : 900) * k), ch = cw * (land ? 0.62 : 0.8);
    const left = (W - cw) / 2, top = (land ? H * 0.56 : H * 0.47) - ch / 2;
    const card = put('left:' + left + 'px;top:' + top + 'px;width:' + cw + 'px;height:' + ch + 'px;border-radius:' + 44 * k + 'px;overflow:hidden;box-sizing:border-box;' +
      'border:' + 12 * k + 'px solid ' + (cfg.cardBorder || '#ffd166') + ';background:' + (seg.color || '#222') + ';box-shadow:0 ' + 30 * k + 'px ' + 70 * k + 'px rgba(0,0,0,.4);');
    const prize = document.createElement('div');
    prize.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:' + 30 * k + 'px;' +
      'font-weight:900;color:' + (seg.textColor || '#ffffff') + ';';
    prize.innerHTML = '<div style="font-size:' + 110 * k + 'px;line-height:1;font-family:' + EMOJI_FONT + '">' + (seg.win !== false ? '🎉' : '🙈') + '</div>' +
      '<div style="font-size:' + 92 * k + 'px;line-height:1.1;margin-top:' + 18 * k + 'px">' + escXml(seg.label) + '</div>';
    card.appendChild(prize);
    const foil = document.createElement('canvas');
    foil.width = Math.round(cw - 24 * k);
    foil.height = Math.round(ch - 24 * k);
    foil.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;display:block;touch-action:none;cursor:crosshair;';
    card.appendChild(foil);
    // The foil: metal, a diagonal sheen, fine lines and the label.
    const fc = foil.getContext('2d');
    const fw = foil.width, fh = foil.height;
    fc.fillStyle = cfg.foilColor || '#c0c4cc';
    fc.fillRect(0, 0, fw, fh);
    const sheen = fc.createLinearGradient(0, 0, fw, fh);
    sheen.addColorStop(0, 'rgba(255,255,255,.45)');
    sheen.addColorStop(0.35, 'rgba(255,255,255,0)');
    sheen.addColorStop(0.55, 'rgba(255,255,255,.35)');
    sheen.addColorStop(0.75, 'rgba(0,0,0,.08)');
    sheen.addColorStop(1, 'rgba(255,255,255,.25)');
    fc.fillStyle = sheen;
    fc.fillRect(0, 0, fw, fh);
    fc.strokeStyle = 'rgba(255,255,255,.18)';
    fc.lineWidth = 2 * k;
    for (let x = -fh; x < fw; x += 22 * k) { fc.beginPath(); fc.moveTo(x, fh); fc.lineTo(x + fh, 0); fc.stroke(); }
    fc.textAlign = 'center';
    fc.textBaseline = 'middle';
    fc.font = '900 ' + 62 * k + 'px ' + UI_FONT;
    fc.fillStyle = 'rgba(255,255,255,.55)';
    fc.fillText(cfg.foilText || 'SCRATCH HERE', fw / 2 + 3 * k, fh / 2 + 3 * k);
    fc.fillStyle = 'rgba(0,0,0,.32)';
    fc.fillText(cfg.foilText || 'SCRATCH HERE', fw / 2, fh / 2);
    fc.font = 54 * k + 'px ' + EMOJI_FONT;
    [[0.12, 0.2], [0.88, 0.2], [0.12, 0.8], [0.88, 0.8]].forEach(function (p) { fc.fillText('✨', fw * p[0], fh * p[1]); });
    const caption = put('left:' + 60 * k + 'px;right:' + 60 * k + 'px;top:' + (top + ch + 60 * k) + 'px;text-align:center;font-weight:900;font-size:' + 76 * k + 'px;' +
      'text-shadow:0 ' + 6 * k + 'px ' + 24 * k + 'px rgba(0,0,0,.3);visibility:hidden;');
    return { root: root, card: card, foil: foil, caption: caption, left: left, top: top, cw: cw, ch: ch, k: k };
  }
  function scratch(cfg, layer, W, H, onEnd) {
    const segs = wheelSegments(cfg);
    const seg = segs[pickWeighted(segs)];
    const ui = scratchUI(cfg, W, H, seg);
    const k = ui.k;
    layer.appendChild(ui.root);
    const fc = ui.foil.getContext('2d');
    const brush = Math.max(20, num(cfg.brush, 80)) * k;
    const need = Math.max(10, Math.min(95, num(cfg.revealAt, 55))) / 100;
    const timers = [];
    let down = false, last = null, moves = 0, done = false;

    const hand = document.createElement('div');
    hand.textContent = '👆';
    hand.style.cssText = 'position:absolute;left:' + (ui.left + ui.cw / 2 - 70 * k) + 'px;top:' + (ui.top + ui.ch * 0.55) + 'px;font-size:' + 140 * k +
      'px;line-height:1;pointer-events:none;font-family:' + EMOJI_FONT + ';filter:drop-shadow(0 ' + 8 * k + 'px ' + 14 * k + 'px rgba(0,0,0,.35));';
    ui.root.appendChild(hand);
    if (hand.animate) {
      hand.animate([
        { transform: 'translate(' + -180 * k + 'px,0) rotate(-12deg)' },
        { transform: 'translate(' + 180 * k + 'px,' + -30 * k + 'px) rotate(12deg)' },
        { transform: 'translate(' + -180 * k + 'px,0) rotate(-12deg)' }
      ], { duration: 1600, iterations: Infinity, easing: 'ease-in-out' });
    }
    const pt = function (e) {
      const r = ui.foil.getBoundingClientRect();
      return { x: (e.clientX - r.left) / r.width * ui.foil.width, y: (e.clientY - r.top) / r.height * ui.foil.height };
    };
    function flake(p) {
      const f = document.createElement('div');
      const s = (10 + Math.random() * 14) * k;
      f.style.cssText = 'position:absolute;left:' + (ui.left + 12 * k + p.x) + 'px;top:' + (ui.top + 12 * k + p.y) + 'px;width:' + s + 'px;height:' + s * 0.7 +
        'px;background:' + (cfg.foilColor || '#c0c4cc') + ';border-radius:' + 3 * k + 'px;pointer-events:none;box-shadow:inset 0 0 0 ' + 2 * k + 'px rgba(255,255,255,.35);';
      ui.root.appendChild(f);
      if (f.animate) {
        f.animate([{ transform: 'translate(0,0) rotate(0)', opacity: 1 }, { transform: 'translate(' + (Math.random() - 0.5) * 160 * k + 'px,' + (180 + Math.random() * 200) * k + 'px) rotate(' + (Math.random() - 0.5) * 720 + 'deg)', opacity: 0 }],
          { duration: 700 + Math.random() * 300, easing: 'ease-in', fill: 'forwards' });
      }
      timers.push(setTimeout(function () { f.remove(); }, 1100));
    }
    function scrape(p) {
      fc.globalCompositeOperation = 'destination-out';
      fc.lineCap = 'round';
      fc.lineJoin = 'round';
      fc.lineWidth = brush;
      fc.beginPath();
      fc.moveTo((last || p).x, (last || p).y);
      fc.lineTo(p.x + 0.01, p.y);
      fc.stroke();
      last = p;
      moves++;
      if (moves % 3 === 0) flake(p);
      if (moves % 8 === 0) check();
    }
    function check() {
      if (done) return;
      const w = ui.foil.width, hh = ui.foil.height;
      const d = fc.getImageData(0, 0, w, hh).data;
      const step = Math.max(4, Math.floor(w / 60));
      let clear = 0, total = 0;
      for (let y = step >> 1; y < hh; y += step) {
        for (let x = step >> 1; x < w; x += step) { total++; if (d[(y * w + x) * 4 + 3] < 40) clear++; }
      }
      if (clear / total >= need) reveal();
    }
    function reveal() {
      done = true;
      down = false;
      hand.remove();
      ui.foil.style.transition = 'opacity .6s';
      ui.foil.style.opacity = '0';
      if (ui.card.animate) ui.card.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.07)' }, { transform: 'scale(1)' }], { duration: 550, easing: 'ease-out' });
      const win = seg.win !== false;
      ui.caption.textContent = win ? (cfg.winText || 'YOU WON!') : (cfg.loseText || 'NOT THIS TIME');
      ui.caption.style.color = win ? '#ffe14d' : (cfg.textColor || '#ffffff');
      ui.caption.className = 'kp-in';
      ui.caption.style.visibility = 'visible';
      if (win) confetti(ui.root, W, H, [seg.color || '#ffd166', '#ffffff', '#ffd166', '#ef476f', '#06d6a0']);
      timers.push(setTimeout(function () { onEnd(num(seg.points, 0), { win: win, vars: { prize: seg.label } }); }, num(cfg.resultTime, 3) * 1000 + 600));
    }
    ui.foil.addEventListener('pointerdown', function (e) {
      if (done) return;
      down = true;
      last = null;
      hand.style.display = 'none';
      try { ui.foil.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
      scrape(pt(e));
    });
    ui.foil.addEventListener('pointermove', function (e) { if (down && !done) scrape(pt(e)); });
    const lift = function () { down = false; last = null; check(); };
    ui.foil.addEventListener('pointerup', lift);
    ui.foil.addEventListener('pointercancel', lift);
    return {
      stop: function () {
        done = true;
        timers.forEach(clearTimeout);
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

  return { quiz: quiz, catch: catchGame, slice: slice, wheel: wheel, runner: runner, memory: memory, whack: whack, stack: stack, scratch: scratch, hoop: hoop,
    wheelUI: wheelUI, memoryBoard: memoryBoard, memorySymbols: memorySymbols, whackLayout: whackLayout, drawHole: drawHole,
    stackColors: stackColors, stackBlock: stackBlock, hoopLayout: hoopLayout, drawHoop: drawHoop, scratchUI: scratchUI, wheelSegments: wheelSegments, sprite: sprite, list: list, sdk: sdk };
}
