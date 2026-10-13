@extends('layouts.app')

@section('title', 'Struktur Audit | Verifikasi ISPO')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Struktur audit')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.team', $project) }}">Kelola Tim</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">{{ $project->company?->name }} · Salinan struktur dari {{ $project->template?->name }}</div>
            </div>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head" style="align-items: flex-start; flex-direction: column; gap: 16px;">
            <div>
                <h2>Struktur audit project</h2>
                <p>Struktur ini digunakan sebagai acuan pelaksanaan audit.</p>
                <div style="font-weight: 500; margin-top: 8px; color: var(--text);">
                    {{ $project->snapshot_principles_count }} Prinsip &bull;
                    {{ $project->snapshot_sections_count }} Kriteria &bull;
                    {{ $project->snapshot_indicators_count }} Indikator &bull;
                    {{ $project->snapshot_parameters_count }} Parameter
                </div>
            </div>

            <div style="width: 100%; position: relative;">
                <input type="search" id="structure-search" placeholder="Cari indikator atau parameter..." style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-strong); border-radius: 8px;">
            </div>
        </div>

        <style>
            .structure-accordion summary::-webkit-details-marker { display: none; }
            .structure-accordion summary { list-style: none; }
            .structure-accordion[open] > summary .acc-icon { transform: rotate(180deg); }

            #search-results {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .search-item {
                padding: 12px;
                border: 1px solid var(--border);
                border-radius: 8px;
                background: #fff;
            }
            .search-path {
                font-size: 12px;
                color: var(--muted);
                margin-bottom: 4px;
            }
            .search-highlight {
                background: #fff3cd;
                padding: 0 2px;
                border-radius: 2px;
            }
        </style>

        @if ($tree)
            <div id="structure-tree-container">
                @include('audit-projects._structure_node', ['nodes' => $tree])
            </div>
            <div id="search-results" style="display: none;"></div>
        @else
            <div class="empty">
                <strong>Belum ada struktur audit</strong>
                <span>Struktur mengikuti template yang dipilih saat project dibuat.</span>
            </div>
        @endif
    </section>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('structure-search');
            const treeContainer = document.getElementById('structure-tree-container');
            const resultsContainer = document.getElementById('search-results');

            if (!searchInput || !treeContainer || !resultsContainer) return;

            // Collect all search data from DOM attributes once on load
            const searchData = [];

            document.querySelectorAll('.searchable-indicator').forEach(el => {
                searchData.push({
                    type: 'indicator',
                    code: el.getAttribute('data-code') || '',
                    title: el.getAttribute('data-title') || '',
                    path: el.getAttribute('data-path') || '',
                    html: el.innerHTML
                });
            });

            document.querySelectorAll('.searchable-parameter').forEach(el => {
                searchData.push({
                    type: 'parameter',
                    content: el.getAttribute('data-content') || '',
                    path: el.getAttribute('data-path') || '',
                    html: el.innerHTML
                });
            });

            let debounceTimer;

            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const query = e.target.value.trim().toLowerCase();

                    if (query === '') {
                        treeContainer.style.display = '';
                        resultsContainer.style.display = 'none';
                        resultsContainer.innerHTML = '';
                        return;
                    }

                    const matches = searchData.filter(item => {
                        if (item.type === 'indicator') {
                            return item.code.toLowerCase().includes(query) || item.title.toLowerCase().includes(query);
                        } else {
                            return item.content.toLowerCase().includes(query);
                        }
                    });

                    treeContainer.style.display = 'none';
                    resultsContainer.style.display = 'flex';

                    if (matches.length === 0) {
                        resultsContainer.innerHTML = `
                            <div class="empty" style="padding: 24px;">
                                <strong>Tidak ditemukan</strong>
                                <span>Tidak ada indikator atau parameter yang sesuai dengan pencarian Anda.</span>
                            </div>
                        `;
                        return;
                    }

                    let html = '';
                    matches.forEach(m => {
                        html += `
                            <div class="search-item">
                                <div class="search-path">${escapeHtml(m.path)}</div>
                                <div>${m.html}</div>
                            </div>
                        `;
                    });

                    resultsContainer.innerHTML = html;
                }, 300);
            });

            function escapeHtml(unsafe) {
                return (unsafe || '').replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
    @endpush
@endsection