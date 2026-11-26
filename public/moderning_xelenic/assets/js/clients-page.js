// Our clients (resources/views/clients/index.blade.php): find a client by name.
(() => {
  'use strict';

  const search = document.querySelector('[data-client-search]');
  const items = [...document.querySelectorAll('[data-client-grid] [data-client]')];
  const none = document.querySelector('[data-client-none]');
  if (!search || !items.length) return;

  search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    let shown = 0;
    items.forEach((item) => {
      const match = !q || item.dataset.client.includes(q);
      item.hidden = !match;
      if (match) shown += 1;
    });
    if (none) none.hidden = shown > 0;
  });
})();
