// Animation templates (resources/views/marketplace/animations): plays Kiosk
// Studio's animated screens live with its own player, and on a template's
// page lays out its animation timeline and contents from the screen itself.
// Needs the studio's games.js, runtime.js and templates.js (loaded first).
(() => {
  'use strict';

  if (typeof KioskRuntime !== 'function' || typeof SCREEN_TEMPLATES === 'undefined') return;

  const RT = KioskRuntime();
  const W = 1080;
  const H = 1920;
  const templates = {};
  SCREEN_TEMPLATES.forEach((t) => { templates[t.name] = t; });

  // The studio's names for its effects (editor-inspector.js).
  const ENTER = { fade: 'Fade in', up: 'Slide up', down: 'Slide down', left: 'Slide in', right: 'Slide in', zoom: 'Zoom in', pop: 'Pop', bounce: 'Drop & bounce', jelly: 'Jelly', blur: 'Blur in', flip: 'Flip in', spin: 'Spin in', letters: 'Letter by letter', typewriter: 'Typewriter' };
  const LOOP = { pulse: 'Pulse', float: 'Float', shine: 'Shine', glow: 'Glow', heartbeat: 'Heartbeat', tada: 'Tada', wiggle: 'Wiggle', swing: 'Swing', breathe: 'Breathe', blink: 'Blink', spin: 'Spin' };
  const EFFECTS = { aurora: 'Aurora glow', rays: 'Sun rays', bubbles: 'Bubbles', stars: 'Twinkling stars', sparkles: 'Sparkles', confetti: 'Confetti', snow: 'Snow' };
  const TRANSITIONS = { rise: 'Rise', fade: 'Fade', slide: 'Slide', zoom: 'Zoom' };

  const el = (tag, attrs, ...children) => {
    const node = document.createElement(tag);
    Object.entries(attrs || {}).forEach(([key, value]) => {
      if (key === 'style') node.style.cssText = value;
      else node.setAttribute(key, value);
    });
    children.flat().filter((c) => c != null).forEach((c) => node.append(c));
    return node;
  };

  // A one-screen kiosk app to play a template in. The sample logo fills the
  // logo slot, and the score reads as a real one would.
  const design = (sc) => ({
    id: 'preview', name: sc.name, version: 1, width: W, height: H, startScreen: sc.id, idleTimeout: 0,
    vars: { score: 1250 }, assets: typeof sampleLogoAsset === 'function' ? [sampleLogoAsset()] : [], screens: [sc],
  });

  // ---- Playing screens: each runs while it's in view ----
  const stages = [...document.querySelectorAll('[data-screen]')].filter((stage) => templates[stage.dataset.screen]);

  stages.forEach((stage) => {
    // Tint its surroundings with the screen's own colours.
    const sc = templates[stage.dataset.screen].make();
    const tinted = stage.closest('[data-tint]');
    if (tinted) {
      tinted.style.setProperty('--game', sc.bg || '#3b3f5c');
      tinted.style.setProperty('--game2', sc.bg2 || sc.bg || '#3b3f5c');
    }
  });

  // A screen made for something (a campaign concept) can carry its own line
  // in place of the template's placeholder one, as its demo does.
  const make = (stage) => {
    const sc = templates[stage.dataset.screen].make();
    const line = stage.dataset.tagline;
    if (line) {
      sc.objects.forEach((o) => {
        if (o.text === 'Your tagline goes here' || o.text === 'Play fun games and win amazing prizes') o.text = line;
      });
    }
    return sc;
  };

  const mount = (stage) => {
    if (stage.player) return;
    const holder = el('span', { class: 'mk-anim-player' });
    stage.append(holder);
    stage.player = RT.createPlayer(holder, design(make(stage)), {});
    stage.classList.add('is-live');
    stage.dispatchEvent(new CustomEvent('screen:play'));
  };
  const unmount = (stage) => {
    if (!stage.player) return;
    stage.player.destroy();
    stage.player = null;
    stage.querySelector('.mk-anim-player')?.remove();
    stage.classList.remove('is-live');
  };

  if ('IntersectionObserver' in window) {
    const watch = new IntersectionObserver((entries) => {
      entries.forEach((entry) => (entry.isIntersecting ? mount(entry.target) : unmount(entry.target)));
    }, { rootMargin: '40px 0px' });
    stages.forEach((stage) => watch.observe(stage));
  } else {
    stages.forEach(mount);
  }

  // Pointing at a card plays its screen again from the start.
  document.querySelectorAll('.mk-anim-card').forEach((card) => {
    const stage = card.querySelector('[data-screen]');
    let last = 0;
    card.addEventListener('pointerenter', () => {
      if (stage?.player && Date.now() - last > 1200) {
        last = Date.now();
        stage.player.reset();
      }
    });
  });

  // ---- A template's page ----
  const main = document.querySelector('[data-screen][data-main]');
  if (!main || !templates[main.dataset.screen]) return;

  const sc = templates[main.dataset.screen].make();
  const moving = sc.objects.filter((o) => (o.enter && o.enter !== 'none') || (o.anim && o.anim !== 'none'));
  const phases = moving.map((o) => ({ o, p: RT.phases(o) }));
  const end = Math.max(1, Math.ceil(Math.max(0, ...phases.map(({ p }) => p.enterEnd)) * 2) / 2);
  const seconds = `${end % 1 ? end.toFixed(1) : end} seconds`;

  // Chips and details.
  const chips = document.querySelector('[data-chips]');
  const facts = document.querySelector('[data-facts]');
  const fact = (label, value) => facts?.append(el('div', {}, el('dt', {}, label), el('dd', {}, value)));
  if (sc.effect && EFFECTS[sc.effect]) chips?.append(el('li', {}, `${EFFECTS[sc.effect]} background`));
  chips?.append(el('li', {}, `Plays in ${seconds}`));
  fact('Animated items', String(moving.length));
  if (sc.effect && EFFECTS[sc.effect]) fact('Background', EFFECTS[sc.effect]);
  if (sc.transition && TRANSITIONS[sc.transition]) fact('Arrives with', TRANSITIONS[sc.transition]);
  fact('Plays in', seconds);

  // What's on it.
  const counts = [
    ['Texts', (o) => o.type === 'text'],
    ['Buttons', (o) => o.type === 'button'],
    ['Stickers', (o) => o.type === 'emoji'],
    ['Shapes', (o) => o.type === 'shape'],
    ['Pictures', (o) => o.type === 'image'],
  ].map(([label, test]) => [label, sc.objects.filter(test).length]).filter(([, n]) => n);
  const countList = document.querySelector('[data-counts]');
  if (countList && counts.length) {
    counts.forEach(([label, n]) => countList.append(el('li', {}, el('strong', {}, String(n)), el('span', {}, label))));
    document.querySelector('[data-counts-section]').hidden = false;
  }

  // The timeline: one row per animated item, a bar for its entrance and a
  // marker for the loop it keeps playing; a playhead runs with the preview.
  const timeline = document.querySelector('[data-timeline]');
  const label = (o) => {
    if (o.type === 'emoji') return `${o.emoji}  Sticker`;
    if (o.type === 'text' || o.type === 'button') {
      const text = String(o.text || '').replace(/\{\{(\w+)\}\}/g, '[$1]').replace(/^[▶‹\s]+/, '').replace(/\s+/g, ' ').trim();
      const short = text.length > 28 ? `${text.slice(0, 27)}…` : text;
      return o.type === 'button' ? `Button: ${short}` : short || 'Text';
    }
    if (o.type === 'image') return 'Picture: your logo';
    return o.shape === 'circle' ? 'Circle' : 'Box';
  };
  const pct = (s) => `${(s / end) * 100}%`;
  let lane = null;

  if (timeline && phases.length) {
    const ticks = el('div', { class: 'mk-tl-ticks', 'aria-hidden': 'true' });
    // Half seconds when there's room for them.
    const step = end > 4 || timeline.clientWidth < 600 ? 1 : 0.5;
    for (let s = 0; s <= end + 0.001; s += step) ticks.append(el('span', { style: `left:${pct(s)}` }, `${s}s`));
    timeline.append(el('div', { class: 'mk-tl-row mk-tl-head-row' }, el('span', { class: 'mk-tl-name' }), ticks));

    phases.slice().sort((a, b) => a.p.enterStart - b.p.enterStart).forEach(({ o, p }, i) => {
      const track = el('div', { class: 'mk-tl-track' });
      if (p.enter) {
        track.append(el('b', {
          style: `left:${pct(p.enterStart)};width:max(${pct(p.enterEnd - p.enterStart)}, 6px);animation-delay:${i * 45}ms`,
          title: `${ENTER[p.enter] || p.enter}: from ${p.enterStart}s for ${(p.enterEnd - p.enterStart).toFixed(1)}s`,
        }, ENTER[p.enter] || p.enter));
      }
      if (o.anim && o.anim !== 'none') {
        track.append(el('em', { style: `left:${pct(p.enterEnd)}`, title: `Then keeps moving: ${LOOP[o.anim] || o.anim}` }, el('span', {}, `${LOOP[o.anim] || o.anim} ∞`)));
      }
      timeline.append(el('div', { class: 'mk-tl-row' }, el('span', { class: 'mk-tl-name', title: label(o) }, label(o)), track));
    });

    lane = el('div', { class: 'mk-tl-lane', 'aria-hidden': 'true' });
    timeline.append(lane);
    document.querySelector('[data-timeline-section]').hidden = false;
  }

  const runPlayhead = () => {
    if (!lane) return;
    lane.replaceChildren(el('i', { class: 'mk-tl-playhead', style: `animation-duration:${end}s` }));
  };

  // The preview starts when it comes into view; Replay starts it again.
  main.addEventListener('screen:play', runPlayhead);
  if (main.player) runPlayhead();
  document.querySelectorAll('[data-replay]').forEach((button) => button.addEventListener('click', () => {
    if (main.player) main.player.reset();
    else mount(main);
    runPlayhead();
  }));
})();
