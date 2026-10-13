import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const BlockEmbed = Quill.import('blots/block/embed');

class AuditEvidenceBlot extends BlockEmbed {
    static blotName = 'auditEvidence';

    static tagName = 'figure';

    static className = 'audit-evidence-embed';

    static create(value) {
        const node = super.create();
        const evidenceId = Number(value?.evidence_id || value?.id || 0);
        const registry = window.auditEvidenceRegistry || {};
        const evidence = registry[String(evidenceId)] || {};

        node.setAttribute('data-evidence-id', String(evidenceId));
        node.setAttribute('contenteditable', 'false');

        const image = document.createElement('img');
        image.src = evidence.preview_url || '';
        image.alt = evidence.name || 'Evidence audit';
        image.loading = 'lazy';
        node.appendChild(image);

        if (evidence.caption) {
            const caption = document.createElement('figcaption');
            caption.textContent = evidence.caption;
            node.appendChild(caption);
        }

        const actions = document.createElement('div');
        actions.className = 'audit-evidence-embed__actions';

        if (evidence.view_url) {
            const viewLink = document.createElement('a');
            viewLink.href = evidence.view_url;
            viewLink.target = '_blank';
            viewLink.rel = 'noopener';
            viewLink.textContent = 'Lihat';
            actions.appendChild(viewLink);
        }

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.setAttribute('data-remove-audit-evidence', String(evidenceId));
        removeButton.textContent = 'Hapus dari Hasil Audit';
        actions.appendChild(removeButton);

        node.appendChild(actions);

        return node;
    }

    static value(node) {
        return {
            evidence_id: Number(node.getAttribute('data-evidence-id')),
        };
    }
}

Quill.register(AuditEvidenceBlot);
window.Quill = Quill;

document.querySelectorAll('.hasil-add').forEach((details) => {
    details.addEventListener('toggle', () => {
        const summary = details.querySelector('summary');
        if (summary) summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
    });

});

document.querySelectorAll('[data-structure-summary]').forEach((summary) => {
    const details = summary.closest('details');

    if (!details) {
        return;
    }

    const updateExpandedState = () => {
        summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
    };

    updateExpandedState();
    details.addEventListener('toggle', updateExpandedState);
});

const logoutModal = document.querySelector('[data-logout-modal]');
const logoutTrigger = document.querySelector('[data-logout-trigger]');

if (logoutModal && logoutTrigger) {
    const logoutForm = logoutTrigger.closest('form');
    const logoutConfirm = logoutModal.querySelector('[data-logout-confirm]');
    const logoutCloseButtons = logoutModal.querySelectorAll('[data-logout-close]');
    let logoutLastFocused;

    const closeLogoutModal = () => {
        logoutModal.hidden = true;
        document.body.classList.remove('modal-open');
        logoutLastFocused?.focus();
    };

    const openLogoutModal = () => {
        logoutLastFocused = document.activeElement;
        logoutModal.hidden = false;
        document.body.classList.add('modal-open');
        logoutConfirm.focus();
    };

    logoutTrigger.addEventListener('click', openLogoutModal);
    logoutCloseButtons.forEach((button) => button.addEventListener('click', closeLogoutModal));
    logoutConfirm.addEventListener('click', () => logoutForm?.requestSubmit());
    document.addEventListener('keydown', (event) => {
        if (logoutModal.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeLogoutModal();
            return;
        }

        if (event.key === 'Tab') {
            const focusable = logoutModal.querySelectorAll('button');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
}

document.querySelectorAll('[data-auditor-picker]').forEach((picker) => {
    const trigger = picker.querySelector('.auditor-picker__trigger');
    const menu = picker.querySelector('.auditor-picker__menu');
    const search = picker.querySelector('[data-auditor-search]');
    const summary = picker.querySelector('[data-auditor-summary]');
    const options = [...picker.querySelectorAll('[data-auditor-option]')];
    const empty = picker.querySelector('[data-auditor-empty]');
    const leadSelect = document.querySelector('#lead_auditor_id');

    const updateSummary = () => {
        const selected = picker.querySelectorAll('[data-auditor-checkbox]:checked').length;
        summary.textContent = selected === 0 ? 'Belum ada anggota dipilih' : `${selected} anggota dipilih`;
    };

    const updateLeadOption = () => {
        const leadId = leadSelect?.value;

        options.forEach((option) => {
            const isLead = option.dataset.auditorId === leadId;
            const checkbox = option.querySelector('[data-auditor-checkbox]');

            option.hidden = isLead;
            checkbox.disabled = isLead;
            if (isLead) {
                checkbox.checked = false;
            }
        });

        updateSummary();
    };

    const closePicker = () => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };

    trigger.addEventListener('click', () => {
        menu.hidden = !menu.hidden;
        trigger.setAttribute('aria-expanded', String(!menu.hidden));
        if (!menu.hidden) {
            search.focus();
        }
    });

    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        let visible = 0;

        options.forEach((option) => {
            const matches = !query || option.dataset.auditorText.includes(query);
            if (!option.hidden) {
                option.style.display = matches ? 'flex' : 'none';
                visible += matches ? 1 : 0;
            }
        });

        empty.hidden = visible > 0;
    });

    picker.addEventListener('change', updateSummary);
    leadSelect?.addEventListener('change', updateLeadOption);
    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            closePicker();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePicker();
        }
    });

    updateLeadOption();
    updateSummary();
});
