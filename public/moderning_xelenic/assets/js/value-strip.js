(() => {
  'use strict';

  // Value strip cards: on hover each card tilts toward the cursor and lifts,
  // its glare and edge light follow the pointer, its 3D cube spins a full,
  // springy turn (the way your mouse came in from) and a sheen sweeps across
  // it. Drives --rx/--ry/--pop/--mx/--my on .value-item (see style.css), and
  // only runs while a card is moving.

  const items = Array.from(document.querySelectorAll('.value-item'));
  if (!items.length) return;
  if (document.documentElement.classList.contains('is-lite')) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const MAX_TILT_X = 14;
  const MAX_TILT_Y = 16;

  const states = items.map((item) => ({
    item,
    card: item.querySelector('.value-card'),
    turn: item.querySelector('.cube-turn'),
    shine: item.querySelector('.value-shine'),
    hover: false,
    px: 0.5,
    py: 0.5,
    rx: 0,
    ry: 0,
    pop: 0,
    written: '',
  }));

  function pointerSpot(s, e) {
    const r = s.card.getBoundingClientRect();
    s.px = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width));
    s.py = Math.max(0, Math.min(1, (e.clientY - r.top) / r.height));
    s.item.style.setProperty('--mx', (s.px * 100).toFixed(1) + '%');
    s.item.style.setProperty('--my', (s.py * 100).toFixed(1) + '%');
  }

  states.forEach((s) => {
    if (!s.card) return;
    s.card.addEventListener('pointerenter', (e) => {
      if (e.pointerType !== 'mouse') return;
      pointerSpot(s, e);
      s.hover = true;
      s.item.classList.add('is-hot');
      // Spin the way the mouse came in, overshooting a little and settling.
      const way = s.px < 0.5 ? 1 : -1;
      if (s.turn && s.turn.animate) {
        s.turn.animate(
          [{ transform: 'rotateY(0deg)' }, { transform: `rotateY(${360 * way}deg)` }],
          { duration: 1100, easing: 'cubic-bezier(0.34, 1.32, 0.5, 1)' }
        );
      }
      if (s.shine && s.shine.animate) {
        s.shine.animate(
          [{ backgroundPosition: way > 0 ? '130% 0' : '-30% 0' }, { backgroundPosition: way > 0 ? '-30% 0' : '130% 0' }],
          { duration: 850, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' }
        );
      }
      wake();
    });
    s.card.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse') return;
      pointerSpot(s, e);
      wake();
    });
    s.card.addEventListener('pointerleave', () => {
      s.hover = false;
      s.item.classList.remove('is-hot');
      wake();
    });
  });

  let running = false;
  let last = null;
  function wake() {
    if (running) return;
    running = true;
    last = null;
    requestAnimationFrame(tick);
  }

  function tick(now) {
    const dt = last === null ? 1 / 60 : Math.max(0, Math.min(0.05, (now - last) / 1000));
    last = now;
    const ease = 1 - Math.exp(-dt * 9);
    let moving = false;
    states.forEach((s) => {
      const targetX = s.hover ? (0.5 - s.py) * 2 * MAX_TILT_X : 0;
      const targetY = s.hover ? (s.px - 0.5) * 2 * MAX_TILT_Y : 0;
      const targetPop = s.hover ? 1 : 0;
      s.rx += (targetX - s.rx) * ease;
      s.ry += (targetY - s.ry) * ease;
      s.pop += (targetPop - s.pop) * ease;
      const settled = Math.abs(targetX - s.rx) < 0.02 && Math.abs(targetY - s.ry) < 0.02 && Math.abs(targetPop - s.pop) < 0.002;
      if (settled) {
        s.rx = targetX;
        s.ry = targetY;
        s.pop = targetPop;
      } else {
        moving = true;
      }
      const key = `${s.rx.toFixed(2)}|${s.ry.toFixed(2)}|${s.pop.toFixed(3)}`;
      if (key === s.written) return;
      s.written = key;
      s.item.style.setProperty('--rx', s.rx.toFixed(2) + 'deg');
      s.item.style.setProperty('--ry', s.ry.toFixed(2) + 'deg');
      s.item.style.setProperty('--pop', s.pop.toFixed(3));
    });
    if (moving) requestAnimationFrame(tick);
    else running = false;
  }
})();
