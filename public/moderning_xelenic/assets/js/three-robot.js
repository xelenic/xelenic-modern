(() => {
  'use strict';

  const canvas = document.getElementById('hero-robot-canvas');

  if (canvas && window.THREE && window.THREE.GLTFLoader && window.WebGLRenderingContext) {
    try {
      initRobot(canvas);
    } catch (err) {
      console.warn('Xelenic: hero robot disabled —', err);
      canvas.style.display = 'none';
    }
  } else if (canvas) {
    canvas.style.display = 'none';
  }

  function initRobot(canvas) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const ACCENT = 0x3b82f6;
    // The robot's body color (the model's own "Plastik" material is orange).
    const BODY_COLOR = 0x1d4ed8;

    // Multisampling is skipped on high-density screens, where the extra
    // pixels already smooth the edges and MSAA would only cost GPU time.
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: dpr < 2 });
    renderer.setPixelRatio(dpr);
    if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;

    // Most of the robot's cost is the pixels it shades, so on a machine that
    // can't keep up it draws fewer of them.
    let pixelRatio = dpr;
    const watchFrames = window.XelenicFrameBudget(() => {
      if (pixelRatio <= 1) return;
      pixelRatio = Math.max(1, pixelRatio - 0.5);
      renderer.setPixelRatio(pixelRatio);
    });

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 100);

    // Soft ambient fill + a clear key light + an accent rim light that echoes
    // the site's blue brand color without touching any material color.
    scene.add(new THREE.HemisphereLight(0xf2f6ff, 0x0a1020, 1.0));
    const key = new THREE.DirectionalLight(0xffffff, 1.2);
    key.position.set(3, 1, 5);
    scene.add(key);
    const rim = new THREE.DirectionalLight(ACCENT, 0.7);
    rim.position.set(-4, 1.5, -3);
    scene.add(rim);

    const robotGroup = new THREE.Group();
    scene.add(robotGroup);

    let modelReady = false;

    const FLOAT_AMPLITUDE = 0.06;
    // How far the robot turns and nods to look at the cursor (radians), and
    // the slight three-quarter pose it rests in when there's no cursor.
    const MAX_YAW = 0.75;
    const MAX_PITCH = 0.12;
    const REST_YAW = 0.35;
    // Normalized model vertices as flat (height, radius around the Y axis)
    // pairs, measured once on load — all the framing math needs.
    let modelProfile = new Float32Array(0);
    let feetY = -1;
    let headY = 1;
    let sweepR = 1; // widest the robot reaches from its vertical axis

    // The robot's journey down the page, tied to scroll. It starts on
    // .hero-visual; as the value strip rises into view it glides left to
    // land beside the "What We Do" heading (.solutions-visual); scrolling on,
    // it travels with the reader to the right side of "Why Xelenic"
    // (.why-visual), where CRM / ERP / POS / AI orbit around it. The canvas
    // keeps the hero spot's pixel size and is moved and scaled with a CSS
    // transform in page coordinates, so while it rests at a stop it scrolls
    // with the page natively — no per-frame repositioning.
    const heroVisual = document.querySelector('.hero-visual');
    const valueStrip = document.querySelector('.value-strip');
    const dock = document.querySelector('.solutions-visual');
    const whySpot = document.querySelector('.why-visual');
    // After that it flies down onto the "How We Work" story path and walks
    // the road from step to step with the reader (story.js owns the road).
    const storyBox = document.querySelector('.story');
    // Its last stop is the "Our Foundation" tech field, where it again drifts
    // after the mouse and the tech chips spring aside to make room for it
    // (tech.js reads where it is from window.XelenicRobot).
    const techSpot = document.querySelector('.tech-spot');
    const techField = techSpot && techSpot.closest('.tech-field');
    const techSection = techSpot && techSpot.closest('section');
    // And finally it flies on to stand beside the Xelenic logo in the "Ready
    // to Grow" call to action, its feet on the bottom of .cta-robot-spot.
    const ctaSpot = document.querySelector('.cta-robot-spot');
    const ctaSection = ctaSpot && ctaSpot.closest('.cta');
    // Between those two stops the robot slowly drifts after the mouse while
    // it's over the "What We Do" section, staying inside its content area —
    // behind the cards — and floats back on course when the mouse leaves.
    const followSection = dock && dock.closest('section');
    const followBounds = dock && dock.closest('.container');
    const FOLLOW_EASE = 0.03;

    const TOP_EDGE = 0.98;
    let baseWidth = 0;
    let baseHeight = 0;
    // Vertical span the robot can occupy inside the canvas, as fractions of
    // its height from the top, and its widest extent as a fraction of the
    // canvas width — set by frameModel().
    let bandTop = 0;
    let bandBottom = 1;
    let bandWidth = 1;
    // Camera framings: tight on the robot alone (hero and "What We Do"), and
    // pulled back to fit the orbit around it ("Why Xelenic").
    let robotFrame = { distance: 6, lift: 0 };
    let orbitFrame = { distance: 9, lift: 0 };
    // ...and on the story path, a roomy centered framing that fits the robot
    // seen from any angle, with where its feet and head fall in it.
    let freeFrame = { distance: 8, lift: 0 };
    let footFrac = 0.8;
    let freeHeightFrac = 0.5;
    let storyRobotSize = 200;
    let techPose = null;
    let ctaPose = null;
    let ctaMid = 0; // page y of the CTA spot's middle
    let techArea = null;
    let techBox = null;
    let heroPose = { x: 0, y: 0, s: 1 };
    let dockPose = null;
    let whyPose = null;
    let flightStart = 0;
    let flightEnd = 1;
    let flight2Start = 0;
    let flight2End = 1;
    let flight3Start = 0;
    let lastTransform = '';
    let docked = false;
    let followArea = null;
    let followBox = null;
    // Last pointer position in client coords, and whether it's a mouse (only
    // a mouse is followed); null when the pointer is away.
    let pointer = null;
    let framed = false;
    let followX = 0;
    let followY = 0;
    let lean = 0;

    // Page position from layout alone (offsets), so transforms such as the
    // scroll-reveal slide on .why-visual can't skew where the robot lands.
    const pageRect = (el) => {
      let left = 0;
      let top = 0;
      for (let node = el; node; node = node.offsetParent) {
        left += node.offsetLeft;
        top += node.offsetTop;
      }
      return { left, top, width: el.offsetWidth, height: el.offsetHeight };
    };
    const clamp01 = (t) => Math.max(0, Math.min(1, t));
    const lerp = (a, b, t) => a + (b - a) * t;
    const smoothstep = (t) => t * t * (3 - 2 * t);
    const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
    // A soft overshoot (peaks ~6% past 1) for the orbit bursting open.
    const easeOutBack = (t) => 1 + 2.2 * Math.pow(t - 1, 3) + 1.2 * Math.pow(t - 1, 2);
    const ORBIT_OVERSHOOT = 1.06;

    // ---- "Why Xelenic" orbit ------------------------------------------------
    // Four labels on tilted rings circle the robot like an atom, passing in
    // front of and behind it, over a pulsing halo at its feet. It's built in
    // the robot's own scene so the robot genuinely hides whatever passes
    // behind it. Rings stay clear of the robot's bounding sphere (radius 1.6).
    const ORBITS = [
      { label: 'CRM', radius: 1.68, tiltX: 0.32, tiltZ: 0.78, speed: 0.5, phase: 0.3 },
      { label: 'ERP', radius: 1.78, tiltX: 0.26, tiltZ: -0.74, speed: -0.42, phase: 2.0 },
      { label: 'POS', radius: 1.72, tiltX: 0.5, tiltZ: 0.6, speed: 0.56, phase: 3.6 },
      { label: 'AI', radius: 1.86, tiltX: 0.18, tiltZ: -0.55, speed: -0.36, phase: 5.0 },
    ];
    const LABEL_HEIGHT = 0.3; // world units
    const orbit = buildOrbit();
    scene.add(orbit.group);

    function buildOrbit() {
      const group = new THREE.Group();
      group.visible = false;
      const accent = new THREE.Color(ACCENT).convertSRGBToLinear();

      const ringMaterial = new THREE.LineDashedMaterial({
        color: accent, dashSize: 0.07, gapSize: 0.06, transparent: true, opacity: 0, depthWrite: false,
      });
      const dotMaterial = new THREE.MeshBasicMaterial({ color: accent, transparent: true, opacity: 0 });
      const dotGeometry = new THREE.SphereGeometry(0.04, 12, 8);

      const rings = ORBITS.map((spec) => {
        const ring = new THREE.Group();
        ring.rotation.set(spec.tiltX, 0, spec.tiltZ);
        const points = [];
        for (let i = 0; i < 128; i++) {
          const a = (i / 128) * Math.PI * 2;
          points.push(new THREE.Vector3(Math.cos(a) * spec.radius, 0, Math.sin(a) * spec.radius));
        }
        const line = new THREE.LineLoop(new THREE.BufferGeometry().setFromPoints(points), ringMaterial);
        line.computeLineDistances();

        const labelCanvas = document.createElement('canvas');
        const texture = new THREE.CanvasTexture(labelCanvas);
        if ('encoding' in texture) texture.encoding = THREE.sRGBEncoding;
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
          map: texture, transparent: true, depthWrite: false, opacity: 0,
        }));
        // A small glowing "electron" trails opposite each label.
        const dot = new THREE.Mesh(dotGeometry, dotMaterial);
        ring.add(line, sprite, dot);
        group.add(ring);
        return Object.assign({ ring, sprite, dot, labelCanvas, texture }, spec);
      });

      // Halo at the robot's feet: a soft glow, a ring, and a ripple that
      // keeps pulsing outward.
      const floor = new THREE.Group();
      const glowCanvas = document.createElement('canvas');
      glowCanvas.width = glowCanvas.height = 128;
      const g = glowCanvas.getContext('2d');
      const gradient = g.createRadialGradient(64, 64, 0, 64, 64, 64);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.55)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
      g.fillStyle = gradient;
      g.fillRect(0, 0, 128, 128);
      const glowTexture = new THREE.CanvasTexture(glowCanvas);
      if ('encoding' in glowTexture) glowTexture.encoding = THREE.sRGBEncoding;
      const flat = (mesh) => { mesh.rotation.x = -Math.PI / 2; return mesh; };
      const glow = flat(new THREE.Mesh(
        new THREE.CircleGeometry(1.25, 48),
        new THREE.MeshBasicMaterial({ map: glowTexture, transparent: true, opacity: 0, depthWrite: false })
      ));
      const ringGeometry = new THREE.RingGeometry(0.84, 0.88, 72);
      const halo = flat(new THREE.Mesh(ringGeometry, new THREE.MeshBasicMaterial({
        color: accent, transparent: true, opacity: 0, side: THREE.DoubleSide, depthWrite: false,
      })));
      const ripple = flat(new THREE.Mesh(ringGeometry, new THREE.MeshBasicMaterial({
        color: accent, transparent: true, opacity: 0, side: THREE.DoubleSide, depthWrite: false,
      })));
      floor.add(glow, halo, ripple);
      group.add(floor);

      // Pill labels drawn to match the page theme (redrawn if it changes).
      function drawLabels() {
        const styles = getComputedStyle(document.documentElement);
        const background = styles.getPropertyValue('--bg-alt').trim() || '#0a0e1a';
        const ink = styles.getPropertyValue('--accent').trim() || '#3b82f6';
        const inkRgb = styles.getPropertyValue('--accent-rgb').trim() || '59, 130, 246';
        rings.forEach((r) => {
          const font = '700 36px Inter, system-ui, sans-serif';
          let ctx = r.labelCanvas.getContext('2d');
          ctx.font = font;
          const h = 72;
          const w = Math.ceil(ctx.measureText(r.label).width) + 78;
          r.labelCanvas.width = w;
          r.labelCanvas.height = h;
          ctx = r.labelCanvas.getContext('2d');
          const rad = h / 2 - 3;
          ctx.beginPath();
          ctx.moveTo(3 + rad, 3);
          ctx.lineTo(w - 3 - rad, 3);
          ctx.arc(w - 3 - rad, h / 2, rad, -Math.PI / 2, Math.PI / 2);
          ctx.lineTo(3 + rad, h - 3);
          ctx.arc(3 + rad, h / 2, rad, Math.PI / 2, (Math.PI * 3) / 2);
          ctx.closePath();
          ctx.fillStyle = background;
          ctx.fill();
          ctx.lineWidth = 3;
          ctx.strokeStyle = `rgba(${inkRgb}, 0.6)`;
          ctx.stroke();
          ctx.fillStyle = ink;
          ctx.beginPath();
          ctx.arc(30, h / 2, 6, 0, Math.PI * 2);
          ctx.fill();
          ctx.font = font;
          ctx.textBaseline = 'middle';
          ctx.fillText(r.label, 46, h / 2 + 2);
          r.texture.needsUpdate = true;
          r.sprite.scale.set((LABEL_HEIGHT * w) / h, LABEL_HEIGHT, 1);
        });
      }
      drawLabels();
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(drawLabels);
      const scheme = window.matchMedia('(prefers-color-scheme: light)');
      if (scheme.addEventListener) scheme.addEventListener('change', drawLabels);

      const world = new THREE.Vector3();
      return {
        group,
        rings,
        // reveal: 0 hidden → 1 fully open (scroll-driven, so it reverses).
        update(time, reveal) {
          group.visible = reveal > 0.001;
          if (!group.visible) return;
          const open = Math.max(0.001, easeOutBack(reveal));
          ringMaterial.opacity = 0.42 * reveal;
          dotMaterial.opacity = reveal;
          rings.forEach((r) => {
            r.ring.scale.setScalar(open);
            const a = r.phase + time * r.speed;
            r.sprite.position.set(Math.cos(a) * r.radius, 0, Math.sin(a) * r.radius);
            r.dot.position.set(-Math.cos(a) * r.radius, 0, -Math.sin(a) * r.radius);
            // Labels on the far side of the robot dim a little, for depth.
            r.sprite.getWorldPosition(world);
            const front = clamp01((world.z / r.radius + 1) / 2);
            r.sprite.material.opacity = reveal * (0.5 + 0.5 * front);
          });
          floor.position.y = feetY - 0.03;
          glow.material.opacity = reveal * (0.75 + 0.25 * Math.sin(time * 2.2));
          halo.material.opacity = 0.75 * reveal;
          const pulse = (time * 0.45) % 1;
          ripple.scale.setScalar(1 + pulse * 1.3);
          ripple.material.opacity = (1 - pulse) * 0.6 * reveal;
        },
      };
    }

    // Pull the camera back just far enough that the whole model — head to
    // feet, arms included — stays in frame at the current canvas aspect,
    // while it turns, nods and floats. Each vertex is assumed to swing as
    // close to the camera (and as high) as any turn plus the largest nod
    // allows, so no pose can push an edge out of view. Then lift the model
    // until its highest point meets the top edge, so it sits at the top of
    // its area, not mid-frame. Also works out the wider, centered framing
    // that fits the robot inside its orbit.
    function frameModel() {
      if (!modelReady) return;
      const tanV = Math.tan((camera.fov * Math.PI) / 360);
      const tanH = tanV * camera.aspect;
      const sinH = Math.sin(Math.atan(tanH));
      const cosP = Math.cos(MAX_PITCH);
      const sinP = Math.sin(MAX_PITCH);
      let fit = 0;
      for (let i = 0; i < modelProfile.length; i += 2) {
        const y = Math.abs(modelProfile[i]);
        const r = modelProfile[i + 1];
        const height = y * cosP + r * sinP + FLOAT_AMPLITUDE;
        const near = y * sinP + r * cosP;
        fit = Math.max(fit, near + height / tanV, y * sinP + r / sinH);
      }
      const distance = fit * 1.03;

      const topEdge = tanV * TOP_EDGE;
      let lift = Infinity;
      for (let i = 0; i < modelProfile.length; i += 2) {
        const y = modelProfile[i];
        const r = modelProfile[i + 1];
        const top = y * cosP + r * sinP + FLOAT_AMPLITUDE;
        const near = Math.abs(y) * sinP + r * cosP;
        lift = Math.min(lift, topEdge * (distance - near) - top);
      }
      lift = Math.max(0, lift);
      robotFrame = { distance, lift };

      // How low and how wide the robot can reach in that frame, so the
      // landing spot (and the follow bounds) fit the robot itself rather
      // than the whole canvas.
      let bottom = 0;
      let side = 0;
      for (let i = 0; i < modelProfile.length; i += 2) {
        const y = modelProfile[i];
        const r = modelProfile[i + 1];
        const below = -(y - r * sinP - FLOAT_AMPLITUDE + lift);
        const near = Math.abs(y) * sinP + r * cosP;
        bottom = Math.max(bottom, below / ((distance - near) * tanV));
        side = Math.max(side, r / ((distance - near) * tanH));
      }
      bandTop = (1 - TOP_EDGE) / 2;
      bandBottom = Math.min(1, (1 + bottom) / 2);
      bandWidth = Math.min(1, side);

      // Orbit framing, centered: far enough back that every ring and label
      // (at its overshoot) plus the robot — bobbing and swaying more here —
      // fits within 90% of the canvas.
      const FILL = 0.9;
      const fillV = FILL * tanV;
      const fillH = FILL * tanH;
      let far = 0;
      for (let i = 0; i < modelProfile.length; i += 2) {
        const y = Math.abs(modelProfile[i]);
        const r = modelProfile[i + 1];
        const height = y * cosP + r * sinP + FLOAT_AMPLITUDE * 2.4;
        far = Math.max(far, r + height / fillV, r + (r + y * 0.06) / fillH);
      }
      const p = new THREE.Vector3();
      orbit.rings.forEach((r) => {
        r.ring.updateMatrix();
        const halfW = r.sprite.scale.x / 2;
        const halfH = r.sprite.scale.y / 2;
        for (let i = 0; i < 48; i++) {
          const a = (i / 48) * Math.PI * 2;
          p.set(Math.cos(a), 0, Math.sin(a)).multiplyScalar(r.radius * ORBIT_OVERSHOOT).applyMatrix4(r.ring.matrix);
          far = Math.max(
            far,
            p.z + (Math.abs(p.x) + halfW * ORBIT_OVERSHOOT) / fillH,
            p.z + (Math.abs(p.y) + halfH * ORBIT_OVERSHOOT) / fillV
          );
        }
      });
      orbitFrame = { distance: far, lift: 0 };

      // Story framing: a sphere around the robot's bounding sphere (radius
      // 1.6) plus room for its hops, so it can turn its side, back, top or
      // underside to the camera without ever being cut off.
      const freeDistance = ((1.6 + 0.4) * 1.04) / Math.sin(Math.atan(Math.min(tanV, tanH)));
      freeFrame = { distance: freeDistance, lift: 0 };
      footFrac = 0.5 + Math.abs(feetY) / (2 * freeDistance * tanV);
      freeHeightFrac = (headY - feetY) / (2 * freeDistance * tanV);
      aimCamera(0, 0);
    }

    // Blend the camera from the tight robot framing toward the orbit framing,
    // then toward the story framing.
    function aimCamera(toOrbit, toStory) {
      const distance = lerp(lerp(robotFrame.distance, orbitFrame.distance, toOrbit), freeFrame.distance, toStory);
      const lift = lerp(lerp(robotFrame.lift, orbitFrame.lift, toOrbit), freeFrame.lift, toStory);
      camera.position.set(0, -lift, distance);
      camera.lookAt(0, -lift, 0);
    }

    // Measure every stop in page coordinates and work out the scroll range
    // of each flight. Runs on load and whenever layout changes.
    function layout() {
      if (!heroVisual) return;
      const hero = pageRect(heroVisual);
      if (!hero.width || !hero.height) return;

      if (hero.width !== baseWidth || hero.height !== baseHeight) {
        baseWidth = hero.width;
        baseHeight = hero.height;
        canvas.style.width = baseWidth + 'px';
        canvas.style.height = baseHeight + 'px';
        camera.aspect = baseWidth / baseHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(baseWidth, baseHeight, false);
        framed = false;
      }
      // The framing only depends on the canvas shape, so it's redone only
      // when that changes (or the model first arrives).
      if (!framed && modelReady) {
        frameModel();
        framed = true;
        // Let the hero backdrop center itself on the robot's middle.
        heroVisual.style.setProperty('--robot-cy', (((bandTop + bandBottom) / 2) * 100).toFixed(1) + '%');
      }
      heroPose = { x: hero.left, y: hero.top, s: 1 };
      const vh = window.innerHeight;

      dockPose = null;
      whyPose = null;
      if (dock && modelReady) {
        const spot = pageRect(dock);
        // Scale so the robot itself fills the spot's height, centered on it.
        const s = spot.height / ((bandBottom - bandTop) * baseHeight);
        dockPose = {
          x: spot.left + (spot.width - baseWidth * s) / 2,
          y: spot.top - bandTop * baseHeight * s,
          s,
        };
        // Take off just before the value strip rises into view; land as the
        // spot reaches the middle of the screen.
        flightStart = valueStrip ? Math.max(0, pageRect(valueStrip).top - vh * 1.2) : 0;
        flightEnd = Math.max(flightStart + vh * 0.4, spot.top + spot.height / 2 - vh / 2);
        followArea = followSection ? pageRect(followSection) : null;
        followBox = followBounds ? pageRect(followBounds) : null;

        const why = whySpot && pageRect(whySpot);
        if (why && why.width && why.height) {
          // The whole canvas (robot plus orbit) fits the spot, centered. Its
          // corners are empty, so it may spill a little past the column
          // sides (never past the page gutter) to show the robot larger.
          const roomW = Math.min(why.width * 1.12, why.width + 2 * Math.min(why.left, 40));
          const ws = Math.min(roomW / baseWidth, why.height / baseHeight);
          whyPose = {
            x: why.left + (why.width - baseWidth * ws) / 2,
            y: why.top + (why.height - baseHeight * ws) / 2,
            s: ws,
          };
          // Set off soon after landing, as the reader moves on through the
          // cards, and arrive as the spot reaches the middle of the screen —
          // so the robot keeps the reader company the whole way down.
          flight2Start = Math.max(flightEnd + vh * 0.1, spot.top + spot.height / 2 - vh * 0.35);
          flight2End = Math.max(flight2Start + vh * 0.5, why.top + why.height / 2 - vh / 2);
          // Leave the orbit as the reader moves on; land on the path's first
          // stop as it reaches mid-screen (see placeRobot).
          flight3Start = Math.max(flight2End + vh * 0.1, why.top + why.height / 2 - vh * 0.2);
        }
      }
      if (storyBox) {
        storyRobotSize = parseFloat(getComputedStyle(storyBox).getPropertyValue('--robot-size')) || 200;
      }
      techPose = null;
      if (techSpot && modelReady) {
        // Centered on the tech field's spot, in the story's all-angles framing.
        const spot = pageRect(techSpot);
        const size = parseFloat(getComputedStyle(techField).getPropertyValue('--robot-size')) || 220;
        const s = size / (freeHeightFrac * baseHeight);
        techPose = {
          x: spot.left + spot.width / 2 - (baseWidth * s) / 2,
          y: spot.top + spot.height / 2 - (baseHeight * s) / 2,
          s,
        };
        techArea = techSection ? pageRect(techSection) : null;
        techBox = pageRect(techField);
      }
      ctaPose = null;
      if (ctaSpot && modelReady && ctaSpot.offsetHeight) {
        const spot = pageRect(ctaSpot);
        const size = parseFloat(getComputedStyle(ctaSection).getPropertyValue('--robot-size')) || 200;
        const s = size / (freeHeightFrac * baseHeight);
        ctaPose = {
          x: spot.left + spot.width / 2 - (baseWidth * s) / 2,
          y: spot.top + spot.height - footFrac * baseHeight * s,
          s,
        };
        ctaMid = spot.top + spot.height / 2;
      }
      lastTransform = '';
    }

    // How far the robot should move from (cx, cy), its center on course, to
    // sit under the mouse — kept inside `box` given its half size. Zero when
    // the mouse isn't over `area` (the section).
    function followOffset(area, box, cx, cy, halfW, halfH) {
      if (!pointer || !pointer.mouse || !area || !box) return [0, 0];
      const px = pointer.x + window.scrollX;
      const py = pointer.y + window.scrollY;
      if (py < area.top || py > area.top + area.height) return [0, 0];
      const within = (v, lo, hi) => (lo > hi ? (lo + hi) / 2 : Math.max(lo, Math.min(hi, v)));
      return [
        within(px, box.left + halfW, box.left + box.width - halfW) - cx,
        within(py, box.top + halfH, box.top + box.height - halfH) - cy,
      ];
    }
    // ...in "What We Do", using the tight robot framing's bands.
    function followTarget(course) {
      const s = course.s;
      return followOffset(
        followArea, followBox,
        course.x + (baseWidth * s) / 2,
        course.y + ((bandTop + bandBottom) / 2) * baseHeight * s,
        (bandWidth * baseWidth * s) / 2,
        ((bandBottom - bandTop) * baseHeight * s) / 2
      );
    }
    // ...in the tech field, where the robot is centered in its canvas. Here
    // it keeps a little way off the cursor, on the side toward the field's
    // center, like a companion at your shoulder — so it never sits under the
    // pointer and shoves away the chip you're reaching for.
    function techFollowTarget(course) {
      const half = (freeHeightFrac * baseHeight * course.s) / 2;
      const cx = course.x + (baseWidth * course.s) / 2;
      const cy = course.y + (baseHeight * course.s) / 2;
      const [dx, dy] = followOffset(techArea, techBox, cx, cy, half * 0.9, half);
      if (!dx && !dy) return [0, 0];
      const toCenterX = techBox.left + techBox.width / 2 - (cx + dx);
      const toCenterY = techBox.top + techBox.height / 2 - (cy + dy);
      const dist = Math.hypot(toCenterX, toCenterY);
      const keep = Math.min(dist, half + 70) / (dist || 1);
      return [dx + toCenterX * keep, dy + toCenterY * keep];
    }

    // One leg of the journey: x and scale ease in and out, y follows its own
    // curve (so the path arcs), and a gentle swell mid-flight, as if it hops
    // toward the viewer — grown about its own center so the path stays smooth.
    function fly(from, to, t, yCurve) {
      const e = easeInOutCubic(t);
      const s = lerp(from.s, to.s, e);
      const grown = s * (1 + 0.08 * Math.sin(Math.PI * e));
      return {
        x: lerp(from.x, to.x, e) - (baseWidth * (grown - s)) / 2,
        y: lerp(from.y, to.y, yCurve(t)) - (baseHeight * (grown - s)) / 2,
        s: grown,
      };
    }
    // First leg: the drop starts sooner than the sideways glide, so the robot
    // doesn't ride up under the header with the page before it gets going.
    // Second leg: mostly steady, so the robot stays level with the reader.
    const firstLegY = smoothstep;
    const secondLegY = (t) => 0.6 * t + 0.4 * smoothstep(t);

    // Canvas pose that stands the robot's feet on a point of the story road,
    // at the size set by the story's --robot-size.
    function storyPose(point) {
      const s = storyRobotSize / (freeHeightFrac * baseHeight);
      return {
        x: point.x - (baseWidth * s) / 2,
        y: point.y - footFrac * baseHeight * s,
        s,
      };
    }

    // Move the canvas along its course for the current scroll position.
    // Returns the eased progress of each flight, where the robot is on the
    // story road (once it's there) and the pose it was placed at.
    function placeRobot() {
      const progress = (start, end) => clamp01((window.scrollY - start) / (end - start));
      const t1 = dockPose ? progress(flightStart, flightEnd) : 0;
      const t2 = dockPose && whyPose ? progress(flight2Start, flight2End) : 0;
      const story = window.XelenicStory;
      const first = whyPose && story ? story.first() : null;
      const t3 = first
        ? progress(flight3Start, Math.max(flight3Start + window.innerHeight * 0.4, first.scroll))
        : 0;
      // Leave the story's last stop as the reader moves on; land in the tech
      // field as its spot reaches mid-screen.
      const lastStop = first && story.last ? story.last() : null;
      let t4 = 0;
      if (lastStop && techPose) {
        const vh = window.innerHeight;
        const start = lastStop.scroll + vh * 0.25;
        const end = Math.max(start + vh * 0.4, techPose.y + (baseHeight * techPose.s) / 2 - vh / 2);
        t4 = progress(start, end);
      }
      // Leave the tech field as the reader moves on; land beside the logo as
      // the call to action reaches mid-screen.
      let t5 = 0;
      if (t4 > 0 && ctaPose) {
        const vh = window.innerHeight;
        const start = techPose.y + (baseHeight * techPose.s) / 2 - vh / 2 + vh * 0.2;
        t5 = progress(start, Math.max(start + vh * 0.35, ctaMid - vh / 2));
      }
      let course = heroPose;
      let walk = null;
      if (t5 > 0) {
        course = t5 >= 1 ? ctaPose : fly(techPose, ctaPose, t5, secondLegY);
      } else if (t4 > 0) {
        walk = story.travel(window.scrollY);
        course = t4 >= 1 ? techPose : fly(storyPose(lastStop), techPose, t4, secondLegY);
      } else if (t3 > 0) {
        walk = t3 >= 1 ? story.travel(window.scrollY) : null;
        const onRoad = storyPose(walk || first);
        course = t3 >= 1 ? onRoad : fly(whyPose, onRoad, t3, secondLegY);
      } else if (t2 > 0) course = fly(dockPose, whyPose, t2, secondLegY);
      else if (dockPose) course = fly(heroPose, dockPose, t1, firstLegY);

      // Glide slowly after the mouse between the two landings; ease back on
      // course otherwise.
      let wantX = 0;
      let wantY = 0;
      if (t1 >= 1 && t2 < 1) [wantX, wantY] = followTarget(course);
      else if (t4 >= 1 && t5 <= 0) [wantX, wantY] = techFollowTarget(course);
      const step = (wantX - followX) * FOLLOW_EASE;
      followX += step;
      followY += (wantY - followY) * FOLLOW_EASE;
      // Lean into the direction of travel, like it's gliding.
      lean += (Math.max(-8, Math.min(8, step * 0.6)) - lean) * 0.08;

      const p1 = easeInOutCubic(t1);
      const p2 = easeInOutCubic(t2);
      const p3 = easeInOutCubic(t3);
      const p4 = easeInOutCubic(t4);
      const p5 = easeInOutCubic(t5);
      const followWeight = t4 > 0 ? 1 : p1;
      const pose = { x: course.x + followX * followWeight, y: course.y + followY * followWeight, s: course.s };

      // Tell the tech field where the robot is (its center and size on the
      // page) and how present it is there, so chips can make room for it.
      const half = (freeHeightFrac * baseHeight * pose.s) / 2;
      window.XelenicRobot = {
        x: pose.x + (baseWidth * pose.s) / 2,
        y: pose.y + (baseHeight * pose.s) / 2,
        radius: half,
        presence: p4 * (1 - p5),
      };

      // The landing glow shows only while the robot is home on its spot.
      if (dock) {
        const home = t1 >= 0.98 && t2 <= 0.02 && Math.hypot(followX, followY) < 30;
        if (docked !== home) {
          docked = home;
          dock.classList.toggle('is-docked', docked);
        }
      }

      // Lean pivots on the canvas center (transform-origin is its corner).
      const cx = (baseWidth / 2).toFixed(1);
      const cy = (baseHeight / 2).toFixed(1);
      const transform =
        `translate3d(${pose.x.toFixed(1)}px, ${pose.y.toFixed(1)}px, 0) scale(${pose.s.toFixed(4)}) ` +
        `translate(${cx}px, ${cy}px) rotate(${lean.toFixed(2)}deg) translate(-${cx}px, -${cy}px)`;
      if (transform !== lastTransform) {
        canvas.style.transform = transform;
        lastTransform = transform;
      }
      return { p1, p2, p3, p4, p5, t1, t2, t3, t4, t5, walk, first, pose };
    }

    function onModelLoaded(gltf) {
      const model = gltf.scene;

      // Normalize the model: center it on its own bounding-box center and
      // scale it so it frames consistently regardless of the source units.
      const box = new THREE.Box3().setFromObject(model);
      const center = box.getCenter(new THREE.Vector3());
      const sphere = box.getBoundingSphere(new THREE.Sphere());

      const targetRadius = 1.6;
      const scale = targetRadius / sphere.radius;
      model.scale.setScalar(scale);
      // The offset lives in the parent's (unscaled) space, so it must be
      // scaled too — otherwise the model ends up off-center and cropped.
      model.position.copy(center).multiplyScalar(-scale);

      robotGroup.add(model);

      // Repaint the body; the metal, face screen and eyes keep their own colors.
      const body = new THREE.Color(BODY_COLOR).convertSRGBToLinear();
      model.traverse((child) => {
        if (child.isMesh && child.material && child.material.name === 'Plastik') {
          child.material.color.copy(body);
        }
      });

      // Measure the actual vertices (not the bounding box corners) so the
      // framing hugs the real silhouette instead of an inflated box.
      robotGroup.updateMatrixWorld(true);
      const meshes = [];
      model.traverse((child) => {
        if (child.isMesh && child.geometry.attributes.position) meshes.push(child);
      });
      modelProfile = new Float32Array(
        meshes.reduce((n, mesh) => n + mesh.geometry.attributes.position.count * 2, 0)
      );
      const v = new THREE.Vector3();
      let k = 0;
      feetY = 0;
      headY = 0;
      sweepR = 0;
      meshes.forEach((mesh) => {
        const pos = mesh.geometry.attributes.position;
        for (let i = 0; i < pos.count; i++) {
          v.fromBufferAttribute(pos, i).applyMatrix4(mesh.matrixWorld);
          modelProfile[k++] = v.y;
          modelProfile[k++] = Math.hypot(v.x, v.z);
          sweepR = Math.max(sweepR, modelProfile[k - 1]);
          feetY = Math.min(feetY, v.y);
          headY = Math.max(headY, v.y);
        }
      });

      modelReady = true;
      // The robot takes over the "Why Xelenic" spot from the flat CSS orbit.
      if (whySpot) whySpot.classList.add('has-robot');
      layout();
      prepareOrbit();
      canvas.style.opacity = '1';
    }

    // The orbit's shaders and label textures would otherwise be built the first
    // time it opens, freezing the page for about a second in the middle of a
    // scroll. Do it now, while the canvas is still hidden.
    function prepareOrbit() {
      orbit.group.visible = true;
      renderer.compile(scene, camera);
      orbit.rings.forEach((r) => renderer.initTexture(r.texture));
      renderer.render(scene, camera);
      orbit.group.visible = false;
    }

    function onModelError(err) {
      console.warn('Xelenic: could not load hero robot model —', err);
      canvas.style.display = 'none';
    }

    // Served over http(s), the model is fetched as one binary file (smaller
    // and cacheable; scripts/optimize-robot-model.mjs builds it). Opened
    // straight from disk, browsers block that fetch, so only then is the
    // base64-embedded copy in robot-model-data.js loaded and parsed in memory
    // instead. A page served from somewhere other than this folder (the
    // Laravel view) sets XELENIC_ASSET_BASE to find it.
    const assetBase = window.XELENIC_ASSET_BASE || 'assets/';
    function parseEmbedded() {
      new THREE.GLTFLoader().parse(
        JSON.stringify(window.XELENIC_ROBOT_GLTF),
        '',
        onModelLoaded,
        onModelError
      );
    }
    if (window.XELENIC_ROBOT_GLTF) {
      parseEmbedded();
    } else if (location.protocol === 'file:') {
      const script = document.createElement('script');
      script.src = assetBase + 'js/robot-model-data.js';
      script.onload = parseEmbedded;
      script.onerror = onModelError;
      document.body.appendChild(script);
    } else {
      // robot.glb is meshopt-compressed, so it needs the decoder to be read.
      new THREE.GLTFLoader().setMeshoptDecoder(window.MeshoptDecoder).load(
        assetBase + 'robot/robot.glb',
        onModelLoaded,
        undefined,
        onModelError
      );
    }

    layout();
    window.addEventListener('resize', layout);
    // Re-measure once web fonts swap in and the page settles, and whenever
    // content reflows — the stops are measured in page coordinates, so any
    // shift above them moves where the robot should be.
    window.addEventListener('load', layout);
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(layout);
    }
    if ('ResizeObserver' in window) {
      new ResizeObserver(layout).observe(document.body);
    }

    if (!reduceMotion) {
      // Just record the pointer here; the frame loop works out where to look
      // from the robot's known position, with no layout reads per event.
      window.addEventListener('pointermove', (e) => {
        pointer = { x: e.clientX, y: e.clientY, mouse: e.pointerType === 'mouse' };
      }, { passive: true });
      document.documentElement.addEventListener('mouseleave', () => {
        pointer = null;
      });
    }

    // Look at the cursor: turn toward it horizontally and nod toward it
    // vertically, measured from the robot's own spot on screen; settle back
    // to the resting pose when there's no cursor.
    function lookTarget(pose) {
      if (!pointer) return [REST_YAW, 0];
      const clamp = (n) => Math.max(-1, Math.min(1, n));
      const cx = pose.x + (baseWidth * pose.s) / 2 - window.scrollX;
      const cy = pose.y + (baseHeight * pose.s) / 2 - window.scrollY;
      return [
        clamp((pointer.x - cx) / (window.innerWidth / 2)) * MAX_YAW,
        clamp((pointer.y - cy) / (window.innerHeight / 2)) * MAX_PITCH,
      ];
    }

    // How the robot carries itself on the story path. Each stop shows it from
    // a different angle — so every step reveals a different side of it —
    // with its own little idle act; in between it hops along the road facing
    // the way it's going. Poses are for a card on the left (robot on the
    // right, turned toward it) and mirrored for the other side.
    const STORY_POSES = [
      // Discover: a curious three-quarter look at its face, glancing around.
      { yaw: -0.55, pitch: 0.1, roll: 0.14, idle: (t) => ({ yaw: Math.sin(t * 1.1) * 0.3 }) },
      // Strategize: side profile, nodding as it thinks.
      { yaw: -1.5, pitch: 0, roll: 0, idle: (t) => ({ pitch: Math.sin(t * 1.6) * 0.08 }) },
      // Build: turned to show its back, hammering away.
      { yaw: -2.5, pitch: 0.08, roll: 0, idle: (t) => ({ pitch: Math.max(0, Math.sin(t * 7)) * 0.14 }) },
      // Deploy: a low hero angle from below, hovering like it's lifting off.
      { yaw: -0.3, pitch: -0.45, roll: 0, idle: (t) => ({ lift: 0.12 + Math.sin(t * 2.4) * 0.1, roll: Math.sin(t * 9) * 0.02 }) },
      // Grow: seen from above, swinging round and hopping for joy.
      { yaw: 0.4, pitch: 0.45, roll: 0, idle: (t) => ({ yaw: Math.sin(t * 0.8) * 1.2, lift: Math.abs(Math.sin(t * 3.2)) * 0.26 }) },
    ];
    function storyMotion(walk, first, t) {
      const at = walk || { index: 0, dwell: 1, side: first.side, dx: 0, dy: 1, length: 0 };
      const pose = STORY_POSES[Math.round(at.index) % STORY_POSES.length];
      const idle = pose.idle(t);
      const side = at.side;
      const stop = {
        yaw: (pose.yaw + (idle.yaw || 0)) * side,
        pitch: pose.pitch + (idle.pitch || 0),
        roll: (pose.roll + (idle.roll || 0)) * side,
        lift: idle.lift || 0,
      };
      const heading = at.dx / (Math.hypot(at.dx, at.dy) || 1);
      const walking = {
        yaw: heading * 1.35,
        pitch: 0.08,
        roll: -heading * 0.08,
        lift: Math.abs(Math.sin(at.length * 0.05)) * 0.22,
      };
      const w = at.dwell;
      return {
        yaw: lerp(walking.yaw, stop.yaw, w),
        pitch: lerp(walking.pitch, stop.pitch, w),
        roll: lerp(walking.roll, stop.roll, w),
        lift: lerp(walking.lift, stop.lift, w),
      };
    }

    // ---- Sound cues (robot-sound.js) -----------------------------------
    // Takeoff and landing on every leg of the journey (either direction), a
    // power-up as the orbit opens, a servo whir that follows how fast it
    // turns, and on the story path a voice for each stop, soft footsteps as
    // it walks and a few beats of its act — hammer clanks while it builds,
    // boings as it hops for joy — before it goes quiet.
    const STOP_VOICES = ['curious', 'think', 'work', 'launch', 'celebrate'];
    const cue = {
      legs: null,
      reveal: 0,
      yaw: 0,
      pitch: 0,
      stop: null,
      arrived: 0,
      beats: 0,
      step: null,
      hammer: 0,
      hop: 0,
    };
    function soundCues(legs, reveal, walk, dt, now) {
      const sound = window.XelenicSound;
      if (!sound) return;
      if (cue.legs) {
        legs.forEach((t, i) => {
          const was = cue.legs[i];
          if ((was <= 0 && t > 0) || (was >= 1 && t < 1)) sound.play('takeoff');
          else if ((was < 1 && t >= 1) || (was > 0 && t <= 0)) sound.play('land');
          // Landing beside the logo at the very end: a happy "ta-da!".
          if (i === 4 && was < 1 && t >= 1) setTimeout(() => sound.play('celebrate'), 380);
        });
      }
      cue.legs = legs;
      if (cue.reveal < 0.6 && reveal >= 0.6) sound.play('powerup');
      cue.reveal = reveal;

      const turn = Math.abs(robotGroup.rotation.y - cue.yaw) + Math.abs(robotGroup.rotation.x - cue.pitch);
      cue.yaw = robotGroup.rotation.y;
      cue.pitch = robotGroup.rotation.x;
      sound.servo(dt > 0 ? turn / dt : 0);

      const onRoad = walk && legs[2] >= 1 && legs[3] <= 0;
      if (!onRoad) {
        cue.stop = null;
        cue.step = null;
        return;
      }
      const stop = Math.round(walk.index);
      if (walk.dwell > 0.92) {
        if (stop !== cue.stop) {
          cue.stop = stop;
          cue.arrived = now;
          cue.beats = 0;
          // A beat after landing, so the two don't blur together.
          setTimeout(() => sound.play(STOP_VOICES[stop % STOP_VOICES.length]), 260);
        }
        // Same phases as its idle act in STORY_POSES, starting once its
        // voice for the stop has finished.
        const acting = now - cue.arrived > 0.9 && cue.beats < 4;
        const hammer = Math.sin(now * 7);
        if (stop === 2 && acting && cue.hammer < 0.9 && hammer >= 0.9) {
          sound.play('clank');
          cue.beats++;
        }
        cue.hammer = hammer;
        const hop = Math.sin(now * 3.2);
        if (stop === 4 && acting && Math.sign(hop) !== Math.sign(cue.hop)) {
          sound.play('boing');
          cue.beats++;
        }
        cue.hop = hop;
      } else if (walk.dwell < 0.5) {
        cue.stop = null;
        // A footstep each time a hop lands (see the walking lift in storyMotion).
        const step = Math.floor((walk.length * 0.05) / Math.PI);
        if (cue.step !== null && step !== cue.step) sound.play('step');
        cue.step = step;
      }
    }

    let floatPhase = 0;
    let lastFrame = null;
    function animate(frameTime) {
      requestAnimationFrame(animate);
      if (!modelReady || !baseWidth) return;
      const dt = lastFrame === null ? 0 : Math.max(0, Math.min(0.1, (frameTime - lastFrame) / 1000));
      lastFrame = frameTime;

      const { p1, p2, p3, p4, p5, t1, t2, t3, t4, t5, walk, first, pose } = placeRobot();
      // In the orbit the camera pulls back and the orbit bursts open over
      // the last stretch of the flight in; it folds away again as the robot
      // leaves for the story path.
      aimCamera(p2, p3);
      const reveal = smoothstep(clamp01((t2 - 0.55) / 0.45)) * (1 - smoothstep(clamp01(t3 * 2.5)));

      if (!reduceMotion) {
        floatPhase += 0.02;
        // Looking at the cursor; livelier in the orbit: a bigger bob, a slow
        // sway and a little wobble.
        const [lookYaw, lookPitch] = lookTarget(pose);
        let yaw = lookYaw + Math.sin(floatPhase * 0.5) * 0.35 * p2;
        let pitch = lookPitch;
        let roll = Math.sin(floatPhase * 1.6) * 0.05 * p2;
        let lift = Math.sin(floatPhase) * FLOAT_AMPLITUDE * (1 + 1.2 * p2);
        // On the story path it takes up each stop's pose instead (and goes
        // back to watching the cursor in the tech field).
        const onStory = p3 * (1 - p4);
        if (onStory > 0 && (walk || first)) {
          const act = storyMotion(walk, first, performance.now() / 1000);
          yaw = lerp(yaw, act.yaw, onStory);
          pitch = lerp(pitch, act.pitch, onStory);
          roll = lerp(roll, act.roll, onStory);
          lift = lerp(lift, act.lift, onStory);
        }
        // In the tech field: a gentle hover, a touch higher than elsewhere.
        lift += Math.sin(floatPhase * 1.3) * 0.08 * p4;
        // One full turn on each flight, starting toward the direction of travel.
        const spin = Math.PI * 2 * (p2 - p1 - p3 + p4 - p5);
        robotGroup.position.y = lift;
        robotGroup.rotation.y += (yaw + spin - robotGroup.rotation.y) * 0.06;
        robotGroup.rotation.x += (pitch - robotGroup.rotation.x) * 0.06;
        robotGroup.rotation.z += (roll - robotGroup.rotation.z) * 0.08;
      } else {
        robotGroup.rotation.y = REST_YAW;
      }
      // Where the robot actually is on the page this frame — its center and
      // half extents in page pixels, for the current camera framing — so
      // other sections can react to it (the client logos part around it).
      if (window.XelenicRobot) {
        const tanV = Math.tan((camera.fov * Math.PI) / 360);
        const dist = camera.position.z;
        const midY = (headY + feetY) / 2 + robotGroup.position.y;
        const ndcY = (midY - camera.position.y) / (dist * tanV);
        window.XelenicRobot.box = {
          x: pose.x + (baseWidth * pose.s) / 2,
          y: pose.y + ((1 - ndcY) / 2) * baseHeight * pose.s,
          hw: (sweepR / (dist * tanV * camera.aspect)) * (baseWidth / 2) * pose.s,
          hh: ((headY - feetY) / 2 / (dist * tanV)) * (baseHeight / 2) * pose.s,
        };
      }

      // Orbit speed follows the clock, so it's the same at any refresh rate;
      // with reduced motion the labels hold still.
      orbit.update(reduceMotion ? 0 : performance.now() / 1000, reveal);
      soundCues([t1, t2, t3, t4, t5], reveal, walk, dt, performance.now() / 1000);

      // Skip drawing while the robot is scrolled out of view.
      const top = pose.y - window.scrollY;
      if (top > window.innerHeight || top + baseHeight * pose.s < 0) return;
      renderer.render(scene, camera);
      watchFrames(frameTime);
    }
    requestAnimationFrame(animate);
  }
})();
