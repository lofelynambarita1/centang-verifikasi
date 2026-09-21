document.querySelectorAll('.hasil-add').forEach((details) => {
    details.addEventListener('toggle', () => {
        const summary = details.querySelector('summary');
        if (summary) summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
    });
});