/* Screen editor: stage rendering, selection, drag / resize / snap, drop, zoom, strip. */

let zoom = 0;            // 0 = fit to area
let fitZoom = 0.3;
let stagePan = { x: 0, y: 0 };

function stageScale() { return zoom || fitZoom; }

function layoutStage() {
  const area = $('#stageArea');
  const a = area.getBoundingClientRect();
  if (!a.width) return;
  const W = project.width, H = project.height;
  fitZoom = Math.max(0.05, Math.min((a.width - 80) / W, (a.height - 80) / H));
  const k = stageScale();
  const x = (a.width - W * k) / 2 + stagePan.x;
  const y = (a.height - H * k) / 2 + stagePan.y;
  $('#stageHolder').style.transform = 'translate(' + x + 'px,' + y + 'px) scale(' + k + ')';
  $('#zoomLabel').textContent = Math.round(k * 100) + '%';
}
function fitStage() {
  zoom = 0;
  stagePan = { x: 0, y: 0 };
  layoutStage();
  drawSelection();
}
function setZoom(z) {
  zoom = Math.max(0.05, Math.min(3, z));
  layoutStage();
  drawSelection();
}

/* Preview of a game screen, drawn with DOM in design coordinates. */
function gamePreview(sc, withLabel) {
  const W = project.width, H = project.height;
  const k = Math.min(W, H) / 1080;
  const g = sc.game;
  const c = g.config || {};
  const kind = baseKind(g.kind) || 'custom';
  const info = gameInfo(g.kind);
  const root = h('div', { class: 'game-ph' });
  const add = function (html, css) {
    const d = document.createElement('div');
    d.style.cssText = 'position:absolute;' + css;
    d.innerHTML = html;
    root.appendChild(d);
    return d;
  };
  const emoji = function (ch, x, y, size, rot) {
    add(esc(ch), 'left:' + (x - size / 2) + 'px;top:' + (y - size / 2) + 'px;width:' + size + 'px;height:' + size + 'px;font-size:' + size * 0.85 +
      'px;line-height:' + size + 'px;text-align:center;font-family:' + RT.EMOJI_FONT + ';rotate:' + (rot || 0) + 'deg');
  };
  const pill = function (title, val, right) {
    add('<div style="font-size:' + 26 * k + 'px;opacity:.75">' + title + '</div><div style="font-size:' + 54 * k + 'px">' + val + '</div>',
      (right ? 'right' : 'left') + ':' + 44 * k + 'px;top:' + 44 * k + 'px;width:' + 290 * k + 'px;height:' + 112 * k + 'px;border-radius:' + 56 * k +
      'px;background:rgba(0,0,0,.32);color:#fff;font:900 10px system-ui;display:flex;flex-direction:column;justify-content:center;text-align:center');
  };
  if (kind === 'quiz') {
    const qs = c.questions || [];
    const q = qs[0] || { q: 'Add a question', answers: ['Answer A', 'Answer B', 'Answer C', 'Answer D'] };
    const answers = (q.answers || []).filter(function (a) { return String(a || '').trim(); });
    const color = c.textColor || '#fff';
    add('Question 1 / ' + Math.max(1, qs.length), 'left:' + 70 * k + 'px;top:' + 120 * k + 'px;font:800 ' + 42 * k + 'px system-ui;color:' + color + ';opacity:.85');
    add('★ 0', 'right:' + 70 * k + 'px;top:' + 108 * k + 'px;font:800 ' + 42 * k + 'px system-ui;background:' + (c.accent || '#ffb703') + ';color:#1b1b1b;border-radius:99px;padding:' + 10 * k + 'px ' + 30 * k + 'px');
    if (+c.time) add('', 'left:' + 70 * k + 'px;right:' + 70 * k + 'px;top:' + 210 * k + 'px;height:' + 18 * k + 'px;border-radius:99px;background:' + (c.accent || '#ffb703'));
    const cols = W > H ? 2 : 1;
    const rows = Math.ceil(answers.length / cols);
    const ah = 150 * k, gap = 28 * k;
    const top0 = H - 90 * k - rows * ah - (rows - 1) * gap;
    add(esc(q.q), 'left:' + 70 * k + 'px;right:' + 70 * k + 'px;top:' + 260 * k + 'px;height:' + (top0 - 290 * k) + 'px;display:flex;align-items:center;justify-content:center;text-align:center;font:800 ' +
      (+c.questionSize || 76) * k + 'px/1.15 system-ui;color:' + color);
    const colW = (W - 140 * k - (cols - 1) * gap) / cols;
    answers.forEach(function (a, i) {
      const r = Math.floor(i / cols), cc = i % cols;
      add('<span style="width:' + 80 * k + 'px;height:' + 80 * k + 'px;border-radius:50%;background:rgba(255,255,255,.2);display:inline-flex;align-items:center;justify-content:center;font-size:' + 40 * k + 'px;flex:none">' + 'ABCDEF'.charAt(i) + '</span><span>' + esc(a) + '</span>',
        'left:' + (70 * k + cc * (colW + gap)) + 'px;top:' + (top0 + r * (ah + gap)) + 'px;width:' + colW + 'px;height:' + ah + 'px;box-sizing:border-box;padding:0 ' + 36 * k +
        'px;display:flex;align-items:center;gap:' + 28 * k + 'px;border-radius:' + 36 * k + 'px;background:' + (c.answerBg || 'rgba(255,255,255,.14)') + ';font:800 ' + 48 * k + 'px system-ui;color:' + color);
    });
  } else if (kind === 'catch') {
    root.style.background = 'linear-gradient(180deg,' + (c.bgTop || '#4fc3f7') + ',' + (c.bgBottom || '#b3e5fc') + ')';
    add('', 'left:' + (W / 2 - W * 0.9) + 'px;top:' + (H + 40 * k - 190 * k) + 'px;width:' + W * 1.8 + 'px;height:' + 380 * k + 'px;border-radius:50%;background:' + (c.ground || '#66bb6a'));
    const good = GX.list(c.good, ['🍎']);
    const bad = GX.list(c.bad, ['💣']);
    const isz = (+c.itemSize || 130) * k, csz = (+c.catcherSize || 230) * k;
    [[0.22, 0.24], [0.7, 0.17], [0.46, 0.4], [0.8, 0.52], [0.28, 0.6]].forEach(function (p, i) { emoji(good[i % good.length], p[0] * W, p[1] * H, isz, i * 25 - 30); });
    emoji(bad[0], 0.6 * W, 0.7 * H, isz, 10);
    emoji(GX.list(c.catcher, ['🧺'])[0], W / 2, H - csz * 0.62 - 90 * k, csz, 0);
    pill('SCORE', 0, false);
    pill('TIME', +c.duration || 30, true);
  } else if (kind === 'wheel') {
    root.appendChild(GX.wheelUI(c, W, H, false).root);
  } else if (kind === 'slice') {
    root.style.background = 'repeating-linear-gradient(90deg, transparent 0 ' + 177 * k + 'px, rgba(0,0,0,.18) ' + 177 * k + 'px ' + 183 * k + 'px), linear-gradient(180deg,' + (c.bgTop || '#5d3a1a') + ',' + (c.bgBottom || '#2b1708') + ')';
    const fr = GX.list(c.fruits, ['🍉']);
    const sz = (+c.itemSize || 170) * k;
    [[0.25, 0.3], [0.68, 0.22], [0.5, 0.48], [0.2, 0.62], [0.78, 0.58]].forEach(function (p, i) { emoji(fr[i % fr.length], p[0] * W, p[1] * H, sz, i * 40 - 60); });
    emoji(GX.list(c.bomb, ['💣'])[0], 0.55 * W, 0.72 * H, sz, -15);
    add('', 'left:' + 0.3 * W + 'px;top:' + 0.42 * H + 'px;width:' + 0.45 * W + 'px;height:' + 16 * k + 'px;border-radius:99px;background:' + (c.trail || '#fff') + ';box-shadow:0 0 ' + 30 * k + 'px ' + (c.trail || '#fff') + ';rotate:-28deg');
    pill('SCORE', 0, false);
    pill('TIME', +c.duration || 30, true);
  } else {
    /* Code template: its preview image if it has one, otherwise its icon and name. */
    const t = findTemplate(g.kind);
    const prev = t && t.manifest.preview && t.assets ? t.assets[t.manifest.preview] : '';
    if (prev) add('', 'inset:0;background:url("' + prev + '") center/cover no-repeat');
    else {
      if (c.bgTop || c.bgBottom) root.style.background = 'linear-gradient(180deg,' + (c.bgTop || 'transparent') + ',' + (c.bgBottom || 'transparent') + ')';
      if (info.iconSrc) add('<img src="' + info.iconSrc + '" style="width:100%;height:100%;object-fit:contain">', 'left:' + (W / 2 - 170 * k) + 'px;top:' + (H * 0.3) + 'px;width:' + 340 * k + 'px;height:' + 340 * k + 'px');
      else add(esc(info.icon), 'left:0;right:0;top:' + H * 0.3 + 'px;text-align:center;font-size:' + 300 * k + 'px;line-height:1;font-family:' + RT.EMOJI_FONT);
      add(esc(info.name), 'left:0;right:0;top:' + (H * 0.3 + 390 * k) + 'px;text-align:center;color:#fff;font:900 ' + 90 * k + 'px system-ui;text-shadow:0 6px 20px rgba(0,0,0,.3)');
      add(info.missing ? 'Template missing: import its .zip' : 'Custom game template', 'left:0;right:0;top:' + (H * 0.3 + 510 * k) + 'px;text-align:center;color:rgba(255,255,255,.85);font:600 ' + 44 * k + 'px system-ui');
    }
    pill('SCORE', 0, false);
    pill('TIME', +c.duration || 30, true);
  }
  if (withLabel) {
    add('<b>' + esc(info.icon) + ' ' + esc(info.name) + ' game</b><br><span style="opacity:.75;font-weight:500">Set it up in Properties · press Play to test</span>',
      'left:50%;' + (kind === 'wheel' ? 'bottom:' + 50 * k : 'top:' + 250 * k) + 'px;transform:translateX(-50%);background:rgba(0,0,0,.6);color:#fff;font:600 ' + 34 * k + 'px/1.35 system-ui;padding:' + 18 * k + 'px ' + 34 * k + 'px;border-radius:' + 20 * k + 'px;text-align:center;white-space:nowrap');
  }
  return root;
}

function makeThumb(sc, maxW, maxH) {
  const W = project.width, H = project.height;
  const k = Math.min(maxW / W, maxH / H);
  const t = h('div', { class: 'thumb', style: 'width:' + Math.round(W * k) + 'px;height:' + Math.round(H * k) + 'px' });
  const tin = h('div', { class: 'tin', style: 'width:' + W + 'px;height:' + H + 'px;transform:scale(' + k + ')' });
  tin.style.background = RT.bgCss(sc, assetsMap());
  if (RT.hasFx(sc)) tin.appendChild(RT.renderFx(sc.effect, sc.id, W, H));
  if (sc.type === 'game') tin.appendChild(gamePreview(sc, false));
  else {
    const ctx = editCtx();
    sc.objects.forEach(function (o) { if (!o.hidden) tin.appendChild(RT.renderObj(o, ctx, 'thumb')); });
  }
  t.appendChild(tin);
  return t;
}

/* ---------- Stage ---------- */
function renderStage() {
  if (typeof tlOnStageRender === 'function') tlOnStageRender();
  const sc = curScreen();
  const stage = $('#stage');
  stage.style.width = project.width + 'px';
  stage.style.height = project.height + 'px';
  stage.style.background = RT.bgCss(sc, assetsMap());
  stage.innerHTML = '';
  const info = sc.type === 'game' ? gameInfo(sc.game.kind).name + ' game' : sc.objects.length + ' object' + (sc.objects.length === 1 ? '' : 's');
  $('#stageLabel').innerHTML = '<b>' + esc(sc.name) + '</b> · ' + info + (sc.id === project.startScreen ? ' · start screen' : '');
  clearTimeout(replayTimer);
  if (RT.hasFx(sc)) stage.appendChild(RT.renderFx(sc.effect, sc.id, project.width, project.height));
  if (sc.type === 'game') stage.appendChild(gamePreview(sc, true));
  else {
    const ctx = editCtx();
    sc.objects.forEach(function (o) {
      const wrap = h('div', { class: 'obj' + (o.id === selId ? ' sel' : '') + (o.locked ? ' locked' : '') });
      wrap.dataset.id = o.id;
      setBox(wrap, o);
      if (o.hidden) wrap.style.display = 'none';
      if (o.locked) wrap.style.pointerEvents = 'none';
      wrap.appendChild(RT.renderObj(o, ctx, 'edit'));
      stage.appendChild(wrap);
    });
  }
  layoutStage();
  drawSelection();
}
function setBox(el, o) {
  el.style.left = o.x + 'px';
  el.style.top = o.y + 'px';
  el.style.width = o.w + 'px';
  el.style.height = o.h + 'px';
}
function refreshObjEl(o) {
  const wrap = $('#stage .obj[data-id="' + o.id + '"]');
  if (!wrap) return;
  setBox(wrap, o);
  wrap.innerHTML = '';
  wrap.appendChild(RT.renderObj(o, editCtx(), 'edit'));
}

function drawSelection() {
  const stage = $('#stage');
  const old = stage.querySelector('.sel-box');
  if (old) old.remove();
  $$('#stage .obj').forEach(function (w) { w.classList.toggle('sel', w.dataset.id === selId); });
  const o = selObj();
  if (!o || view !== 'editor') return;
  const k = stageScale();
  const s = 14 / k;
  const b = h('div', { class: 'sel-box' + (o.locked ? ' locked' : '') });
  setBox(b, o);
  b.style.borderWidth = 2 / k + 'px';
  (o.locked ? [] : ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w']).forEach(function (d) {
    const hd = h('div', { class: 'h ' + d });
    hd.dataset.h = d;
    const st = hd.style;
    st.width = st.height = s + 'px';
    st.borderWidth = 2 / k + 'px';
    st.borderRadius = 3 / k + 'px';
    st.margin = '0';
    st.right = st.bottom = 'auto';
    st.left = d.indexOf('w') >= 0 ? -s / 2 + 'px' : d.indexOf('e') >= 0 ? 'calc(100% - ' + s / 2 + 'px)' : 'calc(50% - ' + s / 2 + 'px)';
    st.top = d.indexOf('n') >= 0 ? -s / 2 + 'px' : d.indexOf('s') >= 0 ? 'calc(100% - ' + s / 2 + 'px)' : 'calc(50% - ' + s / 2 + 'px)';
    b.appendChild(hd);
  });
  const lab = h('div', { class: 'sel-size' }, Math.round(o.w) + ' × ' + Math.round(o.h));
  lab.style.fontSize = 12 / k + 'px';
  lab.style.padding = 2 / k + 'px ' + 8 / k + 'px';
  lab.style.borderRadius = 6 / k + 'px';
  lab.style.transform = 'translate(-50%,' + 10 / k + 'px)';
  b.appendChild(lab);
  stage.appendChild(b);
}

/* Play the current screen's entrance and loop animations on the editing canvas,
   then return to the still editing view. */
let replayTimer = 0;
function replayAnimations() {
  if (typeof dockTab !== 'undefined' && dockTab === 'timeline' && view === 'editor' && curScreen().type !== 'game') { TL.t = 0; tlPlay(); return; }
  const sc = curScreen();
  if (view !== 'editor') { openScreen(sc.id); }
  if (sc.type === 'game') { toast('Game screens animate when you press Play'); return; }
  clearTimeout(replayTimer);
  const ctx = editCtx();
  let end = 0;
  sc.objects.forEach(function (o) {
    const wrap = $('#stage .obj[data-id="' + o.id + '"]');
    if (!wrap) return;
    wrap.innerHTML = '';
    wrap.appendChild(RT.renderObj(o, ctx, 'anim'));
    end = Math.max(end, RT.animEnd(o));
  });
  const sb = $('#stage .sel-box');
  if (sb) sb.style.display = 'none';
  if (!end) toast('No entrance animations on this screen yet. Add one in Properties → Animation.');
  replayTimer = setTimeout(function () { if (view === 'editor' && curScreen() === sc) renderStage(); }, (Math.min(end, 15) + 1.5) * 1000);
}

function selectObject(id) {
  selId = id;
  drawSelection();
  renderInspector();
  if (typeof tlSyncSelection === 'function') tlSyncSelection();
}

/* ---------- Snapping ---------- */
function clearGuides() { $$('#stage .guide').forEach(function (g) { g.remove(); }); }
function showGuides(gx, gy) {
  clearGuides();
  const k = stageScale();
  const stage = $('#stage');
  if (gx != null) stage.appendChild(h('div', { class: 'guide', style: 'left:' + (gx - 1 / k) + 'px;top:0;width:' + 2 / k + 'px;height:' + project.height + 'px' }));
  if (gy != null) stage.appendChild(h('div', { class: 'guide', style: 'top:' + (gy - 1 / k) + 'px;left:0;height:' + 2 / k + 'px;width:' + project.width + 'px' }));
}
function snapMove(x, y, w, hh, others) {
  const th = 8 / stageScale();
  const xs = [0, project.width / 2, project.width];
  const ys = [0, project.height / 2, project.height];
  others.forEach(function (o) { xs.push(o.x, o.x + o.w / 2, o.x + o.w); ys.push(o.y, o.y + o.h / 2, o.y + o.h); });
  let bx = null, by = null, dx = th, dy = th;
  [[x, 0], [x + w / 2, w / 2], [x + w, w]].forEach(function (p) {
    xs.forEach(function (c) { const d = Math.abs(p[0] - c); if (d < dx) { dx = d; bx = { pos: c - p[1], line: c }; } });
  });
  [[y, 0], [y + hh / 2, hh / 2], [y + hh, hh]].forEach(function (p) {
    ys.forEach(function (c) { const d = Math.abs(p[0] - c); if (d < dy) { dy = d; by = { pos: c - p[1], line: c }; } });
  });
  showGuides(bx ? bx.line : null, by ? by.line : null);
  return { x: bx ? bx.pos : x, y: by ? by.pos : y };
}

/* ---------- Pointer interactions ---------- */
function initStage() {
  const area = $('#stageArea');
  area.addEventListener('pointerdown', function (e) {
    if (e.button !== 0 || e.target.closest('.float-tools')) return;
    const handle = e.target.closest('.h');
    const objEl = e.target.closest('.obj');
    if (typeof TL !== 'undefined' && TL.active) tlStop();
    if (handle && selObj()) { startDrag(e, 'resize', handle.dataset.h); return; }
    if (objEl) {
      if (objEl.dataset.id !== selId) selectObject(objEl.dataset.id);
      startDrag(e, 'move');
      return;
    }
    if (selId) selectObject(null);
    startPan(e);
  });
  area.addEventListener('dblclick', function (e) {
    const objEl = e.target.closest('.obj');
    if (!objEl) return;
    const f = $('#inspector textarea, #inspector [data-focus]');
    if (f) { f.focus(); if (f.select) f.select(); }
  });
  area.addEventListener('wheel', function (e) {
    e.preventDefault();
    if (e.ctrlKey || e.metaKey) setZoom(stageScale() * (e.deltaY < 0 ? 1.1 : 0.9));
    else {
      stagePan.x -= e.deltaX;
      stagePan.y -= e.deltaY;
      layoutStage();
    }
  }, { passive: false });
  area.addEventListener('dragover', function (e) {
    const t = e.dataTransfer.types;
    if (t.indexOf('text/kiosk-object') >= 0 || t.indexOf('text/kiosk-emoji') >= 0 || t.indexOf('Files') >= 0) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      if (!$('#stage .drop-hint')) $('#stage').appendChild(h('div', { class: 'drop-hint' }));
    }
  });
  area.addEventListener('dragleave', function (e) {
    if (!area.contains(e.relatedTarget)) { const d = $('#stage .drop-hint'); if (d) d.remove(); }
  });
  area.addEventListener('drop', function (e) {
    e.preventDefault();
    const d = $('#stage .drop-hint');
    if (d) d.remove();
    const r = $('#stage').getBoundingClientRect();
    const k = stageScale();
    const x = (e.clientX - r.left) / k, y = (e.clientY - r.top) / k;
    const kind = e.dataTransfer.getData('text/kiosk-object');
    const em = e.dataTransfer.getData('text/kiosk-emoji');
    if (kind) addObject(kind, x, y);
    else if (em) addObject('emoji', x, y, { emoji: em, x: Math.round(x - 150), y: Math.round(y - 150) });
    else if (e.dataTransfer.files.length && /\.zip$/i.test(e.dataTransfer.files[0].name)) importTemplateFile(e.dataTransfer.files[0]);
    else if (e.dataTransfer.files.length) uploadImages(e.dataTransfer.files);
  });
  if (window.ResizeObserver) new ResizeObserver(function () { if (view === 'editor') { layoutStage(); drawSelection(); } }).observe(area);
}

function startPan(e) {
  const sx = e.clientX, sy = e.clientY, ox = stagePan.x, oy = stagePan.y;
  const move = function (ev) { stagePan.x = ox + ev.clientX - sx; stagePan.y = oy + ev.clientY - sy; layoutStage(); };
  const up = function () { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); };
  window.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
}

function startDrag(e, mode, dir) {
  e.preventDefault();
  const o = selObj();
  if (!o) return;
  const k = stageScale();
  const st = { x: e.clientX, y: e.clientY, ox: o.x, oy: o.y, ow: o.w, oh: o.h };
  const snap = snapshot();
  const others = curScreen().objects.filter(function (x) { return x.id !== o.id; });
  let moved = false;
  const move = function (ev) {
    const dx = (ev.clientX - st.x) / k, dy = (ev.clientY - st.y) / k;
    if (!moved && Math.hypot(ev.clientX - st.x, ev.clientY - st.y) < 3) return;
    moved = true;
    if (mode === 'move') {
      let nx = st.ox + dx, ny = st.oy + dy;
      if (!ev.altKey) { const p = snapMove(nx, ny, o.w, o.h, others); nx = p.x; ny = p.y; } else clearGuides();
      o.x = Math.round(nx);
      o.y = Math.round(ny);
    } else {
      let x = st.ox, y = st.oy, w = st.ow, hh = st.oh;
      if (dir.indexOf('e') >= 0) w = st.ow + dx;
      if (dir.indexOf('w') >= 0) { w = st.ow - dx; x = st.ox + dx; }
      if (dir.indexOf('s') >= 0) hh = st.oh + dy;
      if (dir.indexOf('n') >= 0) { hh = st.oh - dy; y = st.oy + dy; }
      if ((ev.shiftKey || o.type === 'emoji') && dir.length === 2) {
        const ratio = st.ow / st.oh;
        if (w / hh > ratio) w = hh * ratio; else hh = w / ratio;
        if (dir.indexOf('w') >= 0) x = st.ox + st.ow - w;
        if (dir.indexOf('n') >= 0) y = st.oy + st.oh - hh;
      }
      if (w < 20) { if (dir.indexOf('w') >= 0) x -= 20 - w; w = 20; }
      if (hh < 20) { if (dir.indexOf('n') >= 0) y -= 20 - hh; hh = 20; }
      o.x = Math.round(x); o.y = Math.round(y); o.w = Math.round(w); o.h = Math.round(hh);
    }
    refreshObjEl(o);
    drawSelection();
    syncTransformFields(o);
  };
  const up = function () {
    window.removeEventListener('pointermove', move);
    window.removeEventListener('pointerup', up);
    clearGuides();
    if (moved) {
      pushUndo(snap);
      save();
      scheduleThumbs();
    }
  };
  window.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
}

function nudge(dx, dy) {
  const o = selObj();
  if (!o || o.locked) return;
  const snap = snapshot();
  o.x += dx;
  o.y += dy;
  pushUndo(snap);
  save();
  refreshObjEl(o);
  drawSelection();
  syncTransformFields(o);
  scheduleThumbs();
}

/* ---------- Bottom strip ---------- */
function renderStrip() {
  const strip = $('#strip');
  const scroll = strip.scrollLeft;
  strip.innerHTML = '';
  project.screens.forEach(function (sc) {
    const th = makeThumb(sc, 110, 84);
    th.classList.add('strip-thumb');
    if (sc.id === project.startScreen) th.appendChild(h('span', { class: 'badge strip-start' }, 'START'));
    strip.appendChild(h('div', { class: 'strip-item' + (sc.id === curId ? ' on' : ''), title: sc.name, onclick: function () { if (sc.id !== curId) openScreen(sc.id); } },
      th, h('span', {}, (sc.type === 'game' ? gameInfo(sc.game.kind).icon + ' ' : '') + sc.name)));
  });
  strip.appendChild(h('button', { class: 'strip-add', title: 'Add a page screen', html: icon('plus'), onclick: function () { addScreen('page'); } }));
  strip.scrollLeft = scroll;
}
