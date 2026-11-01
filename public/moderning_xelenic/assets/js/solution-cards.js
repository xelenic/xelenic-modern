(() => {
  'use strict';

  // "What We Do" solution cards, built as 3D glass slabs: each card flips
  // up into view, floats gently while idle, tilts toward the cursor and
  // pops forward on hover (via CSS variables read by style.css), and each
  // card's icon is a small animated three.js object that spins up while
  // hovered. If the page starts to lag they give all of that up and lie flat.

  const cards = Array.from(document.querySelectorAll('.solution-card'));
  if (!cards.length) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // The lite (phone) page: clean flat cards — no 3D slab, icons, tilt or flip-in.
  const lite = document.documentElement.classList.contains('is-lite');
  const MAX_TILT_X = 12;
  const MAX_TILT_Y = 14;
  const IDLE_TILT_X = 2.5;
  const IDLE_TILT_Y = 3.5;
  const SLAB_LAYERS = 10;
  const SLAB_GAP = 2; // px between layers: a ~20px-thick slab

  const states = cards.map((card, index) => ({
    card,
    index,
    hover: false,
    px: 0.5, // pointer position inside the card, 0..1
    py: 0.5,
    rx: 0, // smoothed tilt (deg), pop and entrance (0..1)
    ry: 0,
    pop: 0,
    enter: 1,
    enterAt: Infinity, // when the entrance flip may start
    raised: false,
    written: '',
    icon: null, // 3D icon, when WebGL is available
    visible: !('IntersectionObserver' in window),
    spin: Math.random() * Math.PI * 2,
  }));

  // ---- Card build-out ----------------------------------------------------
  // Decorative layers: the slab's stacked edge outlines behind the glass
  // face, and a big outlined index number.

  states.forEach((s) => {
    const inner = s.card.querySelector('.card-inner');
    if (!inner) return;
    if (!lite) {
      const slab = document.createElement('div');
      slab.className = 'card-slab';
      slab.setAttribute('aria-hidden', 'true');
      for (let i = 1; i <= SLAB_LAYERS; i++) {
        const layer = document.createElement('i');
        layer.style.transform = `translateZ(${-i * SLAB_GAP}px)`;
        layer.style.opacity = (1 - (i / SLAB_LAYERS) * 0.6).toFixed(2);
        slab.appendChild(layer);
      }
      inner.prepend(slab);
    }

    const number = document.createElement('span');
    number.className = 'card-index';
    number.setAttribute('aria-hidden', 'true');
    number.textContent = String(s.index + 1).padStart(2, '0');
    inner.appendChild(number);
  });

  if (lite) return;

  // Track which cards are on (or near) the screen — only those animate —
  // and flip each card up as it scrolls into view, staggered across a row.
  if ('IntersectionObserver' in window) {
    const near = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        const s = states.find((st) => st.card === entry.target);
        if (s) s.visible = entry.isIntersecting;
      });
    }, { rootMargin: '120px' });

    const columns = () => {
      const grid = cards[0].parentElement;
      return getComputedStyle(grid).gridTemplateColumns.split(' ').length || 1;
    };
    const entering = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const s = states.find((st) => st.card === entry.target);
        if (!s) return;
        s.enterAt = performance.now() + (s.index % columns()) * 130;
        entering.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px' });

    states.forEach((s) => {
      near.observe(s.card);
      if (!reduceMotion) {
        s.enter = 0;
        s.card.style.setProperty('--enter', '0');
        entering.observe(s.card);
      }
    });

    // Build the 3D icons (WebGL context, geometry, shaders) only once the
    // section is getting close, so it doesn't compete with the hero's load.
    const warm = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      warm.disconnect();
      ensureIcons();
    }, { rootMargin: '900px 0px' });
    warm.observe(cards[0].parentElement);
  }

  // ---- Hover tilt --------------------------------------------------------

  if (!reduceMotion) {
    states.forEach((s) => {
      s.card.addEventListener('pointermove', (e) => {
        if (e.pointerType !== 'mouse') return;
        if (!s.hover) {
          s.hover = true;
          s.card.classList.add('is-active');
        }
        if (flat) return;
        // The card itself never tilts (its inner face does), so its rect is
        // a stable frame of reference.
        const r = s.card.getBoundingClientRect();
        s.px = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width));
        s.py = Math.max(0, Math.min(1, (e.clientY - r.top) / r.height));
        s.card.style.setProperty('--mx', (s.px * 100).toFixed(1) + '%');
        s.card.style.setProperty('--my', (s.py * 100).toFixed(1) + '%');
      });
      s.card.addEventListener('pointerleave', () => {
        s.hover = false;
        s.card.classList.remove('is-active');
      });
    });
  }

  const ENTER_MS = 900;
  const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

  function updateTilt(s, time, now, dt) {
    // Hovered: lean toward the pointer. Otherwise: a slow float, each card
    // out of phase with its neighbours, so the slabs always read as 3D.
    const targetX = s.hover
      ? (0.5 - s.py) * 2 * MAX_TILT_X
      : Math.sin(time * 0.7 + s.index * 1.1) * IDLE_TILT_X;
    const targetY = s.hover
      ? (s.px - 0.5) * 2 * MAX_TILT_Y
      : Math.cos(time * 0.55 + s.index * 0.8) * IDLE_TILT_Y;
    // Time-based easing, so it feels the same at any frame rate.
    const ease = 1 - Math.exp(-dt * 6.5);
    s.rx += (targetX - s.rx) * ease;
    s.ry += (targetY - s.ry) * ease;
    s.pop += ((s.hover ? 1 : 0) - s.pop) * ease;
    if (!s.hover && s.pop < 0.002) s.pop = 0;
    if (s.enter < 1 && now >= s.enterAt) {
      s.enter = easeOutCubic(Math.min(1, (now - s.enterAt) / ENTER_MS));
    }

    // The slab and floating layers only read when a card leans well over, so
    // the rest of the time it's one flat layer (see .is-raised in style.css).
    const raised = s.hover || s.pop > 0.01 || (s.enter > 0 && s.enter < 1);
    if (raised !== s.raised) {
      s.raised = raised;
      s.card.classList.toggle('is-raised', raised);
    }

    // Written at a resolution just below what's visible (0.1deg of tilt is
    // ~0.3px at a card's edge), so the slow idle float restyles the card
    // every few frames rather than every frame.
    const rx = s.rx.toFixed(1);
    const ry = s.ry.toFixed(1);
    const pop = s.pop.toFixed(2);
    const enter = s.enter.toFixed(2);
    const key = `${rx}|${ry}|${pop}|${enter}`;
    if (key === s.written) return;
    s.written = key;
    s.card.style.setProperty('--rx', rx + 'deg');
    s.card.style.setProperty('--ry', ry + 'deg');
    s.card.style.setProperty('--pop', pop);
    s.card.style.setProperty('--enter', enter);
  }

  // ---- 3D icons ----------------------------------------------------------

  const ICON_SIZE = 96; // CSS px, matches .card-3d

  // One shared WebGL renderer draws every icon in turn — six icons for a
  // single WebGL context. Where supported it draws into an OffscreenCanvas
  // and hands each frame to the card's canvas as an ImageBitmap, which stays
  // on the GPU; otherwise each frame is copied with drawImage, which on
  // canvases this small means a slow GPU readback. Returns null (keeping
  // the SVG icons) when WebGL isn't available.
  function setupIcons() {
    if (!window.THREE || !window.WebGLRenderingContext) return null;
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const size = Math.round(ICON_SIZE * dpr);
    const options = { alpha: true, antialias: dpr < 2 };
    let renderer;
    let offscreen = null;
    if (typeof OffscreenCanvas !== 'undefined' && 'transferToImageBitmap' in OffscreenCanvas.prototype) {
      try {
        offscreen = new OffscreenCanvas(size, size);
        renderer = new THREE.WebGLRenderer(Object.assign({ canvas: offscreen }, options));
      } catch (err) {
        offscreen = null; // e.g. OffscreenCanvas without WebGL support
      }
    }
    if (!renderer) {
      try {
        renderer = new THREE.WebGLRenderer(options);
      } catch (err) {
        console.warn('Xelenic: 3D card icons disabled —', err);
        return null;
      }
    }
    renderer.setPixelRatio(dpr);
    renderer.setSize(ICON_SIZE, ICON_SIZE, false);
    renderer.setClearColor(0x000000, 0);
    if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 50);
    camera.position.set(0, 0, 6);
    camera.lookAt(0, 0, 0);

    // Same lighting recipe as the hero robot: soft fill, white key, blue rim.
    scene.add(new THREE.HemisphereLight(0xf2f6ff, 0x0a1020, 1.0));
    const key = new THREE.DirectionalLight(0xffffff, 1.3);
    key.position.set(3, 4, 5);
    scene.add(key);
    const rim = new THREE.DirectionalLight(0x3b82f6, 0.8);
    rim.position.set(-4, 2, -3);
    scene.add(rim);

    const builders = { layers, suite, kiosk, target, globe, growth };

    states.forEach((s) => {
      const tile = s.card.querySelector('.card-icon');
      const build = tile && builders[tile.dataset.shape];
      if (!build) return;
      const canvas = document.createElement('canvas');
      canvas.className = 'card-3d';
      canvas.width = canvas.height = size;
      canvas.setAttribute('aria-hidden', 'true');
      tile.appendChild(canvas);
      tile.classList.add('has-3d');

      const shape = build();
      const holder = new THREE.Group();
      holder.add(shape.group);
      holder.visible = false;
      scene.add(holder);
      const ctx = canvas.getContext(offscreen ? 'bitmaprenderer' : '2d');
      s.icon = { shape, holder, ctx };
    });

    // Compile every icon's shaders now rather than on its first frame.
    scene.children.forEach((child) => { child.visible = true; });
    renderer.compile(scene, camera);

    return {
      draw(s, time) {
        const { shape, holder, ctx } = s.icon;
        scene.children.forEach((child) => {
          if (child.isGroup) child.visible = child === holder;
        });
        const h = s.pop;
        holder.position.y = Math.sin(time * 1.4 + s.spin * 0.1) * 0.06;
        holder.scale.setScalar(1 + h * 0.1);
        // Lean a little toward the pointer while hovered.
        holder.rotation.x = (s.py - 0.5) * 0.5 * h;
        holder.rotation.z = -(s.px - 0.5) * 0.3 * h;
        shape.update(time, h, s.spin);
        renderer.render(scene, camera);
        if (offscreen) {
          ctx.transferFromImageBitmap(offscreen.transferToImageBitmap());
        } else {
          ctx.clearRect(0, 0, size, size);
          ctx.drawImage(renderer.domElement, 0, 0, size, size);
        }
      },
      // Back to the SVG icons, and the WebGL context back to the browser.
      dispose() {
        states.forEach((s) => {
          if (!s.icon) return;
          const tile = s.card.querySelector('.card-icon');
          tile.querySelector('.card-3d').remove();
          tile.classList.remove('has-3d');
          s.icon = null;
        });
        renderer.dispose();
        renderer.forceContextLoss();
      },
    };
  }

  // ---- Icon shapes -------------------------------------------------------
  // Each returns { group, update(time, hover, spin) }; hover eases 0..1 and
  // spin is an angle that advances faster while the card is hovered.

  function srgb(hex) {
    return new THREE.Color(hex).convertSRGBToLinear();
  }
  function glossy(hex, extra) {
    return new THREE.MeshPhysicalMaterial(Object.assign({
      color: srgb(hex),
      roughness: 0.32,
      metalness: 0.08,
      clearcoat: 1,
      clearcoatRoughness: 0.18,
    }, extra));
  }
  let shared;
  function mats() {
    if (!shared) {
      shared = {
        accent: glossy(0x3b82f6),
        accentLight: glossy(0x60a5fa),
        accentPale: glossy(0x93c5fd),
        cream: glossy(0xf1f5ff, { roughness: 0.4 }),
        metal: new THREE.MeshStandardMaterial({ color: srgb(0xd4d8e0), roughness: 0.3, metalness: 0.55 }),
        dark: new THREE.MeshStandardMaterial({ color: srgb(0x15171d), roughness: 0.2, metalness: 0.3 }),
        white: new THREE.MeshBasicMaterial({ color: srgb(0xffffff) }),
      };
    }
    return shared;
  }

  // AI Middleware — a stack of hexagonal layers that spread apart.
  function layers() {
    const m = mats();
    const group = new THREE.Group();
    const geo = new THREE.CylinderGeometry(0.92, 0.92, 0.22, 6);
    const plates = [m.accent, m.cream, m.accent].map((mat) => {
      const plate = new THREE.Mesh(geo, mat);
      group.add(plate);
      return plate;
    });
    return {
      group,
      update(time, h, spin) {
        group.rotation.set(0.55, spin, 0);
        const gap = 0.4 + h * 0.22;
        plates.forEach((plate, i) => {
          plate.position.y = (1 - i) * gap + Math.sin(time * 1.8 + i) * 0.03;
          plate.rotation.y = (i - 1) * h * 0.5;
        });
      },
    };
  }

  // Business Solutions Suite — four connected modules; one lifts out.
  function suite() {
    const m = mats();
    const group = new THREE.Group();
    const geo = new THREE.BoxGeometry(0.62, 0.62, 0.62);
    const cubes = [m.accent, m.cream, m.cream, m.accent].map((mat) => {
      const cube = new THREE.Mesh(geo, mat);
      group.add(cube);
      return cube;
    });
    return {
      group,
      update(time, h, spin) {
        group.rotation.set(0.5, spin, 0);
        const gap = 0.35 + h * 0.14;
        cubes.forEach((cube, i) => {
          const x = i % 2 ? gap : -gap;
          const z = i < 2 ? -gap : gap;
          const lift = i === 3 ? h * 0.38 : 0;
          cube.position.set(x, Math.sin(time * 1.6 + i * 1.3) * 0.04 + lift, z);
          cube.rotation.y = i === 3 ? h * Math.PI * 0.5 : 0;
        });
      },
    };
  }

  // AI-Based Kiosk Apps — a little kiosk with a face like the hero robot.
  function kiosk() {
    const m = mats();
    const group = new THREE.Group();
    const body = new THREE.Mesh(new THREE.BoxGeometry(0.95, 1.5, 0.5), m.accent);
    body.position.y = 0.12;
    const screenMat = m.dark.clone();
    const screen = new THREE.Mesh(new THREE.BoxGeometry(0.72, 0.74, 0.04), screenMat);
    screen.position.set(0, 0.36, 0.26);
    const eyeGeo = new THREE.CapsuleGeometry(0.05, 0.1, 4, 10);
    const eyes = [-0.13, 0.13].map((x) => {
      const eye = new THREE.Mesh(eyeGeo, m.white);
      eye.position.set(x, 0.38, 0.29);
      return eye;
    });
    const slot = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.07, 0.04), m.metal);
    slot.position.set(0, -0.28, 0.26);
    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.6, 0.66, 0.12, 32), m.metal);
    base.position.y = -0.7;
    group.add(body, screen, slot, base, ...eyes);
    const glow = srgb(0x3b82f6);
    return {
      group,
      update(time, h, spin) {
        // Sway rather than spin, so the screen keeps facing the viewer.
        group.rotation.set(0.12, Math.sin(spin * 0.8) * 0.55, 0);
        const blink = time % 3.4 < 0.12 ? 0.15 : 1;
        eyes.forEach((eye) => { eye.scale.y = blink; });
        screenMat.emissive.copy(glow).multiplyScalar(h * 0.22);
      },
    };
  }

  // Marketing Technology Strategy — a target that an arrow keeps hitting.
  function target() {
    const m = mats();
    const group = new THREE.Group();
    const rings = [[0.95, m.accent], [0.64, m.cream], [0.33, m.accent]].map(([radius, mat]) => {
      const ring = new THREE.Mesh(new THREE.TorusGeometry(radius, 0.09, 16, 56), mat);
      group.add(ring);
      return ring;
    });
    const bull = new THREE.Mesh(new THREE.SphereGeometry(0.13, 24, 16), m.accentLight);
    group.add(bull);

    const arrow = new THREE.Group();
    const tip = new THREE.Mesh(new THREE.ConeGeometry(0.09, 0.24, 16), m.metal);
    tip.rotation.x = Math.PI;
    tip.position.y = 0.12;
    const shaft = new THREE.Mesh(new THREE.CylinderGeometry(0.032, 0.032, 1.0, 12), m.metal);
    shaft.position.y = 0.74;
    const fin = new THREE.Mesh(new THREE.ConeGeometry(0.12, 0.26, 3), m.accent);
    fin.position.y = 1.2;
    arrow.add(tip, shaft, fin);
    const dir = new THREE.Vector3(0.5, 0.42, 1).normalize();
    arrow.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), dir);
    group.add(arrow);

    return {
      group,
      update(time, h, spin) {
        group.rotation.set(-0.12, Math.sin(spin * 0.7) * 0.4, 0);
        // While hovered: fly in, hit, and the rings ripple from the impact.
        const phase = (time * 0.75) % 1;
        const flight = phase < 0.45 ? Math.pow(1 - phase / 0.45, 2) : 0;
        arrow.position.copy(dir).multiplyScalar(flight * 1.3 * h);
        const ripple = phase >= 0.45 ? Math.sin(((phase - 0.45) / 0.55) * Math.PI) : 0;
        rings.forEach((ring, i) => {
          ring.scale.setScalar(1 + ripple * h * (0.1 - i * 0.025));
        });
      },
    };
  }

  // Open Source Engineering — a faceted globe with an orbiting satellite.
  function globe() {
    const m = mats();
    const group = new THREE.Group();
    const ball = new THREE.Mesh(new THREE.IcosahedronGeometry(0.78, 1), glossy(0x3b82f6, { flatShading: true }));
    const orbit = new THREE.Group();
    orbit.rotation.set(1.2, 0, 0.35);
    const ring = new THREE.Mesh(new THREE.TorusGeometry(1.12, 0.03, 10, 72), m.metal);
    const moon = new THREE.Mesh(new THREE.SphereGeometry(0.12, 18, 12), m.cream);
    orbit.add(ring, moon);
    group.add(ball, orbit);
    let angle = 0;
    let last = 0;
    return {
      group,
      update(time, h, spin) {
        ball.rotation.set(0.3, spin, 0);
        angle += (time - last) * (0.9 + h * 2.6);
        last = time;
        moon.position.set(Math.cos(angle) * 1.12, Math.sin(angle) * 1.12, 0);
      },
    };
  }

  // Business Growth Consulting — rising bars that grow in a wave.
  function growth() {
    const m = mats();
    const group = new THREE.Group();
    const base = new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.1, 0.62), m.metal);
    base.position.y = -0.8;
    group.add(base);
    const heights = [0.55, 0.95, 1.4];
    const geo = new THREE.BoxGeometry(0.4, 1, 0.4);
    const bars = [m.accentPale, m.accentLight, m.accent].map((mat, i) => {
      const bar = new THREE.Mesh(geo, mat);
      bar.position.x = (i - 1) * 0.52;
      group.add(bar);
      return bar;
    });
    return {
      group,
      update(time, h, spin) {
        group.rotation.set(0.25, Math.sin(spin * 0.7) * 0.6, 0);
        bars.forEach((bar, i) => {
          const wave = 0.5 + 0.5 * Math.sin(time * 3 - i * 0.7);
          const height = heights[i] * (1 + h * 0.22 * wave);
          bar.scale.y = height;
          bar.position.y = -0.75 + height / 2;
        });
      },
    };
  }

  // ---- Loop --------------------------------------------------------------

  let icons = null;
  let iconsTried = false;
  let flat = false;
  function ensureIcons() {
    if (iconsTried) return;
    iconsTried = true;
    icons = setupIcons();
    // Reduced motion: a still pose, drawn once.
    if (icons && reduceMotion) states.forEach((s) => s.icon && icons.draw(s, 0));
  }
  if (!('IntersectionObserver' in window)) ensureIcons();

  if (reduceMotion) return;

  // When the page lags the cards lie flat for the rest of the visit. The watch
  // covers the whole page, so a slow machine has flat cards before it reaches them.
  function goFlat() {
    flat = true;
    iconsTried = true; // if they aren't built yet, they never will be
    if (icons) icons.dispose();
    icons = null;
    states.forEach((s) => {
      s.card.classList.remove('is-raised');
      s.card.classList.add('is-flat');
      s.card.style.setProperty('--pop', '0');
      s.card.style.setProperty('--enter', '1');
    });
  }
  const watchFrames = window.XelenicFrameBudget(goFlat, window.XelenicFrameBudget.QUICK);

  let time = 0;
  let last = null;
  let frame = 0;
  function tick(now) {
    watchFrames(now);
    if (flat) return;
    requestAnimationFrame(tick);
    // A frame's timestamp can predate this script's start, so never let
    // the step go negative (it would fling the easing) or jump after a stall.
    const dt = last === null ? 0 : Math.max(0, Math.min(0.05, (now - last) / 1000));
    last = now;
    time += dt;
    frame++;
    states.forEach((s) => {
      if (!s.visible) return;
      updateTilt(s, time, now, dt);
      if (icons && s.icon) {
        s.spin += dt * (0.45 + s.pop * 1.8);
        // Idle icons turn slowly, so every other frame is plenty; the
        // hovered one gets every frame.
        if (s.hover || s.pop > 0 || frame % 2 === 0) icons.draw(s, time);
      }
    });
  }
  requestAnimationFrame(tick);
})();
