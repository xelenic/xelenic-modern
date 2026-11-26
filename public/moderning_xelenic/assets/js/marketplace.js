// The marketplace (resources/views/marketplace): search, filter and sort a
// list of games or animation templates (cards carry data-name, data-category
// and, for sorting, data-order, data-rating and data-reviews), and start a
// game's demo in its kiosk.
(() => {
  'use strict';

  // ---- The list of games ----
  const grid = document.querySelector('[data-grid]');
  const toolbar = document.querySelector('[data-toolbar]');

  if (grid && toolbar) {
    const cards = [...grid.querySelectorAll('[data-item]')];
    const search = toolbar.querySelector('[data-search]');
    const sort = toolbar.querySelector('[data-sort]');
    const filters = [...toolbar.querySelectorAll('[data-filter]')];
    const none = document.querySelector('[data-no-results]');
    let filter = 'all';

    const apply = () => {
      const words = search.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
      let shown = 0;

      cards.forEach((card) => {
        const match = (filter === 'all' || card.dataset.category === filter)
          && words.every((word) => card.dataset.name.includes(word));
        card.hidden = !match;
        if (match) shown += 1;
      });

      const by = {
        featured: (a, b) => a.dataset.order - b.dataset.order,
        rating: (a, b) => (b.dataset.rating - a.dataset.rating) || (b.dataset.reviews - a.dataset.reviews) || (a.dataset.order - b.dataset.order),
        name: (a, b) => a.dataset.name.localeCompare(b.dataset.name),
      }[sort ? sort.value : 'featured'] || (() => 0);
      cards.slice().sort(by).forEach((card) => grid.appendChild(card));

      if (none) none.hidden = shown > 0;
    };

    filters.forEach((button) => button.addEventListener('click', () => {
      filter = button.dataset.filter;
      filters.forEach((other) => {
        other.classList.toggle('is-active', other === button);
        other.setAttribute('aria-pressed', String(other === button));
      });
      apply();
    }));
    search.addEventListener('input', apply);
    sort?.addEventListener('change', apply);

    // A link can open the list on one group: ?group=ads.
    const wanted = new URLSearchParams(location.search).get('group');
    filters.find((button) => button.dataset.filter === wanted)?.click();
  }

  // ---- A game's demo ----
  const device = document.querySelector('[data-demo][data-demo-src]');

  if (device) {
    const start = () => {
      if (!device.classList.contains('is-playing')) {
        const frame = document.createElement('iframe');
        frame.src = device.dataset.demoSrc;
        frame.title = 'Game demo';
        frame.setAttribute('sandbox', 'allow-scripts');
        frame.setAttribute('allow', 'fullscreen');
        device.querySelector('.mk-device-screen').appendChild(frame);
        device.classList.add('is-playing');
        frame.addEventListener('load', () => frame.focus(), { once: true });
      }
      // On narrow screens the kiosk is below the words: bring it into view.
      const box = device.getBoundingClientRect();
      if (box.top < 0 || box.bottom > window.innerHeight) {
        device.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    };

    document.querySelectorAll('[data-demo-start]').forEach((button) => button.addEventListener('click', start));
  }
})();
