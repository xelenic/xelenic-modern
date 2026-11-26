// Developer Careers (resources/views/careers/show.blade.php): filter the
// roles by team, "Apply for this role" fills in the form's role, and the CV
// field shows the file chosen (or dropped on it).
(() => {
  'use strict';

  // ---- Filter the roles ----
  const filters = [...document.querySelectorAll('[data-role-filters] [data-team]')];
  const cards = [...document.querySelectorAll('[data-role-card]')];
  filters.forEach((button) => button.addEventListener('click', () => {
    const team = button.dataset.team;
    filters.forEach((other) => {
      other.classList.toggle('is-active', other === button);
      other.setAttribute('aria-pressed', String(other === button));
    });
    cards.forEach((card) => { card.hidden = team !== 'all' && card.dataset.team !== team; });
  }));

  // A link to a role (#role-…) opens it.
  const fromHash = () => {
    const card = location.hash.startsWith('#role-') && document.getElementById(location.hash.slice(1));
    if (card && card.tagName === 'DETAILS') card.open = true;
  };
  fromHash();
  window.addEventListener('hashchange', fromHash);

  // ---- Apply for a role ----
  const form = document.querySelector('[data-apply-form]');
  const select = form?.querySelector('#role');
  document.querySelectorAll('[data-apply-for]').forEach((link) => link.addEventListener('click', (event) => {
    if (!select) return;
    event.preventDefault();
    select.value = link.dataset.applyFor;
    document.getElementById('apply').scrollIntoView({ behavior: 'smooth', block: 'start' });
    setTimeout(() => form.querySelector('#name').focus({ preventScroll: true }), 600);
  }));

  // ---- The CV ----
  const drop = document.querySelector('[data-file-drop]');
  if (drop) {
    const input = drop.querySelector('input[type="file"]');
    const label = drop.querySelector('[data-file-name]');
    const original = label.innerHTML;
    const show = () => {
      const file = input.files && input.files[0];
      drop.classList.toggle('has-file', !!file);
      if (!file) { label.innerHTML = original; return; }
      const size = file.size > 1024 * 1024 ? (file.size / 1024 / 1024).toFixed(1) + ' MB' : Math.max(1, Math.round(file.size / 1024)) + ' KB';
      label.textContent = '';
      const name = document.createElement('b');
      name.textContent = file.name;
      const note = document.createElement('small');
      note.textContent = size + ' · choose another to replace it';
      label.append(name, note);
    };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, () => drop.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, () => drop.classList.remove('is-over')));
  }
})();
