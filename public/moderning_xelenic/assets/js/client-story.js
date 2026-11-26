(() => {
  'use strict';

  // Client story pages (clients/show.blade.php): a gallery photo opens large
  // over the page with its caption. Arrow keys, the buttons or a swipe move
  // between photos; Escape, the close button or a click beside it closes it.
  const links = Array.from(document.querySelectorAll('[data-lightbox]'));
  if (!links.length) return;

  const box = document.createElement('div');
  box.className = 'story-lightbox';
  box.hidden = true;
  box.setAttribute('role', 'dialog');
  box.setAttribute('aria-modal', 'true');
  box.setAttribute('aria-label', 'Photo');
  box.innerHTML =
    '<figure><img alt="" /><figcaption></figcaption></figure>' +
    '<span class="lb-count" aria-live="polite"></span>' +
    '<button type="button" class="lb-close" aria-label="Close">&times;</button>' +
    '<button type="button" class="lb-prev" aria-label="Previous photo">&lsaquo;</button>' +
    '<button type="button" class="lb-next" aria-label="Next photo">&rsaquo;</button>';
  document.body.appendChild(box);

  const img = box.querySelector('img');
  const caption = box.querySelector('figcaption');
  const count = box.querySelector('.lb-count');
  if (links.length < 2) box.querySelectorAll('.lb-prev, .lb-next').forEach((button) => { button.hidden = true; });

  let index = 0;
  let opener = null;

  const show = (i) => {
    index = (i + links.length) % links.length;
    const link = links[index];
    img.src = link.href;
    img.alt = link.querySelector('img')?.alt || '';
    caption.textContent = link.dataset.caption || '';
    caption.hidden = !link.dataset.caption;
    count.textContent = links.length > 1 ? `${index + 1} / ${links.length}` : '';
  };
  const open = (i, from) => {
    opener = from;
    show(i);
    box.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    box.querySelector('.lb-close').focus();
  };
  const close = () => {
    box.hidden = true;
    document.documentElement.style.overflow = '';
    if (opener) opener.focus();
  };

  links.forEach((link, i) => link.addEventListener('click', (event) => {
    event.preventDefault();
    open(i, link);
  }));

  box.addEventListener('click', (event) => {
    if (event.target === box || event.target.closest('.lb-close')) close();
    else if (event.target.closest('.lb-prev')) show(index - 1);
    else if (event.target.closest('.lb-next')) show(index + 1);
  });

  document.addEventListener('keydown', (event) => {
    if (box.hidden) return;
    if (event.key === 'Escape') close();
    else if (event.key === 'ArrowLeft') show(index - 1);
    else if (event.key === 'ArrowRight') show(index + 1);
  });

  let startX = null;
  box.addEventListener('touchstart', (event) => { startX = event.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', (event) => {
    if (startX === null) return;
    const dx = event.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
    startX = null;
  });
})();
