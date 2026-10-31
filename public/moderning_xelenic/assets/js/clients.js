(() => {
  'use strict';

  // Clients carousel: two rows of logo tiles glide endlessly in opposite
  // directions. Each row is filled with clones of its tiles so the loop never
  // shows a gap. Scrolling speeds the rows up (and runs them backwards when
  // you scroll up), leaning them a little with your speed before they ease
  // back; hovering a row brings it gently to a stop.
  //
  // When the robot flies through (three-robot.js publishes its on-screen box
  // as window.XelenicRobot.box), the tiles make room for it elastically: the
  // row it crosses parts into a gap around it — tiles beside it pushed out,
  // the ones further along squashed and stretched like elastic — each on its
  // own bouncy spring, so the row wobbles open and springs shut again once
  // the robot has passed. A tile carried across the gap by the row's motion
  // squeezes small as it slips past the robot.

  const section = document.querySelector('.clients');
  if (!section) return;
  const marquee = section.querySelector('.clients-marquee');
  const rows = Array.from(section.querySelectorAll('.clients-row'));
  if (!rows.length || !marquee) return;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    section.classList.add('is-static');
    return;
  }

  const BASE_SPEED = 40; // px per second when nobody is scrolling
  // The lite (phone) page: the rows just glide steadily, untouched by scroll.
  const lite = document.documentElement.classList.contains('is-lite');
  // Springs (per second²/per second): underdamped, so tiles overshoot and wobble.
  const STIFFNESS = 170;
  const DAMPING = 13;
  const CLEARANCE = 22; // px kept between the robot and the nearest tile

  const states = rows.map((row) => ({
    row,
    originals: Array.from(row.children),
    dir: Number(row.dataset.direction) || -1,
    period: 0, // width of one full set of tiles (plus its gap)
    offset: 0,
    speed: 1, // eased multiplier (negative runs it backwards)
    hover: false,
    written: '',
    tiles: [], // { el, left, w, h, top }
    dyn: [], // per tile spring state, indexed like tiles
    page: { x: 0, y: 0, h: 0 }, // the row's layout position on the page
    opening: 0, // how open the row's gap is (0..1), for the sound cue
    settled: true, // every tile at rest — nothing to do while the robot's away
  }));

  const freshDyn = () => ({ ox: 0, vx: 0, oy: 0, vy: 0, sx: 1, vs: 0, side: 0, written: '' });

  function pageOffset(el) {
    let x = 0;
    let y = 0;
    for (let node = el; node; node = node.offsetParent) {
      x += node.offsetLeft;
      y += node.offsetTop;
    }
    return { x, y };
  }

  // Fill a row with enough copies that one set can scroll fully off-screen
  // while the next is already in place, then measure its tiles.
  function fill(s) {
    s.row.querySelectorAll('[data-clone]').forEach((node) => node.remove());
    s.row.style.transform = 'none'; // measure untransformed
    const gap = parseFloat(getComputedStyle(s.row).columnGap) || 0;
    s.period = s.row.scrollWidth + gap;
    let width = s.period;
    while (width < window.innerWidth + s.period * 2) {
      s.originals.forEach((tile) => {
        const copy = tile.cloneNode(true);
        copy.setAttribute('data-clone', '');
        copy.setAttribute('aria-hidden', 'true');
        copy.querySelectorAll('img').forEach((img) => { img.alt = ''; });
        // Copies still link to the story, but only the originals take focus.
        copy.querySelectorAll('a').forEach((link) => { link.tabIndex = -1; });
        s.row.appendChild(copy);
      });
      width += s.period;
    }
    s.tiles = Array.from(s.row.children).map((el) => ({
      el,
      left: el.offsetLeft,
      top: el.offsetTop,
      w: el.offsetWidth,
      h: el.offsetHeight,
    }));
    s.dyn = s.tiles.map(freshDyn);
    s.settled = true;
    s.tiles.forEach((t) => { t.el.style.transform = ''; });
    if (!s.offset) s.offset = s.dir > 0 ? -s.period : 0;
    s.written = '';
    measure(s);
  }
  function measure(s) {
    const o = pageOffset(s.row);
    s.page = { x: o.x, y: o.y, h: s.row.offsetHeight };
  }

  // When the loop wraps by one set, every tile on screen is now a different
  // (identical-looking) element: hand each one's spring state to it, so a
  // wobble in progress carries on seamlessly.
  function shiftDyn(s, by) {
    const n = s.originals.length;
    if (by > 0) s.dyn = s.dyn.slice(n).concat(s.tiles.slice(0, n).map(freshDyn));
    else s.dyn = s.tiles.slice(0, n).map(freshDyn).concat(s.dyn.slice(0, s.dyn.length - n));
    s.dyn.forEach((d) => { d.written = ''; });
  }

  states.forEach((s) => {
    fill(s);
    s.row.addEventListener('pointerenter', () => { s.hover = true; });
    s.row.addEventListener('pointerleave', () => { s.hover = false; });
  });
  let lastWidth = window.innerWidth;
  window.addEventListener('resize', () => {
    if (window.innerWidth === lastWidth) {
      states.forEach(measure);
      return;
    }
    lastWidth = window.innerWidth;
    states.forEach(fill);
  });
  window.addEventListener('load', () => states.forEach(measure));
  if ('ResizeObserver' in window) {
    // Anything above the section changing height moves the rows down the page.
    new ResizeObserver(() => states.forEach(measure)).observe(document.body);
  }

  let visible = true;
  if ('IntersectionObserver' in window) {
    new IntersectionObserver((entries) => {
      visible = entries.some((entry) => entry.isIntersecting);
    }, { rootMargin: '100px 0px' }).observe(section);
  }

  const clamp01 = (t) => Math.max(0, Math.min(1, t));
  const smoothstep = (t) => t * t * (3 - 2 * t);

  // Elastic room-making for one row. Returns whether anything is still moving.
  function elastic(s, robot, dt) {
    let opening = 0;
    if (robot) {
      // How much the robot overlaps this row vertically (0..1, soft edges).
      const tileH = s.tiles[0].h;
      const rowMid = s.page.y + s.page.h / 2;
      const reachY = robot.hh + tileH / 2;
      opening = smoothstep(clamp01((reachY + 30 - Math.abs(robot.y - rowMid)) / 70));
    }
    // A springy "boing" as the row bursts open.
    if (s.opening < 0.5 && opening >= 0.5 && window.XelenicSound) window.XelenicSound.play('boing');
    s.opening = opening;
    if (opening <= 0.001 && s.settled) return false;

    let moving = false;
    const rx = robot ? robot.x - s.page.x - s.offset : 0; // robot, in row coords
    s.tiles.forEach((t, i) => {
      const d = s.dyn[i];
      const cx = t.left + t.w / 2;
      let targetX = 0;
      let targetY = 0;
      let targetS = 1;
      let squeeze = 0;
      if (opening > 0.001) {
        // Which side of the robot this tile goes to (with a little hysteresis,
        // so a tile sitting right at the middle doesn't flutter).
        const rel = cx - rx;
        if (!d.side || (d.side > 0 && rel < -12) || (d.side < 0 && rel > 12)) d.side = rel >= 0 ? 1 : -1;
        // Push: enough to clear the robot right beside it, fading with
        // distance — its two edges are pushed by different amounts, so tiles
        // between the gap and the rest of the row get squashed (elastic).
        const push = (robot.hw + t.w / 2 + CLEARANCE) * opening;
        const near = robot.hw + t.w;
        const reach = Math.max(900, push * 3.6);
        const falloff = (u) => (u <= near ? 1 : Math.exp(-(u - near) / reach));
        const leftEdge = Math.abs(t.left - rx);
        const rightEdge = Math.abs(t.left + t.w - rx);
        const shiftL = push * falloff(leftEdge);
        const shiftR = push * falloff(rightEdge);
        targetX = (d.side * (shiftL + shiftR)) / 2;
        targetS = Math.max(0.68, (t.w + d.side * (shiftR - shiftL)) / t.w);
        // Rows bow apart a little vertically too.
        const tileMid = s.page.y + t.top + t.h / 2;
        targetY = Math.max(-1, Math.min(1, (tileMid - robot.y) / Math.max(1, robot.hh))) * 14 * opening * falloff(Math.abs(rel));
      }
      // Integrate the springs (semi-implicit Euler, sub-stepped for stability).
      const steps = Math.ceil(dt / 0.012) || 1;
      const h = dt / steps;
      for (let k = 0; k < steps; k++) {
        d.vx += (STIFFNESS * (targetX - d.ox) - DAMPING * d.vx) * h;
        d.ox += d.vx * h;
        d.vy += (STIFFNESS * (targetY - d.oy) - DAMPING * d.vy) * h;
        d.oy += d.vy * h;
        d.vs += (STIFFNESS * (targetS - d.sx) - DAMPING * d.vs) * h;
        d.sx += d.vs * h;
      }
      // Squeeze small while its current position overlaps the robot (a tile
      // the row carries across the gap slips past it).
      if (opening > 0.001) {
        const zone = robot.hw + t.w / 2;
        const across = Math.abs(cx + d.ox - rx);
        squeeze = opening * (1 - smoothstep(clamp01(across / zone)));
      }
      const atRest = Math.abs(d.ox) < 0.05 && Math.abs(d.vx) < 0.05 && Math.abs(d.oy) < 0.05 &&
        Math.abs(d.vy) < 0.05 && Math.abs(d.sx - 1) < 0.001 && Math.abs(d.vs) < 0.001 && squeeze < 0.001;
      if (atRest) {
        d.ox = d.vx = d.oy = d.vy = d.vs = 0;
        d.sx = 1;
        if (d.written !== '') {
          d.written = '';
          t.el.style.transform = '';
        }
        return;
      }
      moving = true;
      // Squash and stretch: stretched along fast motion, bulging a touch when
      // squashed (as if keeping its volume), tipping with its speed.
      const stretch = Math.min(0.16, Math.abs(d.vx) / 3600);
      const pinch = 1 - 0.78 * squeeze;
      const scaleX = d.sx * (1 + stretch) * pinch;
      const scaleY = (1 + (1 - d.sx) * 0.35) * (1 - stretch * 0.6) * pinch;
      const tilt = Math.max(-9, Math.min(9, d.vx / 140));
      const transform = `translate3d(${d.ox.toFixed(1)}px, ${d.oy.toFixed(1)}px, 0) rotate(${tilt.toFixed(2)}deg) scale(${scaleX.toFixed(3)}, ${scaleY.toFixed(3)})`;
      if (transform !== d.written) {
        d.written = transform;
        t.el.style.transform = transform;
      }
    });
    s.settled = !moving;
    return moving;
  }

  let last = null;
  let lastScroll = window.scrollY;
  let flow = 1; // eased scroll boost: 1 at rest, larger while scrolling
  let lean = 0; // eased skew, in degrees
  let elasticOn = false;
  function tick(now) {
    requestAnimationFrame(tick);
    const dt = last === null ? 0 : Math.max(0, Math.min(0.05, (now - last) / 1000));
    last = now;
    const scrollY = window.scrollY;
    const velocity = dt && !lite ? (scrollY - lastScroll) / dt : 0; // px/s, + when scrolling down
    lastScroll = scrollY;
    if (!visible || !dt) return;

    // Scrolling pushes the rows along (backwards when scrolling up) and
    // leans them; both ease back once the page is still.
    const push = Math.min(4, Math.abs(velocity) / 500);
    const targetFlow = velocity < -20 ? -(1 + push) : 1 + push;
    flow += (targetFlow - flow) * (1 - Math.exp(-dt * 5));
    lean += (Math.max(-7, Math.min(7, velocity * 0.006)) - lean) * (1 - Math.exp(-dt * 6));

    const robotInfo = window.XelenicRobot && window.XelenicRobot.box;
    const robot = robotInfo && robotInfo.hw > 0 ? robotInfo : null;
    let anyMoving = false;

    states.forEach((s) => {
      const target = s.hover ? 0 : flow;
      s.speed += (target - s.speed) * (1 - Math.exp(-dt * (s.hover ? 6 : 3)));
      // Keep the offset within one set, (-period, 0], handing spring state
      // along whenever it wraps.
      s.offset += s.dir * BASE_SPEED * s.speed * dt;
      while (s.offset <= -s.period) { s.offset += s.period; shiftDyn(s, 1); }
      while (s.offset > 0) { s.offset -= s.period; shiftDyn(s, -1); }
      const skew = (lean * s.dir).toFixed(2);
      const transform = `translate3d(${s.offset.toFixed(1)}px, 0, 0) skewX(${skew}deg)`;
      if (transform !== s.written) {
        s.written = transform;
        s.row.style.transform = transform;
      }
      if (elastic(s, robot, dt)) anyMoving = true;
    });

    // Give the tiles their own layers only while they're springing about.
    if (anyMoving !== elasticOn) {
      elasticOn = anyMoving;
      marquee.classList.toggle('is-elastic', anyMoving);
    }
  }
  requestAnimationFrame(tick);
})();
