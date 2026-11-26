// The Kiosk Studio page (resources/views/studio/show.blade.php): clicking a
// screenshot shows it full size in a dialog. Escape, the × or a click
// outside the picture closes it.
(() => {
  'use strict';

  const box = document.querySelector('[data-lightbox]');
  if (!box || typeof box.showModal !== 'function') return;

  const img = box.querySelector('[data-lightbox-img]');
  let opener = null;

  document.querySelectorAll('[data-zoom]').forEach((button) => button.addEventListener('click', () => {
    opener = button;
    img.src = button.dataset.zoom;
    img.alt = button.dataset.alt || '';
    box.setAttribute('aria-label', button.dataset.alt || 'Screenshot');
    box.showModal();
  }));

  box.addEventListener('click', (event) => {
    if (event.target === box) box.close();
  });
  box.addEventListener('close', () => opener?.focus());
})();
