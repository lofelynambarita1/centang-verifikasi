document.querySelectorAll('.hasil-add').forEach((details) => {
    details.addEventListener('toggle', () => {
        const summary = details.querySelector('summary');
        if (summary) summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
    });
});

const evidencePreviewAndSubmit = () => {
    document.querySelectorAll('input[type="file"][name="evidence"]').forEach((input) => {
        if (input.dataset.bound === 'true') {
            return;
        }

        input.dataset.bound = 'true';

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            const form = input.closest('form');
            const wrapper = input.parentElement;

            if (!file || !form) {
                return;
            }

            const existingPreview = wrapper.querySelector('.evidence-preview');
            if (existingPreview) {
                existingPreview.remove();
            }

            const preview = document.createElement('div');
            preview.className = 'evidence-preview';

            if (file.type.startsWith('image/')) {
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = 'Preview bukti';
                preview.appendChild(image);
            } else {
                const label = document.createElement('span');
                label.textContent = file.name;
                preview.appendChild(label);
            }

            wrapper.appendChild(preview);
            form.requestSubmit();
        });
    });
};

evidencePreviewAndSubmit();

document.addEventListener('turbo:load', evidencePreviewAndSubmit);

document.querySelectorAll('[data-app-toast]').forEach((toast) => {
    const key = toast.dataset.toastKey;
    const storageKey = key ? `centang-verifikasi:${key}` : null;

    if (storageKey && sessionStorage.getItem(storageKey)) {
        toast.remove();
        return;
    }

    if (storageKey) {
        sessionStorage.setItem(storageKey, 'shown');
    }

    let dismissTimeout;
    const dismiss = () => {
        window.clearTimeout(dismissTimeout);
        toast.classList.add('app-toast--dismissed');
        window.setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', dismiss);
    dismissTimeout = window.setTimeout(dismiss, Number(toast.dataset.toastDuration) || 10000);
});
