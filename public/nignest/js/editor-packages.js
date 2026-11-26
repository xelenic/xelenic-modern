/*
 * Game template packages (.zip)
 *
 *   my-game.zip
 *   ├── manifest.json   id, name, version, icon, settings (the Properties form), defaults
 *   ├── game.js         KioskGame.define({ start(ctx) { ... return { stop() {} }; } })
 *   ├── assets/…        optional images / sounds (png jpg gif webp svg mp3 wav ogg m4a json)
 *   └── README.md       optional
 *
 * Two kinds of template:
 *   - Code template: game.js is a new game written against the Game SDK (ctx.sdk).
 *   - Preset: manifest "extends": "quiz" | "catch" | … | "hoop" (see BUILTIN_KINDS) reuses a built-in game
 *     with its own defaults (colours, items, questions…). No code needed.
 *
 * Installed templates live in the browser library (localStorage) and are copied into the
 * project when a screen uses them, so saved projects and exported kiosk apps carry them.
 */

const TEMPLATE_FORMAT = 'kiosk-game-template';
const BUILTIN_KINDS = ['quiz', 'catch', 'slice', 'wheel', 'runner', 'memory', 'whack', 'stack', 'scratch', 'hoop'];
const TEMPLATE_LIB_KEY = 'kioskStudio.templates';
const TEMPLATE_MAX_BYTES = 12 * 1024 * 1024;
const TEMPLATE_MIME = {
  png: 'image/png', jpg: 'image/jpeg', jpeg: 'image/jpeg', gif: 'image/gif', webp: 'image/webp', svg: 'image/svg+xml',
  mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg', m4a: 'audio/mp4', json: 'application/json'
};
const SETTING_TYPES = { number: 'num', text: 'text', emoji: 'text', color: 'color', checkbox: 'check', boolean: 'check', select: 'select' };
const BUILTIN_TINT = { quiz: '#4b1d8f', catch: '#0f6b55', slice: '#7a2a1a', wheel: '#8a5a00', runner: '#0b5f86', memory: '#9d174d',
  whack: '#2d6a4f', stack: '#1d3557', scratch: '#a4133c', hoop: '#c2410c' };

/* ---------- Library + lookup ---------- */
let templateLib = null;
function templateLibrary() {
  if (!templateLib) {
    try { templateLib = JSON.parse(localStorage.getItem(TEMPLATE_LIB_KEY) || '{}') || {}; } catch (e) { templateLib = {}; }
  }
  return templateLib;
}
function saveTemplateLibrary() {
  try { localStorage.setItem(TEMPLATE_LIB_KEY, JSON.stringify(templateLibrary())); return true; } catch (e) { return false; }
}
function isBuiltinKind(kind) { return BUILTIN_KINDS.indexOf(kind) >= 0; }
function findTemplate(id, p) {
  const proj = p || project;
  const own = proj && (proj.gameTemplates || []).find(function (t) { return t.manifest.id === id; });
  return own || catalogPackages[id] || templateLibrary()[id] || null;
}
function customTemplates() {
  const map = {};
  const lib = templateLibrary();
  Object.keys(lib).forEach(function (id) { map[id] = lib[id]; });
  (project.gameTemplates || []).forEach(function (t) { map[t.manifest.id] = t; });
  return Object.keys(map).map(function (id) { return map[id]; }).sort(function (a, b) { return a.manifest.name.localeCompare(b.manifest.name); });
}
/* The built-in game a screen really runs ("catch" for a Catch preset), or null for a code template. */
function baseKind(kind, p) {
  if (isBuiltinKind(kind)) return kind;
  const t = findTemplate(kind, p);
  return t && isBuiltinKind(t.manifest.extends) ? t.manifest.extends : null;
}
function pkgInfo(pkg) {
  const m = pkg.manifest;
  const src = m.icon && pkg.assets && pkg.assets[m.icon] ? pkg.assets[m.icon] : '';
  return {
    name: m.name, icon: src ? '🧩' : (m.icon || '🧩'), iconSrc: src, color: m.color || '#3b3f5c', custom: true,
    blurb: m.description || (m.extends ? 'Preset of ' + GAME_INFO[m.extends].name : 'Custom game')
  };
}
function gameInfo(kind) {
  if (GAME_INFO[kind]) return Object.assign({ color: BUILTIN_TINT[kind] }, GAME_INFO[kind]);
  const t = findTemplate(kind);
  if (!t) return { name: kind, icon: '❓', blurb: 'Template missing. Import its .zip to play it.', missing: true, custom: true, color: '#5a2a2a' };
  return pkgInfo(t);
}
/* A game's icon in the editor: its package picture, its illustration, or a
   game controller for an icon there's no illustration of. */
function gameIcon(kind, cls) { return infoIcon(gameInfo(kind), cls); }
function infoIcon(info, cls) {
  if (info.iconSrc) return h('img', { src: info.iconSrc, alt: '', class: 'art' + (cls ? ' ' + cls : '') });
  return artEl(ART.has(info.icon) ? info.icon : '🎮', cls);
}
function settingsDefaults(settings) {
  const c = {};
  (settings || []).forEach(function (f) { if (f && f.key && f.default !== undefined) c[f.key] = f.default; });
  return c;
}
function templateConfigDefaults(kind, p) {
  if (GAME_DEFAULTS[kind]) return JSON.parse(JSON.stringify(GAME_DEFAULTS[kind].config));
  const t = findTemplate(kind, p);
  if (!t) return {};
  const base = isBuiltinKind(t.manifest.extends) ? JSON.parse(JSON.stringify(GAME_DEFAULTS[t.manifest.extends].config)) : {};
  return Object.assign(base, settingsDefaults(t.manifest.settings), JSON.parse(JSON.stringify((t.manifest.defaults || {}).config || {})));
}
/* Inspector fields as [key, label, kind, options] (or ['h', heading]). */
function templateFields(kind) {
  const base = baseKind(kind);
  if (base) return GAME_FIELDS[base];
  const t = findTemplate(kind);
  if (!t) return [];
  return (t.manifest.settings || []).map(function (f) {
    return f.type === 'heading' ? ['h', f.label || ''] : [f.key, f.label || f.key, SETTING_TYPES[f.type] || 'text', f.options];
  });
}
function makeTemplateScreen(kind) {
  const t = findTemplate(kind);
  const m = t.manifest;
  const base = isBuiltinKind(m.extends) ? GAME_DEFAULTS[m.extends] : null;
  const d = m.defaults || {};
  const s = {
    id: uid('s'), name: m.name, type: 'game',
    bg: d.bg || (base ? base.bg : '#22223b'), bg2: d.bg2 != null ? d.bg2 : (base ? base.bg2 : '#4a4e69'),
    objects: [], timer: { seconds: 0, target: '' },
    game: { kind: kind, threshold: d.threshold != null ? +d.threshold : (base ? base.threshold : 100), win: '', lose: '', config: templateConfigDefaults(kind) }
  };
  if (d.effect) s.effect = d.effect;
  return s;
}
function ensureTemplateInProject(kind) {
  if (isBuiltinKind(kind)) return;
  const list = project.gameTemplates = project.gameTemplates || [];
  if (list.some(function (t) { return t.manifest.id === kind; })) return;
  const t = catalogPackages[kind] || templateLibrary()[kind];
  if (t) list.push(JSON.parse(JSON.stringify(t)));
}
function templateUseCount(id) {
  return project.screens.filter(function (s) { return s.type === 'game' && s.game.kind === id; }).length;
}

/* ---------- Import ---------- */
function validateManifest(m) {
  if (!m || typeof m !== 'object' || Array.isArray(m)) throw new Error('manifest.json must contain an object.');
  if (m.format && m.format !== TEMPLATE_FORMAT) throw new Error('This zip is not a Kiosk Studio game template (format "' + m.format + '").');
  if (m.formatVersion && +m.formatVersion > 1) throw new Error('This template needs a newer version of Kiosk Studio (format version ' + m.formatVersion + ').');
  if (!/^[a-z0-9][a-z0-9_-]{1,40}$/.test(m.id || '')) throw new Error('manifest.json needs an "id" of 2–41 lowercase letters, numbers, "-" or "_".');
  if (isBuiltinKind(m.id)) throw new Error('"' + m.id + '" is the name of a built-in game. Please choose another id.');
  if (!m.name || typeof m.name !== 'string') throw new Error('manifest.json needs a "name".');
  if (m.extends && !isBuiltinKind(m.extends)) throw new Error('"extends" must be one of: ' + BUILTIN_KINDS.join(', ') + '.');
  const settings = Array.isArray(m.settings) ? m.settings.filter(function (f) { return f && (f.type === 'heading' || (typeof f.key === 'string' && f.key)); }) : [];
  return Object.assign({}, m, {
    format: TEMPLATE_FORMAT, formatVersion: 1, version: String(m.version || '1.0.0'), main: m.main || 'game.js',
    settings: settings, defaults: m.defaults && typeof m.defaults === 'object' ? m.defaults : {}
  });
}
async function readTemplateZip(file) {
  if (file.size > TEMPLATE_MAX_BYTES) throw new Error('The file is larger than 12 MB.');
  const files = await Zip.read(await file.arrayBuffer());
  const paths = Object.keys(files).filter(function (p) { return p.indexOf('__MACOSX/') !== 0 && !/(^|\/)\.DS_Store$/.test(p); });
  let root = '';
  if (paths.indexOf('manifest.json') < 0) {
    const nested = paths.find(function (p) { return /^[^/]+\/manifest\.json$/.test(p); });
    if (nested) root = nested.slice(0, nested.indexOf('/') + 1);
  }
  const get = function (rel) { return files[root + rel]; };
  if (!get('manifest.json')) throw new Error('manifest.json was not found at the top of the zip.');
  let manifest;
  try { manifest = JSON.parse(Zip.text(get('manifest.json'))); } catch (e) { throw new Error('manifest.json is not valid JSON (' + e.message + ').'); }
  manifest = validateManifest(manifest);
  let code = '';
  if (get(manifest.main)) code = Zip.text(get(manifest.main));
  else if (!manifest.extends) throw new Error(manifest.main + ' was not found in the zip.');
  const assets = {};
  paths.forEach(function (p) {
    if (p.indexOf(root) !== 0) return;
    const rel = p.slice(root.length);
    if (rel === 'manifest.json' || rel === manifest.main) return;
    const ext = (rel.split('.').pop() || '').toLowerCase();
    if (TEMPLATE_MIME[ext]) assets[rel] = Zip.dataURL(files[p], TEMPLATE_MIME[ext]);
  });
  const readme = paths.find(function (p) { return p.toLowerCase() === (root + 'readme.md').toLowerCase(); });
  return { manifest: manifest, code: code, assets: assets, readme: readme ? Zip.text(files[readme]) : '' };
}
async function importTemplateFile(file) {
  closeMenus();
  let pkg;
  try { pkg = await readTemplateZip(file); } catch (e) { showTemplateError(file.name, e.message); return; }
  confirmTemplateInstall(pkg);
}
function showTemplateError(name, msg) {
  openModal(h('div', {},
    h('h2', {}, 'Could not import ' + name),
    h('p', {}, msg),
    h('p', {}, 'A game template is a .zip with manifest.json and game.js at the top level, plus an optional assets folder. Download the sample template to see a working example.'),
    h('div', { class: 'modal-actions' },
      h('button', { class: 'btn', onclick: function () { closeModal(); downloadSampleTemplate(); } }, 'Download sample'),
      h('button', { class: 'btn primary', onclick: closeModal }, 'OK'))));
}
function templateIconBox(info, size) {
  return h('div', { class: 'ci', style: 'width:' + size + 'px;height:' + size + 'px;font-size:' + Math.round(size * 0.55) + 'px;background:' + (info.color || '#3b3f5c') },
    info.iconSrc ? h('img', { src: info.iconSrc, alt: '', style: 'width:72%;height:72%;object-fit:contain' }) : infoIcon(info));
}
function confirmTemplateInstall(pkg) {
  const m = pkg.manifest;
  const existing = findTemplate(m.id);
  const kb = Math.max(1, Math.round(JSON.stringify(pkg).length / 1024));
  const rows = [
    ['Type', m.extends ? 'Preset of the built-in ' + GAME_INFO[m.extends].name + ' game' : 'Custom game (runs its own code)'],
    ['Version', m.version + (existing ? '   (replaces ' + existing.manifest.version + ')' : '')],
    ['Author', m.author || '—'],
    ['Settings', String((m.settings || []).filter(function (f) { return f.type !== 'heading'; }).length)],
    ['Assets', Object.keys(pkg.assets).length + ' file(s)'],
    ['Size', kb + ' KB']
  ];
  openModal(h('div', {},
    h('div', { class: 'tpl-install-head' }, templateIconBox(pkgInfo(pkg), 56),
      h('div', {}, h('h2', {}, m.name), h('p', {}, m.description || ''))),
    h('table', { class: 'data-table' }, h('tbody', {}, rows.map(function (r) { return h('tr', {}, h('th', {}, r[0]), h('td', {}, r[1])); }))),
    m.extends ? null : h('div', { class: 'warn', html: icon('warn') }, ' This template contains program code (game.js). It runs when you press Play and on the kiosk, with the same access as this editor. Only install templates from people you trust.'),
    h('div', { class: 'modal-actions' },
      h('button', { class: 'btn', onclick: closeModal }, 'Cancel'),
      h('button', { class: 'btn primary', onclick: function () { closeModal(); installTemplate(pkg); } }, existing ? 'Update template' : 'Install template'))));
}
function installTemplate(pkg) {
  const id = pkg.manifest.id;
  templateLibrary()[id] = pkg;
  const inLibrary = saveTemplateLibrary();
  commit(function () {
    const list = project.gameTemplates = project.gameTemplates || [];
    const i = list.findIndex(function (t) { return t.manifest.id === id; });
    if (i >= 0) list[i] = JSON.parse(JSON.stringify(pkg));
    else if (!inLibrary) list.push(JSON.parse(JSON.stringify(pkg)));
  });
  renderLeft();
  openTemplateMarket(id, false, 'games');
  toast('"' + pkg.manifest.name + '" installed' + (inLibrary ? '' : ' (kept in this project: browser storage is full)'));
}
function removeTemplate(id) {
  const t = findTemplate(id);
  if (!t) return;
  const used = templateUseCount(id);
  openModal(h('div', {},
    h('h2', {}, 'Remove "' + t.manifest.name + '"?'),
    h('p', {}, used
      ? 'It will be removed from your template library. ' + used + ' screen(s) in this project still use it, so this project keeps its own copy until those screens are deleted.'
      : 'It will be removed from your template library and from this project. You can import the .zip again at any time.'),
    h('div', { class: 'modal-actions' },
      h('button', { class: 'btn', onclick: function () { openTemplateMarket(null, false, 'games'); } }, 'Cancel'),
      h('button', { class: 'btn primary', onclick: function () {
        delete templateLibrary()[id];
        saveTemplateLibrary();
        commit(function () { if (!used) project.gameTemplates = (project.gameTemplates || []).filter(function (x) { return x.manifest.id !== id; }); });
        openTemplateMarket(null, false, 'games');
        toast('Template removed');
      } }, 'Remove'))));
}

/* ---------- Export ---------- */
function defaultReadme(m) {
  return '# ' + m.name + '\n\n' + (m.description || '') + '\n\n' +
    (m.extends ? 'A preset of the built-in "' + m.extends + '" game for Kiosk Studio. Import this .zip in Kiosk Studio: Screens → Game templates → Import .zip.\n'
      : 'A Kiosk Studio game template. Import this .zip in Kiosk Studio: Screens → Game templates → Import .zip.\n');
}
function packageToZip(pkg) {
  const m = pkg.manifest;
  const files = { 'manifest.json': JSON.stringify(m, null, 2) };
  if (pkg.code) files[m.main || 'game.js'] = pkg.code;
  Object.keys(pkg.assets || {}).forEach(function (p) { files[p] = Zip.fromDataURL(pkg.assets[p]); });
  files['README.md'] = pkg.readme || defaultReadme(m);
  return Zip.write(files);
}
function fieldsToSettings(fields) {
  return fields.map(function (f) {
    if (f[0] === 'h') return { type: 'heading', label: f[1] };
    return { key: f[0], label: f[1], type: { num: 'number', check: 'checkbox', color: 'color', text: 'text' }[f[2]] || 'text' };
  });
}
function presetCode(kind) {
  return '/* Preset of the built-in "' + kind + '" game.\n   Kiosk Studio runs its own ' + kind + ' code; this package carries the settings, defaults and look. */\n' +
    'KioskGame.define({ builtin: \'' + kind + '\' });\n';
}
function builtinPackage(kind, opts) {
  opts = opts || {};
  const d = GAME_DEFAULTS[kind];
  const info = GAME_INFO[kind];
  const defaults = {
    bg: opts.bg || d.bg, bg2: opts.bg2 != null ? opts.bg2 : d.bg2, threshold: opts.threshold != null ? opts.threshold : d.threshold,
    config: JSON.parse(JSON.stringify(opts.config || d.config))
  };
  if (opts.effect) defaults.effect = opts.effect;
  return {
    manifest: {
      format: TEMPLATE_FORMAT, formatVersion: 1, id: opts.id || kind + '-starter', name: opts.name || info.name + ' starter', version: '1.0.0',
      author: opts.author || '', description: opts.description || info.blurb, icon: info.icon, color: BUILTIN_TINT[kind], extends: kind, main: 'game.js',
      defaults: defaults, settings: fieldsToSettings(GAME_FIELDS[kind])
    },
    code: presetCode(kind), assets: {}, readme: ''
  };
}
function exportTemplate(kind) {
  const pkg = isBuiltinKind(kind) ? builtinPackage(kind) : findTemplate(kind);
  if (!pkg) { toast('Template not found'); return; }
  download(pkg.manifest.id + '-' + pkg.manifest.version + '.zip', packageToZip(pkg));
  toast('Exported "' + pkg.manifest.name + '" (.zip)');
}
function slugId(name) {
  let s = String(name).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 36) || 'my-game';
  if (s.length < 2) s += '-game';
  if (isBuiltinKind(s)) s += '-preset';
  return s;
}
/* Pack a configured game screen (its settings, colours and score rule) as a reusable template. */
function saveScreenAsTemplate(sc) {
  const g = sc.game;
  const info = gameInfo(g.kind);
  const nameIn = h('input', { class: 'inp', value: sc.name });
  const descIn = h('input', { class: 'inp', value: info.blurb || '' });
  openModal(h('div', {},
    h('h2', {}, 'Save as game template'),
    h('p', {}, 'Packs this screen\'s game, settings and colours into a .zip you can share or import into other projects. It is also added to your Game templates list.'),
    row('Name', nameIn), row('Description', descIn),
    h('div', { class: 'modal-actions', style: 'margin-top:14px' },
      h('button', { class: 'btn', onclick: closeModal }, 'Cancel'),
      h('button', { class: 'btn primary', onclick: function () {
        const name = nameIn.value.trim() || sc.name;
        const id = slugId(name);
        const base = baseKind(g.kind);
        const src = isBuiltinKind(g.kind) ? null : findTemplate(g.kind);
        const defaults = { bg: sc.bg, bg2: sc.bg2, threshold: g.threshold, config: JSON.parse(JSON.stringify(g.config)) };
        if (sc.effect) defaults.effect = sc.effect;
        let pkg;
        if (base) pkg = builtinPackage(base, Object.assign({ id: id, name: name, description: descIn.value.trim() }, defaults));
        else if (src) {
          pkg = JSON.parse(JSON.stringify(src));
          Object.assign(pkg.manifest, { id: id, name: name, description: descIn.value.trim(), defaults: defaults });
        } else { toast('This screen\'s template is missing'); return; }
        closeModal();
        download(id + '-' + pkg.manifest.version + '.zip', packageToZip(pkg));
        installTemplate(pkg);
      } }, 'Save .zip'))));
  setTimeout(function () { nameIn.focus(); nameIn.select(); }, 30);
}

/* ---------- Game templates: the marketplace ---------- */
/* The games on offer come from the site's template library, its game_templates
   table (GET ../game-templates, App\Http\Controllers\GameTemplateController):
   Kiosk Studio's own games, and presets and custom games the team uploaded,
   whose .zip is downloaded when one is added to the design. Templates imported
   from a .zip on this computer are listed too, as "Imported". Opened without
   the site, the studio offers just its built-in games. The left panel and
   Insert → Game… open it. */
let marketFilter = 'all';
let templateCatalog = null;        // the library's rows, once loaded
let templateCatalogFailed = false; // the library could not be reached
let catalogLoading = null;
const catalogPackages = {};        // packages downloaded from the library, by id

function catalogUrl() { return new URL('../game-templates', location.href).href; }

function validCatalogRow(r) {
  if (!r || typeof r.id !== 'string' || typeof r.name !== 'string') return false;
  if (r.kind === 'builtin') return isBuiltinKind(r.id);
  return !isBuiltinKind(r.id) && typeof r.download === 'string' && (!r.extends || isBuiltinKind(r.extends));
}

/* Resolves to the rows, or null when there's no library to read. */
function loadTemplateCatalog() {
  if (catalogLoading) return catalogLoading;
  catalogLoading = fetch(catalogUrl(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    .then(function (r) {
      // Not JSON: the studio is opened on its own, or signed out.
      if (!r.ok || (r.headers.get('content-type') || '').indexOf('json') < 0) {
        templateCatalogFailed = r.status >= 500;
        return null;
      }
      return r.json().then(function (rows) {
        if (!Array.isArray(rows)) throw new Error('Not a list');
        templateCatalog = rows.filter(validCatalogRow);
        templateCatalogFailed = false;
      });
    })
    .catch(function () { templateCatalogFailed = true; })
    .then(function () { catalogLoading = null; refreshTemplateSummary(); return templateCatalog; });
  return catalogLoading;
}

/* How a library row looks on its card. */
function rowInfo(r) {
  const base = r.kind === 'builtin' ? gameInfo(r.id) : null;
  return {
    name: r.name || (base ? base.name : r.id),
    icon: r.icon || (base ? base.icon : '🧩'),
    iconSrc: typeof r.icon_image === 'string' && r.icon_image.indexOf('data:image/') === 0 ? r.icon_image : '',
    color: r.color || (base ? base.color : '#3b3f5c'),
    blurb: r.description || (base ? base.blurb : r.extends ? 'Preset of ' + GAME_INFO[r.extends].name : 'Custom game'),
    custom: !base
  };
}

/* Every game on offer: { kind, group, row, info, pkg }, group being
   builtin | preset | custom (from the library) or imported. */
function marketItems() {
  const items = [];
  const listed = {};
  if (templateCatalog) {
    templateCatalog.forEach(function (r) {
      listed[r.id] = true;
      const group = r.kind === 'builtin' ? 'builtin' : r.extends ? 'preset' : 'custom';
      items.push({ kind: r.id, group: group, row: r, info: rowInfo(r), pkg: group === 'builtin' ? null : findTemplate(r.id) });
    });
  } else {
    BUILTIN_KINDS.forEach(function (kind) {
      listed[kind] = true;
      items.push({ kind: kind, group: 'builtin', row: null, info: gameInfo(kind), pkg: null });
    });
  }
  customTemplates().forEach(function (t) {
    if (!listed[t.manifest.id]) items.push({ kind: t.manifest.id, group: 'imported', row: null, info: pkgInfo(t), pkg: t });
  });
  return items;
}

function templateSummary() {
  let items = [];
  try { items = marketItems(); } catch (e) { /* before the project is open */ }
  const games = items.filter(function (x) { return x.group !== 'imported'; }).length || BUILTIN_KINDS.length;
  const imported = items.length - games;
  return games + ' game' + (games === 1 ? '' : 's') + (imported > 0 ? ' · ' + imported + ' imported' : '') + ' · import your own';
}
function refreshTemplateSummary() {
  const text = templateSummary();
  document.querySelectorAll('.tpl-count').forEach(function (el) { el.textContent = text; });
}

function renderTemplateSection(body) {
  body.append(h('div', { class: 'lp-title', style: 'margin-top:14px' }, 'Game templates'));
  body.append(h('button', { class: 'card', onclick: function () { openTemplateMarket(null, false, 'games'); } },
    h('div', { class: 'ci', style: 'background:' + BUILTIN_TINT.quiz + ';color:#fff' }, artEl('🎮')),
    h('div', {}, h('b', {}, 'Browse game templates'), h('small', { class: 'tpl-count' }, templateSummary()))));
}

/* The marketplace has two sections: games, and animated screen templates.
   highlight: the id of a game just installed, picked out in the grid.
   restore: coming back from a template's page, so keep the search and scroll.
   section: 'games' or 'screens'; the one used last when left out. */
let marketSearch = '';
let marketScroll = 0;
let marketSection = 'games';
let screenFilter = 'all';
const SCREEN_GROUPS = [['all', 'All'], ['welcome', 'Welcome'], ['howto', 'How to play'], ['result', 'Results'], ['ads', 'Advertising']];

function openTemplateMarket(highlight, restore, section) {
  closeMenus();
  if (section) marketSection = section;
  if (!restore) { marketSearch = ''; marketScroll = 0; }
  let items = marketItems();
  const filters = [['all', 'All'], ['builtin', 'Built in'], ['preset', 'Presets'], ['custom', 'Custom games'], ['imported', 'Imported']];
  const matches = function (x, f) { return f === 'all' || x.group === f; };
  const search = h('input', { class: 'inp mk-search', type: 'search', 'aria-label': 'Search templates' });
  search.value = marketSearch;
  const intro = h('p', {});
  const tabs = h('div', { class: 'seg mk-tabs', role: 'tablist', 'aria-label': 'Template type' });
  const chips = h('div', { class: 'mk-chips' });
  const tools = h('div', { class: 'btn-row' });
  const grid = h('div', { class: 'mk-grid', 'aria-live': 'polite' });
  const foot = h('div', { class: 'mk-foot' });
  let loading = !templateCatalog;
  const chip = function (f, current, n, onclick) {
    return h('button', { type: 'button', class: 'mk-chip' + (current === f[0] ? ' on' : ''), 'aria-pressed': String(current === f[0]), onclick: onclick }, f[1], h('span', {}, String(n)));
  };

  function renderTabs() {
    tabs.innerHTML = '';
    [['games', 'Games', items.length], ['screens', 'Screens', SCREEN_TEMPLATES.length]].forEach(function (tb) {
      const on = marketSection === tb[0];
      tabs.append(h('button', { type: 'button', role: 'tab', class: on ? 'on' : '', 'aria-selected': String(on),
        onclick: function () {
          if (on) return;
          marketSection = tb[0];
          marketSearch = search.value = '';
          body.scrollTop = 0;
          render();
        } }, tb[1], h('span', { class: 'mk-tab-n' }, String(tb[2]))));
    });
  }

  function render() {
    renderTabs();
    const q = search.value.trim().toLowerCase();
    grid.innerHTML = '';
    chips.innerHTML = '';
    tools.innerHTML = '';
    foot.classList.remove('warn-text');

    if (marketSection === 'screens') {
      search.placeholder = 'Search screens';
      intro.textContent = 'Ready-made animated screens to start from: welcome, how to play, results and ads.';
      SCREEN_GROUPS.forEach(function (gr) {
        const n = SCREEN_TEMPLATES.filter(function (t) { return gr[0] === 'all' || t.group === gr[0]; }).length;
        chips.append(chip(gr, screenFilter, n, function () { screenFilter = gr[0]; render(); }));
      });
      const shown = SCREEN_TEMPLATES.filter(function (t) {
        return (screenFilter === 'all' || t.group === screenFilter) && (!q || (t.name + ' ' + t.blurb).toLowerCase().indexOf(q) >= 0);
      });
      if (!shown.length) grid.append(h('div', { class: 'mk-empty' }, 'No screens match "' + search.value.trim() + '".'));
      shown.forEach(function (t) { grid.append(screenCard(t)); });
      foot.textContent = 'Hover a screen to play its animations. A how-to-play screen\'s PLAY button opens its game when that game is already in your design.';
      return;
    }

    search.placeholder = 'Search games';
    intro.textContent = 'Add a game to your kiosk as a new screen, or import your own from a .zip.';
    tools.append(btn('Import .zip', function () { $('#fileTemplate').click(); }, '', 'upload'), btn('Sample template', downloadSampleTemplate, '', 'download'));
    // Only the groups that have games, and All.
    const shownFilters = filters.filter(function (f) { return f[0] === 'all' || items.some(function (x) { return matches(x, f[0]); }); });
    if (!shownFilters.some(function (f) { return f[0] === marketFilter; })) marketFilter = 'all';
    shownFilters.forEach(function (f) {
      chips.append(chip(f, marketFilter, items.filter(function (x) { return matches(x, f[0]); }).length, function () { marketFilter = f[0]; render(); }));
    });
    const shown = items.filter(function (x) {
      return matches(x, marketFilter) && (!q || (x.info.name + ' ' + x.info.blurb).toLowerCase().indexOf(q) >= 0);
    });
    if (loading) grid.append(h('div', { class: 'mk-empty mk-loading' }, 'Loading games…'));
    else if (!shown.length) {
      grid.append(h('div', { class: 'mk-empty' }, q
        ? 'No games match "' + search.value.trim() + '".'
        : 'No templates here yet. Import a .zip, or download the sample template to see how one is made.'));
    }
    if (!loading) shown.forEach(function (x) { grid.append(marketCard(x, x.kind === highlight)); });
    foot.textContent = templateCatalogFailed
      ? 'The template library could not be loaded, so only the built-in games and your imports are shown. Close this and open it again to retry.'
      : 'Add a game as a new screen. A template is a .zip with its settings, and for a custom game its code: import one of your own, or export one to use elsewhere.';
    foot.classList.toggle('warn-text', templateCatalogFailed);
  }
  search.addEventListener('input', function () { marketSearch = search.value; render(); });
  const body = h('div', { class: 'mk-body' }, grid);
  body.addEventListener('scroll', function () { marketScroll = body.scrollTop; });

  openModal(h('div', { class: 'mk' },
    h('div', { class: 'mk-head' },
      h('div', { class: 'mk-title' }, h('h2', {}, 'Templates'), intro),
      tabs,
      search,
      h('button', { class: 'icon-btn', title: 'Close (Esc)', 'aria-label': 'Close', html: icon('close'), onclick: closeModal })),
    h('div', { class: 'mk-bar' }, chips, tools),
    body,
    foot
  ), { wide: true });
  render();
  setTimeout(function () {
    const fresh = grid.querySelector('.mk-card.fresh');
    if (fresh) fresh.scrollIntoView({ block: 'nearest' });
    else if (restore) body.scrollTop = marketScroll;
    search.focus({ preventScroll: true });
  }, 30);

  // Fetch the games each time, in case the team changed them.
  loadTemplateCatalog().then(function () {
    if (!grid.isConnected) return;
    const top = body.scrollTop;
    loading = false;
    items = marketItems();
    render();
    body.scrollTop = top || (restore ? marketScroll : 0);
  });
}

/* ---------- Animated screen templates ---------- */
/* A screen drawn small. animated: its objects play their entrance and loop
   animations (and its background effect moves), as on the kiosk. */
function screenThumb(sc, maxW, maxH, animated) {
  if (!animated) return makeThumb(sc, maxW, maxH);
  const W = project.width, H = project.height;
  const k = Math.min(maxW / W, maxH / H);
  const t = h('div', { class: 'thumb live', style: 'width:' + Math.round(W * k) + 'px;height:' + Math.round(H * k) + 'px' });
  const tin = h('div', { class: 'tin', style: 'width:' + W + 'px;height:' + H + 'px;transform:scale(' + k + ')' });
  tin.style.background = RT.bgCss(sc, assetsMap());
  if (RT.hasFx(sc)) tin.appendChild(RT.renderFx(sc.effect, sc.id, W, H));
  const ctx = editCtx();
  sc.objects.forEach(function (o) { if (!o.hidden) tin.appendChild(RT.renderObj(o, ctx, 'anim')); });
  t.appendChild(tin);
  return t;
}
function screenGroupName(t) { return (SCREEN_GROUPS.find(function (g) { return g[0] === t.group; }) || [0, 'Screen'])[1]; }
function effectName(e) { return e && e !== 'none' ? e.charAt(0).toUpperCase() + e.slice(1) : null; }
/* For a how-to-play screen: the game screen in this design its PLAY button will open. */
function screenTemplateLink(t) {
  return t.kind ? project.screens.find(function (s) { return s.type === 'game' && s.game.kind === t.kind; }) || null : null;
}
function animatedObjects(sc) {
  return sc.objects.filter(function (o) { return (o.enter && o.enter !== 'none') || (o.anim && o.anim !== 'none'); });
}
function addScreenFromMarket(t) {
  closeModal();
  addScreenTemplate(t);
}

function screenCard(t) {
  const sc = t.make();
  const land = project.width > project.height;
  const linked = screenTemplateLink(t);
  const art = h('div', { class: 'mk-art live', style: '--tint:' + (sc.bg || '#3b3f5c') },
    h('span', { class: 'mk-art-icon', 'aria-hidden': 'true' }, artEl(t.icon)));
  const draw = function (animated) {
    const old = art.querySelector('.mk-phone');
    if (old) old.remove();
    art.append(phoneFrame(screenThumb(sc, land ? 220 : 120, land ? 124 : 148, animated)));
  };
  draw(false);
  const moving = animatedObjects(sc).length;
  const meta = [
    moving ? plural(moving, 'animated item') : null,
    effectName(sc.effect) ? effectName(sc.effect) + ' background' : null,
    t.kind ? (linked ? 'PLAY opens "' + linked.name + '"' : 'for the ' + GAME_INFO[t.kind].name + ' game') : null
  ].filter(Boolean).join(' · ');
  return h('div', { class: 'mk-card', onmouseenter: function () { draw(true); }, onmouseleave: function () { draw(false); } },
    h('button', { type: 'button', class: 'mk-open', title: 'Preview its animations', onclick: function () { openScreenTemplateDetails(t); } },
      art,
      h('div', { class: 'mk-info' },
        h('div', { class: 'mk-name' }, h('b', {}, t.name), h('span', { class: 'mk-tag screen' }, screenGroupName(t))),
        h('p', {}, t.blurb),
        meta ? h('small', {}, meta) : null)),
    h('div', { class: 'mk-actions' },
      h('button', { class: 'btn primary', onclick: function () { addScreenFromMarket(t); } }, 'Add to project'),
      h('button', { class: 'btn', onclick: function () { openScreenTemplateDetails(t); } }, 'Preview')));
}

/* A screen template's page: the screen playing large, what's on it, and its
   animations on a timeline that runs along with the preview. */
function openScreenTemplateDetails(t) {
  closeMenus();
  const sc = t.make();
  const land = project.width > project.height;
  const linked = screenTemplateLink(t);
  const moving = animatedObjects(sc);
  const end = Math.max(1, Math.ceil(Math.max.apply(null, moving.map(function (o) { return RT.animEnd(o); }).concat([0])) * 2) / 2);
  const stage = h('div', { class: 'sd-hero-art' });
  const timeline = h('div', { class: 'sd-tl' });
  let playhead = null;

  function play() {
    stage.innerHTML = '';
    stage.append(phoneFrame(screenThumb(sc, land ? 420 : 230, land ? 236 : 410, true)));
    // A new playhead runs across the timeline in step with the preview.
    if (playhead) playhead.remove();
    playhead = h('i', { class: 'sd-tl-head', style: 'animation-duration:' + end + 's', 'aria-hidden': 'true' });
    timeline.append(playhead);
  }

  const counts = [
    ['Texts', sc.objects.filter(function (o) { return o.type === 'text'; }).length],
    ['Buttons', sc.objects.filter(function (o) { return o.type === 'button'; }).length],
    ['Stickers', sc.objects.filter(function (o) { return o.type === 'emoji'; }).length],
    ['Shapes', sc.objects.filter(function (o) { return o.type === 'shape'; }).length],
    ['Pictures', sc.objects.filter(function (o) { return o.type === 'image'; }).length],
    ['Form fields', sc.objects.filter(function (o) { return o.type === 'input'; }).length]
  ].filter(function (c) { return c[1]; });

  // One row per animated object: when it comes in, and whether it keeps moving.
  const label = function (o) {
    if (o.type === 'emoji') return ART.label(o.emoji) + ' sticker';
    if (o.type === 'text' || o.type === 'button') {
      const txt = String(o.text || '').replace(/\{\{(\w+)\}\}/g, '[$1]').replace(/\s+/g, ' ').trim();
      return (o.type === 'button' ? 'Button: ' : '') + (txt.length > 26 ? txt.slice(0, 25) + '…' : txt || o.type);
    }
    return o.type === 'shape' ? (o.shape === 'circle' ? 'Circle' : 'Box') : o.type;
  };
  const ticks = h('div', { class: 'sd-tl-ticks' });
  for (let s = 0; s <= end; s += end > 4 ? 1 : 0.5) ticks.append(h('span', { style: 'left:' + (s / end * 100) + '%' }, s + 's'));
  timeline.append(ticks);
  moving.slice().sort(function (a, b) { return (+a.enterDelay || 0) - (+b.enterDelay || 0); }).forEach(function (o, i) {
    const enter = o.enter && o.enter !== 'none';
    const delay = enter ? Math.max(0, +o.enterDelay || 0) : 0;
    const dur = enter ? Math.max(0.1, +o.enterDuration || 0.7) : 0;
    const track = h('div', { class: 'sd-tl-track' });
    if (enter) track.append(h('b', { style: 'left:' + (delay / end * 100) + '%;width:' + Math.max(2, dur / end * 100) + '%;animation-delay:' + i * 40 + 'ms', title: o.enter + ', ' + delay + 's + ' + dur + 's' }, o.enter));
    if (o.anim && o.anim !== 'none') track.append(h('em', { style: 'left:' + ((delay + dur) / end * 100) + '%', title: 'Then keeps moving: ' + o.anim }, h('span', {}, o.anim + ' ∞')));
    timeline.append(h('div', { class: 'sd-tl-row' }, h('span', { class: 'sd-tl-name', title: label(o) }, label(o)), track));
  });

  const links = t.kind
    ? (linked ? 'Its PLAY button opens your "' + linked.name + '" screen.' : 'Add the ' + GAME_INFO[t.kind].name + ' game to your design first, and its PLAY button will open it. You can also link it later in the Flow Map.')
    : t.group === 'welcome' ? 'Link its button to the next screen in the Flow Map, and set it as the start screen for an attract loop.'
      : t.group === 'ads' ? 'It moves on by itself after ' + AD_SECONDS + ' seconds: choose the next ad in Auto advance (or in the Flow Map) and a few ads become a rotating loop. Link its button to an offer or form screen.'
        : 'Use it after a game: it shows the player\'s {{score}}. Link its buttons in the Flow Map.';

  const add = function (cls) { return h('button', { class: 'btn primary ' + cls, onclick: function () { addScreenFromMarket(t); } }, 'Add to project'); };
  const view = h('div', { class: 'mk sd', style: '--tint:' + (sc.bg || '#3b3f5c') },
    h('div', { class: 'mk-head sd-head' },
      h('button', { type: 'button', class: 'btn sd-back', onclick: function () { openTemplateMarket(null, true, 'screens'); } }, '‹  All screens'),
      h('div', { class: 'mk-title' }, h('p', {}, 'Templates  ›  Screens  ›  ' + t.name)),
      h('button', { class: 'icon-btn', title: 'Close (Esc)', 'aria-label': 'Close', html: icon('close'), onclick: closeModal })),
    h('div', { class: 'mk-body sd-body' },
      h('div', { class: 'sd-hero' },
        h('span', { class: 'sd-hero-emoji', 'aria-hidden': 'true' }, artEl(t.icon)),
        stage,
        h('div', { class: 'sd-hero-info' },
          h('div', { class: 'sd-kicker' }, h('span', { class: 'sd-chip' }, screenGroupName(t)), h('span', { class: 'sd-chip' }, 'Animated')),
          h('h2', {}, t.name),
          h('p', { class: 'sd-by' }, 'by Kiosk Studio  ·  ' + plural(moving.length, 'animated item') + '  ·  plays for ' + end + 's'),
          h('p', { class: 'sd-blurb' }, t.blurb),
          h('div', { class: 'sd-actions' }, add('big'),
            h('button', { class: 'btn sd-ghost', onclick: play }, h('span', { html: icon('restart') }), 'Replay')))),
      h('div', { class: 'sd-grid' },
        h('div', { class: 'sd-main' },
          h('section', { class: 'sd-sec' }, h('h3', {}, 'Animation timeline', h('small', {}, 'Each item\'s entrance, then any loop')), timeline),
          h('section', { class: 'sd-sec' }, h('h3', {}, 'How it links'), h('p', { class: 'sd-lead' }, links)),
          counts.length ? h('section', { class: 'sd-sec' }, h('h3', {}, 'What\'s on it'),
            h('div', { class: 'sd-counts' }, counts.map(function (c) { return h('div', {}, h('b', {}, String(c[1])), h('span', {}, c[0])); }))) : null),
        h('aside', { class: 'sd-side' },
          h('div', { class: 'sd-facts' },
            h('h4', {}, 'Details'),
            h('dl', {}, [
              ['Type', 'Animated screen'],
              ['Section', screenGroupName(t)],
              effectName(sc.effect) ? ['Background', effectName(sc.effect)] : null,
              sc.transition ? ['Arrives with', effectName(sc.transition)] : null,
              ['Plays for', end + ' seconds'],
              t.kind ? ['Opens', GAME_INFO[t.kind].name + ' game'] : null
            ].filter(Boolean).map(function (r) { return [h('dt', {}, r[0]), h('dd', {}, r[1])]; }).flat()),
            add('wide'))))));
  openModal(view, { wide: true });
  play();
}

/* ---------- Cards ---------- */
/* Five stars filled to a rating (CSS draws them; --fill is how much is gold). */
function starsEl(rating, big) {
  const r = Math.max(0, Math.min(5, +rating || 0));
  return h('span', { class: 'stars' + (big ? ' big' : ''), role: 'img', 'aria-label': (Math.round(r * 10) / 10) + ' out of 5 stars', style: '--fill:' + (r / 5 * 100) + '%' });
}
function plural(n, word) { return n + ' ' + word + (n === 1 ? '' : 's'); }

/* The game as it looks in this design: its screen, or (which = 'start') its
   how-to-play screen. Null when there's nothing to draw it from. */
function gameShot(kind, which, maxW, maxH) {
  try {
    let sc = null;
    if (which === 'start') sc = isBuiltinKind(kind) && START_INFO[kind] ? gameStartScreen(kind) : null;
    else if (isBuiltinKind(kind)) sc = makeScreen(kind);
    else if (findTemplate(kind)) sc = makeTemplateScreen(kind);
    return sc ? makeThumb(sc, maxW, maxH) : null;
  } catch (e) {
    return null;
  }
}
function phoneFrame(shot) { return h('div', { class: 'mk-phone' }, shot); }

/* A card's picture: the cover photo, else a live preview in a phone, else the icon. */
function cardArt(x) {
  const r = x.row;
  const art = h('div', { class: 'mk-art', style: '--tint:' + (x.info.color || '#3b3f5c') });
  if (r && r.thumbnail) {
    art.classList.add('photo');
    art.append(h('img', { src: r.thumbnail, alt: '', loading: 'lazy' }));
    return art;
  }
  const land = project.width > project.height;
  const shot = gameShot(x.kind, 'game', land ? 220 : 120, land ? 124 : 148);
  if (shot) {
    art.classList.add('live');
    art.append(h('span', { class: 'mk-art-icon', 'aria-hidden': 'true' }, x.info.iconSrc ? '' : infoIcon(x.info)), phoneFrame(shot));
    return art;
  }
  art.append(x.info.iconSrc ? h('img', { src: x.info.iconSrc, alt: '', class: 'mk-icon-img' }) : h('span', {}, infoIcon(x.info)));
  return art;
}
function ratingLine(r) {
  if (!r) return null;
  return h('div', { class: 'mk-rating' }, starsEl(r.rating),
    r.reviews_count ? h('span', {}, h('b', {}, (+r.rating).toFixed(1)), ' (' + r.reviews_count + ')') : h('span', { class: 'none' }, 'No reviews yet'));
}

/* What a card and a store page say about a game. */
function gameFacts(x) {
  const r = x.row;
  const m = x.pkg ? x.pkg.manifest : null;
  // A design made with an older version of a library game.
  const own = r && x.group !== 'builtin' ? (project.gameTemplates || []).find(function (t) { return t.manifest.id === x.kind; }) : null;
  return {
    version: r && x.group !== 'builtin' ? r.version : m ? m.version : null,
    based: r ? r.extends : m && m.extends,
    author: x.group === 'builtin' ? 'Kiosk Studio' : r ? r.author : m && m.author,
    used: templateUseCount(x.kind),
    own: own,
    update: !!(own && own.manifest.version !== r.version),
    tag: { builtin: 'Built in', preset: 'Preset', custom: 'Custom', imported: 'Imported' }[x.group]
  };
}

function marketCard(x, fresh) {
  const f = gameFacts(x);
  const meta = [
    f.version ? 'v' + f.version : null,
    f.based ? 'based on ' + GAME_INFO[f.based].name : null,
    x.group !== 'builtin' && f.author ? 'by ' + f.author : null,
    f.used ? 'used on ' + plural(f.used, 'screen') : null,
    f.update ? 'this design has v' + f.own.manifest.version : null,
  ].filter(Boolean).join(' · ');
  const add = h('button', { class: 'btn primary', onclick: function () { addFromMarket(x, add); } }, f.update ? 'Update & add' : 'Add to project');
  return h('div', { class: 'mk-card' + (fresh ? ' fresh' : '') },
    h('button', { type: 'button', class: 'mk-open', title: 'See screenshots, details and reviews', onclick: function () { openTemplateDetails(x); } },
      cardArt(x),
      h('div', { class: 'mk-info' },
        h('div', { class: 'mk-name' }, h('b', {}, x.info.name), h('span', { class: 'mk-tag ' + x.group }, f.tag)),
        ratingLine(x.row),
        h('p', {}, x.info.blurb),
        meta ? h('small', {}, meta) : null)),
    h('div', { class: 'mk-actions' },
      add,
      h('button', { class: 'btn', onclick: function () { openTemplateDetails(x); } }, 'Details'),
      h('span', { class: 'spacer' }),
      h('button', { class: 'icon-btn', title: 'Export as .zip', 'aria-label': 'Export ' + x.info.name + ' as .zip', html: icon('download'), onclick: function () { exportFromMarket(x); } }),
      x.group === 'imported' ? h('button', { class: 'icon-btn', title: 'Remove template', 'aria-label': 'Remove ' + x.info.name, html: icon('trash'), onclick: function () { removeTemplate(x.kind); } }) : null));
}

/* ---------- A game's store page ----------
   GET ../game-templates/{id} for its screenshots, write-up and reviews;
   PUT / DELETE ../game-templates/{id}/review to rate it. Writes carry the
   site's XSRF-TOKEN cookie as a header, as Laravel expects. */
function storeUrl(id, more) { return new URL('../game-templates/' + encodeURIComponent(id) + (more || ''), location.href).href; }
function xsrfToken() {
  const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
  return m ? decodeURIComponent(m[1]) : '';
}
async function storeRequest(id, method, body) {
  const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  if (method !== 'GET') headers['X-XSRF-TOKEN'] = xsrfToken();
  if (body) headers['Content-Type'] = 'application/json';
  let res;
  try {
    res = await fetch(storeUrl(id, method === 'GET' ? '' : '/review'), { method: method, credentials: 'same-origin', headers: headers, body: body ? JSON.stringify(body) : undefined });
  } catch (e) {
    throw new Error('Could not reach the server. Check your connection and try again.');
  }
  let data = null;
  if ((res.headers.get('content-type') || '').indexOf('json') >= 0) data = await res.json();
  if (res.ok && data) return data;
  const first = data && data.errors ? Object.values(data.errors)[0][0] : null;
  throw new Error(first || ({
    401: 'Sign in to your account to see and write reviews.',
    404: 'This game is no longer offered.',
    419: 'Your session has expired. Reload the page and try again.',
    429: 'Too many tries. Wait a minute, then try again.'
  }[res.status] || (res.ok ? 'Reviews need Kiosk Studio to be opened from your account.' : 'Something went wrong (' + res.status + '). Try again.')));
}
/* Keep the list's stars in step with a page just loaded or reviewed. */
function syncCatalogRow(page) {
  const row = (templateCatalog || []).find(function (r) { return r.id === page.id; });
  if (row) { row.rating = page.rating; row.reviews_count = page.reviews_count; }
}
function avatarColor(name) {
  let n = 0;
  String(name).split('').forEach(function (c) { n = (n * 31 + c.charCodeAt(0)) >>> 0; });
  return 'hsl(' + (n % 360) + ' 55% 45%)';
}

function openTemplateDetails(x) {
  closeMenus();
  const f = gameFacts(x);
  const land = project.width > project.height;
  let page = null, loadError = '';
  const main = h('div', { class: 'mk-body sd-body' });
  const view = h('div', { class: 'mk sd', style: '--tint:' + (x.info.color || '#3b3f5c') },
    h('div', { class: 'mk-head sd-head' },
      h('button', { type: 'button', class: 'btn sd-back', onclick: function () { openTemplateMarket(null, true, 'games'); } }, '‹  All games'),
      h('div', { class: 'mk-title' }, h('p', {}, 'Templates  ›  Games  ›  ' + x.info.name)),
      h('button', { class: 'icon-btn', title: 'Close (Esc)', 'aria-label': 'Close', html: icon('close'), onclick: closeModal })),
    main);
  openModal(view, { wide: true });

  function render() {
    const top = main.scrollTop;
    main.innerHTML = '';
    main.append(hero(), h('div', { class: 'sd-grid' },
      h('div', { class: 'sd-main' }, shots(), about(), reviews()),
      h('aside', { class: 'sd-side' }, facts())));
    main.scrollTop = top;
  }
  function addButton(cls) {
    const b = h('button', { class: 'btn primary ' + (cls || ''), onclick: function () { addFromMarket(x, b); } }, f.update ? 'Update & add to project' : 'Add to project');
    return b;
  }

  function hero() {
    const r = page || x.row;
    let cover;
    if (r && r.thumbnail) cover = h('img', { class: 'sd-photo', src: r.thumbnail, alt: x.info.name });
    else {
      const shot = gameShot(x.kind, 'game', land ? 380 : 200, land ? 214 : 356);
      cover = shot ? phoneFrame(shot) : h('div', { class: 'sd-icon-art' }, x.info.iconSrc ? h('img', { src: x.info.iconSrc, alt: '' }) : infoIcon(x.info));
    }
    const rating = r ? h('button', { type: 'button', class: 'sd-rating', onclick: function () { const s = main.querySelector('#sd-reviews'); if (s) s.scrollIntoView({ behavior: 'smooth' }); } },
      starsEl(r.rating, true),
      h('b', {}, r.reviews_count ? (+r.rating).toFixed(1) : ''),
      h('span', {}, r.reviews_count ? plural(r.reviews_count, 'review') : 'No reviews yet · be the first')) : null;
    return h('div', { class: 'sd-hero' + (r && r.thumbnail ? ' has-photo' : '') },
      h('span', { class: 'sd-hero-emoji', 'aria-hidden': 'true' }, infoIcon(x.info)),
      h('div', { class: 'sd-hero-art' }, cover),
      h('div', { class: 'sd-hero-info' },
        h('div', { class: 'sd-kicker' }, h('span', { class: 'sd-chip' }, f.tag), f.based ? h('span', { class: 'sd-chip' }, 'Based on ' + GAME_INFO[f.based].name) : null),
        h('h2', {}, x.info.name),
        h('p', { class: 'sd-by' }, [f.author ? 'by ' + f.author : null, f.version ? 'v' + f.version : null].filter(Boolean).join('  ·  ')),
        rating,
        h('p', { class: 'sd-blurb' }, x.info.blurb),
        h('div', { class: 'sd-actions' }, addButton('big'),
          h('button', { class: 'btn sd-ghost', onclick: function () { exportFromMarket(x); } }, h('span', { html: icon('download') }), 'Export .zip'))));
  }

  /* Uploaded screenshots, or live previews in this design's size. */
  function shots() {
    const urls = page && page.screenshots ? page.screenshots : [];
    const items = urls.length ? urls.map(function (u) { return { img: u }; }) : [
      { which: 'start', caption: 'How to play' },
      { which: 'game', caption: 'The game' }
    ].filter(function (it) { return it.which === 'start' ? isBuiltinKind(x.kind) && !!START_INFO[x.kind] : isBuiltinKind(x.kind) || !!findTemplate(x.kind); });
    if (!items.length) return null;
    const strip = h('div', { class: 'sd-shots' });
    items.forEach(function (it, i) {
      const pic = it.img ? h('img', { src: it.img, alt: 'Screenshot ' + (i + 1), loading: 'lazy' }) : gameShot(x.kind, it.which, land ? 420 : 9999, land ? 9999 : 320);
      strip.append(h('button', { type: 'button', class: 'sd-shot', style: 'animation-delay:' + i * 70 + 'ms', title: 'View larger', onclick: function () { lightbox(items, i); } },
        pic, it.caption ? h('span', { class: 'sd-cap' }, it.caption) : null));
    });
    return h('section', { class: 'sd-sec' },
      h('h3', {}, 'Screenshots', urls.length ? h('small', {}, plural(urls.length, 'picture')) : h('small', {}, 'Live preview in your design\'s size')), strip);
  }
  function lightbox(items, start) {
    let i = start;
    const stage = h('div', { class: 'sd-lb-stage' });
    const count = h('span', { class: 'sd-lb-count' });
    const show = function () {
      stage.innerHTML = '';
      const it = items[i];
      const pic = it.img ? h('img', { src: it.img, alt: 'Screenshot ' + (i + 1) }) : gameShot(x.kind, it.which, box.clientWidth * 0.8, box.clientHeight * 0.78);
      stage.append(pic);
      count.textContent = (i + 1) + ' / ' + items.length + (it.caption ? '  ·  ' + it.caption : '');
    };
    const go = function (d) { i = (i + d + items.length) % items.length; show(); };
    const close = function () { window.removeEventListener('keydown', keys, true); box.remove(); };
    const keys = function (e) {
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') go(-1);
      else if (e.key === 'ArrowRight') go(1);
      else return;
      e.preventDefault();
      e.stopPropagation();
    };
    const box = h('div', { class: 'sd-lightbox', role: 'dialog', 'aria-label': x.info.name + ' screenshots', onclick: function (e) { if (e.target === box) close(); } },
      stage,
      items.length > 1 ? h('button', { type: 'button', class: 'sd-lb-nav prev', 'aria-label': 'Previous', onclick: function () { go(-1); } }, '‹') : null,
      items.length > 1 ? h('button', { type: 'button', class: 'sd-lb-nav next', 'aria-label': 'Next', onclick: function () { go(1); } }, '›') : null,
      h('div', { class: 'sd-lb-bar' }, count, h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Close', html: icon('close'), onclick: close })));
    view.append(box);
    window.addEventListener('keydown', keys, true);
    show();
  }

  function about() {
    const sec = h('section', { class: 'sd-sec' }, h('h3', {}, 'About this game'));
    const html = page && page.details_html ? page.details_html.trim() : '';
    sec.append(html ? h('div', { class: 'sd-prose', html: html }) : h('p', { class: 'sd-lead' }, x.info.blurb));
    const how = START_INFO[isBuiltinKind(x.kind) ? x.kind : f.based];
    if (how) sec.append(h('div', { class: 'sd-how' }, h('b', {}, 'How to play'), h('ol', {}, how.steps.map(function (step) { return h('li', {}, step); }))));
    return sec;
  }

  function facts() {
    const settings = (function () { try { return templateFields(x.kind).filter(function (fl) { return fl[0] !== 'h'; }).length; } catch (e) { return 0; } })();
    const updated = page && page.updated ? new Date(page.updated + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : null;
    const rows = [
      ['Type', { builtin: 'Kiosk Studio game', preset: 'Preset of a built-in game', custom: 'Custom game', imported: 'Imported from a .zip' }[x.group]],
      f.based ? ['Based on', GAME_INFO[f.based].name] : null,
      f.version ? ['Version', f.version] : null,
      f.author ? ['Made by', f.author] : null,
      updated ? ['Updated', updated] : null,
      settings ? ['Settings', settings + ' to adjust'] : null,
      ['In this design', f.used ? 'On ' + plural(f.used, 'screen') : 'Not used yet']
    ].filter(Boolean);
    return h('div', { class: 'sd-facts' },
      h('h4', {}, 'Details'),
      h('dl', {}, rows.map(function (r) { return [h('dt', {}, r[0]), h('dd', {}, r[1])]; }).flat()),
      addButton('wide'));
  }

  function reviews() {
    const sec = h('section', { class: 'sd-sec', id: 'sd-reviews' }, h('h3', {}, 'Ratings & reviews'));
    if (!x.row) {
      sec.append(h('p', { class: 'sd-muted' }, x.group === 'imported'
        ? 'Imported templates live on this computer only, so they have no reviews.'
        : 'Reviews show when Kiosk Studio is opened from your account.'));
      return sec;
    }
    if (loadError) {
      sec.append(h('p', { class: 'sd-muted warn-text' }, loadError), h('button', { class: 'btn', onclick: load }, 'Try again'));
      return sec;
    }
    if (!page) {
      sec.append(h('div', { class: 'rv-skeleton' }, h('i'), h('i'), h('i')));
      return sec;
    }
    const total = page.reviews_count;
    sec.append(h('div', { class: 'rv-summary' },
      h('div', { class: 'rv-score' }, h('b', {}, total ? (+page.rating).toFixed(1) : '–'), starsEl(page.rating, true), h('span', {}, total ? plural(total, 'review') : 'No reviews yet')),
      h('div', { class: 'rv-bars' }, [5, 4, 3, 2, 1].map(function (n, i) {
        const c = +(page.breakdown[n] || 0);
        return h('div', { class: 'rv-bar' }, h('span', {}, n + ' ★'), h('i', {}, h('em', { style: '--w:' + (total ? c / total * 100 : 0) + '%;animation-delay:' + i * 60 + 'ms' })), h('small', {}, String(c)));
      }))));
    sec.append(reviewForm());
    const list = h('div', { class: 'rv-list' });
    page.reviews.forEach(function (rv, i) {
      list.append(h('article', { class: 'rv-item' + (rv.mine ? ' mine' : ''), style: 'animation-delay:' + Math.min(i, 12) * 45 + 'ms' },
        h('span', { class: 'rv-av', style: 'background:' + avatarColor(rv.name), 'aria-hidden': 'true' }, rv.initials),
        h('div', { class: 'rv-body' },
          h('div', { class: 'rv-head' }, h('b', {}, rv.name), rv.mine ? h('span', { class: 'rv-you' }, 'You') : null, starsEl(rv.rating),
            h('time', { datetime: rv.date, title: rv.date }, rv.ago + (rv.edited ? ' · edited' : ''))),
          rv.comment ? h('p', {}, rv.comment) : null)));
    });
    if (!page.reviews.length) list.append(h('p', { class: 'sd-muted' }, 'No reviews yet. Be the first to rate this game.'));
    sec.append(list);
    return sec;
  }

  function reviewForm() {
    const mine = page.my_review;
    const words = ['Tap a star', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
    let rating = mine ? mine.rating : 0;
    const word = h('span', { class: 'rv-word' });
    const pick = h('div', { class: 'rv-pick', role: 'radiogroup', 'aria-label': 'Your rating' });
    const paint = function (n) {
      Array.prototype.forEach.call(pick.children, function (b, i) { b.classList.toggle('on', i < n); b.setAttribute('aria-checked', String(i + 1 === rating)); });
      word.textContent = words[n];
    };
    for (let n = 1; n <= 5; n++) {
      pick.append(h('button', { type: 'button', role: 'radio', 'aria-label': plural(n, 'star'),
        onclick: function () { rating = n; paint(n); pick.children[n - 1].animate && pick.children[n - 1].animate([{ transform: 'scale(1)' }, { transform: 'scale(1.35)' }, { transform: 'scale(1)' }], { duration: 260 }); },
        onmouseenter: function () { paint(n); }, onmouseleave: function () { paint(rating); } }, '★'));
    }
    paint(rating);
    const text = h('textarea', { class: 'inp rv-text', rows: '3', maxlength: '1000', placeholder: 'How did it go on your kiosk? What did players like? (optional)' });
    text.value = mine && mine.comment ? mine.comment : '';
    const msg = h('span', { class: 'rv-msg', role: 'status' });
    const post = h('button', { class: 'btn primary', type: 'button' }, mine ? 'Update review' : 'Post review');
    const del = mine ? h('button', { class: 'btn', type: 'button' }, 'Remove') : null;
    const send = async function (method, body, button) {
      const label = button.textContent;
      post.disabled = true;
      if (del) del.disabled = true;
      button.textContent = method === 'PUT' ? 'Posting…' : 'Removing…';
      msg.textContent = '';
      try {
        page = await storeRequest(x.kind, method, body);
        syncCatalogRow(page);
        render();
        toast(page.message);
      } catch (e) {
        msg.textContent = e.message;
        post.disabled = false;
        if (del) del.disabled = false;
        button.textContent = label;
      }
    };
    post.addEventListener('click', function () {
      if (!rating) { msg.textContent = 'Choose a star rating first.'; return; }
      send('PUT', { rating: rating, comment: text.value }, post);
    });
    if (del) del.addEventListener('click', function () { send('DELETE', null, del); });
    return h('div', { class: 'rv-form' },
      h('div', { class: 'rv-form-head' }, h('b', {}, mine ? 'Your review' : 'Rate this game'), pick, word),
      text,
      h('div', { class: 'rv-form-actions' }, post, del, msg));
  }

  function load() {
    loadError = '';
    render();
    storeRequest(x.kind, 'GET').then(function (data) {
      if (!view.isConnected) return;
      page = data;
      syncCatalogRow(page);
      render();
    }).catch(function (e) {
      if (!view.isConnected) return;
      loadError = e.message;
      render();
    });
  }
  if (x.row) load();
  else render();
}

/* Add the game as a screen. A library game's .zip is downloaded first, unless
   this design already has that version of it. */
async function addFromMarket(x, button) {
  const r = x.row;
  if (x.group === 'builtin' || !r) { closeModal(); addScreen(x.kind); return; }
  const own = (project.gameTemplates || []).find(function (t) { return t.manifest.id === x.kind; });
  const have = own || catalogPackages[x.kind];
  if (have && have.manifest.version === r.version) { closeModal(); addScreen(x.kind); return; }

  const label = button.textContent;
  button.disabled = true;
  button.textContent = 'Downloading…';
  let pkg;
  try { pkg = await fetchCatalogPackage(r); } catch (e) {
    button.disabled = false;
    button.textContent = label;
    toast('Could not add "' + x.info.name + '": ' + e.message);
    return;
  }
  catalogPackages[x.kind] = pkg;
  closeModal();
  if (own) {
    // Screens already using it play the new version too.
    commit(function () {
      const list = project.gameTemplates;
      list[list.indexOf(own)] = JSON.parse(JSON.stringify(pkg));
    });
    toast('"' + x.info.name + '" updated to v' + pkg.manifest.version);
  }
  addScreen(x.kind);
}

async function fetchCatalogPackage(r) {
  let res;
  try { res = await fetch(r.download, { credentials: 'same-origin' }); } catch (e) { throw new Error('check your connection and try again.'); }
  if (res.status === 404) throw new Error('it is no longer offered.');
  if (!res.ok) throw new Error('the server answered ' + res.status + '. Try again.');
  const pkg = await readTemplateZip(await res.blob());
  if (pkg.manifest.id !== r.id) throw new Error('its package is a different template ("' + pkg.manifest.id + '").');
  return pkg;
}

function exportFromMarket(x) {
  if (x.row && x.row.download) {
    // The library's own .zip.
    const a = h('a', { href: x.row.download, download: '' });
    document.body.append(a);
    a.click();
    a.remove();
    toast('Downloading "' + x.info.name + '" (.zip)');
    return;
  }
  exportTemplate(x.kind);
}

loadTemplateCatalog();

/* ---------- Sample code template: Balloon Pop ---------- */
/* Written as a real function so it is syntax-checked; its source becomes game.js in the zip. */
function balloonPopStart(ctx) {
  var sdk = ctx.sdk, cfg = ctx.config, W = ctx.width, H = ctx.height;
  var k = Math.min(W, H) / 1080;
  var colors = sdk.words(cfg.colors, ['#ff595e', '#ffca3a', '#8ac926', '#1982c4', '#6a4c93']);
  var size = sdk.num(cfg.balloonSize, 170) * k;
  var points = sdk.num(cfg.points, 10);
  var goldPoints = sdk.num(cfg.goldenPoints, 50);
  var goldChance = sdk.num(cfg.goldenChance, 8) / 100;
  var bombChance = sdk.num(cfg.bombChance, 12) / 100;
  var penalty = sdk.num(cfg.penalty, 20);
  var cloudSrc = ctx.assets['assets/cloud.svg'];
  var clouds = [[0.18, 0.12, 1], [0.72, 0.3, 0.8], [0.32, 0.55, 0.6], [0.85, 0.74, 0.9]];
  var balloons = [];
  var acc = 0;
  var api = null;

  function drawBalloon(c, b) {
    var x = b.x + Math.sin(b.sway) * 16 * k, y = b.y, r = b.r;
    c.strokeStyle = 'rgba(255,255,255,.85)';
    c.lineWidth = 3 * k;
    c.beginPath();
    c.moveTo(x, y + r);
    c.quadraticCurveTo(x - 22 * k, y + r * 1.6, x + 6 * k, y + r * 2.3);
    c.stroke();
    if (b.bomb) { sdk.drawSprite(c, sdk.sprite('💣', r * 2), x, y, Math.sin(b.sway) * 0.2); return; }
    var g = c.createRadialGradient(x - r * 0.35, y - r * 0.4, r * 0.1, x, y, r * 1.1);
    g.addColorStop(0, 'rgba(255,255,255,.9)');
    g.addColorStop(0.25, b.color);
    g.addColorStop(1, b.color);
    c.fillStyle = g;
    c.beginPath();
    c.ellipse(x, y, r * 0.86, r, 0, 0, Math.PI * 2);
    c.fill();
    c.fillStyle = b.color;
    c.beginPath();
    c.moveTo(x, y + r * 0.95);
    c.lineTo(x - r * 0.14, y + r * 1.14);
    c.lineTo(x + r * 0.14, y + r * 1.14);
    c.closePath();
    c.fill();
    if (b.gold) sdk.drawSprite(c, sdk.sprite('✨', r), x + r * 0.5, y - r * 0.7, 0);
  }

  var game = sdk.canvasGame(ctx, {
    down: function (p) {
      for (var i = balloons.length - 1; i >= 0; i--) {
        var b = balloons[i];
        var bx = b.x + Math.sin(b.sway) * 16 * k;
        if (Math.hypot(p.x - bx, p.y - b.y) < b.r * 1.15) {
          balloons.splice(i, 1);
          if (b.bomb) {
            api.add(-penalty, bx, b.y);
            api.shake(40);
            api.flash('#ff2d2d');
            api.burst(bx, b.y, '#333333', 24, 900);
          } else {
            api.add(b.gold ? goldPoints : points, bx, b.y - b.r);
            api.burst(bx, b.y, b.color, 20);
            if (b.gold) api.say('GOLDEN!', bx, b.y - b.r * 1.8, '#ffd700', 80);
          }
          return;
        }
      }
    },
    update: function (dt, progress) {
      var rate = sdk.lerp(sdk.num(cfg.spawnStart, 1.4), sdk.num(cfg.spawnEnd, 3.2), progress);
      var speed = sdk.lerp(sdk.num(cfg.speedStart, 260), sdk.num(cfg.speedEnd, 520), progress) * (H / 1920);
      acc += dt * rate;
      while (acc >= 1) {
        acc -= 1;
        var roll = Math.random();
        var bomb = roll < bombChance;
        var gold = !bomb && roll < bombChance + goldChance;
        balloons.push({
          x: size + Math.random() * (W - size * 2), y: H + size, r: size / 2, vy: speed * (0.8 + Math.random() * 0.4),
          sway: Math.random() * 6.28, color: gold ? '#ffd23f' : sdk.pick(colors), bomb: bomb, gold: gold
        });
      }
      balloons.forEach(function (b) { b.y -= b.vy * dt; b.sway += dt * 2; });
      balloons = balloons.filter(function (b) { return b.y > -size * 2; });
    },
    draw: function (c) {
      var g = c.createLinearGradient(0, 0, 0, H);
      g.addColorStop(0, cfg.bgTop || '#5ec8f8');
      g.addColorStop(1, cfg.bgBottom || '#d8f3ff');
      c.fillStyle = g;
      c.fillRect(0, 0, W, H);
      var img = sdk.image(cloudSrc);
      if (img) {
        clouds.forEach(function (cl) {
          var w = 420 * k * cl[2];
          c.drawImage(img, cl[0] * W - w / 2, cl[1] * H, w, w * 0.55);
        });
      }
      balloons.forEach(function (b) { drawBalloon(c, b); });
    }
  });
  api = game.api;
  return { stop: game.stop };
}

const SAMPLE_CLOUD_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 120"><g fill="#ffffff" fill-opacity=".92">' +
  '<circle cx="62" cy="70" r="40"/><circle cx="112" cy="50" r="50"/><circle cx="162" cy="72" r="38"/><rect x="40" y="70" width="142" height="42" rx="21"/></g></svg>';

function samplePreviewSvg() {
  const balloons = [[260, 520, '#ff595e'], [760, 380, '#1982c4'], [520, 860, '#ffca3a'], [220, 1180, '#8ac926'], [820, 1080, '#6a4c93'], [600, 1480, '#ff595e']];
  let s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1920"><defs><linearGradient id="s" x1="0" y1="0" x2="0" y2="1">' +
    '<stop offset="0" stop-color="#5ec8f8"/><stop offset="1" stop-color="#d8f3ff"/></linearGradient></defs><rect width="1080" height="1920" fill="url(#s)"/>';
  balloons.forEach(function (b) {
    const x = b[0], y = b[1], r = 90;
    s += '<path d="M' + x + ' ' + (y + r) + ' q-22 60 6 120" stroke="#ffffff" stroke-width="4" fill="none"/>' +
      '<ellipse cx="' + x + '" cy="' + y + '" rx="' + r * 0.86 + '" ry="' + r + '" fill="' + b[2] + '"/>' +
      '<ellipse cx="' + (x - r * 0.3) + '" cy="' + (y - r * 0.4) + '" rx="' + r * 0.18 + '" ry="' + r * 0.26 + '" fill="#ffffff" fill-opacity=".55"/>';
  });
  return s + '</svg>';
}

const SAMPLE_README = [
  '# Balloon Pop · a sample Kiosk Studio game template',
  '',
  'Players tap balloons before they float away. Golden balloons are worth more; bombs cost points.',
  'Use this package as a starting point for your own games: edit, zip, import.',
  '',
  '## Package layout',
  '',
  '    balloon-pop.zip',
  '    ├── manifest.json   name, id, version, icon, settings, defaults',
  '    ├── game.js         the game code',
  '    ├── assets/         images and sounds the game uses (optional)',
  '    └── README.md       this file (optional)',
  '',
  'Zip the files themselves (or one folder that contains them). Import in Kiosk Studio:',
  'Screens → Game templates → Import .zip. The game then appears in the list like the built-in games.',
  '',
  '## manifest.json',
  '',
  '- `format`: always `"kiosk-game-template"`; `formatVersion`: `1`',
  '- `id`: unique, lowercase letters, numbers, `-` or `_` (not quiz / catch / slice / wheel)',
  '- `name`, `version`, `author`, `description`',
  '- `icon`: an emoji, or a path to an image in the zip (e.g. `"assets/icon.png"`)',
  '- `preview`: optional image path shown in the editor for this game screen',
  '- `defaults`: `{ "bg", "bg2", "threshold", "config": { … } }` (threshold = score needed to win)',
  '- `settings`: the form shown in Properties. Each item: `{ "key", "label", "type", "default" }`',
  '  with type `number`, `text`, `emoji`, `color`, `checkbox` or `select` (+ `"options": [...]`),',
  '  or `{ "type": "heading", "label": "Look" }` for a section title.',
  '- `extends`: optional. `"catch"` (or quiz / slice / wheel) makes a preset of a built-in game; then game.js is not needed.',
  '',
  '## game.js',
  '',
  '    KioskGame.define({',
  '      start: function (ctx) {',
  '        // ctx.width, ctx.height  design size in pixels (e.g. 1080 × 1920)',
  '        // ctx.layer             the element to draw into (fills the screen)',
  '        // ctx.config            values from the settings form',
  '        // ctx.assets            { "assets/cloud.svg": "data:…" } files from the zip',
  '        // ctx.sdk               helpers (below)',
  '        // ctx.end(score, { win, vars })  finish the game; Kiosk Studio moves to the win or lose screen',
  '        return { stop: function () { /* clean up timers and listeners */ } };',
  '      }',
  '    });',
  '',
  '## Game SDK (ctx.sdk)',
  '',
  '- `canvasGame(ctx, hooks)`: a full-screen canvas with the 3-2-1 countdown, SCORE and TIME pills,',
  '  particles and the "time\'s up" card. hooks: `update(dt, progress)`, `draw(c, dt)`, `down(p)`, `move(p, isDown)`, `up(p)`.',
  '  Returns `{ api, stop }`; `api.add(points, x, y)`, `api.say(text, x, y, color, size)`, `api.burst(x, y, color, n)`,',
  '  `api.shake(amount)`, `api.flash(color)`, `api.end()` (finish early), `api.st.score`.',
  '- `sprite(emoji, size)` → canvas image of an emoji · `drawSprite(c, img, x, y, rotation, scaleX, scaleY)`',
  '- `image(src)` → a loaded image (or null while loading) · `sound(src, volume)` plays a sound',
  '- `words(text, fallback)`, `list(emojiText, fallback)`, `num(value, fallback)`, `pick(array)`, `lerp(a, b, t)`',
  '- `label(c, text, x, y, size, color)`, `roundRect(c, x, y, w, h, r)`, `confetti(element, W, H, colors)`',
  '',
  'Code in game.js runs with the same access as the kiosk app, so only install templates you trust.',
  ''
].join('\n');

function sampleTemplatePackage() {
  return {
    manifest: {
      format: TEMPLATE_FORMAT, formatVersion: 1, id: 'balloon-pop', name: 'Balloon Pop', version: '1.0.0', author: 'Kiosk Studio sample',
      description: 'Tap the balloons before they float away', icon: '🎈', color: '#1982c4', main: 'game.js', preview: 'assets/preview.svg',
      defaults: { bg: '#5ec8f8', bg2: '#d8f3ff', threshold: 150 },
      settings: [
        { type: 'heading', label: 'Rules' },
        { key: 'duration', label: 'Duration (s)', type: 'number', default: 30 },
        { key: 'points', label: 'Points / balloon', type: 'number', default: 10 },
        { key: 'goldenPoints', label: 'Golden balloon', type: 'number', default: 50 },
        { key: 'goldenChance', label: 'Golden %', type: 'number', default: 8 },
        { key: 'bombChance', label: 'Bombs %', type: 'number', default: 12 },
        { key: 'penalty', label: 'Bomb penalty', type: 'number', default: 20 },
        { key: 'spawnStart', label: 'Balloons/s start', type: 'number', default: 1.4 },
        { key: 'spawnEnd', label: 'Balloons/s end', type: 'number', default: 3.2 },
        { key: 'speedStart', label: 'Rise speed start', type: 'number', default: 260 },
        { key: 'speedEnd', label: 'Rise speed end', type: 'number', default: 520 },
        { type: 'heading', label: 'Look' },
        { key: 'colors', label: 'Balloon colours', type: 'text', default: '#ff595e #ffca3a #8ac926 #1982c4 #6a4c93' },
        { key: 'balloonSize', label: 'Balloon size', type: 'number', default: 170 },
        { key: 'bgTop', label: 'Sky top', type: 'color', default: '#5ec8f8' },
        { key: 'bgBottom', label: 'Sky bottom', type: 'color', default: '#d8f3ff' },
        { key: 'hint', label: 'Start hint', type: 'text', default: 'Tap the balloons!' }
      ]
    },
    code: '/* Balloon Pop: a sample Kiosk Studio game template.\n   Edit this file, zip the package again and re-import it to update the game. */\n' +
      'KioskGame.define({\n  start: ' + balloonPopStart.toString() + '\n});\n',
    assets: {
      'assets/cloud.svg': 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(SAMPLE_CLOUD_SVG),
      'assets/preview.svg': 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(samplePreviewSvg())
    },
    readme: SAMPLE_README
  };
}
function downloadSampleTemplate() {
  download('balloon-pop-1.0.0.zip', packageToZip(sampleTemplatePackage()));
  toast('Sample template downloaded · import it with Screens → Import .zip');
}
