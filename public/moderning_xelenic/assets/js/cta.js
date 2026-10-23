(() => {
  'use strict';

  // "Ready to Grow" call to action: arms the logo's build-up (style.css
  // .cta.is-armed) and plays it once the section is properly in view — the
  // square swings up, the pentagon spins in, the letters drop into place,
  // then the message, copy and button follow. Afterwards the 3D logo leans
  // toward the mouse while it's over the section. Desktop only: phones (and
  // reduced motion) get the finished logo straight away.

  const cta = document.querySelector('.cta');
  const tilt = cta && cta.querySelector('.lg-tilt');
  if (!cta || !tilt) return;
  if (document.documentElement.classList.contains('is-lite')) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  cta.classList.add('is-armed');
  function play() {
    if (cta.classList.contains('is-playing')) return;
    cta.classList.add('is-playing');
    // A power-up as the pentagon spins in (if robot sounds are on).
    setTimeout(() => window.XelenicSound && window.XelenicSound.play('powerup'), 450);
  }
  let visible = true;
  if ('IntersectionObserver' in window) {
    const arrive = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      arrive.disconnect();
      play();
    }, { threshold: 0.45 });
    arrive.observe(cta);
    new IntersectionObserver((entries) => {
      visible = entries.some((entry) => entry.isIntersecting);
    }).observe(cta);
  } else {
    play();
  }

  // Lean toward the mouse while it's over the section; settle back otherwise.
  let pointer = null;
  cta.addEventListener('pointermove', (e) => {
    if (e.pointerType === 'mouse') pointer = { x: e.clientX, y: e.clientY };
  });
  cta.addEventListener('pointerleave', () => { pointer = null; });

  let tx = 0;
  let ty = 0;
  let written = '';
  let last = null;
  function tick(now) {
    requestAnimationFrame(tick);
    const dt = last === null ? 0 : Math.max(0, Math.min(0.05, (now - last) / 1000));
    last = now;
    if (!visible) return;
    let targetX = 0;
    let targetY = 0;
    if (pointer) {
      const r = tilt.getBoundingClientRect();
      const nx = Math.max(-1, Math.min(1, (pointer.x - (r.left + r.width / 2)) / (window.innerWidth / 2)));
      const ny = Math.max(-1, Math.min(1, (pointer.y - (r.top + r.height / 2)) / (window.innerHeight / 2)));
      targetX = -ny * 14;
      targetY = nx * 22;
    }
    const ease = 1 - Math.exp(-dt * 5);
    tx += (targetX - tx) * ease;
    ty += (targetY - ty) * ease;
    const key = `${tx.toFixed(2)}|${ty.toFixed(2)}`;
    if (key === written) return;
    written = key;
    tilt.style.setProperty('--tx', tx.toFixed(2) + 'deg');
    tilt.style.setProperty('--ty', ty.toFixed(2) + 'deg');
  }
  requestAnimationFrame(tick);
})();
