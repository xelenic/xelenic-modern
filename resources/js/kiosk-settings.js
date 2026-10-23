// Kiosk project form (resources/views/projects/kiosk-settings.blade.php):
// redraws the screen preview and its measurements, and shows the choices
// under each switch, as the settings change. The sums match
// App\Support\KioskSettings::dimensions() so nothing jumps on load.
const form = document.querySelector('[data-kiosk-settings]');

if (form) {
    const preview = form.querySelector('[data-kiosk-preview]');
    const screen = preview.querySelector('[data-preview-screen]');
    const pxPerInch = Number(preview.dataset.pxPerInch);
    const orientations = { portrait: 'Portrait', landscape: 'Landscape' };

    const field = (name) => form.querySelector(`[name="settings[${name}]"]`);
    const isOn = (name) => form.querySelector(`[name="settings[${name}]"][type="checkbox"]`)?.checked ?? false;
    const setText = (name, text) => {
        preview.querySelectorAll(`[data-preview-${name}]`).forEach((element) => {
            element.textContent = text;
        });
    };
    const cm = (inches) => (inches * 2.54).toFixed(1);

    const update = () => {
        const size = Number(field('screen_size').value);
        const orientation = form.querySelector('[name="settings[orientation]"]:checked')?.value ?? 'portrait';
        const [resWidth, resHeight] = field('resolution').value.split('x').map(Number);

        const long = (size * 16) / Math.hypot(16, 9);
        const short = (size * 9) / Math.hypot(16, 9);
        const portrait = orientation === 'portrait';
        const [width, height] = portrait ? [short, long] : [long, short];

        screen.style.width = `${(width * pxPerInch).toFixed(1)}px`;
        screen.style.height = `${(height * pxPerInch).toFixed(1)}px`;
        screen.dataset.orientation = orientation;
        screen.classList.toggle('kiosk-webgl', isOn('webgl'));

        setText('width', `${cm(width)} cm`);
        setText('height', `${cm(height)} cm`);
        setText('diagonal', `${field('screen_size').value}″`);
        setText('orientation', orientations[orientation]);
        setText('dimensions', `${cm(width)} × ${cm(height)}`);
        setText('resolution', portrait ? `${resHeight} × ${resWidth}` : `${resWidth} × ${resHeight}`);
        setText('ppi', `${Math.round(Math.hypot(resWidth, resHeight) / size)} PPI`);

        preview.querySelectorAll('[data-chip]').forEach((chip) => {
            chip.hidden = !isOn(chip.dataset.chip);
        });
        form.querySelectorAll('[data-reveal]').forEach((section) => {
            section.hidden = !isOn(section.dataset.reveal);
        });
    };

    form.addEventListener('change', update);

    // Browsers can restore earlier choices on back/forward navigation.
    update();
}
