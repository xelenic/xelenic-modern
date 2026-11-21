// Kiosk Studio, the kiosk engine in public/nignest, over the whole page
// (components/kiosk-studio.blade.php), for the form marked data-kiosk-studio:
//
// - the new kiosk project form (projects/kiosk-form.blade.php): "Continue to
//   design" opens it as step 4, and the project is sent with the design in
//   kiosk_design (Submit project), or without one (Skip design & submit);
// - a kiosk project's page (projects/show.blade.php): it opens with the saved
//   design (fetched from data-studio-source), and Save design sends it back in
//   the background while the studio stays open (data-studio-stay).
//
// The page's part sits in the studio's own menu bar, set through its add-on
// (js/embed.js): the main action beside Play and in the File menu, any other
// item after it, and the way back on the right of the menu bar. The add-on
// also loads a design, or starts one, at the kiosk's orientation
// (data-studio-orientation, or the form's own setting). A design goes with the
// form entries made while playing it (kiosk_data).

const form = document.querySelector('[data-kiosk-studio]');
const overlay = document.querySelector('[data-studio-overlay]');

if (form && overlay) {
    const input = form.querySelector('[data-studio-input]');
    const entriesInput = form.querySelector('[data-studio-entries]');
    const frame = overlay.querySelector('[data-studio-frame]');
    const maxLength = Number(form.dataset.studioMax);
    let actionButton = null; // the page's button beside Play
    let loadedFrom = null; // the field's value when the studio last loaded it
    let returnFocus = null;
    let savedChanges = false; // saved in the background, so the page is out of date

    const parse = (json) => {
        try {
            const design = JSON.parse(json);

            return Array.isArray(design?.screens) && design.screens.length ? design : null;
        } catch {
            return null;
        }
    };

    // The studio keeps a design's form entries in this browser under its id
    // (its Data panel). Sent with the design, the newest first if there are
    // too many for the server (App\Support\KioskDesign::MAX_ENTRIES).
    const playedEntries = (designId) => {
        try {
            const entries = JSON.parse(localStorage.getItem(`kiosk.submissions.${designId}`) || '[]');
            const json = JSON.stringify(Array.isArray(entries) ? entries.slice(-1000) : []);

            return json.length <= 1e6 ? json : '';
        } catch {
            return '';
        }
    };

    const orientation = () => form.dataset.studioOrientation
        || form.querySelector('[name="settings[orientation]"]:checked')?.value
        || 'portrait';
    const studio = () => frame.contentWindow?.KioskStudioEmbed;

    // The studio, loaded into its frame the first time it's needed.
    const studioReady = () => new Promise((resolve) => {
        if (studio()) {
            resolve();

            return;
        }

        frame.addEventListener('load', resolve, { once: true });
        frame.src ||= form.dataset.studioUrl;
    });

    // A project's saved design, fetched into the field the first time.
    const savedDesign = async () => {
        if (input.value || ! form.dataset.studioSource) return true;

        try {
            const response = await fetch(form.dataset.studioSource, { headers: { Accept: 'application/json' } });

            if (! response.ok) return false;

            input.value = await response.text();

            return true;
        } catch {
            return false;
        }
    };

    const close = () => {
        // The page shows the design and its data, so after a save it's reloaded.
        if (savedChanges) {
            location.reload();

            return;
        }

        overlay.hidden = true;
        document.documentElement.classList.remove('overflow-hidden');
        returnFocus?.focus();
    };

    const saveInBackground = async () => {
        let problem = 'The design could not be saved. Check your connection and try again.';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const reply = await response.json().catch(() => ({}));

            if (response.ok) {
                savedChanges = true;
                loadedFrom = input.value;
                problem = null;
                studio().notify('Design saved');
            } else if (response.status === 419) {
                problem = 'Your session has expired. Keep a copy with File → Save project (.json), then reload the page.';
            } else {
                problem = Object.values(reply.errors ?? {})[0]?.[0] ?? reply.message ?? problem;
            }
        } catch {
            // The connection's message stands.
        }

        if (problem) studio().notify(problem);
        actionButton.disabled = false;
    };

    const submit = (withDesign) => {
        if (withDesign) {
            const design = studio().read();
            const json = JSON.stringify(design);

            if (json.length > maxLength) {
                studio().notify(`This design is ${(json.length / 1e6).toFixed(1)} MB, over the ${maxLength / 1e6} MB limit. Use smaller pictures and try again.`);

                return;
            }

            input.value = json;
            if (entriesInput) entriesInput.value = playedEntries(design.id);
        } else {
            input.value = '';
            if (entriesInput) entriesInput.value = '';
        }

        actionButton.disabled = true;
        studio().notify(withDesign ? 'Saving…' : 'Sending your project…');

        if (withDesign && 'studioStay' in form.dataset) {
            saveInBackground();
        } else {
            form.requestSubmit();
        }
    };

    const open = async (trigger) => {
        // A form's own fields come first.
        if (! form.reportValidity()) return;

        returnFocus = trigger;
        overlay.hidden = false;
        document.documentElement.classList.add('overflow-hidden');

        const [loaded] = await Promise.all([savedDesign(), studioReady()]);
        const skip = overlay.dataset.studioSkip;
        actionButton = studio().page({
            context: overlay.dataset.studioTitle,
            action: overlay.dataset.studioAction,
            onAction: () => submit(true),
            items: skip ? [{ label: skip, icon: 'flag', run: () => submit(false) }] : [],
            back: overlay.dataset.studioBack,
            onBack: close,
        });
        actionButton.disabled = ! loaded;

        if (! loaded) {
            studio().notify('Your saved design could not be loaded. Go back and try again.');

            return;
        }

        // Unless the studio already has this design (going back keeps the work
        // in progress), load it.
        if (loadedFrom === input.value) {
            studio().lock(orientation());
        } else {
            studio().load(parse(input.value), orientation());
            loadedFrom = input.value;
        }
        frame.focus();
    };

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-studio-open]');

        if (opener) open(opener);
    });
}
