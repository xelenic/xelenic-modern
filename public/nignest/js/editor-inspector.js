/* Inspector (right panel): properties for the selected object, screen, game or project. */

function afterEdit(final, opts) {
  save();
  if (view === 'editor') {
    const o = selObj();
    if (o && opts.obj) { refreshObjEl(o); drawSelection(); } else renderStage();
    scheduleThumbs();
  } else scheduleFlow();
  if (opts.top) renderTopbar();
  if (opts.left) renderLeft();
  if (opts.rerender && final) renderInspector();
  if (typeof tlAfterInspectorEdit === 'function') tlAfterInspectorEdit();
}

function bindInput(inp, get, set, opts) {
  opts = opts || {};
  const read = function () {
    if (inp.type === 'checkbox') return inp.checked;
    if (inp.type === 'number' || inp.type === 'range') return inp.value === '' ? '' : +inp.value;
    return inp.value;
  };
  const v = get();
  if (inp.type === 'checkbox') inp.checked = !!v;
  else inp.value = v == null ? '' : v;
  let snap = null;
  const begin = function () { if (snap == null) snap = snapshot(); };
  const finish = function () { if (snap != null && snap !== snapshot()) pushUndo(snap); snap = null; };
  inp.addEventListener('focus', begin);
  inp.addEventListener('input', function () {
    begin();
    set(read());
    const discrete = inp.type === 'checkbox' || inp.tagName === 'SELECT';
    if (discrete) finish();
    afterEdit(discrete, opts);
  });
  inp.addEventListener('change', function () {
    if (inp.type === 'checkbox' || inp.tagName === 'SELECT') return;
    begin();
    set(read());
    finish();
    afterEdit(true, opts);
  });
  return inp;
}

/* ---------- Field builders ---------- */
function sec(box, title, extra) {
  const s = h('div', { class: 'sec' }, h('div', { class: 'sec-h' }, h('span', {}, title), h('span', { class: 'grow' }), extra || null));
  box.append(s);
  return s;
}
function row(label) {
  const ctl = h('div', { class: 'ctl' });
  for (let i = 1; i < arguments.length; i++) {
    const c = arguments[i];
    if (Array.isArray(c)) c.forEach(function (x) { ctl.append(x); });
    else if (c) ctl.append(c);
  }
  return h('div', { class: 'row' }, h('label', {}, label), ctl);
}
function fText(get, set, opts, attrs) { return bindInput(h('input', Object.assign({ class: 'inp', type: 'text' }, attrs || {})), get, set, opts); }
function fNum(get, set, opts, attrs) { return bindInput(h('input', Object.assign({ class: 'inp', type: 'number' }, attrs || {})), get, set, opts); }
function fArea(get, set, opts) { return bindInput(h('textarea', { class: 'inp', rows: '3', 'data-focus': '' }), get, set, opts); }
function fSelect(options, get, set, opts) {
  const s = h('select', { class: 'sel-input' }, options.map(function (o) { return h('option', { value: o[0] }, o[1]); }));
  return bindInput(s, get, set, opts);
}
function fCheck(label, get, set, opts) {
  const c = h('input', { type: 'checkbox' });
  bindInput(c, get, set, opts);
  return h('label', { class: 'check' }, c, label);
}
function fRange(min, max, step, get, set, opts, fmt) {
  const r = h('input', { type: 'range', min: min, max: max, step: step });
  const out = h('span', { class: 'muted', style: 'width:40px;text-align:right;font-variant-numeric:tabular-nums' });
  const upd = function () { out.textContent = fmt ? fmt(r.value) : r.value; };
  bindInput(r, get, function (v) { set(v); upd(); }, opts);
  upd();
  return [r, out];
}
function toHex(v) {
  v = String(v || '').trim();
  if (/^#[0-9a-f]{6}$/i.test(v)) return v;
  if (/^#[0-9a-f]{3}$/i.test(v)) return '#' + v.slice(1).split('').map(function (c) { return c + c; }).join('');
  const m = /rgba?\((\d+)[,\s]+(\d+)[,\s]+(\d+)/.exec(v);
  if (m) return '#' + [m[1], m[2], m[3]].map(function (n) { return (+n).toString(16).padStart(2, '0'); }).join('');
  return '#000000';
}
function fColor(get, set, opts, placeholder) {
  const txt = h('input', { class: 'inp', type: 'text', placeholder: placeholder || '' });
  const col = h('input', { type: 'color' });
  bindInput(txt, get, function (v) { set(v); col.value = toHex(v); }, opts);
  bindInput(col, function () { return toHex(get()); }, function (v) { set(v); txt.value = v; }, opts);
  return h('div', { class: 'color' }, col, txt);
}
function btn(label, onclick, cls, ic) {
  return h('button', { class: 'btn small ' + (cls || ''), onclick: onclick, html: (ic ? icon(ic) : '') + esc(label) });
}
function segCtl(options, get, set) {
  return h('div', { class: 'seg-ctl' }, options.map(function (o) {
    return h('button', { class: get() === o[0] ? 'on' : '', title: o[2] || o[0], html: icon(o[1]), onclick: function () { commit(function () { set(o[0]); }); } });
  }));
}
function screenSelect(get, set, opts) {
  return fSelect([['', '— choose a screen —']].concat(project.screens.map(function (s) { return [s.id, s.name]; })), get, set, opts);
}
function assetSelect(get, set, opts) {
  const opts2 = [['', '— none —']].concat((project.assets || []).map(function (a) { return ['asset:' + a.id, a.name]; }));
  const v = get();
  if (v && !opts2.some(function (o) { return o[0] === v; })) opts2.push([v, 'Custom image']);
  return fSelect(opts2, get, set, opts);
}
function clearBtn(onclick) {
  return h('button', { class: 'btn small', title: 'Clear', html: icon('close'), onclick: onclick });
}

/* ---------- Main ---------- */
function renderInspector() {
  $$('#rightTabs button').forEach(function (b) { b.classList.toggle('on', b.dataset.tab === rightTab); });
  const box = $('#inspector');
  const scroll = box.scrollTop;
  box.innerHTML = '';
  if (rightTab === 'project') inspectProject(box);
  else {
    const sc = curScreen();
    const o = selObj();
    if (o) inspectObject(box, sc, o);
    else inspectScreen(box, sc);
  }
  box.scrollTop = scroll;
}

function syncTransformFields(o) {
  ['x', 'y', 'w', 'h'].forEach(function (k) {
    const f = $('#f_' + k);
    if (f && document.activeElement !== f) f.value = o[k];
  });
}

/* ---------- Object ---------- */
function inspectObject(box, sc, o) {
  const OB = { obj: true };
  const RR = { obj: true, rerender: true };
  const names = { text: 'Text', button: 'Button', image: 'Image', emoji: 'Sticker', shape: o.shape === 'circle' ? 'Circle' : 'Box', input: 'Input field' };
  const icons = { text: 'text', button: 'button', image: 'image', emoji: 'sticker', shape: o.shape === 'circle' ? 'circle' : 'rect', input: 'input' };
  box.append(h('div', { class: 'insp-head' }, h('div', { class: 'ic', html: icon(icons[o.type]) }),
    h('div', {}, h('b', {}, names[o.type]), h('small', {}, 'on ' + sc.name))));

  const lay = sec(box, 'Layout');
  const mini = function (lbl, key) {
    return h('div', { class: 'mini' }, h('span', {}, lbl), fNum(function () { return o[key]; }, function (v) { o[key] = Math.max(key === 'w' || key === 'h' ? 10 : -99999, Math.round(+v || 0)); }, OB, { id: 'f_' + key }));
  };
  lay.append(h('div', { class: 'grid2' }, mini('X', 'x'), mini('Y', 'y'), mini('W', 'w'), mini('H', 'h')));
  lay.append(row('Rotation', fRange(-180, 180, 1, function () { return o.rotation || 0; }, function (v) { o.rotation = +v; }, OB, function (v) { return v + '°'; })));
  lay.append(row('Opacity', fRange(0, 1, 0.05, function () { return o.opacity == null ? 1 : o.opacity; }, function (v) { o.opacity = +v; }, OB, function (v) { return Math.round(v * 100) + '%'; })));
  lay.append(h('div', { class: 'btn-row' },
    btn('Center H', function () { commit(function () { o.x = Math.round((project.width - o.w) / 2); }); }),
    btn('Center V', function () { commit(function () { o.y = Math.round((project.height - o.h) / 2); }); }),
    btn('Full width', function () { commit(function () { o.x = 0; o.w = project.width; }); })));

  if (o.type === 'text' || o.type === 'button') {
    const c = sec(box, 'Text');
    c.append(fArea(function () { return o.text; }, function (v) { o.text = v; }, OB));
    c.append(h('div', { class: 'note', style: 'margin:6px 0 10px' }, 'Show values with {{score}}, {{best}}, {{name}}'));
    c.append(row('Font', fSelect([['sans', 'Sans'], ['rounded', 'Rounded'], ['display', 'Display'], ['serif', 'Serif'], ['mono', 'Mono']], function () { return o.font || 'sans'; }, function (v) { o.font = v; }, OB)));
    c.append(row('Size', fNum(function () { return o.fontSize; }, function (v) { o.fontSize = Math.max(6, +v || 6); }, OB, { min: 6, max: 500 })));
    c.append(row('Color', fColor(function () { return o.color; }, function (v) { o.color = v; }, OB)));
    c.append(row('Style', fCheck('Bold', function () { return o.bold; }, function (v) { o.bold = v; }, OB), fCheck('Italic', function () { return o.italic; }, function (v) { o.italic = v; }, OB)));
    c.append(row('Align', segCtl([['left', 'alignL'], ['center', 'alignC'], ['right', 'alignR']], function () { return o.align || 'center'; }, function (v) { o.align = v; })));
    if (o.type === 'text') c.append(row('', fCheck('Text shadow', function () { return o.shadow; }, function (v) { o.shadow = v; }, OB)));
  }

  if (o.type === 'button' || o.type === 'shape') {
    const s = sec(box, o.type === 'button' ? 'Button style' : 'Shape');
    if (o.type === 'shape') s.append(row('Shape', segCtl([['rect', 'rect', 'Box'], ['circle', 'circle', 'Circle']], function () { return o.shape || 'rect'; }, function (v) { o.shape = v; })));
    s.append(row('Fill', fColor(function () { return o.bg; }, function (v) { o.bg = v; }, OB)));
    s.append(row('Gradient', fColor(function () { return o.bg2; }, function (v) { o.bg2 = v; }, OB, 'none'), o.bg2 ? clearBtn(function () { commit(function () { o.bg2 = ''; }); }) : null));
    if (o.type === 'button' || o.shape !== 'circle') s.append(row('Corners', fRange(0, 300, 1, function () { return o.radius || 0; }, function (v) { o.radius = +v; }, OB)));
    s.append(row('Border', fNum(function () { return o.borderWidth || 0; }, function (v) { o.borderWidth = Math.max(0, +v || 0); }, OB, { min: 0, style: 'width:64px;flex:none' }),
      fColor(function () { return o.borderColor || '#ffffff'; }, function (v) { o.borderColor = v; }, OB)));
    if (o.type === 'button') s.append(row('', fCheck('Drop shadow', function () { return o.shadow !== false; }, function (v) { o.shadow = v; }, OB)));
  }

  if (o.type === 'image') {
    const s = sec(box, 'Image');
    s.append(row('Picture', assetSelect(function () { return o.src || ''; }, function (v) { o.src = v; }, RR),
      h('button', { class: 'btn small', title: 'Upload image', html: icon('upload'), onclick: function () { $('#fileImage').click(); } })));
    s.append(row('Or URL', fText(function () { return o.src && o.src.indexOf('asset:') !== 0 && o.src.indexOf('data:') !== 0 ? o.src : ''; }, function (v) { o.src = v; }, OB, { placeholder: 'https://…' })));
    s.append(row('Fit', fSelect([['contain', 'Fit inside'], ['cover', 'Fill and crop'], ['100% 100%', 'Stretch']], function () { return o.fit || 'contain'; }, function (v) { o.fit = v; }, OB)));
    s.append(row('Corners', fRange(0, 300, 1, function () { return o.radius || 0; }, function (v) { o.radius = +v; }, OB)));
  }

  if (o.type === 'emoji') {
    const s = sec(box, 'Sticker');
    s.append(row('Emoji', fText(function () { return o.emoji; }, function (v) { o.emoji = v; }, OB, { 'data-focus': '' })));
    const eg = h('div', { class: 'emoji-grid', style: 'margin:4px 0 0' });
    EMOJI_LIBRARY.slice(0, 24).forEach(function (em) {
      eg.append(h('button', { onclick: function () { commit(function () { o.emoji = em; }); } }, em));
    });
    s.append(eg);
  }

  if (o.type === 'input') {
    const s = sec(box, 'Input field');
    s.append(row('Placeholder', fText(function () { return o.placeholder; }, function (v) { o.placeholder = v; }, OB, { 'data-focus': '' })));
    s.append(row('Saves as', fText(function () { return o.field; }, function (v) { o.field = v.replace(/[^\w]/g, ''); }, OB, { placeholder: 'name' })));
    s.append(h('div', { class: 'note', style: 'margin:-2px 0 10px 92px' }, 'Show it later with {{' + (o.field || 'name') + '}}'));
    s.append(row('Type', fSelect([['text', 'Text'], ['email', 'Email'], ['tel', 'Phone'], ['number', 'Number']], function () { return o.inputType || 'text'; }, function (v) { o.inputType = v; }, OB)));
    s.append(row('', fCheck('Required', function () { return o.required; }, function (v) { o.required = v; }, OB)));
    s.append(row('Size', fNum(function () { return o.fontSize; }, function (v) { o.fontSize = Math.max(6, +v || 6); }, OB)));
    s.append(row('Text', fColor(function () { return o.color || '#222222'; }, function (v) { o.color = v; }, OB)));
    s.append(row('Fill', fColor(function () { return o.bg || '#ffffff'; }, function (v) { o.bg = v; }, OB)));
    s.append(row('Border', fColor(function () { return o.borderColor || 'rgba(255,255,255,0.4)'; }, function (v) { o.borderColor = v; }, OB)));
    s.append(row('Corners', fRange(0, 100, 1, function () { return o.radius == null ? 24 : o.radius; }, function (v) { o.radius = +v; }, OB)));
  }

  const an = sec(box, 'Animation', btn('Timeline', function () { setDock('timeline'); }, '', 'timeline'));
  const textual = o.type === 'text' || o.type === 'button';
  const enterOpts = ENTER_OPTIONS.filter(function (e) { return textual || (e[0] !== 'letters' && e[0] !== 'typewriter'); });
  const replaySoon = function () { setTimeout(replayAnimations, 30); };
  an.append(h('div', { class: 'lp-title', style: 'margin:2px 0 8px' }, '1 · Start (appear)'));
  an.append(row('Effect', fSelect(enterOpts, function () { return o.enter || 'none'; }, function (v) {
    o.enter = v;
    if (v !== 'none' && o.enterDuration == null) o.enterDuration = v === 'typewriter' ? 1.5 : 0.7;
    if (v !== 'none' && dockTab !== 'timeline') replaySoon();
  }, RR)));
  if (o.enter && o.enter !== 'none') {
    an.append(row('Starts at (s)', fNum(function () { return o.enterDelay || 0; }, function (v) { o.enterDelay = Math.max(0, +v || 0); }, OB, { min: 0, step: '0.1' })));
    an.append(row(o.enter === 'letters' || o.enter === 'typewriter' ? 'Total time (s)' : 'Duration (s)',
      fNum(function () { return o.enterDuration == null ? 0.7 : o.enterDuration; }, function (v) { o.enterDuration = Math.max(0.1, +v || 0.1); }, OB, { min: 0.1, step: '0.1' })));
  }
  an.append(h('div', { class: 'lp-title', style: 'margin:12px 0 8px' }, '2 · Meanwhile (while on screen)'));
  an.append(row('Loop', fSelect(LOOP_OPTIONS, function () { return o.anim || 'none'; }, function (v) { o.anim = v; if (v !== 'none' && dockTab !== 'timeline') replaySoon(); }, OB)));
  an.append(h('div', { class: 'lp-title', style: 'margin:12px 0 8px' }, '3 · Exit (disappear)'));
  an.append(row('Effect', fSelect(EXIT_OPTIONS, function () { return o.exit || 'none'; }, function (v) {
    o.exit = v;
    if (v !== 'none') {
      if (o.exitAt == null) o.exitAt = defaultExitAt(o);
      if (o.exitDuration == null) o.exitDuration = 0.6;
    }
  }, RR)));
  if (o.exit && o.exit !== 'none') {
    an.append(row('Starts at (s)', fNum(function () { return o.exitAt; }, function (v) { o.exitAt = Math.max(0, +v || 0); }, OB, { min: 0, step: '0.1' })));
    an.append(row('Duration (s)', fNum(function () { return o.exitDuration == null ? 0.6 : o.exitDuration; }, function (v) { o.exitDuration = Math.max(0.1, +v || 0.1); }, OB, { min: 0.1, step: '0.1' })));
  }
  an.append(h('div', { class: 'note', style: 'margin-top:4px' }, 'Times count from when the screen opens. Open the Timeline to see every layer and drag the bars.'));

  if (o.type !== 'input') {
    const ac = sec(box, 'On tap');
    const act = o.action || { type: 'none', target: '' };
    const ensure = function () { if (!o.action) o.action = { type: 'none', target: '' }; return o.action; };
    ac.append(row('Action', fSelect([['none', 'Do nothing'], ['goto', 'Go to screen'], ['back', 'Go back'], ['restart', 'Restart kiosk'], ['submit', 'Submit form, then go to']],
      function () { return act.type || 'none'; }, function (v) { ensure().type = v; }, RR)));
    if (act.type === 'goto' || act.type === 'submit') {
      ac.append(row('Screen', screenSelect(function () { return act.target; }, function (v) { ensure().target = v; }, RR),
        act.target && screenById(act.target) ? h('button', { class: 'btn small', title: 'Open that screen', html: icon('edit'), onclick: function () { openScreen(act.target); } }) : null));
    }
    if (act.type === 'submit') ac.append(h('div', { class: 'note' }, 'Checks required fields, saves every input on this screen to Data, then moves on.'));
    if (act.type === 'restart') ac.append(h('div', { class: 'note' }, 'Clears the session and returns to the start screen.'));
  }

  const ar = sec(box, 'Arrange');
  ar.append(h('div', { class: 'btn-row' },
    btn('Front', function () { arrange('front'); }, '', 'front'),
    btn('Back', function () { arrange('back'); }, '', 'back'),
    btn('Forward', function () { arrange('up'); }, '', 'up'),
    btn('Backward', function () { arrange('down'); }, '', 'down')));
  ar.append(h('div', { class: 'btn-row', style: 'margin-top:6px' },
    btn('Duplicate', duplicateObject, '', 'copy'),
    btn('Delete', deleteObject, 'danger', 'trash')));
}

/* ---------- Screen ---------- */
const ENTER_OPTIONS = [['none', 'None'], ['fade', 'Fade in'], ['up', 'Slide up'], ['down', 'Slide down'], ['left', 'Slide in from right'],
  ['right', 'Slide in from left'], ['zoom', 'Zoom in'], ['pop', 'Pop'], ['bounce', 'Drop & bounce'], ['jelly', 'Jelly'], ['blur', 'Blur in'],
  ['flip', 'Flip in'], ['spin', 'Spin in'], ['letters', 'Letter by letter'], ['typewriter', 'Typewriter']];
const LOOP_OPTIONS = [['none', 'None'], ['pulse', 'Pulse'], ['float', 'Float'], ['shine', 'Shine sweep'], ['glow', 'Glow'], ['heartbeat', 'Heartbeat'],
  ['tada', 'Tada'], ['wiggle', 'Wiggle'], ['swing', 'Swing'], ['breathe', 'Breathe'], ['blink', 'Blink'], ['spin', 'Spin']];
const EFFECT_OPTIONS = [['none', 'None'], ['aurora', 'Aurora glow'], ['rays', 'Sun rays'], ['bubbles', 'Bubbles'], ['stars', 'Twinkling stars'],
  ['sparkles', 'Sparkles'], ['confetti', 'Confetti'], ['snow', 'Snow']];
const EXIT_OPTIONS = [['none', 'None'], ['fade', 'Fade out'], ['up', 'Slide up & out'], ['down', 'Slide down & out'], ['left', 'Slide out left'],
  ['right', 'Slide out right'], ['zoom', 'Zoom out'], ['grow', 'Grow & fade'], ['pop', 'Pop away'], ['fall', 'Fall away'], ['blur', 'Blur out'],
  ['flip', 'Flip out'], ['spin', 'Spin out']];
const TRANSITION_OPTIONS = [['rise', 'Rise'], ['fade', 'Fade'], ['slide', 'Slide'], ['zoom', 'Zoom'], ['none', 'None']];

const GAME_FIELDS = {
  quiz: [['h', 'Rules'], ['time', 'Seconds / question', 'num'], ['points', 'Points / correct', 'num'], ['shuffle', 'Shuffle questions', 'check'],
    ['h', 'Look'], ['questionSize', 'Question size', 'num'], ['accent', 'Accent', 'color'], ['textColor', 'Text', 'color'], ['answerBg', 'Answer fill', 'color']],
  catch: [['h', 'Rules'], ['duration', 'Duration (s)', 'num'], ['points', 'Points / catch', 'num'], ['penalty', 'Bad item penalty', 'num'], ['badChance', 'Bad items %', 'num'],
    ['spawnStart', 'Items/s at start', 'num'], ['spawnEnd', 'Items/s at end', 'num'], ['speedStart', 'Fall speed start', 'num'], ['speedEnd', 'Fall speed end', 'num'],
    ['h', 'Look'], ['catcher', 'Catcher', 'text'], ['good', 'Good items', 'text'], ['bad', 'Bad items', 'text'], ['catcherSize', 'Catcher size', 'num'], ['itemSize', 'Item size', 'num'],
    ['bgTop', 'Sky top', 'color'], ['bgBottom', 'Sky bottom', 'color'], ['ground', 'Ground', 'color']],
  slice: [['h', 'Rules'], ['duration', 'Duration (s)', 'num'], ['points', 'Points / slice', 'num'], ['penalty', 'Bomb penalty', 'num'], ['bombChance', 'Bombs %', 'num'],
    ['bombEnds', 'A bomb ends the game', 'check'], ['waveStart', 'Wave gap start (s)', 'num'], ['waveEnd', 'Wave gap end (s)', 'num'],
    ['h', 'Look'], ['fruits', 'Fruits', 'text'], ['bomb', 'Bomb', 'text'], ['itemSize', 'Item size', 'num'], ['trail', 'Swipe trail', 'color'],
    ['bgTop', 'Background top', 'color'], ['bgBottom', 'Background bottom', 'color']],
  wheel: [['h', 'Text'], ['title', 'Title', 'text'], ['subtitle', 'Subtitle', 'text'], ['buttonText', 'Button text', 'text'], ['winText', 'Win message', 'text'], ['loseText', 'No-prize message', 'text'],
    ['h', 'Spin'], ['spinTime', 'Spin time (s)', 'num'], ['turns', 'Full turns', 'num'], ['resultTime', 'Show result (s)', 'num'],
    ['h', 'Look'], ['textColor', 'Text', 'color'], ['buttonColor', 'Button', 'color'], ['buttonTextColor', 'Button text', 'color'],
    ['pointerColor', 'Pointer', 'color'], ['rimColor', 'Rim', 'color'], ['hubColor', 'Hub', 'color']]
};

function inspectScreen(box, sc) {
  const isStart = sc.id === project.startScreen;
  const isGame = sc.type === 'game';
  const gi = isGame ? gameInfo(sc.game.kind) : null;
  const sub = isGame ? gi.name + ' game' + (gi.custom ? ' · custom template' : '') : 'Page screen';
  const showsBg = !isGame || ['quiz', 'wheel', null].indexOf(baseKind(sc.game.kind)) >= 0;
  box.append(h('div', { class: 'insp-head' }, h('div', { class: 'ic' }, isGame ? gi.icon : '📄'),
    h('div', { style: 'flex:1' }, h('b', {}, sc.name), h('small', {}, sub)), isStart ? h('span', { class: 'badge' }, 'START') : null));

  const s = sec(box, 'Screen');
  s.append(row('Name', fText(function () { return sc.name; }, function (v) { sc.name = v; }, { left: true }, { 'data-focus': '' })));
  s.append(h('div', { class: 'btn-row' },
    isStart ? null : btn('Set as start', function () { commit(function () { project.startScreen = sc.id; }); }, '', 'flag'),
    view === 'flow' ? btn('Edit screen', function () { openScreen(sc.id); }, '', 'edit') : null,
    btn('Duplicate', function () { duplicateScreen(sc.id); }, '', 'copy'),
    btn('Delete', function () { deleteScreen(sc.id); }, 'danger', 'trash')));

  if (showsBg) {
    const b = sec(box, 'Background');
    b.append(row('Color', fColor(function () { return sc.bg; }, function (v) { sc.bg = v; })));
    b.append(row('Gradient', fColor(function () { return sc.bg2; }, function (v) { sc.bg2 = v; }, {}, 'none'), sc.bg2 ? clearBtn(function () { commit(function () { sc.bg2 = ''; }); }) : null));
    b.append(row('Image', assetSelect(function () { return sc.bgImage || ''; }, function (v) { sc.bgImage = v; }, { rerender: true })));
  }

  const fxs = sec(box, 'Effects', isGame ? null : btn('Play', replayAnimations, '', 'play'));
  if (showsBg) {
    fxs.append(row('Background', fSelect(EFFECT_OPTIONS, function () { return sc.effect || 'none'; }, function (v) { sc.effect = v; })));
  }
  fxs.append(row('Transition', fSelect(TRANSITION_OPTIONS, function () { return sc.transition || 'rise'; }, function (v) { sc.transition = v; })));
  fxs.append(h('div', { class: 'note' }, 'The transition plays when the kiosk arrives on this screen.'));

  if (isGame) inspectGame(box, sc);

  const t = sec(box, 'Auto advance');
  t.append(row('After (s)', fNum(function () { return sc.timer.seconds || 0; }, function (v) { sc.timer.seconds = Math.max(0, +v || 0); }, { rerender: true }, { min: 0 })));
  if (sc.timer.seconds > 0) t.append(row('Go to', screenSelect(function () { return sc.timer.target; }, function (v) { sc.timer.target = v; }, { rerender: true })));
  else t.append(h('div', { class: 'note' }, 'Off. Use it for attract loops and thank-you screens.'));

  if (!isGame) {
    const l = sec(box, 'Objects (' + sc.objects.length + ')');
    if (!sc.objects.length) l.append(h('div', { class: 'note' }, 'Drag objects from the left panel onto the screen.'));
    sc.objects.slice().reverse().forEach(function (o) {
      const label = o.type === 'emoji' ? o.emoji + ' Sticker' : o.type === 'input' ? 'Input · ' + (o.field || '') : o.type === 'image' ? 'Image' :
        o.type === 'shape' ? (o.shape === 'circle' ? 'Circle' : 'Box') : String(o.text || '').replace(/\s+/g, ' ').slice(0, 26);
      const linkTo = o.action && (o.action.type === 'goto' || o.action.type === 'submit') && screenById(o.action.target);
      l.append(h('button', { class: 'card', style: 'padding:6px 8px;margin-bottom:4px', onclick: function () {
        if (view !== 'editor') openScreen(sc.id);
        selectObject(o.id);
      } },
      h('span', { style: 'color:var(--muted)', html: icon(o.type === 'shape' ? (o.shape === 'circle' ? 'circle' : 'rect') : o.type === 'emoji' ? 'sticker' : o.type) }),
      h('span', { style: 'flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap' }, label || o.type),
      linkTo ? h('span', { class: 'link-chip' }, '→ ' + linkTo.name) : null));
    });
  }
}

function inspectGame(box, sc) {
  const g = sc.game;
  const c = g.config;
  const base = baseKind(g.kind);
  const info = gameInfo(g.kind);
  const tpl = isBuiltinKind(g.kind) ? null : findTemplate(g.kind);

  const ts = sec(box, 'Game template');
  if (tpl) {
    const m = tpl.manifest;
    ts.append(h('div', { class: 'tpl-meta' },
      h('b', {}, m.name), ' · v' + m.version + (m.author ? ' · ' + m.author : ''), h('br'),
      m.extends ? 'Preset of the built-in ' + GAME_INFO[m.extends].name + ' game' : 'Custom game from a .zip package'));
  } else if (info.missing) {
    ts.append(h('div', { class: 'warn' }, 'This screen uses the game template "' + g.kind + '", which is not installed. Import its .zip to edit and play it.'));
  } else {
    ts.append(h('div', { class: 'tpl-meta' }, h('b', {}, info.name), ' · built-in game'));
  }
  ts.append(h('div', { class: 'btn-row' },
    info.missing ? btn('Import .zip', function () { $('#fileTemplate').click(); }, 'primary', 'upload')
      : btn('Save as template', function () { saveScreenAsTemplate(sc); }, '', 'download'),
    info.missing ? null : btn('Export ' + (tpl ? 'template' : 'starter'), function () { exportTemplate(g.kind); }, '', 'download')));

  const fields = templateFields(g.kind);
  if (fields.length) {
    const s = sec(box, 'Game settings');
    fields.forEach(function (f) {
      if (f[0] === 'h') { s.append(h('div', { class: 'lp-title', style: 'margin:10px 0 8px' }, f[1])); return; }
      const key = f[0];
      const get = function () { return c[key]; };
      if (f[2] === 'num') s.append(row(f[1], fNum(get, function (v) { c[key] = v === '' ? '' : +v; })));
      else if (f[2] === 'check') s.append(row('', fCheck(f[1], get, function (v) { c[key] = v; })));
      else if (f[2] === 'color') s.append(row(f[1], fColor(get, function (v) { c[key] = v; })));
      else if (f[2] === 'select') {
        const opts = (f[3] || []).map(function (o) { return typeof o === 'object' ? [String(o.value), String(o.label || o.value)] : [String(o), String(o)]; });
        s.append(row(f[1], fSelect(opts, function () { return c[key] == null ? '' : String(c[key]); }, function (v) { c[key] = v; })));
      } else s.append(row(f[1], fText(get, function (v) { c[key] = v; })));
    });
    if (base === 'catch' || base === 'slice') s.append(h('div', { class: 'note' }, 'Items are emoji. Separate them with spaces.'));
  }

  const isWheel = base === 'wheel';
  if (isWheel) inspectSegments(box, c);

  const e = sec(box, isWheel ? 'After the spin' : 'When the game ends');
  if (!isWheel) e.append(row('Win at score', fNum(function () { return g.threshold; }, function (v) { g.threshold = +v || 0; }, {}, { min: 0 })));
  e.append(row(isWheel ? 'Prize → go to' : 'Win → go to', screenSelect(function () { return g.win; }, function (v) { g.win = v; }, { rerender: true })));
  e.append(row(isWheel ? 'No prize → go to' : 'Lose → go to', screenSelect(function () { return g.lose; }, function (v) { g.lose = v; }, { rerender: true })));
  e.append(h('div', { class: 'note' }, isWheel
    ? 'Slices marked "Counts as a prize" go to the prize screen. Show the result with {{prize}} and its points with {{score}}.'
    : 'The final score is available as {{score}} on the next screen.'));

  if (base === 'quiz') inspectQuestions(box, c);
}

function inspectSegments(box, c) {
  const segs = c.segments = c.segments || [];
  const s = sec(box, 'Wheel slices (' + segs.length + ')', segs.length < 16 ? btn('Add', function () {
    commit(function () { segs.push({ label: 'New prize', color: WHEEL_COLORS[segs.length % WHEEL_COLORS.length], textColor: '#ffffff', weight: 1, points: 10, win: true }); });
  }, '', 'plus') : null);
  const pctEls = [];
  const updatePct = function () {
    const total = segs.reduce(function (a, g) { return a + Math.max(0, +g.weight || 0); }, 0);
    pctEls.forEach(function (el, i) {
      const w = Math.max(0, +segs[i].weight || 0);
      el.textContent = total ? (Math.round(w / total * 1000) / 10) + '%' : '—';
    });
  };
  segs.forEach(function (g, i) {
    const card = h('div', { class: 'q-card' });
    const pct = h('span', { class: 'link-chip', title: 'Chance of landing here' });
    pctEls.push(pct);
    card.append(h('div', { class: 'q-top' },
      h('span', { style: 'width:14px;height:14px;border-radius:4px;flex:none;background:' + esc(g.color || '#888') }),
      h('span', {}, 'Slice ' + (i + 1)), pct, h('span', { class: 'grow' }),
      i > 0 ? h('button', { class: 'btn small', title: 'Move up', html: icon('up'), onclick: function () { commit(function () { segs.splice(i - 1, 0, segs.splice(i, 1)[0]); }); } }) : null,
      h('button', { class: 'btn small', title: 'Duplicate slice', html: icon('copy'), onclick: function () { commit(function () { segs.splice(i + 1, 0, JSON.parse(JSON.stringify(g))); }); } }),
      segs.length > 2 ? h('button', { class: 'btn small danger', title: 'Delete slice', html: icon('trash'), onclick: function () { commit(function () { segs.splice(i, 1); }); } }) : null));
    card.append(row('Label', fText(function () { return g.label; }, function (v) { g.label = v; })));
    card.append(row('Fill', fColor(function () { return g.color; }, function (v) { g.color = v; })));
    card.append(row('Text', fColor(function () { return g.textColor || '#ffffff'; }, function (v) { g.textColor = v; })));
    card.append(row('Chance', fNum(function () { return g.weight; }, function (v) { g.weight = v === '' ? 0 : Math.max(0, +v); updatePct(); }, {}, { min: 0, step: '0.5' })));
    card.append(row('Points', fNum(function () { return g.points; }, function (v) { g.points = +v || 0; })));
    card.append(row('', fCheck('Counts as a prize', function () { return g.win !== false; }, function (v) { g.win = v; })));
    s.append(card);
  });
  updatePct();
  s.append(h('div', { class: 'note' }, 'Chance is relative: a slice with 2 lands twice as often as a slice with 1. Use 0 for a slice that should never land.'));
}

function inspectQuestions(box, c) {
  const qs = c.questions = c.questions || [];
  const s = sec(box, 'Questions (' + qs.length + ')',
    btn('Add', function () { commit(function () { qs.push({ q: 'New question?', answers: ['', '', '', ''], correct: 0 }); }); }, '', 'plus'));
  qs.forEach(function (q, qi) {
    const card = h('div', { class: 'q-card' });
    card.append(h('div', { class: 'q-top' }, h('span', {}, 'Question ' + (qi + 1)), h('span', { class: 'grow' }),
      qi > 0 ? h('button', { class: 'btn small', title: 'Move up', html: icon('up'), onclick: function () { commit(function () { qs.splice(qi - 1, 0, qs.splice(qi, 1)[0]); }); } }) : null,
      h('button', { class: 'btn small danger', title: 'Delete question', html: icon('trash'), onclick: function () { commit(function () { qs.splice(qi, 1); }); } })));
    card.append(bindInput(h('textarea', { class: 'inp', rows: '2' }), function () { return q.q; }, function (v) { q.q = v; }, {}));
    for (let j = 0; j < 4; j++) {
      const radio = h('input', { type: 'radio', name: 'q' + qi, title: 'Mark as the correct answer' });
      radio.checked = (+q.correct || 0) === j;
      radio.addEventListener('change', function () { commit(function () { q.correct = j; }); });
      const ans = fText(function () { return (q.answers || [])[j] || ''; }, function (v) {
        q.answers = q.answers || [];
        while (q.answers.length < 4) q.answers.push('');
        q.answers[j] = v;
      }, {}, { placeholder: 'Answer ' + 'ABCD'.charAt(j) + (j > 1 ? ' (optional)' : '') });
      card.append(h('div', { class: 'q-ans' }, radio, ans));
    }
    s.append(card);
  });
  s.append(h('div', { class: 'note' }, 'The green dot marks the correct answer.'));
}

/* ---------- Project ---------- */
function inspectProject(box) {
  box.append(h('div', { class: 'insp-head' }, h('div', { class: 'ic' }, h('span', { class: 'logo', style: 'display:block;width:20px;height:20px' })),
    h('div', {}, h('b', {}, 'Project'), h('small', {}, 'Settings for the whole kiosk app'))));
  const p = sec(box, 'Project');
  p.append(row('Name', fText(function () { return project.name; }, function (v) { project.name = v; }, { top: true })));
  const orient = h('select', { class: 'sel-input' }, h('option', { value: 'portrait' }, 'Portrait 1080 × 1920'), h('option', { value: 'landscape' }, 'Landscape 1920 × 1080'));
  orient.value = project.width > project.height ? 'landscape' : 'portrait';
  orient.addEventListener('change', function () { setOrientation(orient.value); });
  p.append(row('Screen', orient));
  p.append(row('Start screen', screenSelect(function () { return project.startScreen; }, function (v) { if (v) project.startScreen = v; }, { rerender: true })));
  p.append(row('Idle reset (s)', fNum(function () { return project.idleTimeout || 0; }, function (v) { project.idleTimeout = Math.max(0, +v || 0); }, {}, { min: 0 })));
  p.append(h('div', { class: 'note' }, 'After this many seconds without a touch, the kiosk clears the session and returns to the start screen. 0 turns it off.'));

  const ed = sec(box, 'Editor theme');
  ed.append(h('div', { class: 'seg-ctl theme-seg', style: 'margin-bottom:8px' }, THEME_MODES.map(function (m) {
    return h('button', { class: themeMode === m ? 'on' : '', title: THEME_INFO[m].name + ' theme', html: icon(THEME_INFO[m].icon) + esc(THEME_INFO[m].name), onclick: function () { setThemeMode(m); } });
  })));
  ed.append(h('div', { class: 'note' }, 'Changes the editor only. Your kiosk screens keep their own colours. System follows your computer’s setting.'));

  const objs = project.screens.reduce(function (n, s) { return n + s.objects.length; }, 0);
  const kb = Math.round(JSON.stringify(project).length / 1024);
  const st = sec(box, 'Overview');
  st.append(h('div', { class: 'note', style: 'margin:0' },
    project.screens.length + ' screens · ' + objs + ' objects · ' + (project.assets || []).length + ' images · ' + kb + ' KB'));

  const pub = sec(box, 'Save and publish');
  pub.append(h('div', { class: 'btn-row' },
    btn('Save .json', saveJson, '', 'download'),
    btn('Export kiosk .html', exportKiosk, 'primary', 'kiosk'),
    btn('Data', openData, '', 'table')));
  pub.append(h('div', { class: 'note', style: 'margin-top:8px' }, 'The kiosk export is one offline HTML file. Open it in the kiosk browser in fullscreen or kiosk mode.'));

  const sh = sec(box, 'Shortcuts');
  [['P', 'Play from start'], ['Ctrl/⌘ Z', 'Undo'], ['Ctrl/⌘ Shift Z', 'Redo'], ['Ctrl/⌘ D', 'Duplicate object'], ['Delete', 'Delete object'],
    ['Arrows', 'Nudge (Shift = 10)'], ['Alt + drag', 'Move without snapping'], ['Ctrl/⌘ scroll', 'Zoom the editor'], ['Esc', 'Deselect or close']].forEach(function (r) {
    sh.append(h('div', { class: 'row', style: 'margin-bottom:4px' }, h('label', { style: 'width:110px' }, r[0]), h('div', { class: 'ctl muted' }, r[1])));
  });
}
