(() => {
  'use strict';

  // Lets the 3D layers notice when a machine can't keep up. Each layer makes a
  // watcher and calls it with the frame time on every animation frame. After
  // two runs of a couple of seconds below about 25 frames a second (well under
  // what any screen refreshes at, 30Hz monitors included), onSlow runs so the
  // layer can step its quality down. A step lasts for the rest of the visit
  // (going back up could make the page hunt between the two). A layer with
  // less to lose can pass options to be judged sooner and more strictly.
  const FRAMES = 120;
  const SLOW_MS = 40;
  const STRIKES = 2;
  const IGNORE_GAPS_OVER_MS = 250; // switching tabs, or scrolling back into view
  // By default only a page at rest is judged. A fast scroll can dip on any
  // machine, and giving up quality for it would outlast the scroll.
  const SCROLL_SETTLE_MS = 300;

  let lastScroll = -Infinity;
  window.addEventListener('scroll', () => { lastScroll = performance.now(); }, { passive: true });

  window.XelenicFrameBudget = (onSlow, {
    frames = FRAMES,
    slowMs = SLOW_MS,
    strikesAllowed = STRIKES,
    whileScrolling = false,
    graceMs = 0,
  } = {}) => {
    const from = performance.now() + graceMs;
    let last = null;
    let total = 0;
    let count = 0;
    let strikes = 0;

    return (now) => {
      if (now < from) return;
      const gap = last === null ? Infinity : now - last;
      last = now;
      if (gap >= IGNORE_GAPS_OVER_MS) return;
      if (!whileScrolling && now - lastScroll <= SCROLL_SETTLE_MS) return;
      total += gap;
      count++;
      if (count < frames) return;

      strikes = total / count > slowMs ? strikes + 1 : 0;
      total = 0;
      count = 0;
      if (strikes >= strikesAllowed) {
        strikes = 0;
        onSlow();
      }
    };
  };

  // For what's cheap to give up: a couple of seconds under about 35 frames a
  // second, once the page has started up, and scrolling counts because that's
  // when lag is felt.
  window.XelenicFrameBudget.QUICK = {
    frames: 60,
    slowMs: 28,
    strikesAllowed: 1,
    whileScrolling: true,
    graceMs: 3000,
  };
})();
