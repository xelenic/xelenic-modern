(() => {
  'use strict';

  const canvas = document.getElementById('bg-canvas');

  if (canvas && window.THREE && window.WebGLRenderingContext) {
    try {
      initScene(canvas);
    } catch (err) {
      console.warn('Xelenic: 3D background disabled —', err);
      canvas.style.display = 'none';
    }
  } else if (canvas) {
    canvas.style.display = 'none';
  }

  function initScene(canvas) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const ACCENT = 0x3b82f6;
    const NODE_COUNT = 130;
    const FIELD_RADIUS = 15;
    const LINK_DISTANCE = 4.4;

    // This full-screen layer of faint dots and hairlines is the largest
    // canvas on the page, so it renders at no more than 1.5x density and
    // skips multisampling on high-density screens — at 2x with MSAA it
    // shaded ~5M pixels a frame on a retina laptop for a barely-there effect.
    const dpr = window.devicePixelRatio || 1;
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: dpr < 2 });
    renderer.setPixelRatio(Math.min(dpr, 1.5));

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
    camera.position.z = 24;

    const group = new THREE.Group();
    group.rotation.x = 0.35;
    scene.add(group);

    // --- Nodes -------------------------------------------------------------
    const positions = new Float32Array(NODE_COUNT * 3);
    for (let i = 0; i < NODE_COUNT; i++) {
      const r = FIELD_RADIUS * Math.cbrt(Math.random());
      const theta = Math.random() * Math.PI * 2;
      const phi = Math.acos(Math.random() * 2 - 1);
      positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
      positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
      positions[i * 3 + 2] = r * Math.cos(phi);
    }

    const pointGeometry = new THREE.BufferGeometry();
    pointGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const pointMaterial = new THREE.PointsMaterial({
      color: ACCENT,
      size: 0.24,
      transparent: true,
      opacity: 0.8,
      sizeAttenuation: true,
    });
    group.add(new THREE.Points(pointGeometry, pointMaterial));

    // --- Connections between nearby nodes (computed once — rigid rotation
    // never changes relative distances, so this stays valid every frame) ----
    const linkPositions = [];
    for (let i = 0; i < NODE_COUNT; i++) {
      for (let j = i + 1; j < NODE_COUNT; j++) {
        const dx = positions[i * 3] - positions[j * 3];
        const dy = positions[i * 3 + 1] - positions[j * 3 + 1];
        const dz = positions[i * 3 + 2] - positions[j * 3 + 2];
        if (Math.sqrt(dx * dx + dy * dy + dz * dz) < LINK_DISTANCE) {
          linkPositions.push(
            positions[i * 3], positions[i * 3 + 1], positions[i * 3 + 2],
            positions[j * 3], positions[j * 3 + 1], positions[j * 3 + 2]
          );
        }
      }
    }
    const lineGeometry = new THREE.BufferGeometry();
    lineGeometry.setAttribute('position', new THREE.BufferAttribute(new Float32Array(linkPositions), 3));
    const lineMaterial = new THREE.LineBasicMaterial({ color: ACCENT, transparent: true, opacity: 0.1 });
    group.add(new THREE.LineSegments(lineGeometry, lineMaterial));

    // --- Sizing --------------------------------------------------------------
    function resize() {
      const { innerWidth, innerHeight } = window;
      camera.aspect = innerWidth / innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(innerWidth, innerHeight);
    }
    resize();
    window.addEventListener('resize', resize);

    // --- Mouse parallax tilt (subtle, independent of scroll rotation) -------
    let mouseX = 0;
    let mouseY = 0;
    if (!reduceMotion) {
      window.addEventListener('mousemove', (e) => {
        mouseX = (e.clientX / window.innerWidth - 0.5) * 0.5;
        mouseY = (e.clientY / window.innerHeight - 0.5) * 0.4;
      });
    }

    // --- Scroll-driven rotation via GSAP ScrollTrigger ----------------------
    // Y-axis rotation is owned entirely by scroll progress through the page;
    // idle animation below only ever touches Z, so the two never fight.
    if (window.gsap && window.ScrollTrigger && !reduceMotion) {
      window.gsap.to(group.rotation, {
        y: Math.PI * 1.4,
        ease: 'none',
        scrollTrigger: {
          trigger: document.body,
          start: 'top top',
          end: 'bottom bottom',
          scrub: 0.8,
        },
      });
    }

    // --- Render loop ---------------------------------------------------------
    function animate() {
      requestAnimationFrame(animate);

      if (!reduceMotion) {
        group.rotation.z += 0.0007;
        group.rotation.x += (0.35 + mouseY - group.rotation.x) * 0.02;
        group.position.x += (mouseX * 1.5 - group.position.x) * 0.02;
      }

      renderer.render(scene, camera);
    }
    animate();
  }
})();
