(() => {
  'use strict';

  // Smooths mouse wheel scrolling and anchor links with Lenis. It still moves
  // the real page, so everything reading window.scrollY carries on as before,
  // and touch and keyboard scrolling stay native. This loads ahead of the other
  // animated scripts on purpose: animation frames run in the order they were
  // first asked for, so they all see this frame's position, not the last one's,
  // and the robot stays locked to the page.

  // Without it (the CDN failed, or reduced motion) the page just scrolls natively.
  if (!window.Lenis || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

  const lenis = new window.Lenis({
    anchors: { duration: 1.4, easing: easeInOutCubic },
    // A long message scrolls inside its own box.
    prevent: (node) => node.nodeName === 'TEXTAREA',
  });
  lenis.on('scroll', () => {
    if (window.ScrollTrigger) window.ScrollTrigger.update();
  });

  // Native scrolling carries on smoothly while the page is busy; this doesn't,
  // since it moves the page from the same thread as everything else. So if the
  // page lags it steps aside for the rest of the visit.
  let running = true;
  const watchFrames = window.XelenicFrameBudget(() => {
    running = false;
    lenis.destroy();
  }, window.XelenicFrameBudget.QUICK);

  function tick(now) {
    watchFrames(now);
    if (!running) return;
    lenis.raf(now);
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
