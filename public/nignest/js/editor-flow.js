/* Flow map: screens as nodes, navigation as wires. Drag a port onto a screen to link it. */

let flowPan = { x: 60, y: 80 };
let flowZoom = 1;
const NODE_W = 210;

function applyFlowTransform() {
  $('#flowWorld').style.transform = 'translate(' + flowPan.x + 'px,' + flowPan.y + 'px) scale(' + flowZoom + ')';
}

function portsOf(sc) {
  const out = [];
  if (sc.type === 'game') {
    const g = sc.game;
    const isWheel = baseKind(g.kind) === 'wheel';
    out.push({ key: 'win:' + sc.id, label: isWheel ? '🏆 Prize won' : '🏆 Win · score ≥ ' + (g.threshold || 0), target: g.win || '' });
    out.push({ key: 'lose:' + sc.id, label: isWheel ? '💔 No prize' : '💔 Lose', target: g.lose || '' });
  } else {
    sc.objects.forEach(function (o) {
      const a = o.action || { type: 'none' };
      if (o.type !== 'button' && (!a.type || a.type === 'none')) return;
      let label = String(o.text || o.emoji || o.type).replace(/\{\{.*?\}\}/g, '…').replace(/\s+/g, ' ').trim();
      if (label.length > 22) label = label.slice(0, 21) + '…';
      const linkable = a.type === 'goto' || a.type === 'submit' || a.type === 'none' || !a.type;
      out.push({
        key: 'obj:' + sc.id + ':' + o.id,
        label: (a.type === 'submit' ? '📨 ' : '▸ ') + label,
        target: linkable ? (a.target || '') : '',
        fixed: a.type === 'back' ? '← back' : a.type === 'restart' ? '⟲ start' : ''
      });
    });
  }
  if (sc.timer && sc.timer.seconds > 0) out.push({ key: 'timer:' + sc.id, label: '⏱ After ' + sc.timer.seconds + 's', target: sc.timer.target || '' });
  return out;
}

function renderFlow() {
  const nodes = $('#flowNodes');
  nodes.innerHTML = '';
  project.screens.forEach(function (sc, i) {
    if (sc.fx == null) { sc.fx = 60 + (i % 5) * 290; sc.fy = 60 + Math.floor(i / 5) * 420; }
    const isStart = sc.id === project.startScreen;
    const n = h('div', { class: 'node' + (sc.id === curId ? ' sel' : ''), style: 'left:' + sc.fx + 'px;top:' + sc.fy + 'px' });
    n.dataset.id = sc.id;
    const head = h('div', { class: 'node-head' },
      h('span', { class: 'ty' }, sc.type === 'game' ? gameInfo(sc.game.kind).icon : '📄'),
      h('span', { class: 'nm' }, sc.name),
      isStart ? h('span', { class: 'badge' }, 'START') : null,
      sc.type === 'game' ? h('span', { class: 'badge game' }, 'GAME') : null);
    n.append(head, h('i', { class: 'port-in' }));
    n.append(h('div', { class: 'node-thumb' }, makeThumb(sc, 190, 140)));
    const ports = h('div', { class: 'node-ports' });
    const list = portsOf(sc);
    if (!list.length) ports.append(h('div', { class: 'port-empty' }, 'No buttons yet'));
    list.forEach(function (p) {
      const t = p.target ? screenById(p.target) : null;
      const row = h('div', { class: 'port-row' },
        h('span', { class: 'pl' }, p.label),
        p.fixed ? h('span', { class: 'to' }, p.fixed) : t ? h('span', { class: 'to' }, t.name) : null);
      if (!p.fixed) {
        const dot = h('i', { class: 'port' + (t ? ' linked' : ''), title: 'Drag onto a screen to link' });
        dot.dataset.key = p.key;
        dot.dataset.target = t ? t.id : '';
        row.append(dot);
      }
      ports.append(row);
    });
    n.append(ports);
    head.addEventListener('pointerdown', function (e) { startNodeDrag(e, sc, n); });
    n.addEventListener('pointerdown', function (e) {
      if (e.target.closest('.port')) return;
      e.stopPropagation();
      if (curId !== sc.id || selId) selectScreenInFlow(sc.id);
    });
    n.addEventListener('dblclick', function () { openScreen(sc.id); });
    nodes.append(n);
  });
  applyFlowTransform();
  drawEdges();
}

function selectScreenInFlow(id) {
  curId = id;
  selId = null;
  $$('#flowNodes .node').forEach(function (n) { n.classList.toggle('sel', n.dataset.id === id); });
  drawEdges();
  renderInspector();
}

function nodeEl(id) { return $('#flowNodes .node[data-id="' + id + '"]'); }
function worldPoint(el) {
  const r = el.getBoundingClientRect();
  const wr = $('#flowWorld').getBoundingClientRect();
  return { x: (r.left + r.width / 2 - wr.left) / flowZoom, y: (r.top + r.height / 2 - wr.top) / flowZoom };
}
function curve(a, b) {
  const dx = Math.max(60, Math.abs(b.x - a.x) * 0.5);
  return 'M' + a.x + ' ' + a.y + ' C' + (a.x + dx) + ' ' + a.y + ' ' + (b.x - dx) + ' ' + b.y + ' ' + b.x + ' ' + b.y;
}

function drawEdges(temp) {
  const svg = $('#flowEdges');
  let html = '<defs>' +
    '<marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" style="fill:var(--edge)"/></marker>' +
    '<marker id="arrHot" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" style="fill:var(--accent)"/></marker>' +
    '</defs>';
  $$('#flowNodes .port').forEach(function (p) {
    const tid = p.dataset.target;
    if (!tid) return;
    const tn = nodeEl(tid);
    if (!tn) return;
    const src = p.closest('.node').dataset.id;
    const a = worldPoint(p);
    const b = worldPoint(tn.querySelector('.port-in'));
    b.x -= 8;
    const hot = src === curId || tid === curId;
    html += '<path class="edge' + (hot ? ' hot' : '') + '" d="' + curve(a, b) + '" marker-end="url(#' + (hot ? 'arrHot' : 'arr') + ')"/>';
  });
  if (temp) html += '<path class="temp" d="' + curve(temp.a, temp.b) + '"/>';
  svg.innerHTML = html;
}

/* ---------- Interactions ---------- */
function startNodeDrag(e, sc, n) {
  if (e.button !== 0) return;
  e.stopPropagation();
  e.preventDefault();
  if (curId !== sc.id || selId) selectScreenInFlow(sc.id);
  const sx = e.clientX, sy = e.clientY, ox = sc.fx, oy = sc.fy;
  const snap = snapshot();
  let moved = false;
  const move = function (ev) {
    const dx = (ev.clientX - sx) / flowZoom, dy = (ev.clientY - sy) / flowZoom;
    if (!moved && Math.hypot(ev.clientX - sx, ev.clientY - sy) < 3) return;
    moved = true;
    sc.fx = Math.round(ox + dx);
    sc.fy = Math.round(oy + dy);
    n.style.left = sc.fx + 'px';
    n.style.top = sc.fy + 'px';
    drawEdges();
  };
  const up = function () {
    window.removeEventListener('pointermove', move);
    window.removeEventListener('pointerup', up);
    if (moved) { pushUndo(snap); save(); }
  };
  window.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
}

function startPortDrag(e, port) {
  e.stopPropagation();
  e.preventDefault();
  const a = worldPoint(port);
  const wr = function () { return $('#flowWorld').getBoundingClientRect(); };
  let over = null;
  const move = function (ev) {
    const r = wr();
    const b = { x: (ev.clientX - r.left) / flowZoom, y: (ev.clientY - r.top) / flowZoom };
    const el = document.elementFromPoint(ev.clientX, ev.clientY);
    const n = el && el.closest('.node');
    if (over && over !== n) over.classList.remove('drop');
    over = n;
    if (n) n.classList.add('drop');
    drawEdges({ a: a, b: b });
  };
  const up = function () {
    window.removeEventListener('pointermove', move);
    window.removeEventListener('pointerup', up);
    if (over) over.classList.remove('drop');
    const target = over ? over.dataset.id : '';
    if (target || port.dataset.target) {
      setLink(port.dataset.key, target);
      toast(target ? 'Linked to "' + screenById(target).name + '"' : 'Link removed');
    } else drawEdges();
  };
  window.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
}

function setLink(key, target) {
  const parts = key.split(':');
  const sc = screenById(parts[1]);
  commit(function () {
    if (parts[0] === 'obj') {
      const o = sc.objects.find(function (x) { return x.id === parts[2]; });
      o.action = o.action || { type: 'none', target: '' };
      if (target) {
        if (o.action.type !== 'submit') o.action.type = 'goto';
        o.action.target = target;
      } else {
        o.action.target = '';
        if (o.action.type === 'goto') o.action.type = 'none';
      }
    } else if (parts[0] === 'win') sc.game.win = target;
    else if (parts[0] === 'lose') sc.game.lose = target;
    else if (parts[0] === 'timer') sc.timer.target = target;
  });
}

function nodeHeight(sc) {
  const n = portsOf(sc).length;
  return 36 + 150 + 10 + (n ? n * 25 : 28);
}

function autoLayout() {
  commit(function () {
    const depth = {};
    const queue = [project.startScreen];
    depth[project.startScreen] = 0;
    while (queue.length) {
      const id = queue.shift();
      const sc = screenById(id);
      if (!sc) continue;
      portsOf(sc).forEach(function (p) {
        if (p.target && depth[p.target] == null) { depth[p.target] = depth[id] + 1; queue.push(p.target); }
      });
    }
    let maxD = 0;
    Object.keys(depth).forEach(function (k) { maxD = Math.max(maxD, depth[k]); });
    project.screens.forEach(function (s) { if (depth[s.id] == null) depth[s.id] = maxD + 1; });
    const cols = {};
    project.screens.forEach(function (s) { (cols[depth[s.id]] = cols[depth[s.id]] || []).push(s); });
    Object.keys(cols).forEach(function (d) {
      let y = 40;
      cols[d].forEach(function (s) { s.fx = 40 + d * 290; s.fy = y; y += nodeHeight(s) + 40; });
    });
  });
  fitFlow();
}

function fitFlow() {
  const v = $('#flowView').getBoundingClientRect();
  if (!v.width || !project.screens.length) return;
  let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
  project.screens.forEach(function (s) {
    x0 = Math.min(x0, s.fx || 0);
    y0 = Math.min(y0, s.fy || 0);
    x1 = Math.max(x1, (s.fx || 0) + NODE_W);
    y1 = Math.max(y1, (s.fy || 0) + nodeHeight(s));
  });
  flowZoom = Math.max(0.3, Math.min(1.1, Math.min((v.width - 80) / (x1 - x0), (v.height - 140) / (y1 - y0))));
  flowPan.x = (v.width - (x1 - x0) * flowZoom) / 2 - x0 * flowZoom;
  flowPan.y = 70 + (v.height - 140 - (y1 - y0) * flowZoom) / 2 - y0 * flowZoom;
  applyFlowTransform();
  drawEdges();
}

function initFlow() {
  const fv = $('#flowView');
  fv.addEventListener('pointerdown', function (e) {
    if (e.button !== 0) return;
    const port = e.target.closest('.port');
    if (port) { startPortDrag(e, port); return; }
    if (e.target.closest('.node') || e.target.closest('.float-tools')) return;
    const sx = e.clientX, sy = e.clientY, ox = flowPan.x, oy = flowPan.y;
    fv.classList.add('panning');
    const move = function (ev) { flowPan.x = ox + ev.clientX - sx; flowPan.y = oy + ev.clientY - sy; applyFlowTransform(); };
    const up = function () {
      fv.classList.remove('panning');
      window.removeEventListener('pointermove', move);
      window.removeEventListener('pointerup', up);
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', up);
  });
  fv.addEventListener('wheel', function (e) {
    e.preventDefault();
    const r = fv.getBoundingClientRect();
    const mx = e.clientX - r.left, my = e.clientY - r.top;
    const nz = Math.max(0.25, Math.min(2, flowZoom * (e.deltaY < 0 ? 1.1 : 0.9)));
    flowPan.x = mx - (mx - flowPan.x) * nz / flowZoom;
    flowPan.y = my - (my - flowPan.y) * nz / flowZoom;
    flowZoom = nz;
    applyFlowTransform();
  }, { passive: false });
  $('#flowAdd').addEventListener('click', function () { addScreen('page'); });
  $('#flowLayout').addEventListener('click', autoLayout);
  $('#flowFit').addEventListener('click', fitFlow);
}
