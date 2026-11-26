// Cloud order forms (resources/views/cloud/order*.blade.php): keeps the
// "Your order" summary and the price up to date as choices change. Prices
// here are estimates (InterServer's price plus our markup); the server
// prices the order again from InterServer's own quote when it's placed.

const money = (value) => '$' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;
const withMarkup = (cost, markup) => Math.round(cost * (1 + markup / 100) * 100) / 100;

function setup(form) {
    const markup = parseFloat(form.dataset.markup) || 0;
    const catalogEl = form.querySelector('script[data-catalog]');
    const catalog = catalogEl ? JSON.parse(catalogEl.textContent) : {};
    const price = form.querySelector('[data-price]');
    const note = form.querySelector('[data-price-note]');
    const label = form.querySelector('[data-price-label]');

    const sum = (key, text) => {
        const el = form.querySelector(`[data-sum="${key}"]`);
        if (el) el.textContent = text || '—';
    };
    const checked = (name) => form.querySelector(`input[name="${name}"]:checked`);
    const periodDiscount = (months) => Number((catalog.periods || {})[months] || 0);

    // A total for `months` months, shown as the price with a monthly figure
    // under it when it's paid for more than a month at a time.
    const showPrice = (monthlyCost, months) => {
        if (!monthlyCost) {
            price.textContent = '—';
            note.innerHTML = '&nbsp;';
            return;
        }
        const discount = periodDiscount(months);
        const total = withMarkup(monthlyCost * months * (1 - discount / 100), markup);
        label.textContent = months === 1 ? 'Per month' : `Every ${months} months`;
        price.textContent = money(total);
        note.textContent = months === 1
            ? 'Estimate. We confirm the final price with you.'
            : `About ${money(total / months)} a month${discount ? `, ${discount}% off` : ''}. Estimate.`;
    };

    const product = form.dataset.cloudOrder;
    const update = { vps, hosting, domain, dedicated }[product]?.(form, { catalog, markup, sum, checked, showPrice, price, note, label });

    if (update) {
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        update();
    }
}

// Cloud servers: the OS list follows the platform, Windows needs 2 slices and
// no control panel, and sold-out locations can't be picked.
function vps(form, { catalog, sum, checked, showPrice }) {
    const os = form.querySelector('[data-os]');
    const version = form.querySelector('[data-version]');
    const slices = form.querySelector('[data-slices]');
    const windowsNote = form.querySelector('[data-windows-note]');
    const panel = form.querySelector('[data-control-panel]');
    let platform = checked('platform')?.value;

    const fill = (select, entries) => {
        const current = select.value;
        select.replaceChildren(...entries.map(([value, text]) => new Option(text, value)));
        if (entries.some(([value]) => value === current)) select.value = current;
    };

    const platformChanged = () => {
        const distros = catalog.os[platform] || {};
        fill(os, Object.entries(distros).map(([key, info]) => [key, info.name]));
        osChanged();

        form.querySelectorAll('[data-location]').forEach((card) => {
            const stock = catalog.locations[card.dataset.location]?.stock || {};
            const open = stock[platform] ?? true;
            const input = card.querySelector('input');
            input.disabled = !open;
            if (!open) input.checked = false;
            card.querySelector('[data-stock]').textContent = open ? 'Available' : 'Sold out for this platform';
        });
        if (!checked('location')) {
            const first = form.querySelector('input[name="location"]:not(:disabled)');
            if (first) first.checked = true;
        }
    };

    const osChanged = () => {
        const info = (catalog.os[platform] || {})[os.value];
        fill(version, Object.entries(info?.versions || {}));
        const windows = Boolean(info?.windows);
        slices.min = windows ? 2 : 1;
        if (Number(slices.value) < Number(slices.min)) slices.value = slices.min;
        windowsNote.classList.toggle('hidden', !windows);
        panel.classList.toggle('hidden', windows);
        if (windows) panel.querySelector('select').value = 'none';
    };

    form.addEventListener('change', (event) => {
        if (event.target.name === 'platform') {
            platform = event.target.value;
            platformChanged();
        } else if (event.target === os) {
            osChanged();
        }
    });
    platformChanged();

    return () => {
        const count = Number(slices.value);
        const info = (catalog.os[platform] || {})[os.value];
        const windows = Boolean(info?.windows);
        const months = Number(checked('period')?.value || 1);
        const disk = count * (platform === 'kvmstorage' ? catalog.slice.storageDisk : catalog.slice.disk);
        const ram = count * catalog.slice.ram / 1024;

        form.querySelector('[data-slices-label]').textContent = plural(count, 'slice');
        form.querySelector('[data-ram]').textContent = `${+ram.toFixed(1)} GB`;
        form.querySelector('[data-disk]').textContent = `${disk.toLocaleString()} GB`;
        form.querySelector('[data-bandwidth]').textContent = `${Math.round(count * catalog.slice.bandwidth / 1000)} TB`;

        const platformInfo = catalog.platforms[platform] || {};
        const location = catalog.locations[checked('location')?.value];
        const cp = windows ? 'none' : (form.querySelector('[name="control_panel"]')?.value || 'none');

        sum('platform', platformInfo.name);
        sum('os', info ? `${info.name} ${info.versions[version.value] || ''}`.trim() : '');
        sum('size', `${plural(count, 'slice')} · ${+ram.toFixed(1)} GB RAM`);
        sum('location', location?.name);
        sum('period', plural(months, 'month'));

        const sliceCost = windows ? platformInfo.windows : platformInfo.linux;
        showPrice(count * (sliceCost || 0) + (catalog.controlPanels[cp] || 0), months);
    };
}

function hosting(form, { catalog, sum, checked, showPrice }) {
    const domain = form.querySelector('[data-domain]');

    return () => {
        const plan = (catalog.plans || []).find((p) => String(p.id) === checked('plan')?.value);
        const months = Number(checked('period')?.value || 1);

        sum('plan', plan ? `${plan.name} · ${plan.panel}` : '');
        sum('domain', domain?.value.trim().toLowerCase());
        sum('period', plural(months, 'month'));
        showPrice(plan?.cost || 0, months);
    };
}

function dedicated(form, { catalog, markup, sum, checked, price, note, label }) {
    return () => {
        const server = (catalog.servers || []).find((s) => s.id === checked('server')?.value);

        sum('server', server ? [server.cpu, server.memory && `${server.memory} RAM`].filter(Boolean).join(' · ') : '');
        sum('location', server?.location);
        label.textContent = 'Per month';
        price.textContent = server ? money(withMarkup(server.cost, markup)) : '—';
        note.textContent = server ? 'We confirm the final price with you.' : ' ';
    };
}

// Domains: look the name up, show what it costs, then ask for the
// registrant's details.
function domain(form, { sum, price, note, label }) {
    const input = form.querySelector('[data-domain]');
    const button = form.querySelector('[data-domain-check]');
    const result = form.querySelector('[data-domain-result]');
    const privacyRow = form.querySelector('[data-privacy]');
    const privacy = privacyRow.querySelector('input[type="checkbox"]');
    const contact = form.querySelector('[data-contact]');
    const submit = form.querySelector('button[type="submit"]');
    let found = null; // the last lookup, if the name is free
    let request = 0;

    const tone = (kind) => {
        result.className = 'mt-4 rounded-xl px-4 py-3 text-sm ' + {
            ok: 'border border-emerald-200 bg-emerald-50 text-emerald-800',
            no: 'border border-slate-200 bg-slate-50 text-slate-700',
            error: 'border border-red-200 bg-red-50 text-red-700',
            busy: 'border border-slate-200 bg-white text-slate-500',
        }[kind];
    };

    const check = async () => {
        const name = input.value.trim().toLowerCase().replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/.*$/, '');
        if (!name) {
            input.focus();
            return;
        }
        input.value = name;
        const mine = ++request;
        found = null;
        tone('busy');
        result.textContent = `Checking ${name}…`;
        button.disabled = true;

        try {
            const url = new URL(form.dataset.checkUrl, location.origin);
            url.searchParams.set('domain', name);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => ({}));
            if (mine !== request) return;

            if (!response.ok) {
                tone('error');
                result.textContent = data.errors?.domain?.[0] || data.message || 'We couldn\'t check that domain just now.';
            } else if (data.available) {
                found = data;
                tone('ok');
                result.innerHTML = '';
                const strong = document.createElement('strong');
                strong.textContent = data.domain;
                result.append(strong, ` is available${data.premium ? ' (a premium name)' : ''}. `
                    + `${money(data.price)} for the first year${data.renewal ? `, then ${money(data.renewal)} a year` : ''}.`);
                contact.classList.remove('hidden');
            } else {
                tone('no');
                result.textContent = `${data.domain} is already taken. Try another name or ending.`;
            }
        } catch {
            if (mine !== request) return;
            tone('error');
            result.textContent = 'We couldn\'t check that domain just now. Please try again.';
        } finally {
            if (mine === request) button.disabled = false;
            update();
        }
    };

    button.addEventListener('click', check);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            check();
        }
    });
    // A different name needs checking again before it can be ordered.
    input.addEventListener('input', () => {
        if (found && input.value.trim().toLowerCase() !== found.domain) {
            found = null;
            result.classList.add('hidden');
            update();
        }
    });
    const update = () => {
        const withPrivacy = Boolean(found?.privacy !== null && found?.privacy !== undefined && privacy.checked);
        privacyRow.classList.toggle('hidden', !(found && found.privacy !== null));
        sum('domain', found?.domain || input.value.trim().toLowerCase());
        sum('privacy', found ? (withPrivacy ? `Yes (+${money(found.privacy)})` : 'No') : '');
        label.textContent = 'First year';
        price.textContent = found ? money(found.price + (withPrivacy ? found.privacy : 0)) : '—';
        note.textContent = found ? (found.renewal ? `Renews at ${money(found.renewal)} a year.` : ' ') : 'Search for a name to see its price.';
        submit.disabled = !found && contact.classList.contains('hidden');
    };

    // After a failed submit the name is already filled in: look it up again.
    if (input.value.trim()) check();

    return update;
}

document.querySelectorAll('form[data-cloud-order]').forEach(setup);

// A service being set up (cloud/show.blade.php) checks again on its own.
const autoRefresh = document.querySelector('[data-auto-refresh]');
if (autoRefresh) {
    setTimeout(() => {
        if (document.visibilityState === 'visible') location.reload();
        else document.addEventListener('visibilitychange', () => location.reload(), { once: true });
    }, Number(autoRefresh.dataset.autoRefresh) * 1000);
}
