(() => {
  'use strict';

  // "How We Work" storybook path. Builds a cartoon road that winds through
  // each step's spot, draws it in as you scroll (just ahead of the robot),
  // pops up a milestone flag as each stop is reached and lights up the step
  // the robot is standing at. It also tells three-robot.js where on the road
  // the robot should be (window.XelenicStory), so the robot walks the path
  // with the reader.

  const story = document.querySelector('.story');
  if (!story) return;
  const steps = Array.from(story.querySelectorAll('.story-step'));
  const svg = story.querySelector('.story-road');
  if (!steps.length || !svg) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // The lite (phone) page: the road is simply drawn in full, every flag is up
  // and every scene plays its little loop — nothing is tied to scrolling.
  const lite = document.documentElement.classList.contains('is-lite');
  const still = reduceMotion || lite;
  const LEAD = 70; // the road is drawn this far ahead of the robot (px)

  const clamp01 = (t) => Math.max(0, Math.min(1, t));
  const lerp = (a, b, t) => a + (b - a) * t;
  const smoothstep = (t) => t * t * (3 - 2 * t);

  // The road's layers are built once; layout() only rewrites their paths.
  svg.innerHTML = `
    <defs>
      <mask id="story-road-mask" maskUnits="userSpaceOnUse">
        <path class="road-mask-path"/>
      </mask>
    </defs>
    <path class="road-ghost"/>
    <g mask="url(#story-road-mask)">
      <path class="road-edge"/>
      <path class="road-surface"/>
      <path class="road-line"/>
    </g>
    <g class="road-markers"></g>`;
  const ghost = svg.querySelector('.road-ghost');
  const maskPath = svg.querySelector('.road-mask-path');
  const layers = Array.from(svg.querySelectorAll('.road-edge, .road-surface, .road-line'));

  // A milestone flag for each stop, numbered like its card.
  const markerGroup = svg.querySelector('.road-markers');
  const markers = steps.map((step, i) => {
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('class', 'road-marker');
    g.innerHTML = `
      <g class="marker-pop">
        <ellipse class="marker-base" cx="0" cy="0" rx="16" ry="5"/>
        <rect class="marker-pole" x="-3" y="-62" width="6" height="62" rx="2"/>
        <path class="marker-flag" d="M3 -60 h40 l-9 12 l9 12 h-40 z"/>
        <text x="21" y="-47">${String(i + 1).padStart(2, '0')}</text>
      </g>`;
    markerGroup.appendChild(g);
    return g;
  });

  let geom = null;
  const pointCache = new Map();
  let roadOffset = Infinity; // the dash offset last written to the road

  // Position relative to the story box (layout offsets; ignores transforms).
  function offsetIn(el) {
    let x = 0;
    let y = 0;
    for (let node = el; node && node !== story; node = node.offsetParent) {
      x += node.offsetLeft;
      y += node.offsetTop;
    }
    return { x, y };
  }
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
    const W = story.offsetWidth;
    const H = story.offsetHeight;
    if (!W || !H) return;
    svg.setAttribute('viewBox', `0 0 ${W} ${H}`);

    // Where the robot stands at each step: --stand across and --ground down
    // its spot (set in CSS per layout).
    const points = steps.map((step) => {
      const spot = step.querySelector('.story-spot');
      const o = offsetIn(spot);
      const styles = getComputedStyle(spot);
      const stand = parseFloat(styles.getPropertyValue('--stand')) || 50;
      const ground = parseFloat(styles.getPropertyValue('--ground')) || 80;
      return {
        x: o.x + (spot.offsetWidth * stand) / 100,
        y: o.y + (spot.offsetHeight * ground) / 100,
        mid: o.y + spot.offsetHeight / 2,
        width: spot.offsetWidth,
        // +1: the card is on the left, so the scene (and robot) is on the right.
        side: step.classList.contains('is-left') ? 1 : -1,
      };
    });

    // Smooth S-curves leaving and entering each stop vertically, from the top
    // of the story down to its bottom.
    const curve = (a, b) => {
      const k = (b.y - a.y) * 0.55;
      return ` C ${a.x} ${a.y + k}, ${b.x} ${b.y - k}, ${b.x} ${b.y}`;
    };
    // Starts and ends just inside the world's rounded panel.
    const start = { x: W / 2, y: 34 };
    const end = { x: W / 2, y: H - 34 };
    let d = `M ${start.x} ${start.y}` + curve(start, points[0]);
    ghost.setAttribute('d', d);
    const lengths = [ghost.getTotalLength()];
    for (let i = 1; i < points.length; i++) {
      d += curve(points[i - 1], points[i]);
      ghost.setAttribute('d', d);
      lengths.push(ghost.getTotalLength());
    }
    d += curve(points[points.length - 1], end);
    ghost.setAttribute('d', d);
    const total = ghost.getTotalLength();
    [maskPath, ...layers].forEach((path) => path.setAttribute('d', d));
    maskPath.style.strokeDasharray = `${total} ${total}`;
    pointCache.clear();
    roadOffset = Infinity;

    // Flags stand beside the road, on the card's side of the robot.
    points.forEach((p, i) => {
      const gap = Math.min(150, p.width * 0.3);
      markers[i].setAttribute('transform', `translate(${p.x - p.side * gap} ${p.y + 6})`);
    });

    const origin = pageOffset(story);
    const vh = window.innerHeight;
    geom = {
      left: origin.x,
      top: origin.y,
      total,
      stops: points.map((p, i) => ({
        x: origin.x + p.x,
        y: origin.y + p.y,
        side: p.side,
        length: lengths[i],
        // The robot is at this stop while the step's scene is mid-screen.
        scroll: origin.y + p.mid - vh / 2,
      })),
    };
    update();
  }

  // A point along the road. getPointAtLength walks the whole path each time
  // and this runs several times a frame while scrolling, so answers are kept on
  // a 4px grid (layout() clears them) and blended, which is just as smooth.
  const POINT_STEP = 4;
  function gridPoint(index) {
    let point = pointCache.get(index);
    if (!point) {
      const { x, y } = ghost.getPointAtLength(index * POINT_STEP);
      point = { x, y };
      pointCache.set(index, point);
    }
    return point;
  }
  function pointAt(length) {
    const index = Math.floor(length / POINT_STEP);
    const t = length / POINT_STEP - index;
    const a = gridPoint(index);
    const b = gridPoint(index + 1);
    return { x: lerp(a.x, b.x, t), y: lerp(a.y, b.y, t) };
  }

  // Where along the road the robot is for a scroll position. Between two
  // stops it lingers at each (the first and last fifth of the way) and walks
  // in between. `dwell` is 1 at a stop and 0 mid-walk.
  function travel(scrollY) {
    if (!geom) return null;
    const s = geom.stops;
    const last = s.length - 1;
    let length;
    let index;
    let dwell = 1;
    if (scrollY <= s[0].scroll) {
      length = s[0].length;
      index = 0;
    } else if (scrollY >= s[last].scroll) {
      length = s[last].length;
      index = last;
    } else {
      let i = 0;
      while (scrollY >= s[i + 1].scroll) i++;
      const t = (scrollY - s[i].scroll) / (s[i + 1].scroll - s[i].scroll);
      const walk = smoothstep(clamp01((t - 0.2) / 0.6));
      length = lerp(s[i].length, s[i + 1].length, walk);
      index = i + walk;
      dwell = 1 - Math.sin(Math.PI * walk);
    }
    const p = pointAt(length);
    const q = pointAt(Math.min(geom.total, length + 6));
    return {
      x: geom.left + p.x,
      y: geom.top + p.y,
      length,
      index,
      dwell,
      dx: q.x - p.x,
      dy: q.y - p.y,
      side: s[Math.round(index)].side,
    };
  }

  function update() {
    if (!geom) return;
    const scrollY = window.scrollY;
    const s = geom.stops;
    const at = travel(scrollY);
    let drawn;
    if (still) {
      drawn = geom.total;
    } else if (scrollY < s[0].scroll) {
      // Before the first stop the road grows in as the section arrives.
      const vh = window.innerHeight;
      drawn = s[0].length * clamp01((scrollY - (s[0].scroll - vh * 0.6)) / (vh * 0.6));
    } else {
      drawn = at.length + LEAD;
    }
    // Writing this repaints the road, so only when it has actually grown.
    const offset = geom.total - Math.min(geom.total, drawn);
    if (Math.abs(offset - roadOffset) >= 0.5) {
      roadOffset = offset;
      maskPath.style.strokeDashoffset = String(offset);
    }

    steps.forEach((step, i) => {
      const reached = still || drawn >= s[i].length - 2;
      const active = !lite && reached && Math.round(at.index) === i && at.dwell > 0.5;
      step.classList.toggle('is-reached', reached);
      step.classList.toggle('is-active', active);
      // Its scenery animates while the robot stands there — or always, on phones.
      step.classList.toggle('is-live', lite || active);
      markers[i].classList.toggle('is-reached', reached);
    });
  }

  // Cards slide in from their side as they come into view.
  if ('IntersectionObserver' in window && !still) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px' });
    steps.forEach((step) => io.observe(step));
  } else {
    steps.forEach((step) => step.classList.add('is-in'));
  }

  let queued = false;
  window.addEventListener('scroll', () => {
    if (lite || queued) return;
    queued = true;
    requestAnimationFrame(() => {
      queued = false;
      update();
    });
  }, { passive: true });
  window.addEventListener('resize', layout);
  window.addEventListener('load', layout);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
  // Re-measure when the story resizes, or anything above it moves it down
  // the page (the body's height changes with it).
  if ('ResizeObserver' in window) {
    const ro = new ResizeObserver(layout);
    ro.observe(story);
    ro.observe(document.body);
  }
  layout();

  window.XelenicStory = {
    travel,
    // The first stop, where the robot lands when it arrives on the path,
    // and the last, which it leaves from for the next section.
    first: () => (geom ? geom.stops[0] : null),
    last: () => (geom ? geom.stops[geom.stops.length - 1] : null),
  };
})();
