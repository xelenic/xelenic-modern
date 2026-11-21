/* Editor core: state, history, storage, helpers, left panel, preview, export. */

const RT = KioskRuntime();
const GX = KioskGames();
RT.injectStyle();

const STORE_KEY = 'kioskStudio.project.v1';
let project = null;
let curId = null;
let selId = null;
let view = 'flow';
let leftTab = 'objects';
let rightTab = 'props';
let undoStack = [];
let redoStack = [];

/* ---------- Icons ---------- */
const ICONS = {
  flow: '<circle cx="5" cy="6" r="2.5"/><circle cx="19" cy="6" r="2.5"/><circle cx="12" cy="18" r="2.5"/><path d="M7.5 6h9M6.5 8l4.3 7.8M17.5 8l-4.3 7.8"/>',
  editor: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
  undo: '<path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 010 11H11"/>',
  redo: '<path d="M15 14l5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 000 11H13"/>',
  play: '<path d="M7 4v16l13-8z"/>',
  table: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16"/>',
  file: '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  minus: '<path d="M5 12h14"/>',
  upload: '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 002 2h12a2 2 0 002-2v-3"/>',
  download: '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 16v3a2 2 0 002 2h12a2 2 0 002-2v-3"/>',
  kiosk: '<rect x="6" y="2" width="12" height="16" rx="2"/><path d="M10 22h4M12 18v4"/>',
  layout: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><path d="M10 6.5h4M17.5 10v4"/>',
  fit: '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
  restart: '<path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
  close: '<path d="M6 6l12 12M18 6L6 18"/>',
  text: '<path d="M5 6V4h14v2M12 4v16M9 20h6"/>',
  button: '<rect x="3" y="7" width="18" height="10" rx="5"/><path d="M9 12h6"/>',
  image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/>',
  sticker: '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>',
  rect: '<rect x="3" y="5" width="18" height="14" rx="3"/>',
  circle: '<circle cx="12" cy="12" r="9"/>',
  input: '<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10v4"/>',
  score: '<path d="M12 2l3 6.5 7 .9-5.1 4.8 1.3 7L12 17.8 5.8 21.2l1.3-7L2 9.4l7-.9z"/>',
  trash: '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>',
  copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/>',
  up: '<path d="M12 19V5M5 12l7-7 7 7"/>',
  down: '<path d="M12 5v14M19 12l-7 7-7-7"/>',
  front: '<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M4 16V4h12"/>',
  back: '<rect x="3" y="3" width="13" height="13" rx="2"/><path d="M20 8v12H8"/>',
  flag: '<path d="M4 22V4M4 4h13l-2 4 2 4H4"/>',
  edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/>',
  alignL: '<path d="M4 6h16M4 10h10M4 14h16M4 18h10"/>',
  alignC: '<path d="M4 6h16M7 10h10M4 14h16M7 18h10"/>',
  alignR: '<path d="M4 6h16M10 10h10M4 14h16M10 18h10"/>',
  page: '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  moon: '<path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>',
  monitor: '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
  timeline: '<path d="M3 6h9M3 12h18M3 18h6"/><circle cx="15.5" cy="6" r="2"/><circle cx="12.5" cy="18" r="2"/>',
  rewind: '<path d="M6 5v14"/><path d="M19 5L9 12l10 7z"/>',
  pause: '<rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>',
  eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
  eyeOff: '<path d="M3 3l18 18"/><path d="M10.6 5.1A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a17.7 17.7 0 01-3.2 4.2M6.1 6.1C3.4 7.9 2 12 2 12s3.5 7 10 7a9.7 9.7 0 005.9-2.1"/><path d="M9.9 9.9a3 3 0 004.2 4.2"/>',
  lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/>',
  unlock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 017.5-2"/>'
};
function icon(name) {
  return '<svg class="i" viewBox="0 0 24 24">' + (ICONS[name] || '') + '</svg>';
}

/* ---------- Editor theme (dark / light / system) ---------- */
const THEME_KEY = 'kioskStudio.theme';
const THEME_MODES = ['dark', 'light', 'system'];
const THEME_INFO = { dark: { name: 'Dark', icon: 'moon' }, light: { name: 'Light', icon: 'sun' }, system: { name: 'System', icon: 'monitor' } };
const themeQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: light)') : null;
let themeMode = 'dark';

function loadThemeMode() {
  try {
    const m = localStorage.getItem(THEME_KEY);
    return THEME_MODES.indexOf(m) >= 0 ? m : 'dark';
  } catch (e) { return 'dark'; }
}
function resolvedTheme() {
  if (themeMode === 'system') return themeQuery && themeQuery.matches ? 'light' : 'dark';
  return themeMode;
}
function applyTheme() {
  document.documentElement.setAttribute('data-theme', resolvedTheme());
  const b = document.getElementById('btnTheme');
  if (b) {
    b.innerHTML = icon(THEME_INFO[themeMode].icon);
    b.title = 'Editor theme: ' + THEME_INFO[themeMode].name + ' (click to change)';
  }
}
function setThemeMode(m) {
  themeMode = m;
  try { localStorage.setItem(THEME_KEY, m); } catch (e) { /* storage unavailable */ }
  applyTheme();
  if (rightTab === 'project' && project) renderInspector();
}
function cycleTheme() {
  setThemeMode(THEME_MODES[(THEME_MODES.indexOf(themeMode) + 1) % THEME_MODES.length]);
  toast(THEME_INFO[themeMode].name + ' theme' + (themeMode === 'system' ? ' · follows your computer' : ''));
}

/* ---------- DOM helpers ---------- */
const $ = function (s, r) { return (r || document).querySelector(s); };
const $$ = function (s, r) { return Array.from((r || document).querySelectorAll(s)); };

function h(tag, attrs) {
  const el = document.createElement(tag);
  const a = attrs || {};
  Object.keys(a).forEach(function (k) {
    const v = a[k];
    if (v == null || v === false) return;
    if (k === 'class') el.className = v;
    else if (k === 'html') el.innerHTML = v;
    else if (k === 'style') el.style.cssText = v;
    else if (k.slice(0, 2) === 'on') el.addEventListener(k.slice(2), v);
    else if (k === 'dataset') Object.assign(el.dataset, v);
    else el.setAttribute(k, v === true ? '' : v);
  });
  for (let i = 2; i < arguments.length; i++) {
    const c = arguments[i];
    if (c == null || c === false) continue;
    if (Array.isArray(c)) c.forEach(function (x) { if (x != null && x !== false) el.append(x); });
    else el.append(c);
  }
  return el;
}
function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
}
function slug(s) { return String(s || 'kiosk').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'kiosk'; }
function debounce(fn, ms) {
  let t = 0;
  return function () { clearTimeout(t); t = setTimeout(fn, ms); };
}

let toastTimer = 0;
function toast(msg) {
  const t = $('#toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(function () { t.classList.remove('show'); }, 2600);
}

function download(name, content, type) {
  const blob = content instanceof Blob ? content : new Blob([content], { type: type || 'application/octet-stream' });
  const url = URL.createObjectURL(blob);
  const a = h('a', { href: url, download: name });
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
}

/* ---------- Model access ---------- */
function screenById(id) { return project.screens.find(function (s) { return s.id === id; }); }
function curScreen() { return screenById(curId); }
function selObj() {
  const sc = curScreen();
  return sc && selId ? sc.objects.find(function (o) { return o.id === selId; }) : null;
}
function assetsMap() {
  const m = {};
  (project.assets || []).forEach(function (a) { m[a.id] = a.src; });
  return m;
}
const RAW_VARS = new Proxy({}, { get: function (t, k) { return typeof k === 'string' ? '{{' + k + '}}' : undefined; } });
function editCtx() { return { vars: RAW_VARS, assets: assetsMap() }; }

function normalize(p) {
  p.screens = p.screens || [];
  if (!p.screens.length) p.screens.push(makeScreen('page', 'Start'));
  p.assets = p.assets || [];
  p.vars = p.vars || { score: 0 };
  p.width = p.width || 1080;
  p.height = p.height || 1920;
  p.id = p.id || uid('p');
  p.gameTemplates = p.gameTemplates || [];
  p.screens.forEach(function (s) {
    s.objects = s.objects || [];
    s.timer = s.timer || { seconds: 0, target: '' };
    if (s.type === 'game') {
      s.game = s.game || { kind: 'quiz', config: {} };
      s.game.config = Object.assign(templateConfigDefaults(s.game.kind, p), s.game.config || {});
    }
  });
  if (!p.screens.find(function (s) { return s.id === p.startScreen; })) p.startScreen = p.screens[0].id;
  return p;
}

/* ---------- History & storage ---------- */
function snapshot() { return JSON.stringify(project); }
function pushUndo(snap) {
  undoStack.push(snap);
  if (undoStack.length > 120) undoStack.shift();
  redoStack.length = 0;
  updateUndoButtons();
}
function commit(fn) {
  const snap = snapshot();
  fn();
  if (snapshot() !== snap) pushUndo(snap);
  save();
  renderAll();
}
function undo() {
  if (!undoStack.length) return;
  redoStack.push(snapshot());
  project = JSON.parse(undoStack.pop());
  save();
  renderAll();
}
function redo() {
  if (!redoStack.length) return;
  undoStack.push(snapshot());
  project = JSON.parse(redoStack.pop());
  save();
  renderAll();
}
function updateUndoButtons() {
  $('#btnUndo').disabled = !undoStack.length;
  $('#btnRedo').disabled = !redoStack.length;
}
let saveWarned = false;
function save() {
  try { localStorage.setItem(STORE_KEY, JSON.stringify(project)); }
  catch (e) {
    if (!saveWarned) { saveWarned = true; toast('Autosave failed (browser storage full). Use File → Save project.'); }
  }
}
function loadSaved() {
  try {
    const s = localStorage.getItem(STORE_KEY);
    if (s) return JSON.parse(s);
  } catch (e) { /* ignore */ }
  return null;
}
function setProject(p) {
  project = normalize(p);
  curId = project.startScreen;
  selId = null;
  undoStack = [];
  redoStack = [];
  save();
  renderAll();
  if (view === 'flow') fitFlow(); else fitStage();
}

/* ---------- Render orchestration ---------- */
function renderAll() {
  if (!screenById(curId)) curId = project.screens[0].id;
  if (selId && !selObj()) selId = null;
  renderTopbar();
  renderLeft();
  if (view === 'flow') renderFlow();
  else { renderStage(); renderStrip(); if (typeof renderTimeline === 'function') renderTimeline(); }
  renderInspector();
  updateUndoButtons();
}
function renderTopbar() {
  const n = $('#projName');
  if (document.activeElement !== n) n.value = project.name || '';
  $('#orient').value = project.width > project.height ? 'landscape' : 'portrait';
  $$('#viewSeg button').forEach(function (b) { b.classList.toggle('on', b.dataset.view === view); });
}
const scheduleThumbs = debounce(function () { if (view === 'editor') renderStrip(); }, 250);
const scheduleFlow = debounce(function () { if (view === 'flow') renderFlow(); }, 120);

function setView(v) {
  if (v !== 'editor' && typeof tlStop === 'function') tlStop();
  view = v;
  $('#flowView').hidden = v !== 'flow';
  $('#editorView').hidden = v !== 'editor';
  renderAll();
  if (v === 'editor') fitStage();
}
function openScreen(id) {
  curId = id;
  selId = null;
  if (view !== 'editor') setView('editor');
  else { renderAll(); fitStage(); }
}

/* ---------- Screen operations ---------- */
function addScreen(kind) {
  const near = curScreen();
  const builtin = kind === 'page' || isBuiltinKind(kind);
  if (!builtin && !findTemplate(kind)) { toast('That game template is not installed'); return; }
  const s = builtin ? makeScreen(kind) : makeTemplateScreen(kind);
  s.fx = (near && near.fx != null ? near.fx : 40) + 280;
  s.fy = (near && near.fy != null ? near.fy : 40) + 40;
  commit(function () {
    if (s.type === 'game') ensureTemplateInProject(kind);
    project.screens.push(s);
    curId = s.id;
    selId = null;
  });
  toast((s.type === 'game' ? gameInfo(kind).name + ' game' : 'Screen') + ' added');
}
function duplicateScreen(id) {
  const src = screenById(id);
  const s = JSON.parse(JSON.stringify(src));
  s.id = uid('s');
  s.name = src.name + ' copy';
  s.fx = (src.fx || 0) + 40;
  s.fy = (src.fy || 0) + 40;
  s.objects.forEach(function (o) { o.id = uid('o'); });
  commit(function () {
    project.screens.splice(project.screens.indexOf(src) + 1, 0, s);
    curId = s.id;
    selId = null;
  });
}
function deleteScreen(id) {
  if (project.screens.length <= 1) { toast('A project needs at least one screen'); return; }
  const s = screenById(id);
  commit(function () {
    project.screens = project.screens.filter(function (x) { return x.id !== id; });
    project.screens.forEach(function (x) {
      x.objects.forEach(function (o) {
        if (o.action && o.action.target === id) { o.action.target = ''; if (o.action.type === 'goto') o.action.type = 'none'; }
      });
      if (x.timer && x.timer.target === id) x.timer.target = '';
      if (x.game) { if (x.game.win === id) x.game.win = ''; if (x.game.lose === id) x.game.lose = ''; }
    });
    if (project.startScreen === id) project.startScreen = project.screens[0].id;
    curId = project.startScreen;
    selId = null;
  });
  toast('Deleted "' + s.name + '"');
}

function ensureSampleLogo() {
  if (!(project.assets || []).some(function (a) { return a.id === 'a_logo'; })) project.assets = (project.assets || []).concat([sampleLogoAsset()]);
}
function addSampleLogo() {
  commit(ensureSampleLogo);
  toast('Sample logo added to Assets');
}
/* Insert a ready-made animated screen. A game start screen's PLAY button links to a matching game if there is one. */
function addScreenTemplate(t) {
  const near = curScreen();
  const s = t.make();
  s.fx = (near && near.fx != null ? near.fx : 40) + 280;
  s.fy = (near && near.fy != null ? near.fy : 40) + 40;
  let linked = null;
  if (t.kind) {
    linked = project.screens.find(function (x) { return x.type === 'game' && x.game.kind === t.kind; }) || null;
    const play = s.objects.filter(function (o) { return o.action && o.action.type === 'goto' && !o.action.target; }).pop();
    if (linked && play) play.action.target = linked.id;
  }
  commit(function () {
    if (JSON.stringify(s).indexOf('asset:a_logo') >= 0) ensureSampleLogo();
    project.screens.push(s);
    curId = s.id;
    selId = null;
  });
  if (view === 'editor') setTimeout(replayAnimations, 80);
  toast(t.name + ' screen added' + (linked ? ' · PLAY opens "' + linked.name + '"' : ''));
}

/* ---------- Object operations ---------- */
function addObject(kind, cx, cy, extra) {
  const sc = curScreen();
  if (!sc) return;
  if (sc.type === 'game') { toast('Game screens are set up in the Properties panel. Add objects to a page screen.'); return; }
  if (view !== 'editor') setView('editor');
  commit(function () {
    const o = makeObject(kind, cx == null ? project.width / 2 : cx, cy == null ? project.height / 2 : cy);
    Object.assign(o, extra || {});
    sc.objects.push(o);
    selId = o.id;
  });
}
function deleteObject() {
  const sc = curScreen();
  if (!selObj()) return;
  commit(function () {
    sc.objects = sc.objects.filter(function (o) { return o.id !== selId; });
    selId = null;
  });
}
function duplicateObject() {
  const sc = curScreen();
  const o = selObj();
  if (!o) return;
  commit(function () {
    const c = JSON.parse(JSON.stringify(o));
    c.id = uid('o');
    c.x += 40;
    c.y += 40;
    sc.objects.splice(sc.objects.indexOf(o) + 1, 0, c);
    selId = c.id;
  });
}
function arrange(where) {
  const sc = curScreen();
  const o = selObj();
  if (!o) return;
  commit(function () {
    const i = sc.objects.indexOf(o);
    sc.objects.splice(i, 1);
    if (where === 'front') sc.objects.push(o);
    else if (where === 'back') sc.objects.unshift(o);
    else if (where === 'up') sc.objects.splice(Math.min(sc.objects.length, i + 1), 0, o);
    else sc.objects.splice(Math.max(0, i - 1), 0, o);
  });
}

/* ---------- Assets ---------- */
function readImage(file) {
  return new Promise(function (resolve, reject) {
    const fr = new FileReader();
    fr.onerror = reject;
    fr.onload = function () {
      const img = new Image();
      img.onerror = reject;
      img.onload = function () {
        const max = 2048;
        const big = Math.max(img.width, img.height);
        if (big <= max || /svg|gif/.test(file.type)) { resolve({ src: fr.result, w: img.width, h: img.height }); return; }
        const k = max / big;
        const c = document.createElement('canvas');
        c.width = Math.round(img.width * k);
        c.height = Math.round(img.height * k);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        const png = /png|webp/.test(file.type);
        resolve({ src: c.toDataURL(png ? 'image/png' : 'image/jpeg', 0.9), w: c.width, h: c.height });
      };
      img.src = fr.result;
    };
    fr.readAsDataURL(file);
  });
}
async function uploadImages(files) {
  const added = [];
  for (const f of Array.from(files)) {
    try {
      const r = await readImage(f);
      added.push({ id: uid('a'), name: f.name, src: r.src, w: r.w, h: r.h });
    } catch (e) { toast('Could not read ' + f.name); }
  }
  if (!added.length) return;
  commit(function () { project.assets = (project.assets || []).concat(added); });
  leftTab = 'assets';
  renderLeft();
  toast(added.length + ' image' + (added.length > 1 ? 's' : '') + ' added to Assets');
}
function useAsset(a) {
  const o = selObj();
  if (o && o.type === 'image') {
    commit(function () { o.src = 'asset:' + a.id; });
    return;
  }
  const w = 600;
  const hgt = a.w && a.h ? Math.round(w * a.h / a.w) : 600;
  addObject('image', null, null, { src: 'asset:' + a.id, w: w, h: hgt, x: Math.round(project.width / 2 - w / 2), y: Math.round(project.height / 2 - hgt / 2) });
}
function deleteAsset(id) {
  commit(function () {
    project.assets = project.assets.filter(function (a) { return a.id !== id; });
    project.screens.forEach(function (s) {
      if (s.bgImage === 'asset:' + id) s.bgImage = '';
      s.objects.forEach(function (o) { if (o.src === 'asset:' + id) o.src = ''; });
    });
  });
}

/* ---------- Left panel ---------- */
const TILE_ICONS = { text: 'text', button: 'button', image: 'image', emoji: 'sticker', rect: 'rect', circle: 'circle', input: 'input', score: 'score' };

function renderLeft() {
  $$('#leftTabs button').forEach(function (b) { b.classList.toggle('on', b.dataset.tab === leftTab); });
  const body = $('#leftBody');
  body.innerHTML = '';
  if (leftTab === 'objects') {
    body.append(h('div', { class: 'lp-title' }, 'Drag onto the screen'));
    const grid = h('div', { class: 'tile-grid' });
    Object.keys(OBJECT_KINDS).forEach(function (kind) {
      grid.append(h('div', {
        class: 'tile', draggable: 'true', title: 'Click to add, or drag onto the screen',
        ondragstart: function (e) { e.dataTransfer.setData('text/kiosk-object', kind); e.dataTransfer.effectAllowed = 'copy'; },
        onclick: function () { addObject(kind); }
      }, h('div', { class: 'ti', html: icon(TILE_ICONS[kind]) }), OBJECT_KINDS[kind].label));
    });
    body.append(grid);
    body.append(h('div', { class: 'lp-title' }, 'Stickers'));
    const eg = h('div', { class: 'emoji-grid' });
    EMOJI_LIBRARY.forEach(function (em) {
      eg.append(h('button', {
        draggable: 'true', title: 'Add sticker',
        ondragstart: function (e) { e.dataTransfer.setData('text/kiosk-emoji', em); },
        onclick: function () {
          const o = selObj();
          if (o && o.type === 'emoji') commit(function () { o.emoji = em; });
          else addObject('emoji', null, null, { emoji: em });
        }
      }, em));
    });
    body.append(eg);
    body.append(h('p', { class: 'note' }, 'Tip: use {{score}}, {{best}} or {{name}} in any text to show live values.'));
  } else if (leftTab === 'screens') {
    body.append(h('div', { class: 'lp-title' }, 'Add a screen'));
    body.append(h('button', { class: 'card', onclick: function () { addScreen('page'); } },
      h('div', { class: 'ci', style: 'background:#2b3a67;color:#ffffff', html: icon('page') }),
      h('div', {}, h('b', {}, 'Page'), h('small', {}, 'Text, buttons, images, forms'))));
    renderTemplateSection(body);
    body.append(h('div', { class: 'lp-title', style: 'margin-top:14px' }, 'Animated screen templates'));
    SCREEN_TEMPLATES.forEach(function (t) {
      body.append(h('button', { class: 'card', onclick: function () { addScreenTemplate(t); } },
        h('div', { class: 'ci', style: 'background:linear-gradient(135deg,#5b21b6,#f72585)' }, t.icon),
        h('div', {}, h('b', {}, t.name), h('small', {}, t.blurb))));
    });
    body.append(h('div', { class: 'lp-title', style: 'margin-top:14px' }, 'All screens'));
    project.screens.forEach(function (s) {
      body.append(h('button', { class: 'card', style: 'padding:7px 10px' + (s.id === curId ? ';border-color:var(--accent)' : ''), onclick: function () { openScreen(s.id); } },
        h('span', { style: 'font-size:16px' }, s.type === 'game' ? gameInfo(s.game.kind).icon : '📄'),
        h('span', { style: 'flex:1;font-weight:600' }, s.name),
        s.id === project.startScreen ? h('span', { class: 'badge' }, 'START') : null));
    });
  } else {
    body.append(h('button', { class: 'btn-wide', html: icon('upload') + ' Upload images', onclick: function () { $('#fileImage').click(); } }));
    if (!(project.assets || []).some(function (a) { return a.id === 'a_logo'; })) {
      body.append(h('button', { class: 'btn-wide', onclick: addSampleLogo }, '✨ Add sample logo'));
    }
    if (!(project.assets || []).length) {
      body.append(h('p', { class: 'note' }, 'No images yet. Upload logos, backgrounds or product photos. They are stored inside the project file.'));
    } else {
      body.append(h('div', { class: 'lp-title' }, 'Click to place · ' + project.assets.length + ' image' + (project.assets.length > 1 ? 's' : '')));
      const ag = h('div', { class: 'asset-grid' });
      project.assets.forEach(function (a) {
        const tile = h('div', { class: 'asset', title: a.name, style: 'background-image:url("' + a.src + '")', onclick: function () { useAsset(a); } });
        tile.append(h('button', { class: 'x', title: 'Delete image', html: icon('trash'), onclick: function (e) { e.stopPropagation(); deleteAsset(a.id); } }));
        ag.append(tile);
      });
      body.append(ag);
      body.append(h('p', { class: 'note' }, 'Select an image object first to swap its picture, or set a screen background in Properties.'));
    }
  }
}

/* ---------- Preview ---------- */
let player = null;
function openPreview(fromCurrent) {
  closeMenus();
  $('#preview').hidden = false;
  sizePreview();
  if (player) player.destroy();
  player = RT.createPlayer($('#pvScreen'), JSON.parse(JSON.stringify(project)), {
    start: fromCurrent ? curId : null,
    onScreen: function (sc) { $('#pvTitle').textContent = 'Preview · ' + sc.name; },
    onSubmit: function () { toast('Form saved · see Data'); }
  });
}
function sizePreview() {
  const W = project.width, H = project.height;
  const k = Math.min((innerWidth - 120) / W, (innerHeight - 52 - 70) / H);
  $('#pvScreen').style.width = Math.round(W * k) + 'px';
  $('#pvScreen').style.height = Math.round(H * k) + 'px';
}
function closePreview() {
  if (player) player.destroy();
  player = null;
  $('#preview').hidden = true;
}

/* ---------- Modal ---------- */
function openModal(content) {
  const card = $('#modalCard');
  card.innerHTML = '';
  card.append(content);
  $('#modal').hidden = false;
}
function closeModal() { $('#modal').hidden = true; }

function newProjectDialog() {
  const pickT = function (fn) { return function () { closeModal(); setProject(fn()); toast('New project created'); }; };
  const grid = h('div', { class: 'tpl-grid' },
    h('button', { class: 'tpl', onclick: pickT(demoProject) }, h('div', { class: 'te' }, '🎮'), h('b', {}, 'Play & Win demo'), h('small', {}, 'Attract loop, game menu, all three games, prize form')),
    h('button', { class: 'tpl', onclick: pickT(function () { return singleGameProject('quiz'); }) }, h('div', { class: 'te' }, '🧠'), h('b', {}, 'Quiz kiosk'), h('small', {}, 'Start screen, quiz, result')),
    h('button', { class: 'tpl', onclick: pickT(function () { return singleGameProject('catch'); }) }, h('div', { class: 'te' }, '🧺'), h('b', {}, 'Catch game'), h('small', {}, 'Start screen, catch game, result')),
    h('button', { class: 'tpl', onclick: pickT(function () { return singleGameProject('slice'); }) }, h('div', { class: 'te' }, '🍉'), h('b', {}, 'Slice game'), h('small', {}, 'Start screen, slice game, result')),
    h('button', { class: 'tpl', onclick: pickT(function () { return singleGameProject('wheel'); }) }, h('div', { class: 'te' }, '🎡'), h('b', {}, 'Prize wheel'), h('small', {}, 'Start screen, spin wheel, prize result')),
    h('button', { class: 'tpl', onclick: pickT(blankProject) }, h('div', { class: 'te' }, '📄'), h('b', {}, 'Blank project'), h('small', {}, 'One empty screen')));
  openModal(h('div', {},
    h('h2', {}, 'New project'),
    h('p', {}, 'Pick a starting point. This replaces the current project, so save it first if you need it.'),
    grid,
    h('div', { class: 'modal-actions' }, h('button', { class: 'btn', onclick: closeModal }, 'Cancel'))));
}

function singleGameProject(kind) {
  const gi = GAME_INFO[kind];
  const start = makeScreen('page', 'Start');
  const game = makeScreen(kind);
  const result = makeScreen('page', 'Result');
  start.bg = '#4361ee'; start.bg2 = '#7209b7'; start.fx = 60; start.fy = 120;
  game.fx = 360; game.fy = 120;
  result.bg = '#06d6a0'; result.bg2 = '#118ab2'; result.fx = 660; result.fy = 120;
  result.timer = { seconds: 20, target: start.id };
  start.effect = 'aurora';
  start.transition = 'fade';
  result.effect = 'confetti';
  result.transition = 'zoom';
  start.objects.push(A(E(gi.icon, 380, 480), 'bounce', 0.2, 1.1, 'float'), A(T(gi.name.toUpperCase() + ' TIME', 950, 120, { shadow: true }), 'letters', 0.9, 0.6),
    A(B('TAP TO PLAY', 1420, game.id, { bg: '#ffd166', color: '#3a0ca3' }), 'pop', 1.8, 0.7, 'pulse'));
  game.game.win = result.id;
  game.game.lose = result.id;
  const isWheel = kind === 'wheel';
  result.objects.push(E(isWheel ? '🎉' : '🏆', 380, 460, { anim: 'wiggle' }), T(isWheel ? 'Your spin' : 'Your score', 900, 72, { bold: false }),
    T(isWheel ? '{{prize}}' : '{{score}}', 1010, isWheel ? 140 : 180, { shadow: true, h: 240 }), B('Play again', 1400, game.id, { bg: '#ffffff', color: '#118ab2' }), B('Done', 1630, start.id, { x: 270, w: 540, h: 140, fontSize: 48, bg: 'rgba(255,255,255,0.2)', shadow: false }));
  return { id: uid('p'), name: gi.name + ' Kiosk', version: 1, width: 1080, height: 1920, startScreen: start.id, idleTimeout: 60, vars: { score: 0 }, assets: [], screens: [start, game, result] };
}

/* ---------- Data (form submissions) ---------- */
function openData() {
  closeMenus();
  const key = 'kiosk.submissions.' + project.id;
  let rows = [];
  try { rows = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { /* ignore */ }
  const cols = [];
  rows.forEach(function (r) { Object.keys(r).forEach(function (c) { if (cols.indexOf(c) < 0) cols.push(c); }); });
  const body = h('div', {}, h('h2', {}, 'Collected data'),
    h('p', {}, 'Form submissions captured while playing in this browser. An exported kiosk keeps its own data on each device.'));
  if (!rows.length) body.append(h('div', { class: 'empty' }, h('div', { class: 'big' }, '🗂️'), 'No submissions yet. Play the project and submit a form.'));
  else {
    const tbl = h('table', { class: 'data-table' },
      h('thead', {}, h('tr', {}, cols.map(function (c) { return h('th', {}, c); }))),
      h('tbody', {}, rows.slice().reverse().slice(0, 100).map(function (r) {
        return h('tr', {}, cols.map(function (c) { return h('td', {}, c === 'time' ? new Date(r[c]).toLocaleString() : String(r[c] == null ? '' : r[c])); }));
      })));
    body.append(h('div', { style: 'overflow:auto' }, tbl));
  }
  body.append(h('div', { class: 'modal-actions' },
    rows.length ? h('button', { class: 'btn danger', onclick: function () { try { localStorage.removeItem(key); } catch (e) { /* ignore */ } closeModal(); toast('Data cleared'); } }, 'Clear') : null,
    rows.length ? h('button', { class: 'btn', html: icon('download') + ' Download CSV', onclick: function () {
      const q = function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; };
      const csv = [cols.map(q).join(',')].concat(rows.map(function (r) { return cols.map(function (c) { return q(r[c]); }).join(','); })).join('\n');
      download(slug(project.name) + '-data.csv', csv, 'text/csv');
    } }) : null,
    h('button', { class: 'btn primary', onclick: closeModal }, 'Close')));
  openModal(body);
}

/* ---------- File commands ---------- */
function saveJson() {
  download(slug(project.name) + '.kiosk.json', JSON.stringify(project, null, 2), 'application/json');
  toast('Project saved');
}
function openJson(file) {
  const fr = new FileReader();
  fr.onload = function () {
    try {
      const p = JSON.parse(fr.result);
      if (!p || !Array.isArray(p.screens)) throw new Error('bad');
      setProject(p);
      toast('Opened "' + (p.name || file.name) + '"');
    } catch (e) { toast('That file is not a Kiosk Studio project'); }
  };
  fr.readAsText(file);
}
function exportKiosk() {
  const LS = String.fromCharCode(0x2028), PS = String.fromCharCode(0x2029);
  const json = JSON.stringify(project).replace(/</g, '\\u003c').split(LS).join('\\u2028').split(PS).join('\\u2029');
  const html = '<!doctype html>\n<html lang="en"><head><meta charset="utf-8">' +
    '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">' +
    '<title>' + esc(project.name) + '</title>' +
    '<style>html,body{margin:0;height:100%;background:#000;overflow:hidden;overscroll-behavior:none}#app{position:fixed;inset:0}</style>' +
    '</head><body><div id="app"></div><script>\n' +
    KioskGames.toString() + '\n' + KioskRuntime.toString() + '\n' +
    'KioskRuntime().createPlayer(document.getElementById("app"), ' + json + ', { kiosk: true });\n' +
    '<' + '/script></body></html>';
  download(slug(project.name) + '-kiosk.html', html, 'text/html');
  toast('Kiosk app exported · open it fullscreen on the kiosk');
}
function setOrientation(o) {
  const landscape = o === 'landscape';
  if (landscape === project.width > project.height) return;
  commit(function () {
    const ow = project.width, oh = project.height;
    project.width = oh;
    project.height = ow;
    const rx = project.width / ow, ry = project.height / oh, m = Math.min(rx, ry);
    project.screens.forEach(function (s) {
      s.objects.forEach(function (ob) {
        const cx = (ob.x + ob.w / 2) * rx, cy = (ob.y + ob.h / 2) * ry;
        ob.w = Math.round(ob.w * m);
        ob.h = Math.round(ob.h * m);
        ob.x = Math.round(cx - ob.w / 2);
        ob.y = Math.round(cy - ob.h / 2);
        if (ob.fontSize) ob.fontSize = Math.round(ob.fontSize * m);
      });
    });
  });
  fitStage();
  toast('Switched to ' + o + '. Check each screen\'s layout.');
}
function closeMenus() { $('#fileMenu').hidden = true; }
