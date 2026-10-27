document.addEventListener('DOMContentLoaded', () => {
    if (!window.Fancybox || !document.querySelector('.dg-items [data-dg-fancybox]')) return;
    // Только ссылки компонента; галереи других расширений сохраняют собственные настройки.
    window.Fancybox.bind('.dg-items [data-dg-fancybox]', {});
});
