// Use native select change events for mouse, touch and keyboard users.
const alertFilters = document.querySelector('.alert-filters');
if (alertFilters) {
    alertFilters.addEventListener('change', event => {
        if (!event.target.matches('select')) return;
        alertFilters.elements.page.value = '1';
        const url = new URL(window.location.href);
        url.searchParams.set('level', alertFilters.elements.level.value);
        url.searchParams.set('per_page', alertFilters.elements.per_page.value);
        url.searchParams.set('page', '1');
        url.hash = 'alerts-heading';
        window.location.assign(url.href);
    });
}
