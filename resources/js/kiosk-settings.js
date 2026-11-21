// Kiosk project form (resources/views/projects/kiosk-form.blade.php):
// redraws step 3's screen preview with its measurements as the screen
// settings change. The sums match App\Support\KioskSettings::dimensions()
// so nothing jumps on load.
const form = document.querySelector('[data-kiosk-settings]');

if (form) {
    const previews = [...form.querySelectorAll('[data-kiosk-preview]')];
    const orientations = { portrait: 'Portrait', landscape: 'Landscape' };

    const field = (name) => form.querySelector(`[name="settings[${name}]"]`);
    const cm = (inches) => (inches * 2.54).toFixed(1);

    const update = () => {
        const size = Number(field('screen_size').value);
        const orientation = form.querySelector('[name="settings[orientation]"]:checked')?.value ?? 'portrait';
        const [resWidth, resHeight] = field('resolution').value.split('x').map(Number);

        const long = (size * 16) / Math.hypot(16, 9);
        const short = (size * 9) / Math.hypot(16, 9);
        const portrait = orientation === 'portrait';
        const [width, height] = portrait ? [short, long] : [long, short];

        const text = {
            width: `${cm(width)} cm`,
            height: `${cm(height)} cm`,
            diagonal: `${field('screen_size').value}″`,
            orientation: orientations[orientation],
            dimensions: `${cm(width)} × ${cm(height)}`,
            resolution: portrait ? `${resHeight} × ${resWidth}` : `${resWidth} × ${resHeight}`,
            ppi: `${Math.round(Math.hypot(resWidth, resHeight) / size)} PPI`,
        };

        previews.forEach((preview) => {
            const screen = preview.querySelector('[data-preview-screen]');
            const pxPerInch = Number(preview.dataset.pxPerInch);

            screen.style.width = `${(width * pxPerInch).toFixed(1)}px`;
            screen.style.height = `${(height * pxPerInch).toFixed(1)}px`;
            screen.dataset.orientation = orientation;

            Object.entries(text).forEach(([name, value]) => {
                preview.querySelectorAll(`[data-preview-${name}]`).forEach((element) => {
                    element.textContent = value;
                });
            });
        });
    };

    form.addEventListener('change', update);

    // Browsers can restore earlier choices on back/forward navigation.
    update();
}
