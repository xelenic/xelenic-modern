// Article editors ([data-md-editor], e.g. admin/clients/form.blade.php): the
// toolbar puts Markdown around the selection (or the lines it touches), Cmd/
// Ctrl+B and +I do bold and italic, and Preview shows the article as the page
// will, rendered by the server with the same rules.
document.querySelectorAll('[data-md-editor]').forEach((editor) => {
    const input = editor.querySelector('[data-md-input]');
    const preview = editor.querySelector('[data-md-preview]');
    const toolbar = editor.querySelector('[data-md-toolbar]');
    const modes = editor.querySelectorAll('[data-md-mode]');
    const token = input.form?.querySelector('input[name="_token"]')?.value ?? '';

    // Put text in place of start..end, then select selectFrom..selectTo.
    const replace = (start, end, text, selectFrom = start + text.length, selectTo = selectFrom) => {
        input.focus();
        input.setRangeText(text, start, end, 'end');
        input.setSelectionRange(selectFrom, selectTo);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    // **bold**, *italic*: around the selection, or a placeholder to type over.
    const wrap = (mark, placeholder) => {
        const { selectionStart: start, selectionEnd: end, value } = input;
        const text = value.slice(start, end) || placeholder;
        replace(start, end, mark + text + mark, start + mark.length, start + mark.length + text.length);
    };

    // ## headings, lists and quotes: at the start of each line selected (or the
    // line the cursor is on), in place of any such mark already there.
    const prefix = (mark) => {
        const { value } = input;
        const start = value.lastIndexOf('\n', input.selectionStart - 1) + 1;
        let end = value.indexOf('\n', Math.max(input.selectionEnd, input.selectionStart));
        if (end < 0) end = value.length;
        const text = value.slice(start, end).split('\n')
            .map((line, i) => mark(i) + line.replace(/^(#{1,6} |> |[-*] |\d+\. )/, ''))
            .join('\n');
        replace(start, end, text, start, start + text.length);
    };

    const actions = {
        h2: () => prefix(() => '## '),
        h3: () => prefix(() => '### '),
        bold: () => wrap('**', 'bold text'),
        italic: () => wrap('*', 'italic text'),
        ul: () => prefix(() => '- '),
        ol: () => prefix((i) => `${i + 1}. `),
        quote: () => prefix(() => '> '),
        link: () => {
            const { selectionStart: start, selectionEnd: end, value } = input;
            const label = value.slice(start, end) || 'link text';
            const url = 'https://';
            // The address is selected, ready to paste over.
            const at = start + label.length + 3;
            replace(start, end, `[${label}](${url})`, at, at + url.length);
        },
        hr: () => replace(input.selectionStart, input.selectionEnd, '\n\n---\n\n'),
    };

    toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-md]');
        if (button) actions[button.dataset.md]?.();
    });

    input.addEventListener('keydown', (event) => {
        if (!(event.metaKey || event.ctrlKey)) return;
        const key = event.key.toLowerCase();
        if (key === 'b' || key === 'i') {
            event.preventDefault();
            actions[key === 'b' ? 'bold' : 'italic']();
        }
    });

    const show = async (mode) => {
        const previewing = mode === 'preview';
        modes.forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.mdMode === mode)));
        toolbar.querySelectorAll('[data-md]').forEach((button) => { button.disabled = previewing; });
        // Hidden, the textarea is still sent with the form.
        input.hidden = previewing;
        preview.hidden = !previewing;
        if (!previewing) {
            input.focus();
            return;
        }

        preview.textContent = 'Loading the preview…';
        try {
            const response = await fetch(editor.dataset.previewUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ article: input.value }),
            });
            if (!response.ok) throw new Error(String(response.status));
            const { html } = await response.json();
            // The server's HTML, with any raw HTML in the article dropped.
            preview.innerHTML = html.trim() || '<p class="text-slate-400">Nothing to preview yet. Write something first.</p>';
        } catch {
            preview.textContent = 'The preview couldn’t load. Check your connection and try again.';
        }
    };

    modes.forEach((tab) => tab.addEventListener('click', () => show(tab.dataset.mdMode)));
});
