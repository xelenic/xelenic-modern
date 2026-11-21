/* Opening the studio from the Xelenic panel.

   ?embed=1  The Kiosk App project form shows the studio in a frame and drives
             it through window.KioskStudioEmbed: load the design attached to the
             project (or start one at the kiosk's orientation), read it back,
             and give the page its place in the studio: its main button (Save
             design) beside Play and in the File menu, and the way back to it
             on the menu bar. The orientation is the project's, so its menu
             is locked.
   ?src=URL  Opens a design attached to a project, from the project's page.

   Either way it's someone's project rather than the studio's own, so nothing
   is autosaved over the project kept in this browser. This loads after the
   editor's functions and before editor-main.js starts the studio, so the
   replacements below are in place when it does. */

(function () {
  const params = new URLSearchParams(location.search);
  const embedded = params.get('embed') === '1' && window.parent !== window;
  const src = params.get('src');
  if (!embedded && !src) return;

  window.loadSaved = function () { return blankProject(); };
  window.save = function () {};

  if (src) {
    const url = new URL(src, location.href);
    if (url.origin !== location.origin) return; // only designs from this site
    window.addEventListener('load', function () {
      fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(String(r.status)); return r.json(); })
        .then(function (p) {
          if (!p || !Array.isArray(p.screens)) throw new Error('bad');
          setProject(p);
          toast('Opened "' + (p.name || 'design') + '" · changes stay here; use File → Save project to keep a copy');
        })
        .catch(function () { toast('That design could not be opened'); });
    });
    return;
  }

  let locked = null;
  const orient = document.getElementById('orient');
  orient.disabled = true;
  orient.title = 'Set by the screen orientation on your project';

  // The look of the page's button beside Play (see page() below).
  const style = document.createElement('style');
  style.textContent =
    '.embed-action{display:flex;align-items:center;height:34px;padding:0 16px;border:0;border-radius:8px;' +
    'font-weight:700;color:#fff;background:var(--accent);white-space:nowrap;cursor:pointer}' +
    '.embed-action:hover{filter:brightness(1.1)}' +
    '.embed-action:disabled{opacity:.5;cursor:not-allowed;filter:none}';
  document.head.appendChild(style);

  // Every project shown here takes the kiosk's orientation, including the
  // starting points picked from File → New, which all begin in portrait.
  const baseSetProject = setProject;
  window.setProject = function (p) {
    baseSetProject(p);
    if (locked) setOrientation(locked);
  };

  window.KioskStudioEmbed = {
    load: function (design, orientation) {
      locked = orientation === 'landscape' ? 'landscape' : 'portrait';
      if (design && Array.isArray(design.screens)) { window.setProject(design); return; }
      window.setProject(blankProject());
      newProjectDialog();
    },
    lock: function (orientation) {
      locked = orientation === 'landscape' ? 'landscape' : 'portrait';
      setOrientation(locked);
    },
    read: function () { return JSON.parse(JSON.stringify(project)); },
    // The page's place in the studio: its main button beside Play (action,
    // onAction) and at the top of the File menu with any other items of its
    // own, and on the right of the menu bar what this is (context) and the
    // way back (back, onBack), also at the end of the File menu. Asking
    // again just updates it all. Returns the button beside Play.
    page: function (o) {
      let button = document.getElementById('embedAction');
      if (!button) {
        button = h('button', { id: 'embedAction', type: 'button', class: 'embed-action' });
        document.getElementById('btnPlay').insertAdjacentElement('afterend', button);
      }
      button.textContent = o.action;
      button.onclick = o.onAction;

      setMenuExtras(
        [{ label: o.action, icon: 'download', primary: true, run: o.onAction, enabled: function () { return !button.disabled; } }]
          .concat((o.items || []).map(function (item) { return { label: item.label, icon: item.icon, run: item.run }; })),
        [{ label: o.back, icon: 'close', run: o.onBack }]
      );

      const right = document.getElementById('menubarRight');
      right.innerHTML = '';
      right.append(
        h('span', { class: 'mb-context' }, o.context || ''),
        h('button', { type: 'button', class: 'mb-back', onclick: o.onBack }, o.back)
      );
      return button;
    },
    // A message from the page, as the studio shows its own.
    notify: function (text) { toast(text); },
  };
})();
