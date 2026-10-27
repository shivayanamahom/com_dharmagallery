document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dg-contents]').forEach(root => {
        const input = root.querySelector('[data-dg-files]');
        const drop = root.querySelector('[data-dg-drop]');
        const start = root.querySelector('[data-dg-start]');
        const status = root.querySelector('[data-dg-status]');
        const results = root.querySelector('[data-dg-results]');
        const subform = root.querySelector('joomla-field-subform');
        if (!input || !subform) return;
        let queue = [], busy = false;
        const choose = files => {
            queue = Array.from(files);
            status.textContent = queue.map(file => file.name).join(', ');
        };
        input.addEventListener('change', () => choose(input.files));
        drop.addEventListener('click', () => { if (!busy) input.click(); });
        drop.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); if (!busy) input.click(); }
        });
        drop.addEventListener('dragover', event => { event.preventDefault(); drop.classList.add('bg-light'); });
        drop.addEventListener('dragleave', () => drop.classList.remove('bg-light'));
        drop.addEventListener('drop', event => {
            event.preventDefault(); drop.classList.remove('bg-light');
            if (!busy) choose(event.dataTransfer.files);
        });
        const setBusy = value => {
            busy = value; start.disabled = input.disabled = value;
            document.querySelectorAll('#toolbar-apply button, #toolbar-save button, #toolbar-save-new button').forEach(button => { button.disabled = value; });
        };
        const append = async data => {
            await customElements.whenDefined('joomla-field-subform');
            await customElements.whenDefined('joomla-field-media');
            const rows = subform.getRows();
            const row = subform.addRow(rows.length ? rows[rows.length - 1] : null);
            if (!row) throw new Error(Joomla.Text._('COM_DHARMAGALLERY_UPLOAD_FAILED'));
            const media = row.querySelector('joomla-field-media');
            try { media.setValue(data.path); } catch (error) {
                const field = row.querySelector('[name$="[path]"]'); field.value = data.path;
                field.dispatchEvent(new Event('change', {bubbles: true}));
            }
            const title = row.querySelector('[name$="[title]"]');
            if (title) { title.value = data.title; title.dispatchEvent(new Event('change', {bubbles: true})); }
        };
        start.addEventListener('click', async () => {
            if (busy) return;
            if (!queue.length) { input.click(); return; }
            setBusy(true); results.replaceChildren(); let ok = 0;
            for (const [index, file] of queue.entries()) {
                status.textContent = `${Joomla.Text._('COM_DHARMAGALLERY_UPLOADING')} ${index + 1}/${queue.length}`;
                const line = document.createElement('li'); line.textContent = file.name; results.append(line);
                try {
                    const body = new FormData();
                    body.append('album_id', root.dataset.album); body.append('file', file); body.append(root.dataset.token, '1');
                    const response = await fetch(root.dataset.url, {method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'application/json'}});
                    const result = await response.json();
                    if (!response.ok || result.error) throw new Error(result.message || Joomla.Text._('COM_DHARMAGALLERY_UPLOAD_FAILED'));
                    await append(result.data);
                    line.classList.add('text-success'); line.textContent += ' — ✓'; ok++;
                } catch (error) { line.classList.add('text-danger'); line.textContent += ` — ${error.message}`; }
            }
            status.textContent = `${Joomla.Text._('COM_DHARMAGALLERY_UPLOAD_DONE')} ${ok}/${queue.length}. ${Joomla.Text._('COM_DHARMAGALLERY_SAVE_CONTENTS')}`;
            setBusy(false); queue = []; input.value = '';
        });
    });
});
