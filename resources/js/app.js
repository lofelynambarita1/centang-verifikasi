document.querySelectorAll('.hasil-add').forEach((details) => {
    details.addEventListener('toggle', () => {
        const summary = details.querySelector('summary');
        if (summary) summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
    });
});

const evidenceFilePicker = () => {
    document.querySelectorAll('input[type="file"][name="evidence"]').forEach((input) => {
        if (input.dataset.bound === 'true') {
            return;
        }

        input.dataset.bound = 'true';

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            const picker = input.closest('.hasil-file-picker');

            if (!file || !picker) {
                return;
            }

            const existingPreview = picker.querySelector('.evidence-preview');
            if (existingPreview) {
                existingPreview.remove();
            }

            const preview = document.createElement('div');
            preview.className = 'evidence-preview';
            preview.setAttribute('aria-live', 'polite');

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

            picker.appendChild(preview);
        });
    });
};

const auditStatusOptions = {
    pending: { label: 'Belum diperiksa', chip: 'chip--todo' },
    compliant: { label: 'Memenuhi', chip: 'chip--done' },
    non_compliant: { label: 'Tidak memenuhi', chip: 'chip--red' },
    not_applicable: { label: 'NA', chip: 'chip--todo' },
};

const auditStatusHighlight = () => {
    document.querySelectorAll('[data-audit-status]').forEach((select) => {
        if (select.dataset.statusBound === 'true') {
            return;
        }

        select.dataset.statusBound = 'true';
        select.addEventListener('change', () => {
            const auditItem = select.closest('[data-audit-item]');
            const statusChip = auditItem?.querySelector('[data-audit-status-chip]');
            const parameterLabel = auditItem?.querySelector('[data-audit-parameter-label]');
            const alert = auditItem?.querySelector('.hasil-alert');
            const statusControls = auditItem?.querySelector('[data-audit-status-controls]');
            const isNonCompliant = select.value === 'non_compliant';
            const statusOption = auditStatusOptions[select.value];

            if (!auditItem || !statusChip || !parameterLabel || !alert || !statusOption) {
                return;
            }

            auditItem.classList.toggle('checklist-item--non-compliant', isNonCompliant);
            statusControls?.classList.toggle('status-form--non-compliant', isNonCompliant);
            auditItem.querySelector('.parameter-box')?.classList.toggle('parameter-box--non-compliant', isNonCompliant);
            auditItem.querySelector('.hasil-block')?.classList.toggle('hasil-block--non-compliant', isNonCompliant);
            auditItem.querySelectorAll('.hasil-card').forEach((card) => {
                card.classList.toggle('hasil-card--non-compliant', isNonCompliant);
            });
            auditItem.querySelectorAll('.note-box').forEach((note) => {
                note.classList.toggle('note-box--non-compliant', isNonCompliant);
            });

            parameterLabel.textContent = isNonCompliant ? 'Parameter · Tidak memenuhi' : 'Parameter';
            alert.hidden = !isNonCompliant;
            statusChip.textContent = statusOption.label;
            statusChip.classList.remove('chip--todo', 'chip--done', 'chip--red');
            statusChip.classList.add(statusOption.chip);
        });
    });
};

evidenceFilePicker();
auditStatusHighlight();

document.addEventListener('turbo:load', evidenceFilePicker);
document.addEventListener('turbo:load', auditStatusHighlight);
