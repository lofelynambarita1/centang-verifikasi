const sections = document.querySelectorAll('.page-section');
const links = document.querySelectorAll('[data-view-link]');
const pageLabel = document.querySelector('#page-label');
const labels = { dashboard: 'Dashboard', companies: 'Daftar perusahaan', checklist: 'Checklist audit', reports: 'Laporan' };

function showView(view) {
    const target = document.querySelector(`#${view}-view`);
    if (!target) return;
    sections.forEach((section) => section.classList.toggle('active', section === target));
    links.forEach((link) => link.classList.toggle('active', link.dataset.viewLink === view));
    if (pageLabel) pageLabel.textContent = labels[view] || 'Dashboard';
}

links.forEach((link) => link.addEventListener('click', (event) => {
    event.preventDefault();
    const view = link.dataset.viewLink;
    showView(view);
    history.replaceState(null, '', `#${view}`);
}));

document.querySelectorAll('.open-checklist, .row-action').forEach((button) => button.addEventListener('click', () => showView('checklist')));

const toast = document.querySelector('#toast');
document.querySelector('#finish-audit')?.addEventListener('click', () => {
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
});

document.querySelectorAll('.select-status input').forEach((input) => input.addEventListener('change', () => {
    const checked = document.querySelectorAll('.select-status input:checked').length;
    const count = document.querySelector('#checked-count');
    const percent = document.querySelector('#progress-percent');
    const bar = document.querySelector('#progress-bar');
    const value = Math.round((checked / 4) * 100);
    if (count) count.textContent = checked;
    if (percent) percent.textContent = `${value}%`;
    if (bar) bar.style.width = `${value}%`;
}));

const initialView = window.location.hash.replace('#', '') || 'dashboard';
showView(labels[initialView] ? initialView : 'dashboard');
