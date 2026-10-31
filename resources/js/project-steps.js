// Multi-step project form (resources/views/projects/kiosk-form.blade.php):
// one [data-step-panel] shows at a time (see app.css). Any [data-step-goto]
// button moves between steps; going forward first checks the fields of the
// step on screen, so nobody reaches the end with a required field empty.
const form = document.querySelector('[data-steps]');

if (form) {
    const panels = [...form.querySelectorAll('[data-step-panel]')];
    const indicators = [...document.querySelectorAll('[data-step-indicator]')];
    const panelFor = (step) => panels.find((panel) => Number(panel.dataset.stepPanel) === step);
    let current = Number(form.querySelector('[data-step-panel][data-active]')?.dataset.stepPanel ?? panels[0].dataset.stepPanel);

    const fieldsAreValid = (step) => {
        for (const field of panelFor(step).querySelectorAll('input, select, textarea')) {
            if (!field.checkValidity()) {
                field.reportValidity();

                return false;
            }
        }

        return true;
    };

    const show = (step) => {
        current = step;

        panels.forEach((panel) => {
            panel.toggleAttribute('data-active', Number(panel.dataset.stepPanel) === step);
        });

        indicators.forEach((indicator) => {
            const number = Number(indicator.dataset.stepIndicator);
            indicator.dataset.state = number < step ? 'complete' : number === step ? 'current' : 'upcoming';
            indicator.firstElementChild.toggleAttribute('aria-current', number === step);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
        panelFor(step).querySelector('h2')?.focus({ preventScroll: true });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-step-goto]');

        if (!trigger) {
            return;
        }

        const step = Number(trigger.dataset.stepGoto);

        if (step === current || (step > current && !fieldsAreValid(current))) {
            return;
        }

        show(step);
    });

    // Show the project name in the step 4 summary as it's typed.
    form.querySelectorAll('[data-summary-source]').forEach((source) => {
        source.addEventListener('input', () => {
            form.querySelectorAll(`[data-summary="${source.dataset.summarySource}"]`).forEach((target) => {
                target.textContent = source.value.trim() || 'Your project';
            });
        });
    });
}
