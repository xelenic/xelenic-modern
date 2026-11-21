/* Startup: wire the top bar, tabs, menus, keyboard shortcuts, then load the project. */

(function init() {
  $$('[data-icon]').forEach(function (el) { el.insertAdjacentHTML('afterbegin', icon(el.dataset.icon)); });

  $$('#viewSeg button').forEach(function (b) { b.addEventListener('click', function () { setView(b.dataset.view); }); });
  $('#btnUndo').addEventListener('click', undo);
  $('#btnRedo').addEventListener('click', redo);
  $('#btnPlay').addEventListener('click', function () { openPreview(false); });
  $('#btnPlayHere').addEventListener('click', function () { openPreview(true); });
  $('#btnData').addEventListener('click', openData);

  themeMode = loadThemeMode();
  applyTheme();
  $('#btnTheme').addEventListener('click', cycleTheme);
  if (themeQuery) {
    const onSystemChange = function () { if (themeMode === 'system') applyTheme(); };
    if (themeQuery.addEventListener) themeQuery.addEventListener('change', onSystemChange);
    else if (themeQuery.addListener) themeQuery.addListener(onSystemChange);
  }
  $('#orient').addEventListener('change', function (e) { setOrientation(e.target.value); });

  const nameInput = $('#projName');
  nameInput.addEventListener('input', function () { project.name = nameInput.value; save(); });
  nameInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') nameInput.blur(); });

  $('#btnFile').addEventListener('click', function (e) { e.stopPropagation(); $('#fileMenu').hidden = !$('#fileMenu').hidden; });
  document.addEventListener('click', function (e) { if (!e.target.closest('.menu-wrap')) closeMenus(); });
  $$('#fileMenu button').forEach(function (b) {
    b.addEventListener('click', function () {
      closeMenus();
      const cmd = b.dataset.cmd;
      if (cmd === 'new') newProjectDialog();
      else if (cmd === 'open') $('#fileOpen').click();
      else if (cmd === 'save') saveJson();
      else if (cmd === 'export') exportKiosk();
      else if (cmd === 'importTemplate') $('#fileTemplate').click();
      else if (cmd === 'sampleTemplate') downloadSampleTemplate();
    });
  });
  $('#fileOpen').addEventListener('change', function (e) { if (e.target.files[0]) openJson(e.target.files[0]); e.target.value = ''; });
  $('#fileImage').addEventListener('change', function (e) { if (e.target.files.length) uploadImages(e.target.files); e.target.value = ''; });
  $('#fileTemplate').addEventListener('change', function (e) { if (e.target.files[0]) importTemplateFile(e.target.files[0]); e.target.value = ''; });

  $$('#leftTabs button').forEach(function (b) { b.addEventListener('click', function () { leftTab = b.dataset.tab; renderLeft(); }); });
  $$('#rightTabs button').forEach(function (b) { b.addEventListener('click', function () { rightTab = b.dataset.tab; renderInspector(); }); });

  $('#zoomIn').addEventListener('click', function () { setZoom(stageScale() * 1.25); });
  $('#zoomOut').addEventListener('click', function () { setZoom(stageScale() / 1.25); });
  $('#zoomFit').addEventListener('click', fitStage);
  $('#stageAnimate').addEventListener('click', replayAnimations);

  $('#pvClose').addEventListener('click', closePreview);
  $('#pvRestart').addEventListener('click', function () { if (player) player.reset(); });
  $('#modal').addEventListener('pointerdown', function (e) { if (e.target.id === 'modal') closeModal(); });
  window.addEventListener('resize', function () { if (!$('#preview').hidden) sizePreview(); });

  document.addEventListener('keydown', function (e) {
    const tag = (e.target.tagName || '').toLowerCase();
    const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
    const mod = e.ctrlKey || e.metaKey;
    if (e.key === 'Escape') {
      if (!$('#preview').hidden) closePreview();
      else if (!$('#modal').hidden) closeModal();
      else if (!$('#fileMenu').hidden) closeMenus();
      else if (typing) e.target.blur();
      else if (selId) selectObject(null);
      return;
    }
    if (!$('#preview').hidden || !$('#modal').hidden) return;
    if (mod && e.key.toLowerCase() === 'z' && !typing) { e.preventDefault(); if (e.shiftKey) redo(); else undo(); return; }
    if (mod && e.key.toLowerCase() === 'y' && !typing) { e.preventDefault(); redo(); return; }
    if (mod && e.key.toLowerCase() === 's') { e.preventDefault(); saveJson(); return; }
    if (typing) return;
    if (mod && e.key.toLowerCase() === 'd') { e.preventDefault(); duplicateObject(); return; }
    if ((e.key === 'Delete' || e.key === 'Backspace') && selId && view === 'editor') { e.preventDefault(); deleteObject(); return; }
    if (e.key.indexOf('Arrow') === 0 && selId && view === 'editor') {
      e.preventDefault();
      const step = e.shiftKey ? 10 : 1;
      nudge(e.key === 'ArrowLeft' ? -step : e.key === 'ArrowRight' ? step : 0, e.key === 'ArrowUp' ? -step : e.key === 'ArrowDown' ? step : 0);
      return;
    }
    if (!mod && e.key.toLowerCase() === 'p') { openPreview(false); }
  });

  initStage();
  initFlow();
  initTimeline();

  const saved = loadSaved();
  setProject(saved && Array.isArray(saved.screens) ? saved : demoProject());
  if (!saved) toast('Welcome! This demo kiosk is ready. Press Play to try it.');
})();
