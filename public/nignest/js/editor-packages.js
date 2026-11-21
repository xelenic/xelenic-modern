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
 *   - Preset: manifest "extends": "quiz" | "catch" | "slice" | "wheel" reuses a built-in game
 *     with its own defaults (colours, items, questions…). No code needed.
 *
 * Installed templates live in the browser library (localStorage) and are copied into the
 * project when a screen uses them, so saved projects and exported kiosk apps carry them.
 */

const TEMPLATE_FORMAT = 'kiosk-game-template';
const BUILTIN_KINDS = ['quiz', 'catch', 'slice', 'wheel'];
const TEMPLATE_LIB_KEY = 'kioskStudio.templates';
const TEMPLATE_MAX_BYTES = 12 * 1024 * 1024;
const TEMPLATE_MIME = {
  png: 'image/png', jpg: 'image/jpeg', jpeg: 'image/jpeg', gif: 'image/gif', webp: 'image/webp', svg: 'image/svg+xml',
  mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg', m4a: 'audio/mp4', json: 'application/json'
};
const SETTING_TYPES = { number: 'num', text: 'text', emoji: 'text', color: 'color', checkbox: 'check', boolean: 'check', select: 'select' };
const BUILTIN_TINT = { quiz: '#4b1d8f', catch: '#0f6b55', slice: '#7a2a1a', wheel: '#8a5a00' };

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
  return own || templateLibrary()[id] || null;
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
  if (!t) return { name: kind, icon: '⚠️', blurb: 'Template missing. Import its .zip to play it.', missing: true, custom: true, color: '#5a2a2a' };
  return pkgInfo(t);
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
  const t = templateLibrary()[kind];
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
    info.iconSrc ? h('img', { src: info.iconSrc, alt: '', style: 'width:72%;height:72%;object-fit:contain' }) : info.icon);
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
    m.extends ? null : h('div', { class: 'warn' }, '⚠️ This template contains program code (game.js). It runs when you press Play and on the kiosk, with the same access as this editor. Only install templates from people you trust.'),
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
  leftTab = 'screens';
  renderLeft();
  toast('"' + pkg.manifest.name + '" installed · Screens → Game templates' + (inLibrary ? '' : ' (kept in this project: browser storage is full)'));
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
      h('button', { class: 'btn', onclick: closeModal }, 'Cancel'),
      h('button', { class: 'btn primary', onclick: function () {
        closeModal();
        delete templateLibrary()[id];
        saveTemplateLibrary();
        commit(function () { if (!used) project.gameTemplates = (project.gameTemplates || []).filter(function (x) { return x.manifest.id !== id; }); });
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

/* ---------- Left panel: Game templates ---------- */
function renderTemplateSection(body) {
  body.append(h('div', { class: 'lp-title', style: 'margin-top:14px' }, 'Game templates'));
  BUILTIN_KINDS.forEach(function (kind) { body.append(templateCard(kind, gameInfo(kind), null)); });
  customTemplates().forEach(function (t) { body.append(templateCard(t.manifest.id, pkgInfo(t), t)); });
  body.append(h('div', { class: 'btn-row', style: 'margin:2px 0 8px' },
    btn('Import .zip', function () { $('#fileTemplate').click(); }, '', 'upload'),
    btn('Sample template', downloadSampleTemplate, '', 'download')));
  body.append(h('p', { class: 'note' }, 'Templates are .zip packages. Import one to add it here, or export any template to share it.'));
}
function templateCard(kind, info, t) {
  const acts = h('div', { class: 'card-acts' },
    h('button', { class: 'icon-btn', title: 'Export as .zip', html: icon('download'), onclick: function (e) { e.stopPropagation(); exportTemplate(kind); } }),
    t ? h('button', { class: 'icon-btn', title: 'Remove template', html: icon('trash'), onclick: function (e) { e.stopPropagation(); removeTemplate(kind); } }) : null);
  return h('div', { class: 'card tpl-card', title: 'Add a ' + info.name + ' screen', onclick: function () { addScreen(kind); } },
    templateIconBox(info, 44),
    h('div', { style: 'flex:1;min-width:0' },
      h('b', {}, info.name, t ? h('span', { class: 'badge custom' }, 'CUSTOM') : null),
      h('small', {}, (t ? 'v' + t.manifest.version + ' · ' : '') + info.blurb)),
    acts);
}

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
