(() => {
  'use strict';

  // Logo art: the backdrops drawn from the logo's shapes (.logo-art — the
  // hero's, and the "What We Do" emblem and field). Each one's burst-in waits
  // until it scrolls into view, and while it's on screen its layers get
  // depth: the cursor position (--mx/--my, -1..1) and how far its middle sits
  // from the middle of the screen (--sp, -1..1) shift each layer by its own
  // --depth (see .logo-art in CSS), so near layers move more than far ones.

  const arts = Array.from(document.querySelectorAll('.logo-art'));
  if (!arts.length) return;
  // The lite (phone) page keeps only the art's own gentle CSS motion.
  if (document.documentElement.classList.contains('is-lite')) return;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const states = arts.map((el) => ({ el, visible: true, mx: 0, my: 0, written: '' }));

  if ('IntersectionObserver' in window) {
    // Hold each burst-in until the art is properly on screen.
    const arrive = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.remove('is-pending');
        arrive.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -15% 0px' });
    // Only feed depth to art that's on (or near) the screen.
    const near = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        const s = states.find((st) => st.el === entry.target);
        if (s) s.visible = entry.isIntersecting;
      });
    }, { rootMargin: '150px 0px' });
    states.forEach((s) => {
      s.el.classList.add('is-pending');
      arrive.observe(s.el);
      near.observe(s.el);
    });
  }

  if (reduceMotion) return;

  let pointer = null;
  window.addEventListener('pointermove', (e) => {
    if (e.pointerType === 'mouse') pointer = { x: e.clientX, y: e.clientY };
  }, { passive: true });
  document.documentElement.addEventListener('mouseleave', () => { pointer = null; });

  let last = null;
  function tick(now) {
    requestAnimationFrame(tick);
    const dt = last === null ? 0 : Math.max(0, Math.min(0.05, (now - last) / 1000));
    last = now;
    const targetX = pointer ? (pointer.x / window.innerWidth - 0.5) * 2 : 0;
    const targetY = pointer ? (pointer.y / window.innerHeight - 0.5) * 2 : 0;
    const ease = 1 - Math.exp(-dt * 3);
    const vh = window.innerHeight;
    // Read every position first, then write, so styles aren't recalculated
    // part-way through the frame.
    const live = states.filter((s) => s.visible);
    const middles = live.map((s) => {
      const r = s.el.getBoundingClientRect();
      return r.top + r.height / 2;
    });
    live.forEach((s, i) => {
      s.mx += (targetX - s.mx) * ease;
      s.my += (targetY - s.my) * ease;
      const sp = Math.max(-1, Math.min(1, (vh / 2 - middles[i]) / vh));
      const key = `${s.mx.toFixed(3)}|${s.my.toFixed(3)}|${sp.toFixed(3)}`;
      if (key === s.written) return;
      s.written = key;
      s.el.style.setProperty('--mx', s.mx.toFixed(3));
      s.el.style.setProperty('--my', s.my.toFixed(3));
      s.el.style.setProperty('--sp', sp.toFixed(3));
    });
  }
  requestAnimationFrame(tick);
})();
