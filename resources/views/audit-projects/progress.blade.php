@extends('layouts.app')

@section('title', 'Monitoring Progress | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Monitoring & Review')
@section('project-role', $projectRoleLabel ?: auth()->user()->system_role->label())

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
@endsection

@php
    $reviewTabs = [
        'submitted' => ['label' => 'Perlu Review', 'count' => $reviewCounts['submitted']],
        'revision_requested' => ['label' => 'Revisi', 'count' => $reviewCounts['revision_requested']],
        'approved' => ['label' => 'Approved', 'count' => $reviewCounts['approved']],
        'all' => ['label' => 'Semua', 'count' => $summary['total']],
    ];
@endphp

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">Monitoring read-only dan review hasil audit.</div>
            </div>
        </div>
    </section>

    <section class="panel panel--pad progress-review-queue">
        <div class="panel__head">
            <div>
                <h2>Perlu Direview</h2>
                <p>Hasil audit yang telah dikirim auditor dan menunggu tindakan Anda.</p>
            </div>
            <strong class="progress-review-queue__count">{{ $reviewCounts['submitted'] }} Perlu Direview</strong>
        </div>

        <nav class="progress-review-tabs" aria-label="Filter review">
            @foreach ($reviewTabs as $status => $tab)
                <a class="progress-review-tab {{ $reviewStatus === $status ? 'is-active' : '' }}" href="{{ route('audit-projects.progress', [$project, 'review_status' => $status]) }}">
                    {{ $tab['label'] }} ({{ $tab['count'] }})
                </a>
            @endforeach
        </nav>

        @if ($reviewQueue->isNotEmpty())
            <div class="progress-review-list">
                @foreach ($reviewQueue as $indicator)
                    @php
                        $status = $indicator->result?->workflow_status?->value;
                        $statusLabel = match ($status) {
                            'submitted' => 'Menunggu Review',
                            'revision_requested' => 'Revisi',
                            'approved' => 'Approved',
                            default => 'Belum Dikerjakan',
                        };
                        $statusClass = match ($status) {
                            'submitted' => 'chip--todo',
                            'revision_requested' => 'chip--wip',
                            'approved' => 'chip--done',
                            default => 'chip--none',
                        };
                    @endphp
                    <article class="progress-review-card">
                        <div class="progress-review-card__body">
                            <div class="progress-review-card__eyebrow">
                                <span class="chip {{ $statusClass }}">{{ $statusLabel }}</span>
                                <span>Indikator {{ $indicator->code }}</span>
                            </div>
                            <h3>{{ $indicator->title }}</h3>
                            <div class="progress-review-card__meta">
                                <span>Auditor: {{ $indicator->assignment?->pic?->name ?? '-' }}</span>
                                <span>{{ $indicator->section?->principle?->title ?? 'Struktur belum tersedia' }}</span>
                                <span>Dikirim: {{ $indicator->result?->submitted_at?->format('d M Y · H:i') ?? '-' }}</span>
                                <span>{{ $indicator->activeEvidences->count() }} Evidence</span>
                                @if ($indicator->result?->assessment_status)
                                    <span>Kesimpulan: {{ $indicator->result->assessment_status->label() }}</span>
                                @endif
                            </div>
                        </div>
                        @can('review', $indicator)
                            <a class="btn btn--primary" href="{{ route('audit-projects.work.show', [$project, $indicator]) }}">
                                {{ $status === 'submitted' ? 'Review Sekarang' : 'Lihat Hasil Audit' }} →
                            </a>
                        @else
                            <span class="chip chip--wip">Menunggu reviewer lain</span>
                        @endcan
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty empty--compact">
                <strong>✓ Tidak ada hasil audit yang menunggu review</strong>
                <span>Semua hasil audit pada filter ini telah ditindaklanjuti.</span>
            </div>
        @endif
    </section>

    <section class="panel panel--pad">
        <div class="panel__head"><div><h2>Progress Audit</h2></div></div>
        <div class="stat-grid progress-summary-grid">
            <div class="stat-card"><span>Total Indikator</span><strong>{{ $summary['total'] }}</strong></div>
            <div class="stat-card"><span>Belum Dikerjakan</span><strong>{{ $summary['not_started'] }}</strong></div>
            <div class="stat-card"><span>Menunggu Review</span><strong>{{ $summary['submitted'] }}</strong></div>
            <div class="stat-card"><span>Progress Approved</span><strong>{{ $summary['completed'] }} / {{ $summary['total'] }}</strong><small>{{ $summary['completion_percentage'] }}% selesai direview</small></div>
        </div>
        <div class="progress-bar" aria-label="Progress Approved">
            <span style="width: {{ $summary['completion_percentage'] }}%"></span>
        </div>
        <p class="cell-meta">{{ $summary['indicators_with_result'] }} / {{ $summary['total'] }} memiliki hasil audit.</p>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head"><div><h2>Progress per Auditor</h2></div></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Auditor</th><th>Ditugaskan</th><th>Belum Dikerjakan</th><th>Draft</th><th>Menunggu Review</th><th>Revisi</th><th>Approved</th><th>Progress</th></tr></thead>
                <tbody>
                @forelse ($auditors as $row)
                    <tr>
                        <td>{{ $row['user']->name }}</td>
                        <td>{{ $row['assigned'] }}</td>
                        <td>{{ $row['not_started'] }}</td>
                        <td>{{ $row['draft_complete'] }}</td>
                        <td>
                            @if ($row['submitted'] > 0)
                                <a href="{{ route('audit-projects.progress', [$project, 'review_status' => 'submitted', 'review_auditor' => $row['user']->id]) }}">{{ $row['submitted'] }}</a>
                            @else
                                0
                            @endif
                        </td>
                        <td>{{ $row['revision_requested'] }}</td>
                        <td>{{ $row['approved'] }}</td>
                        <td>{{ $row['completeness_percentage'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada anggota project.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head"><div><h2>Daftar Indikator</h2><p>Monitoring read-only; pilih indikator untuk melihat workspace.</p></div></div>
        <form method="GET" class="filter-bar" style="margin-bottom: 1rem;">
            <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Cari kode atau judul">
            <select class="input" name="auditor">
                <option value="">Semua auditor</option>
                @foreach ($auditors as $row)
                    <option value="{{ $row['user']->id }}" @selected((string) request('auditor') === (string) $row['user']->id)>{{ $row['user']->name }}</option>
                @endforeach
            </select>
            <select class="input" name="progress_state">
                <option value="">Semua status</option>
                @foreach (\App\Enums\AuditProgressState::cases() as $state)
                    <option value="{{ $state->value }}" @selected(request('progress_state') === $state->value)>{{ $state->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn--ghost" type="submit">Filter</button>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kode</th><th>Indikator</th><th>Prinsip</th><th>PIC</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($indicators->filter(function ($indicator) {
                    $search = strtolower((string) request('search'));
                    $matchesSearch = $search === '' || str_contains(strtolower($indicator->code.' '.$indicator->title), $search);
                    $matchesState = request('progress_state') === null || request('progress_state') === '' || $indicator->progress_state->value === request('progress_state');
                    $matchesAuditor = request('auditor') === null || request('auditor') === '' || (string) $indicator->assignment?->pic_user_id === (string) request('auditor');
                    return $matchesSearch && $matchesState && $matchesAuditor;
                }) as $indicator)
                    <tr>
                        <td>{{ $indicator->code }}</td>
                        <td>{{ $indicator->title }}</td>
                        <td>{{ $indicator->section?->principle?->title ?? '-' }}</td>
                        <td>{{ $indicator->assignment?->pic?->name ?? '-' }}</td>
                        <td><span class="chip chip--todo">{{ $indicator->progress_state->label() }}</span></td>
                        <td><a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.work.show', [$project, $indicator]) }}">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada indikator yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
