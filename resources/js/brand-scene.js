// Sign-in pages: the 3D logo in the brand panel leans toward the mouse, and the
// robot turns a little to watch it, wherever it is on the page. It eases there
// and stops drawing once it has settled, so a still mouse costs nothing.

const scene = document.querySelector('[data-brand-scene]');
const tilt = scene?.querySelector('.bs-tilt');
const robot = scene?.querySelector('.bs-robot-lean');

if (tilt && robot && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    let target = { x: 0, y: 0, lean: 0 };
    let current = { x: 0, y: 0, lean: 0 };
    let last = null;
    let running = false;

    const tick = (now) => {
        const dt = last === null ? 0.016 : Math.min(0.05, (now - last) / 1000);
        last = now;
        const ease = 1 - Math.exp(-dt * 5);
        let moving = false;

        for (const key of ['x', 'y', 'lean']) {
            current[key] += (target[key] - current[key]) * ease;
            if (Math.abs(target[key] - current[key]) > 0.01) moving = true;
        }

        tilt.style.setProperty('--tx', `${current.x.toFixed(2)}deg`);
        tilt.style.setProperty('--ty', `${current.y.toFixed(2)}deg`);
        robot.style.setProperty('--lean', `${current.lean.toFixed(2)}deg`);

        if (moving) {
            requestAnimationFrame(tick);
        } else {
            running = false;
            last = null;
        }
    };

    const aimAt = (x, y) => {
        target = { x, y, lean: y * 0.25 };

        if (!running) {
            running = true;
            requestAnimationFrame(tick);
        }
    };

    window.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') return;

        const box = tilt.getBoundingClientRect();
        const nx = Math.max(-1, Math.min(1, (event.clientX - (box.left + box.width / 2)) / (window.innerWidth / 2)));
        const ny = Math.max(-1, Math.min(1, (event.clientY - (box.top + box.height / 2)) / (window.innerHeight / 2)));
        aimAt(-ny * 14, nx * 22);
    }, { passive: true });

    document.documentElement.addEventListener('mouseleave', () => aimAt(0, 0));
}
