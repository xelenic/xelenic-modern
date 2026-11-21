/* Menu bar: File, Edit, View, Insert, Play and Help, desktop style. Click a
   menu to open it; while one is open, pointing at another opens that one
   instead. Items show their shortcut, a tick when they're the current choice,
   and are greyed out when they don't apply. Arrow keys move through an open
   menu, Enter picks, Escape closes.

   A page that embeds the studio (embed.js) can add its own items to the top
   and bottom of the File menu, and things on the right of the bar. */

const MAC = /Mac|iPhone|iPad/.test(navigator.platform || '');
const menuExtras = { top: [], bottom: [] };

function keys(k) { return MAC ? k.replace('Mod+', '⌘').replace('Shift+', '⇧') : k.replace('Mod+', 'Ctrl+'); }
function inEditor() { return view === 'editor'; }
function objectPicked() { return inEditor() && !!selObj(); }
function pageScreen() { const sc = curScreen(); return !!sc && sc.type !== 'game'; }

const MENUS = [
  { id: 'file', label: 'File', items: function () {
    return [].concat(menuExtras.top, menuExtras.top.length ? ['-'] : [], [
      { label: 'New project…', icon: 'plus', run: newProjectDialog },
      { label: 'Open project (.json)…', icon: 'upload', run: function () { $('#fileOpen').click(); } },
      { label: 'Save project (.json)', icon: 'download', keys: 'Mod+S', run: saveJson },
      '-',
      { label: 'Export kiosk app (.html)', icon: 'kiosk', run: exportKiosk },
      '-',
      { label: 'Import game template (.zip)…', icon: 'upload', run: function () { $('#fileTemplate').click(); } },
      { label: 'Download sample template', icon: 'download', run: downloadSampleTemplate },
    ], menuExtras.bottom.length ? ['-'] : [], menuExtras.bottom);
  } },
  { id: 'edit', label: 'Edit', items: function () {
    return [
      { label: 'Undo', icon: 'undo', keys: 'Mod+Z', run: undo, enabled: function () { return undoStack.length > 0; } },
      { label: 'Redo', icon: 'redo', keys: 'Mod+Shift+Z', run: redo, enabled: function () { return redoStack.length > 0; } },
      '-',
      { label: 'Duplicate', icon: 'copy', keys: 'Mod+D', run: duplicateObject, enabled: objectPicked },
      { label: 'Delete', icon: 'trash', keys: 'Del', run: deleteObject, enabled: objectPicked },
      '-',
      { label: 'Bring to front', icon: 'front', run: function () { arrange('front'); }, enabled: objectPicked },
      { label: 'Send to back', icon: 'back', run: function () { arrange('back'); }, enabled: objectPicked },
      '-',
      { label: 'Duplicate screen', icon: 'copy', run: function () { duplicateScreen(curId); }, enabled: function () { return !!curScreen(); } },
      { label: 'Delete screen', icon: 'trash', run: function () { deleteScreen(curId); }, enabled: function () { return project.screens.length > 1; } },
    ];
  } },
  { id: 'view', label: 'View', items: function () {
    return [
      { label: 'Flow map', icon: 'flow', run: function () { setView('flow'); }, checked: function () { return view === 'flow'; } },
      { label: 'Screen editor', icon: 'editor', run: function () { setView('editor'); }, checked: inEditor },
      '-',
      { label: 'Zoom in', icon: 'plus', run: function () { setZoom(stageScale() * 1.25); }, enabled: inEditor },
      { label: 'Zoom out', icon: 'minus', run: function () { setZoom(stageScale() / 1.25); }, enabled: inEditor },
      { label: 'Fit to window', icon: 'fit', run: function () { if (inEditor()) fitStage(); else fitFlow(); } },
      { label: 'Tidy the flow map', icon: 'layout', run: autoLayout, enabled: function () { return view === 'flow'; } },
      '-',
      { label: 'Dark theme', icon: 'moon', run: function () { setThemeMode('dark'); }, checked: function () { return themeMode === 'dark'; } },
      { label: 'Light theme', icon: 'sun', run: function () { setThemeMode('light'); }, checked: function () { return themeMode === 'light'; } },
      { label: 'Match the system', icon: 'monitor', run: function () { setThemeMode('system'); }, checked: function () { return themeMode === 'system'; } },
      '-',
      { label: 'Collected data…', icon: 'table', run: openData },
    ];
  } },
  { id: 'insert', label: 'Insert', items: function () {
    return [
      { label: 'Screen', icon: 'page', run: function () { addScreen('page'); } },
      { label: 'Game…', icon: 'flag', run: function () { openTemplateMarket(null, false, 'games'); } },
      { label: 'Screen template…', icon: 'layout', run: function () { openTemplateMarket(null, false, 'screens'); } },
      '-',
    ]
      .concat(Object.keys(OBJECT_KINDS).map(function (kind) {
        const shape = OBJECT_KINDS[kind];
        return { label: shape.label, icon: kind === 'emoji' ? 'sticker' : kind, run: function () { addObject(kind); }, enabled: pageScreen };
      }))
      .concat(['-', { label: 'Upload images…', icon: 'upload', run: function () { $('#fileImage').click(); } }]);
  } },
  { id: 'play', label: 'Play', items: function () {
    return [
      { label: 'Play from the start', icon: 'play', keys: 'P', run: function () { openPreview(false); } },
      { label: 'Play this screen', icon: 'play', run: function () { openPreview(true); } },
      { label: 'Replay this screen\'s animations', icon: 'restart', run: replayAnimations, enabled: inEditor },
    ];
  } },
  { id: 'help', label: 'Help', items: function () {
    return [
      { label: 'Keyboard shortcuts', icon: 'edit', run: shortcutsDialog },
      { label: 'About Kiosk Studio', icon: 'kiosk', run: aboutDialog },
    ];
  } },
];

let openMenuId = null;

function menusOpen() { return openMenuId !== null; }

function closeMenus() {
  openMenuId = null;
  $('#mbDropdown').hidden = true;
  $$('#menubar .mb-top').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); b.classList.remove('on'); });
}

function openMenu(id, focusFirst) {
  const menu = MENUS.find(function (m) { return m.id === id; });
  const top = $('#menubar .mb-top[data-menu="' + id + '"]');
  if (!menu || !top) return;
  closeMenus();
  openMenuId = id;
  top.setAttribute('aria-expanded', 'true');
  top.classList.add('on');

  const box = $('#mbDropdown');
  box.innerHTML = '';
  box.setAttribute('aria-label', menu.label);
  menu.items().forEach(function (item) {
    if (item === '-') { box.append(h('div', { class: 'menu-sep', role: 'separator' })); return; }
    const off = item.enabled ? !item.enabled() : false;
    const on = item.checked ? item.checked() : false;
    const b = h('button', { type: 'button', role: on || item.checked ? 'menuitemradio' : 'menuitem', class: 'mb-item' + (item.primary ? ' primary' : '') });
    if (item.checked) b.setAttribute('aria-checked', String(on));
    b.disabled = off;
    b.innerHTML = '<span class="mb-check">' + (on ? '✓' : '') + '</span>' + (item.icon ? icon(item.icon) : '<span class="mb-noicon"></span>') +
      '<span class="mb-label"></span>' + (item.keys ? '<kbd>' + esc(keys(item.keys)) + '</kbd>' : '');
    b.querySelector('.mb-label').textContent = item.label;
    b.addEventListener('click', function () { closeMenus(); item.run(); });
    box.append(b);
  });

  const r = top.getBoundingClientRect();
  box.style.left = Math.max(4, Math.min(r.left, window.innerWidth - 260)) + 'px';
  box.style.top = r.bottom + 2 + 'px';
  box.hidden = false;
  if (focusFirst) { const first = box.querySelector('.mb-item:not(:disabled)'); if (first) first.focus(); }
}

/* Items a page embedding the studio adds to the File menu (embed.js). */
function setMenuExtras(top, bottom) {
  menuExtras.top = top || [];
  menuExtras.bottom = bottom || [];
}

function shortcutsDialog() {
  const rows = [
    ['Mod+Z', 'Undo'], ['Mod+Shift+Z', 'Redo'], ['Mod+S', 'Save project (.json)'], ['Mod+D', 'Duplicate the selected object'],
    ['Del', 'Delete the selected object'], ['Arrows', 'Nudge it (Shift: 10px)'], ['P', 'Play from the start'], ['Esc', 'Close a dialog, the preview or a menu'],
  ];
  openModal(h('div', {},
    h('h2', {}, 'Keyboard shortcuts'),
    h('table', { class: 'data-table' }, h('tbody', {}, rows.map(function (r) {
      return h('tr', {}, h('td', {}, h('kbd', {}, keys(r[0]))), h('td', {}, r[1]));
    }))),
    h('div', { class: 'modal-actions' }, h('button', { class: 'btn primary', onclick: closeModal }, 'Close'))));
}

function aboutDialog() {
  openModal(h('div', {},
    h('div', { class: 'about-head' },
      h('img', { src: 'img/xelenic-mark.png', alt: 'Xelenic', width: '56', height: '56' }),
      h('div', {}, h('h2', {}, 'Kiosk Studio'), h('p', {}, 'by Xelenic'))),
    h('p', {}, 'Build touchscreen kiosk apps: map out the screens, lay out each one, add games and forms, play it here, then export it as a single file that runs full screen on the kiosk.'),
    h('div', { class: 'modal-actions' }, h('button', { class: 'btn primary', onclick: closeModal }, 'Close'))));
}

(function initMenubar() {
  const bar = $('#menubar');
  MENUS.forEach(function (m) {
    bar.insertBefore(h('button', { type: 'button', class: 'mb-top', role: 'menuitem', 'aria-haspopup': 'true', 'aria-expanded': 'false', 'data-menu': m.id }, m.label), $('#menubarRight'));
  });

  bar.addEventListener('click', function (e) {
    const top = e.target.closest('.mb-top');
    if (!top) return;
    e.stopPropagation();
    if (openMenuId === top.dataset.menu) closeMenus(); else openMenu(top.dataset.menu);
  });
  // With a menu open, pointing at another opens it, as in a desktop app.
  bar.addEventListener('pointerover', function (e) {
    const top = e.target.closest('.mb-top');
    if (top && openMenuId && openMenuId !== top.dataset.menu) openMenu(top.dataset.menu);
  });
  bar.addEventListener('keydown', function (e) {
    const top = e.target.closest('.mb-top');
    if (top && (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openMenu(top.dataset.menu, true); }
  });

  $('#mbDropdown').addEventListener('keydown', function (e) {
    const items = $$('#mbDropdown .mb-item:not(:disabled)');
    const i = items.indexOf(document.activeElement);
    const tops = $$('#menubar .mb-top');
    const t = tops.findIndex(function (b) { return b.dataset.menu === openMenuId; });
    if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
    else if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      openMenu(tops[(t + (e.key === 'ArrowRight' ? 1 : -1) + tops.length) % tops.length].dataset.menu, true);
    } else if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      const top = $('#menubar .mb-top[data-menu="' + openMenuId + '"]');
      closeMenus();
      if (top) top.focus();
    }
  });
  window.addEventListener('resize', closeMenus);
  window.addEventListener('blur', closeMenus);
})();
