/*
 * Layer timeline. One row per object (front-most on top). Each row shows three phases:
 *   Start (entrance) -> Meanwhile (loop) -> Exit (disappear)
 * Drag a bar to move it, drag its right edge to change its length, double-click (or right-click)
 * to pick the effect. Scrub the ruler or press Play to preview the screen on the canvas.
 */

let dockTab = 'screens';
const TL = { t: 0, pps: 110, len: 6, playing: false, active: false, raf: 0, wall: 0, t0: 0, screenId: null };
const TL_NAME_W = 230;
const TL_SNAP = 0.05;
const scheduleTimeline = debounce(function () { renderTimeline(); }, 120);

function tlScreen() {
  const sc = curScreen();
  return sc && sc.type !== 'game' ? sc : null;
}
function tlLength(sc) {
  let end = 4;
  sc.objects.forEach(function (o) {
    const p = RT.phases(o);
    end = Math.max(end, p.enterEnd + (o.anim && o.anim !== 'none' ? 2 : 0), p.exitEnd || 0);
  });
  if (sc.timer && sc.timer.seconds > 0) end = Math.max(end, +sc.timer.seconds);
  return Math.ceil(end + 1);
}
function snapT(t, free) {
  t = Math.max(0, t);
  return free ? Math.round(t * 100) / 100 : Math.round(Math.round(t / TL_SNAP) * TL_SNAP * 100) / 100;
}
function optLabel(list, v) {
  const f = list.find(function (x) { return x[0] === v; });
  return f ? f[1] : v;
}
function layerName(o) {
  if (o.name) return o.name;
  const txt = String(o.text || '').replace(/\s+/g, ' ').trim();
  switch (o.type) {
    case 'text': return txt.slice(0, 28) || 'Text';
    case 'button': return 'Button · ' + (txt.slice(0, 20) || 'untitled');
    case 'image': return 'Image';
    case 'emoji': return ART.label(o.emoji) + ' sticker';
    case 'shape': return o.shape === 'circle' ? 'Circle' : 'Box';
    case 'input': return 'Input · ' + (o.field || 'field');
  }
  return o.type;
}
function layerIcon(o) {
  return o.type === 'shape' ? (o.shape === 'circle' ? 'circle' : 'rect') : o.type === 'emoji' ? 'sticker' : o.type;
}
/* A sensible default for when an exit should start: before the screen's auto-advance, or a few seconds after the entrance. */
function defaultExitAt(o) {
  const p = RT.phases(Object.assign({}, o, { exit: 'none' }));
  const sc = curScreen();
  let t = sc && sc.timer && sc.timer.seconds > 0 ? Math.max(p.enterEnd + 0.5, sc.timer.seconds - 0.8) : p.enterEnd + 3;
  return Math.round(t * 10) / 10;
}

/* ---------- Dock (Screens strip / Timeline) ---------- */
function setDock(tab) {
  if (view !== 'editor') setView('editor');
  dockTab = tab;
  $$('#dockTabs button').forEach(function (b) { b.classList.toggle('on', b.dataset.dock === tab); });
  $('#strip').hidden = tab !== 'screens';
  $('#timeline').hidden = tab !== 'timeline';
  $('#tlTools').hidden = tab !== 'timeline';
  try { localStorage.setItem('kioskStudio.dock', tab); } catch (e) { /* ignore */ }
  if (tab === 'timeline') renderTimeline();
  else { tlStop(); renderStrip(); }
  layoutStage();
  drawSelection();
}

/* ---------- Rendering ---------- */
function renderTimeline() {
  if (dockTab !== 'timeline' || view !== 'editor') return;
  const box = $('#tlScroll');
  const sc = curScreen();
  const keepX = box.scrollLeft, keepY = box.scrollTop;
  box.innerHTML = '';
  if (!sc) return;
  if (sc.id !== TL.screenId) { TL.screenId = sc.id; TL.t = 0; }
  if (sc.type === 'game') {
    box.append(h('div', { class: 'tl-empty' }, 'Game screens run their own animation. Open a page screen to build its timeline.'));
    TL.len = 0;
    updateTlTools();
    return;
  }
  TL.len = Math.max(tlLength(sc), Math.ceil(TL.t));
  const pps = TL.pps;
  const trackW = (TL.len + 3) * pps;
  const grid = h('div', { class: 'tl-grid', style: 'width:' + (TL_NAME_W + trackW) + 'px;--pps:' + pps + 'px' });

  const ruler = h('div', { class: 'tl-track tl-ruler', style: 'width:' + trackW + 'px', title: 'Click or drag to scrub' });
  const step = pps >= 90 ? 0.5 : 1;
  const every = pps >= 60 ? 1 : 2;
  for (let i = 0; i * step <= TL.len + 3 + 1e-6; i++) {
    const s = i * step;
    const major = Math.abs(s / every - Math.round(s / every)) < 1e-6;
    ruler.append(h('div', { class: 'tl-tick', style: 'left:' + s * pps + 'px;height:' + (major ? 10 : 5) + 'px' }));
    if (major) ruler.append(h('div', { class: 'tl-tick-label', style: 'left:' + (s * pps + (s === 0 ? 8 : 0)) + 'px' }, s + 's'));
  }
  ruler.append(h('div', { class: 'tl-head', id: 'tlHead', style: 'left:' + TL.t * pps + 'px' }));
  ruler.addEventListener('pointerdown', startScrub);
  grid.append(h('div', { class: 'tl-row tl-ruler-row' }, h('div', { class: 'tl-name' }, 'Layers · ' + sc.objects.length), ruler));

  const layers = sc.objects.slice().reverse();
  if (!layers.length) grid.append(h('div', { class: 'tl-empty' }, 'No layers yet. Add objects from the left panel, then give each one a Start, Meanwhile and Exit animation here.'));
  layers.forEach(function (o) { grid.append(tlRow(o, trackW, pps)); });

  if (sc.timer && sc.timer.seconds > 0) {
    grid.append(h('div', { class: 'tl-marker', style: 'left:' + (TL_NAME_W + sc.timer.seconds * pps) + 'px' },
      h('span', { class: 'tl-marker-label' }, '→ ' + ((screenById(sc.timer.target) || {}).name || 'next screen'))));
  }
  grid.append(h('div', { class: 'tl-playhead', id: 'tlPlayhead', style: 'left:' + (TL_NAME_W + TL.t * pps) + 'px' }));
  box.append(grid);
  box.scrollLeft = keepX;
  box.scrollTop = keepY;
  updateTlTools();
}

function barStyle(start, len, pps) {
  return 'left:' + start * pps + 'px;width:' + Math.max(len * pps, 8) + 'px';
}

function tlRow(o, trackW, pps) {
  const p = RT.phases(o);
  const row = h('div', { class: 'tl-row' + (o.id === selId ? ' sel' : '') + (o.hidden ? ' hidden-layer' : '') });
  row.dataset.id = o.id;

  const nm = h('span', { class: 'nm', title: 'Double-click to rename' }, layerName(o));
  const grip = h('span', { class: 'grip', title: 'Drag to change the layer order' }, '⋮⋮');
  const name = h('div', { class: 'tl-name' }, grip, h('span', { class: 'ty', html: icon(layerIcon(o)) }), nm,
    h('button', { class: 'tg' + (o.hidden ? '' : ' on'), title: o.hidden ? 'Show layer' : 'Hide layer', html: icon(o.hidden ? 'eyeOff' : 'eye'),
      onclick: function (e) { e.stopPropagation(); commit(function () { o.hidden = !o.hidden; }); } }),
    h('button', { class: 'tg' + (o.locked ? ' on' : ''), title: o.locked ? 'Unlock layer' : 'Lock layer (can\'t be moved on the canvas)', html: icon(o.locked ? 'lock' : 'unlock'),
      onclick: function (e) { e.stopPropagation(); commit(function () { o.locked = !o.locked; }); } }));
  name.addEventListener('click', function () { if (selId !== o.id) selectObject(o.id); });
  nm.addEventListener('dblclick', function (e) { e.stopPropagation(); renameLayer(o, nm); });
  grip.addEventListener('pointerdown', function (e) { startLayerDrag(e, o, row); });

  const track = h('div', { class: 'tl-track', style: 'width:' + trackW + 'px' });
  track.addEventListener('pointerdown', function (e) { if (e.target === track && selId !== o.id) selectObject(o.id); });

  const addBtn = function (label, start, len, phase) {
    const b = h('div', { class: 'tl-add', style: barStyle(start, len, pps) }, label);
    b.addEventListener('pointerdown', function (e) { e.stopPropagation(); });
    b.addEventListener('click', function (e) { openPhaseMenu(e, o, phase); });
    return b;
  };
  const wire = function (bar, phase, movable) {
    bar.addEventListener('pointerdown', function (e) {
      if (!movable) { e.stopPropagation(); if (selId !== o.id) selectObject(o.id); return; }
      startBarDrag(e, o, phase, e.target.classList.contains('rz') ? 'resize' : 'move');
    });
    bar.addEventListener('dblclick', function (e) { openPhaseMenu(e, o, phase); });
    bar.addEventListener('contextmenu', function (e) { openPhaseMenu(e, o, phase); });
  };

  // Meanwhile (drawn first so the Start and Exit bars sit on top of it)
  const midStart = p.loopStart;
  const midEnd = p.exit ? p.exitAt : TL.len + 3;
  const hasLoop = o.anim && o.anim !== 'none';
  if (midEnd > midStart) {
    const loopName = optLabel(LOOP_OPTIONS, o.anim);
    const mid = h('div', {
      class: 'tl-bar mid' + (hasLoop ? '' : ' plain'), style: barStyle(midStart, midEnd - midStart, pps),
      title: hasLoop ? 'Meanwhile: ' + loopName + ' (loops while on screen) · double-click to change' : 'On screen · double-click to add a Meanwhile loop'
    }, hasLoop ? loopName + ' ∞' : '');
    wire(mid, 'mid', false);
    track.append(mid);
  }
  if (!hasLoop) track.append(addBtn('+ Meanwhile', midStart + 0.15, 1.1, 'mid'));

  // Start
  if (p.enter) {
    const bar = h('div', {
      class: 'tl-bar start', style: barStyle(p.enterStart, p.enterEnd - p.enterStart, pps),
      title: 'Start: ' + optLabel(ENTER_OPTIONS, o.enter) + ' · ' + p.enterStart.toFixed(2) + 's → ' + p.enterEnd.toFixed(2) + 's\nDrag to move · drag the right edge to change length · double-click to change'
    }, optLabel(ENTER_OPTIONS, o.enter), h('i', { class: 'rz r' }));
    wire(bar, 'start', true);
    track.append(bar);
  } else track.append(addBtn('+ Start', 0, 0.75, 'start'));

  // Exit
  if (p.exit) {
    const bar = h('div', {
      class: 'tl-bar exit', style: barStyle(p.exitAt, p.exitDuration, pps),
      title: 'Exit: ' + optLabel(EXIT_OPTIONS, o.exit) + ' · ' + p.exitAt.toFixed(2) + 's → ' + p.exitEnd.toFixed(2) + 's\nDrag to move · drag the right edge to change length · double-click to change'
    }, optLabel(EXIT_OPTIONS, o.exit), h('i', { class: 'rz r' }));
    wire(bar, 'exit', true);
    track.append(bar);
  } else track.append(addBtn('+ Exit', Math.max(p.loopStart + 1.4, TL.len - 0.9), 0.75, 'exit'));

  row.append(name, track);
  return row;
}

function updateTlTools() {
  const play = $('#tlPlay');
  if (play) play.innerHTML = icon(TL.playing ? 'pause' : 'play');
  const tm = $('#tlTime');
  if (tm) tm.textContent = TL.t.toFixed(2) + 's';
  const ln = $('#tlLen');
  if (ln) ln.textContent = TL.len + 's';
}
function updatePlayhead() {
  const x = TL.t * TL.pps;
  const ph = $('#tlPlayhead');
  if (ph) ph.style.left = TL_NAME_W + x + 'px';
  const hd = $('#tlHead');
  if (hd) hd.style.left = x + 'px';
  $('#tlTime').textContent = TL.t.toFixed(2) + 's';
  if (TL.playing) {
    const box = $('#tlScroll');
    const visible = box.clientWidth - TL_NAME_W;
    if (x > box.scrollLeft + visible - 40) box.scrollLeft = x - 40;
  }
}
function tlSyncSelection() {
  $$('#tlScroll .tl-row[data-id]').forEach(function (r) { r.classList.toggle('sel', r.dataset.id === selId); });
}

/* ---------- Canvas preview (scrub + play) ---------- */
function stageAnims() {
  const st = $('#stage');
  return st && st.getAnimations ? st.getAnimations({ subtree: true }) : [];
}
function seekPreview(t) {
  stageAnims().forEach(function (a) {
    try { a.pause(); a.currentTime = t * 1000; } catch (e) { /* ignore */ }
  });
}
/* Re-render the current screen with its animations, frozen at the playhead time. */
function previewRender() {
  const sc = tlScreen();
  if (!sc || view !== 'editor') return;
  clearTimeout(replayTimer);
  const ctx = editCtx();
  sc.objects.forEach(function (o) {
    const wrap = $('#stage .obj[data-id="' + o.id + '"]');
    if (!wrap) return;
    wrap.innerHTML = '';
    wrap.appendChild(RT.renderObj(o, ctx, 'anim'));
  });
  $('#stage').classList.add('tl-preview');
  const sb = $('#stage .sel-box');
  if (sb) sb.style.display = 'none';
  TL.active = true;
  seekPreview(TL.t);
}
function tlOnStageRender() {
  if (TL.playing) { TL.playing = false; cancelAnimationFrame(TL.raf); }
  TL.active = false;
  const st = document.getElementById('stage');
  if (st) st.classList.remove('tl-preview');
  updateTlTools();
}
/* Leave preview mode and show the still, editable canvas again. */
function tlStop() {
  const wasActive = TL.active;
  TL.playing = false;
  cancelAnimationFrame(TL.raf);
  TL.active = false;
  if (wasActive && view === 'editor') renderStage();
  updateTlTools();
}
function tlPlay() {
  const sc = tlScreen();
  if (!sc) { toast('Open a page screen to play its timeline'); return; }
  if (dockTab !== 'timeline') setDock('timeline');
  if (TL.t >= TL.len - 0.02) TL.t = 0;
  previewRender();
  stageAnims().forEach(function (a) { try { a.play(); } catch (e) { /* ignore */ } });
  TL.playing = true;
  TL.wall = performance.now();
  TL.t0 = TL.t;
  const step = function (now) {
    if (!TL.playing) return;
    TL.t = TL.t0 + (now - TL.wall) / 1000;
    if (TL.t >= TL.len) {
      TL.t = TL.len;
      tlPause();
      updatePlayhead();
      return;
    }
    updatePlayhead();
    TL.raf = requestAnimationFrame(step);
  };
  TL.raf = requestAnimationFrame(step);
  updateTlTools();
}
function tlPause() {
  if (!TL.playing) return;
  TL.playing = false;
  cancelAnimationFrame(TL.raf);
  if (TL.active) seekPreview(TL.t);
  updateTlTools();
}
function tlToggle() { if (TL.playing) tlPause(); else tlPlay(); }
function tlSeek(t) {
  TL.t = Math.max(0, Math.min(TL.len, t));
  if (!TL.active) previewRender();
  else seekPreview(TL.t);
  updatePlayhead();
}
function startScrub(e) {
  if (e.button !== 0 || !tlScreen()) return;
  e.preventDefault();
  tlPause();
  const ruler = e.currentTarget;
  const at = function (ev) {
    const r = ruler.getBoundingClientRect();
    tlSeek((ev.clientX - r.left) / TL.pps);
  };
  at(e);
  const up = function () { window.removeEventListener('pointermove', at); window.removeEventListener('pointerup', up); };
  window.addEventListener('pointermove', at);
  window.addEventListener('pointerup', up);
}
/* After an inspector edit: keep the timeline bars and the frozen preview in step. */
function tlAfterInspectorEdit() {
  scheduleTimeline();
  if (TL.active && !TL.playing) previewRender();
}

/* ---------- Editing on the timeline ---------- */
let tlTipEl = null;
function tlTip(text, x, y) {
  if (!tlTipEl) {
    tlTipEl = h('div', { style: 'position:fixed;z-index:130;pointer-events:none;background:var(--toast);color:var(--toast-text);font:600 12px var(--font);padding:4px 8px;border-radius:6px;box-shadow:var(--shadow)' });
    document.body.append(tlTipEl);
  }
  tlTipEl.textContent = text;
  tlTipEl.style.left = x + 12 + 'px';
  tlTipEl.style.top = y - 34 + 'px';
  tlTipEl.hidden = false;
}
function tlTipHide() { if (tlTipEl) tlTipEl.hidden = true; }

function startBarDrag(e, o, phase, mode) {
  if (e.button !== 0) return;
  e.stopPropagation();
  e.preventDefault();
  if (selId !== o.id) selectObject(o.id);
  const snap = snapshot();
  const p0 = RT.phases(o);
  const extra = o.enter === 'letters' ? 0.6 : 0;
  const sx = e.clientX;
  let moved = false;
  const move = function (ev) {
    if (!moved && Math.abs(ev.clientX - sx) < 3) return;
    moved = true;
    const dt = (ev.clientX - sx) / TL.pps;
    const free = ev.altKey;
    let tip = '';
    if (phase === 'start') {
      if (mode === 'move') o.enterDelay = snapT(p0.enterStart + dt, free);
      else o.enterDuration = Math.max(0.1, snapT(p0.enterEnd - p0.enterStart + dt, free) - extra);
      const p = RT.phases(o);
      tip = 'Start ' + p.enterStart.toFixed(2) + 's → ' + p.enterEnd.toFixed(2) + 's';
    } else {
      if (mode === 'move') o.exitAt = snapT(p0.exitAt + dt, free);
      else o.exitDuration = Math.max(0.1, snapT(p0.exitDuration + dt, free));
      const p = RT.phases(o);
      tip = 'Exit ' + p.exitAt.toFixed(2) + 's → ' + p.exitEnd.toFixed(2) + 's';
    }
    tlTip(tip + (free ? '' : '  (Alt = no snap)'), ev.clientX, ev.clientY);
    renderTimeline();
    if (TL.active) previewRender();
  };
  const up = function () {
    window.removeEventListener('pointermove', move);
    window.removeEventListener('pointerup', up);
    tlTipHide();
    if (!moved) return;
    pushUndo(snap);
    save();
    scheduleThumbs();
    renderInspector();
  };
  window.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
}

function setPhase(o, phase, value) {
  const wasActive = TL.active;
  commit(function () {
    if (phase === 'start') {
      o.enter = value;
      if (value !== 'none') {
        if (o.enterDelay == null) o.enterDelay = 0;
        if (o.enterDuration == null) o.enterDuration = value === 'typewriter' ? 1.5 : 0.7;
      }
    } else if (phase === 'mid') o.anim = value;
    else {
      o.exit = value;
      if (value !== 'none') {
        if (o.exitAt == null) o.exitAt = defaultExitAt(o);
        if (o.exitDuration == null) o.exitDuration = 0.6;
      }
    }
    selId = o.id;
  });
  if (wasActive) previewRender();
}

function closeTlMenu() {
  const m = document.getElementById('tlMenu');
  if (m) m.remove();
  document.removeEventListener('pointerdown', tlMenuOutside, true);
}
function tlMenuOutside(e) { if (!e.target.closest('#tlMenu')) closeTlMenu(); }
function openPhaseMenu(e, o, phase) {
  e.preventDefault();
  e.stopPropagation();
  closeTlMenu();
  if (selId !== o.id) selectObject(o.id);
  const textual = o.type === 'text' || o.type === 'button';
  const list = phase === 'start' ? ENTER_OPTIONS.filter(function (x) { return textual || (x[0] !== 'letters' && x[0] !== 'typewriter'); })
    : phase === 'mid' ? LOOP_OPTIONS : EXIT_OPTIONS;
  const cur = phase === 'start' ? (o.enter || 'none') : phase === 'mid' ? (o.anim || 'none') : (o.exit || 'none');
  const m = h('div', { id: 'tlMenu' }, h('div', { class: 'mh' }, { start: 'Start animation', mid: 'Meanwhile loop', exit: 'Exit animation' }[phase]));
  list.forEach(function (x) {
    if (x[0] === 'none') return;
    m.append(h('button', { class: x[0] === cur ? 'on' : '', onclick: function () { closeTlMenu(); setPhase(o, phase, x[0]); } }, (x[0] === cur ? '✓ ' : '') + x[1]));
  });
  if (cur !== 'none') {
    m.append(h('div', { class: 'sep' }));
    m.append(h('button', { class: 'danger', onclick: function () { closeTlMenu(); setPhase(o, phase, 'none'); } }, 'Remove'));
  }
  document.body.append(m);
  const r = m.getBoundingClientRect();
  m.style.left = Math.max(8, Math.min(e.clientX, innerWidth - r.width - 8)) + 'px';
  m.style.top = Math.max(8, Math.min(e.clientY - r.height - 6, innerHeight - r.height - 8)) + 'px';
  setTimeout(function () { document.addEventListener('pointerdown', tlMenuOutside, true); }, 0);
}

function renameLayer(o, nm) {
  const inp = h('input', { value: layerName(o) });
  nm.innerHTML = '';
  nm.append(inp);
  inp.focus();
  inp.select();
  let done = false;
  const finish = function (ok) {
    if (done) return;
    done = true;
    const v = inp.value.trim();
    if (ok && v !== layerName(o)) commit(function () { o.name = v; });
    else renderTimeline();
  };
  inp.addEventListener('keydown', function (e) {
    e.stopPropagation();
    if (e.key === 'Enter') finish(true);
    if (e.key === 'Escape') finish(false);
  });
  inp.addEventListener('blur', function () { finish(true); });
  inp.addEventListener('pointerdown', function (e) { e.stopPropagation(); });
}

/* Drag a layer's grip up or down to change which objects sit in front. */
function startLayerDrag(e, o, row) {
  if (e.button !== 0) return;
  e.preventDefault();
  e.stopPropagation();
  const sc = curScreen();
  const rows = $$('#tlScroll .tl-row[data-id]');
  let target = null, below = false;
  const clear = function () { rows.forEach(function (r) { r.classList.remove('drop-above', 'drop-below'); }); };
  const move = function (ev) {
    clear();
    const r = rows.find(function (x) { const b = x.getBoundingClientRect(); return ev.clientY >= b.top && ev.clientY < b.bottom; });
    if (!r || r === row) { target = null; return; }
    const b = r.getBoundingClientRect();
    below = ev.clientY > b.top + b.height / 2;
    r.classList.add(below ? 'drop-below' : 'drop-above');
    target = r.dataset.id;
  };
  const up = function () {
    window.removeEventListener('pointermove', move);
    window.removeEventListener('pointerup', up);
    clear();
    if (!target) return;
    commit(function () {
      const disp = sc.objects.slice().reverse().filter(function (x) { return x.id !== o.id; });
      const i = disp.findIndex(function (x) { return x.id === target; });
      disp.splice(below ? i + 1 : i, 0, o);
      sc.objects = disp.reverse();
      selId = o.id;
    });
  };
  window.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
}

/* ---------- Setup ---------- */
function initTimeline() {
  $$('#dockTabs button').forEach(function (b) { b.addEventListener('click', function () { setDock(b.dataset.dock); }); });
  $('#tlPlay').addEventListener('click', tlToggle);
  $('#tlRestart').addEventListener('click', function () { tlPause(); tlSeek(0); $('#tlScroll').scrollLeft = 0; });
  const zoomIn = $('#tlZoom');
  try { const z = +localStorage.getItem('kioskStudio.tlZoom'); if (z) { TL.pps = z; zoomIn.value = z; } } catch (e) { /* ignore */ }
  zoomIn.addEventListener('input', function () {
    TL.pps = +zoomIn.value;
    try { localStorage.setItem('kioskStudio.tlZoom', TL.pps); } catch (e) { /* ignore */ }
    renderTimeline();
  });

  // Resize the dock by dragging its top edge (timeline only)
  try { const hgt = +localStorage.getItem('kioskStudio.tlHeight'); if (hgt) $('#timeline').style.setProperty('--tl-h', hgt + 'px'); } catch (e) { /* ignore */ }
  $('#dockResize').addEventListener('pointerdown', function (e) {
    if (dockTab !== 'timeline') return;
    e.preventDefault();
    const tl = $('#timeline');
    const sy = e.clientY, sh = tl.getBoundingClientRect().height;
    const move = function (ev) {
      const hgt = Math.max(120, Math.min(innerHeight * 0.65, sh - (ev.clientY - sy)));
      tl.style.setProperty('--tl-h', hgt + 'px');
    };
    const up = function () {
      window.removeEventListener('pointermove', move);
      window.removeEventListener('pointerup', up);
      try { localStorage.setItem('kioskStudio.tlHeight', Math.round(tl.getBoundingClientRect().height)); } catch (err) { /* ignore */ }
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', up);
  });

  document.addEventListener('keydown', function (e) {
    if (view !== 'editor' || dockTab !== 'timeline' || !$('#preview').hidden || !$('#modal').hidden) return;
    const tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
    if (e.key === ' ') { e.preventDefault(); tlToggle(); }
    else if (e.key === 'Home') { e.preventDefault(); tlPause(); tlSeek(0); }
    else if (e.key === 'Escape' && TL.active) { tlStop(); }
  });

  let saved = 'screens';
  try { saved = localStorage.getItem('kioskStudio.dock') || 'screens'; } catch (e) { /* ignore */ }
  dockTab = saved === 'timeline' ? 'timeline' : 'screens';
  $$('#dockTabs button').forEach(function (b) { b.classList.toggle('on', b.dataset.dock === dockTab); });
  $('#strip').hidden = dockTab !== 'screens';
  $('#timeline').hidden = dockTab !== 'timeline';
  $('#tlTools').hidden = dockTab !== 'timeline';
  updateTlTools();
}
