// Tabs ([data-tabs], see projects/partials/tabs.blade.php). Every tab is a link
// to its own ?tab=…, so they work without JavaScript and can be shared. Here
// a tab switches in place and the address keeps up; the arrow keys, Home and
// End move between tabs; [data-tab-link] links elsewhere on the page open one.
document.querySelectorAll('[data-tabs]').forEach((tabs) => {
    const list = tabs.querySelector('[role="tablist"]');
    const links = [...list.querySelectorAll('[role="tab"]')];

    const select = (key, focus = false) => {
        const chosen = links.find((link) => link.dataset.tab === key);

        if (! chosen) return;

        links.forEach((link) => {
            const selected = link === chosen;
            link.setAttribute('aria-selected', String(selected));
            link.tabIndex = selected ? 0 : -1;
            document.getElementById(link.getAttribute('aria-controls')).hidden = ! selected;
        });

        if (focus) chosen.focus();
        reveal(chosen);
        history.replaceState(null, '', chosen.href);
    };

    // On a phone the tabs scroll sideways; keep the open one in sight.
    const reveal = (link) => {
        const left = link.offsetLeft - list.offsetLeft;
        if (left < list.scrollLeft || left + link.offsetWidth > list.scrollLeft + list.clientWidth) {
            list.scrollLeft = left - (list.clientWidth - link.offsetWidth) / 2;
        }
    };
    const open = links.find((link) => link.getAttribute('aria-selected') === 'true');
    if (open) reveal(open);

    tabs.addEventListener('click', (event) => {
        const link = event.target.closest('[role="tab"], [data-tab-link]');

        // A modified click still opens the tab in a new browser tab.
        if (! link || event.metaKey || event.ctrlKey || event.shiftKey) return;

        event.preventDefault();
        select(link.dataset.tab ?? link.dataset.tabLink);
    });

    list.addEventListener('keydown', (event) => {
        const index = links.indexOf(document.activeElement);
        const next = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: links.length - 1 }[event.key];

        if (index < 0 || next === undefined) return;

        event.preventDefault();
        select(links[(next + links.length) % links.length].dataset.tab, true);
    });
});
