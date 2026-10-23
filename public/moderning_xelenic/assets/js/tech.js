(() => {
  'use strict';

  // "Our Foundation" tech field. Ten glass chips sit on a ring around the
  // robot's spot in real CSS 3D, each at its own depth. The whole stage
  // tilts toward the cursor (near chips swing further than far ones), each
  // chip leans toward the pointer and lifts when hovered, the chips burst out
  // from the centre when the section arrives, and as the robot drifts after
  // the cursor (three-robot.js publishes where it is) nearby chips spring
  // aside to make room, linked to it by flowing data lines.

  const field = document.querySelector('.tech-field');
  if (!field) return;
  const spot = field.querySelector('.tech-spot');
  const list = field.querySelector('.tech-chips');
  const stage = field.querySelector('.tech-stage');
  const links = field.querySelector('.tech-links');
  const chips = Array.from(field.querySelectorAll('.tech-chip'));
  if (!chips.length || !spot || !list || !stage || !links) return;

  // The lite (phone) page: the chips simply sit in tidy rows (see .is-lite in CSS).
  if (document.documentElement.classList.contains('is-lite')) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // Per-chip depth (1 = nearest) and a little irregularity in the ring, so
  // it reads as a loose constellation rather than a clock face.
  const DEPTHS = [1, 0.55, 0.85, 0.6, 0.95, 0.7, 0.5, 0.9, 0.65, 0.8];
  const ANGLE_JITTER = [0, 7, -5, 4, -7, 0, 6, -4, 7, -3];
  const RADIUS_JITTER = [1, 0.9, 1.04, 0.94, 1.02, 1, 0.92, 1.05, 0.95, 1];
  const DEPTH_RANGE = 150; // px of translateZ between the nearest and farthest chip
  const ENTER_MS = 950;
  const ENTER_STAGGER = 85;

  const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  // Springy overshoot for the burst-out.
  const easeOutBack = (t) => 1 + 2.4 * Math.pow(t - 1, 3) + 1.4 * Math.pow(t - 1, 2);

  const NS = 'http://www.w3.org/2000/svg';
  const states = chips.map((chip, i) => {
    const line = document.createElementNS(NS, 'line');
    line.setAttribute('class', 'tech-link');
    links.appendChild(line);
    return {
      chip,
      line,
      index: i,
      depth: DEPTHS[i % DEPTHS.length],
      hx: 0, // home centre and size, in field coordinates
      hy: 0,
      w: 0,
      h: 0,
      ox: 0, // spring offset that makes room for the robot, and its velocity
      oy: 0,
      vx: 0,
      vy: 0,
      enter: reduceMotion ? 1 : 0,
      phase: i * 1.7,
      written: '',
    };
  });

  let ring = true;
  let W = 0;
  let H = 0;
  let spotX = 0;
  let spotY = 0;
  let origin = { x: 0, y: 0 };

  function pageOffset(el) {
    let x = 0;
    let y = 0;
    for (let node = el; node; node = node.offsetParent) {
      x += node.offsetLeft;
      y += node.offsetTop;
    }
    return { x, y };
  }

  function layout() {
    W = field.offsetWidth;
    H = field.offsetHeight;
    ring = getComputedStyle(field).getPropertyValue('--layout').trim() !== 'flow';
    if (!ring) {
      stage.style.transform = '';
      lastTilt = '';
    }
    origin = pageOffset(field);
    spotX = spot.offsetLeft + spot.offsetWidth / 2;
    spotY = spot.offsetTop + spot.offsetHeight / 2;
    const n = states.length;
    states.forEach((s) => {
      s.w = s.chip.offsetWidth;
      s.h = s.chip.offsetHeight;
      if (ring) {
        // On a loose ellipse around the spot, kept inside the field.
        const a = ((-90 + (s.index * 360) / n + ANGLE_JITTER[s.index % 10]) * Math.PI) / 180;
        const r = RADIUS_JITTER[s.index % 10];
        s.hx = clamp(W / 2 + Math.cos(a) * W * 0.4 * r, s.w / 2 + 8, W - s.w / 2 - 8);
        s.hy = clamp(H / 2 + Math.sin(a) * H * 0.4 * r, s.h / 2 + 8, H - s.h / 2 - 8);
      } else {
        // Flowing rows (phones): home is wherever the layout put it.
        s.hx = stage.offsetLeft + list.offsetLeft + s.chip.offsetLeft + s.w / 2;
        s.hy = stage.offsetTop + list.offsetTop + s.chip.offsetTop + s.h / 2;
      }
      s.written = '';
    });
    links.setAttribute('viewBox', `0 0 ${W} ${H}`);
  }

  // ---- Pointer: stage tilt, depth parallax and per-chip hover lean ---------

  let pointer = null; // client coords of the mouse, null when away
  let tiltX = 0;
  let tiltY = 0;
  let lastTilt = '';
  if (!reduceMotion) {
    window.addEventListener('pointermove', (e) => {
      if (e.pointerType === 'mouse') pointer = { x: e.clientX, y: e.clientY };
    }, { passive: true });
    document.documentElement.addEventListener('mouseleave', () => { pointer = null; });

    states.forEach((s) => {
      const face = s.chip.querySelector('.tech-chip-face');
      if (!face) return;
      face.addEventListener('pointermove', (e) => {
        if (e.pointerType !== 'mouse') return;
        const r = face.getBoundingClientRect();
        const px = clamp((e.clientX - r.left) / r.width, 0, 1);
        const py = clamp((e.clientY - r.top) / r.height, 0, 1);
        s.chip.style.setProperty('--rx', ((0.5 - py) * 26).toFixed(1) + 'deg');
        s.chip.style.setProperty('--ry', ((px - 0.5) * 22).toFixed(1) + 'deg');
        s.chip.classList.add('is-hot');
      });
      face.addEventListener('pointerleave', () => {
        s.chip.style.setProperty('--rx', '0deg');
        s.chip.style.setProperty('--ry', '0deg');
        s.chip.classList.remove('is-hot');
      });
    });
  }

  // ---- Frame -----------------------------------------------------------

  let visible = false;
  let enterAt = Infinity;
  let time = 0;
  let last = null;

  function frame(now, dt) {
    time += dt;
    const step = dt * 60; // springs are tuned per 60fps frame

    // Where the cursor is across the field (-1..1), for tilt and parallax.
    let aimX = 0;
    let aimY = 0;
    if (pointer && !reduceMotion) {
      const fx = origin.x - window.scrollX;
      const fy = origin.y - window.scrollY;
      aimX = clamp((pointer.x - (fx + W / 2)) / (W / 2), -1, 1);
      aimY = clamp((pointer.y - (fy + H / 2)) / (H / 2), -1, 1);
    }
    const ease = 1 - Math.exp(-dt * 4);
    tiltX += (-aimY * 7 - tiltX) * ease;
    tiltY += (aimX * 9 - tiltY) * ease;
    if (ring) {
      // Set as a transform directly (not via CSS variables) so only the
      // stage restyles, not every chip inside it.
      const tilt = `rotateX(${tiltX.toFixed(2)}deg) rotateY(${tiltY.toFixed(2)}deg)`;
      if (tilt !== lastTilt) {
        stage.style.transform = tilt;
        lastTilt = tilt;
      }
    }

    // The robot, in field coordinates (only while it's here).
    const robot = window.XelenicRobot;
    const presence = robot ? robot.presence : 0;
    const rx = robot ? robot.x - origin.x : 0;
    const ry = robot ? robot.y - origin.y : 0;
    const rr = robot ? robot.radius : 0;

    states.forEach((s) => {
      if (s.enter < 1) {
        s.enter = clamp((now - enterAt - s.index * ENTER_STAGGER) / ENTER_MS, 0, 1);
      }
      const e = reduceMotion ? 1 : easeOutBack(s.enter);

      // Make room: a chip whose home falls within the robot's reach — an
      // ellipse sized to the chip, since chips are wide and short — is pushed
      // straight out to its edge, on a bouncy spring that lets it settle
      // back home once the robot moves on.
      let tx = 0;
      let ty = 0;
      // (The chip you're hovering stays put.)
      if (presence > 0.01 && !reduceMotion && !s.chip.classList.contains('is-hot')) {
        const dx = s.hx - rx;
        const dy = s.hy - ry;
        const reachX = rr * 1.05 + s.w / 2 + 40;
        const reachY = rr + s.h / 2 + 36;
        const q = Math.hypot(dx / reachX, dy / reachY);
        if (q < 1) {
          const out = (1 / Math.max(q, 0.15) - 1) * presence;
          tx = (q < 0.15 && Math.abs(dx) < 1 ? reachX * presence : dx * out);
          ty = dy * out;
        }
      }
      s.vx = (s.vx + (tx - s.ox) * 0.08 * step) * Math.pow(0.8, step);
      s.vy = (s.vy + (ty - s.oy) * 0.08 * step) * Math.pow(0.8, step);
      s.ox += s.vx * step;
      s.oy += s.vy * step;

      // A slow float, plus depth parallax: near chips drift further with the cursor.
      const floatX = reduceMotion ? 0 : Math.sin(time * 0.8 + s.phase) * 5 * s.depth;
      const floatY = reduceMotion ? 0 : Math.cos(time * 0.9 + s.phase) * 7 * s.depth;
      const parX = aimX * 22 * s.depth;
      const parY = aimY * 14 * s.depth;
      const offX = s.ox + floatX + parX;
      const offY = s.oy + floatY + parY;

      let transform;
      if (ring) {
        // Burst out from the robot's spot to home, spinning in.
        const x = lerp(spotX, s.hx, e) + offX - s.w / 2;
        const y = lerp(spotY, s.hy, e) + offY - s.h / 2;
        const z = (s.depth - 0.7) * DEPTH_RANGE * e;
        const turn = (1 - Math.min(1, e)) * 70;
        transform = `translate3d(${x.toFixed(1)}px, ${y.toFixed(1)}px, ${z.toFixed(1)}px) rotateY(${turn.toFixed(1)}deg) scale(${(0.4 + 0.6 * e).toFixed(3)})`;
      } else {
        const x = (spotX - s.hx) * (1 - e) + offX;
        const y = (spotY - s.hy) * (1 - e) + offY;
        transform = `translate3d(${x.toFixed(1)}px, ${y.toFixed(1)}px, 0) scale(${(0.4 + 0.6 * e).toFixed(3)})`;
      }
      const opacity = clamp(s.enter * 1.8, 0, 1) * (ring ? 0.72 + 0.28 * s.depth : 1);
      const key = transform + opacity.toFixed(2);
      if (key !== s.written) {
        s.written = key;
        s.chip.style.transform = transform;
        s.chip.style.opacity = opacity.toFixed(2);
      }

      // Data line from the robot's edge to the chip, fading with distance.
      const cx = s.hx + offX;
      const cy = s.hy + offY;
      const ldx = cx - rx;
      const ldy = cy - ry;
      const ld = Math.hypot(ldx, ldy) || 1;
      const strength = clamp(1 - (ld - rr) / 340, 0, 1) * presence * Math.min(1, s.enter * 1.5);
      if (strength > 0.02) {
        s.line.setAttribute('x1', (rx + (ldx / ld) * rr * 0.85).toFixed(1));
        s.line.setAttribute('y1', (ry + (ldy / ld) * rr * 0.85).toFixed(1));
        s.line.setAttribute('x2', cx.toFixed(1));
        s.line.setAttribute('y2', cy.toFixed(1));
        s.line.style.opacity = (strength * 0.75).toFixed(2);
      } else if (s.line.style.opacity !== '0') {
        s.line.style.opacity = '0';
      }
    });
  }

  function tick(now) {
    requestAnimationFrame(tick);
    // A frame's timestamp can predate this script, so never step backwards.
    const dt = last === null ? 0 : Math.max(0, Math.min(0.05, (now - last) / 1000));
    last = now;
    if (visible) frame(now, dt);
  }

  // Animate only while the field is on (or near) the screen; the burst-out
  // starts the first time it really comes into view.
  if ('IntersectionObserver' in window) {
    new IntersectionObserver((entries) => {
      visible = entries.some((entry) => entry.isIntersecting);
    }, { rootMargin: '200px 0px' }).observe(field);
    const arrive = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      enterAt = performance.now();
      arrive.disconnect();
    }, { rootMargin: '0px 0px -18% 0px' });
    arrive.observe(field);
  } else {
    visible = true;
    enterAt = 0;
  }

  window.addEventListener('resize', layout);
  window.addEventListener('load', layout);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
  if ('ResizeObserver' in window) {
    const ro = new ResizeObserver(layout);
    ro.observe(field);
    ro.observe(document.body);
  }
  layout();

  if (reduceMotion) {
    // Still: every chip at home, drawn once.
    visible = false;
    frame(performance.now(), 0);
  } else {
    requestAnimationFrame(tick);
  }
})();
