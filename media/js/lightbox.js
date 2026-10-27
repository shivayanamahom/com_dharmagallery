document.addEventListener('DOMContentLoaded', () => {
 const modal = document.getElementById('dg-lightbox');
 if (!modal || !window.bootstrap?.Modal) return;
 const links = [...document.querySelectorAll('[data-dg-image]')]; let index = 0;
 const image = modal.querySelector('[data-dg-full]');
 const show = i => { index = (i + links.length) % links.length; const link = links[index]; image.src = link.href; image.alt = link.querySelector('img')?.alt || ''; modal.querySelector('.modal-title').textContent = link.dataset.title || ''; };
 links.forEach((link, i) => link.addEventListener('click', e => { e.preventDefault(); show(i); bootstrap.Modal.getOrCreateInstance(modal).show(); }));
 modal.querySelector('[data-dg-prev]').addEventListener('click', () => show(index - 1));
 modal.querySelector('[data-dg-next]').addEventListener('click', () => show(index + 1));
 modal.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') show(index - 1); if (e.key === 'ArrowRight') show(index + 1); });
 modal.addEventListener('hidden.bs.modal', () => { image.removeAttribute('src'); });
});
