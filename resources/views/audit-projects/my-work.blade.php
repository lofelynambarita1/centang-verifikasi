@extends('layouts.app')

@php
    $statusMeta = [
        'not_started' => ['label' => 'Belum Dikerjakan', 'class' => 'chip--none', 'action' => 'Mulai Audit'],
        'draft' => ['label' => 'Draft', 'class' => 'chip--wip', 'action' => 'Lanjutkan Audit'],
        'submitted' => ['label' => 'Menunggu Review', 'class' => 'chip--todo', 'action' => 'Lihat Hasil Audit'],
        'revision_requested' => ['label' => 'Revisi Diminta', 'class' => 'chip--wip', 'action' => 'Perbaiki Audit'],
        'approved' => ['label' => 'Approved', 'class' => 'chip--done', 'action' => 'Lihat Hasil Audit'],
    ];
@endphp

@section('title', 'Pekerjaan Saya | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Pekerjaan Saya')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
    @can('manageAssignments', $project)
        <a class="btn btn--ghost" href="{{ route('audit-projects.assignments', $project) }}">Pembagian Auditor</a>
    @endcan
@endsection

@section('content')
    <section class="hero auditor-work-hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">{{ $project->company?->name }} · {{ $summary['total'] }} indikator menjadi tanggung jawab Anda</div>
            </div>
        </div>
        <div class="auditor-progress">
            <div class="auditor-progress__head">
                <div>
                    <span class="eyebrow">PROGRESS AUDIT</span>
                    <strong>{{ $summary['completed'] }} / {{ $summary['total'] }} indikator selesai</strong>
                </div>
                <strong>{{ $summary['completion_percentage'] }}%</strong>
            </div>
            <div class="auditor-progress__track" role="progressbar" aria-valuenow="{{ $summary['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                <span style="width: {{ $summary['completion_percentage'] }}%;"></span>
            </div>
            <div class="auditor-status-counts" aria-label="Ringkasan status indikator">
                @foreach (['not_started', 'draft', 'submitted', 'revision_requested', 'approved'] as $status)
                    @php
                        $countKey = match ($status) {
                            'not_started' => 'not_started',
                            'draft' => 'draft_complete',
                            'submitted' => 'submitted',
                            'revision_requested' => 'revision_requested',
                            'approved' => 'approved',
                        };
                    @endphp
                    @if (($summary[$countKey] ?? 0) > 0)
                        <span class="auditor-status-count auditor-status-count--{{ $status }}">
                            <strong>{{ $summary[$countKey] }}</strong>
                            {{ $statusMeta[$status]['label'] }}
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <section class="panel panel--pad auditor-work-panel">
        <div class="panel__head">
            <div>
                <h2>Indikator Saya</h2>
                <p>Kerjakan indikator yang menjadi tanggung jawab Anda. Simpan sebagai draft atau kirim hasil audit untuk review.</p>
            </div>
        </div>

        @if ($indicators->isNotEmpty())
            <div class="auditor-work-toolbar">
                <label class="auditor-work-search">
                    <span class="sr-only">Cari indikator</span>
                    <input type="search" placeholder="Cari indikator..." data-auditor-work-search>
                </label>
                <div class="auditor-work-filters" role="group" aria-label="Filter status indikator">
                    <button class="auditor-work-filter is-active" type="button" data-status-filter="all">Semua</button>
                    @foreach (['not_started', 'draft', 'submitted', 'revision_requested', 'approved'] as $status)
                        <button class="auditor-work-filter" type="button" data-status-filter="{{ $status }}">{{ $statusMeta[$status]['label'] }}</button>
                    @endforeach
                </div>
            </div>

            <div class="auditor-indicator-list" data-auditor-indicator-list>
                @foreach ($indicators as $indicator)
                    @php
                        $workflowStatus = $indicator->result?->workflow_status?->value;
                        $status = $workflowStatus === null ? 'not_started' : ($workflowStatus === 'draft' ? 'draft' : $workflowStatus);
                        $meta = $statusMeta[$status] ?? $statusMeta['not_started'];
                        $searchText = strtolower($indicator->code.' '.$indicator->title.' '.($indicator->section?->principle?->title ?? '').' '.($indicator->section?->title ?? ''));
                    @endphp
                    <article class="auditor-indicator-card" data-status="{{ $status }}" data-search="{{ $searchText }}">
                        <div class="auditor-indicator-card__content">
                            <div class="auditor-indicator-card__code">Indikator {{ $indicator->code }}</div>
                            <h3>{{ $indicator->title }}</h3>
                            <p class="auditor-indicator-card__location">
                                <span>Lokasi</span>
                                {{ $indicator->section?->principle?->title ?? 'Prinsip' }}
                                <span aria-hidden="true">→</span>
                                {{ $indicator->section?->title ?? 'Kriteria' }}
                            </p>
                        </div>
                        <div class="auditor-indicator-card__footer">
                            <span class="chip {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                            <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.work.show', [$project, $indicator]) }}">{{ $meta['action'] }} →</a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="empty" data-auditor-work-empty hidden>
                <strong>Indikator tidak ditemukan</strong>
                <span>Coba ubah pencarian atau filter status.</span>
            </div>
        @else
            <div class="empty">
                <strong>Belum ada indikator audit yang ditugaskan kepada Anda.</strong>
                <span>Pembagian indikator belum tersedia atau audit belum dimulai oleh Tim Teknis.</span>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const list = document.querySelector('[data-auditor-indicator-list]');
            if (!list) return;

            const cards = Array.from(list.querySelectorAll('[data-status]'));
            const search = document.querySelector('[data-auditor-work-search]');
            const empty = document.querySelector('[data-auditor-work-empty]');
            let activeStatus = 'all';

            function applyFilters() {
                const query = (search?.value || '').trim().toLowerCase();
                let visible = 0;

                cards.forEach(function (card) {
                    const matchesStatus = activeStatus === 'all' || card.dataset.status === activeStatus;
                    const matchesSearch = !query || card.dataset.search.includes(query);
                    const show = matchesStatus && matchesSearch;
                    card.hidden = !show;
                    visible += show ? 1 : 0;
                });

                if (empty) empty.hidden = visible > 0;
            }

            document.querySelectorAll('[data-status-filter]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('[data-status-filter]').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    activeStatus = button.dataset.statusFilter;
                    applyFilters();
                });
            });

            search?.addEventListener('input', applyFilters);
        })();
    </script>
@endpush
