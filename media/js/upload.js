document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dg-contents]').forEach(root => {
        const input = root.querySelector('[data-dg-files]');
        const drop = root.querySelector('[data-dg-drop]');
        const start = root.querySelector('[data-dg-start]');
        const status = root.querySelector('[data-dg-status]');
        const results = root.querySelector('[data-dg-results]');
        const subform = root.querySelector('joomla-field-subform');
        if (!subform) return;
        let busy = false;
        const text = key => Joomla.Text._(`COM_DHARMAGALLERY_${key}`);
        const waitFor = name => Promise.race([
            customElements.whenDefined(name),
            new Promise((resolve, reject) => setTimeout(() => reject(new Error(text('UPLOAD_FAILED'))), 5000))
        ]);
        const refresh = row => {
            if (!row) return;
            const value = row.querySelector('[name$="[path]"]')?.value || '';
            const image = row.querySelector('[data-dg-thumb]');
            const icon = row.querySelector('[data-dg-file-icon]');
            const label = row.querySelector('[data-dg-extension]');
            if (!image || !icon || !label) return;
            const path = value.split('#')[0].replace(/^local-images:\/+/, 'images/');
            const ext = path.split('.').pop()?.toLowerCase() || '';
            const isImage = ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
            image.classList.toggle('d-none', !isImage);
            icon.classList.toggle('d-none', isImage);
            label.textContent = path ? ext.toUpperCase() : '';
            if (isImage && /^images\//.test(path) && !path.split('/').includes('..')) {
                const base = (Joomla.getOptions('system.paths', {}).root || '').replace(/\/$/, '');
                image.src = `${base}/${path.split('/').map(encodeURIComponent).join('/')}`;
            } else image.removeAttribute('src');
        };
        const append = async data => {
            await waitFor('joomla-field-subform');
            const rows = subform.getRows();
            const row = subform.addRow(rows.length ? rows[rows.length - 1] : null);
            if (!row) throw new Error(text('TOO_MANY_FILES'));
            const field = row.querySelector('[name$="[path]"]');
            field.value = data.path;
            field.dispatchEvent(new Event('change', {bubbles: true}));
            const title = row.querySelector('[name$="[title]"]');
            if (title) { title.value = data.title; title.dispatchEvent(new Event('change', {bubbles: true})); }
            refresh(row);
            // Используем API ядра только для синхронизации медиаполя, после появления редактируемой карточки.
            const media = row.querySelector('joomla-field-media');
            if (media) { waitFor('joomla-field-media').then(() => { try { media.setValue(data.path); } catch (error) {} }).catch(() => {}); }
        };
        root.addEventListener('change', event => {
            if (event.target.matches('[name$="[path]"]')) refresh(event.target.closest('.subform-repeatable-group'));
        });
        root.addEventListener('subform-row-add', event => refresh(event.detail.row));
        subform.querySelectorAll('.subform-repeatable-group').forEach(refresh);
        if (!input) return;
        const setBusy = value => {
            busy = value; start.disabled = input.disabled = value;
            document.querySelectorAll('#toolbar-apply button, #toolbar-save button, #toolbar-save-new button').forEach(button => { button.disabled = value; });
        };
        const upload = async fileList => {
            if (busy || !fileList.length) return;
            const files = [...fileList];
            setBusy(true); results.replaceChildren(); let ok = 0;
            try {
                await waitFor('joomla-field-subform');
                for (const [index, file] of files.entries()) {
                    if (subform.getRows().length >= Number(subform.maximum)) { throw new Error(text('TOO_MANY_FILES')); }
                    status.textContent = `${text('UPLOADING')} ${index + 1}/${files.length}`;
                    const line = document.createElement('li'); line.textContent = file.name; results.append(line);
                    try {
                        const body = new FormData();
                        body.append('album_id', root.dataset.album); body.append('file', file); body.append(root.dataset.token, '1');
                        const response = await fetch(root.dataset.url, {method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'application/json'}});
                        let result;
                        try { result = await response.json(); } catch (error) { throw new Error(text('UPLOAD_FAILED')); }
                        if (!response.ok || result.error || !result.data?.path) throw new Error(result.message || text('UPLOAD_FAILED'));
                        await append(result.data);
                        line.classList.add('text-success'); line.textContent += ' — ✓'; ok++;
                    } catch (error) { line.classList.add('text-danger'); line.textContent += ` — ${error.message}`; }
                }
                status.textContent = `${text('UPLOAD_DONE')} ${ok}/${files.length}. ${ok ? text('SAVE_CONTENTS') : ''}`;
            } catch (error) { status.textContent = error.message; }
            finally { setBusy(false); input.value = ''; }
        };
        start.addEventListener('click', () => { if (!busy) input.click(); });
        input.addEventListener('change', () => upload(input.files));
        drop.addEventListener('dragover', event => { event.preventDefault(); drop.classList.add('dg-dragover'); });
        drop.addEventListener('dragleave', () => drop.classList.remove('dg-dragover'));
        drop.addEventListener('drop', event => { event.preventDefault(); drop.classList.remove('dg-dragover'); upload(event.dataTransfer.files); });
    });
});
