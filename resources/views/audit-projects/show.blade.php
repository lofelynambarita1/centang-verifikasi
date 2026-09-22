@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
    $statusChip = [
        'draft' => 'chip--todo',
        'team_setup' => 'chip--wip',
        'assignment' => 'chip--wip',
        'in_progress' => 'chip--wip',
        'review' => 'chip--wip',
        'final' => 'chip--done',
        'archived' => 'chip--red',
    ];
@endphp

@section('title', $project->name.' | Collaborative Audit Report')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Detail project')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.index') }}">← Daftar</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.structure', $project) }}">Struktur Audit</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.my-work', $project) }}">Pekerjaan Saya</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.assignments', $project) }}">Pembagian Auditor</a>
    @can('manage-audit-projects')
        <a class="btn btn--primary" href="{{ route('audit-projects.edit', $project) }}">Edit project</a>
        <a class="btn btn--ghost" href="{{ route('audit-projects.team', $project) }}">Kelola Tim</a>
    @endcan
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">{{ $project->company?->name }} · {{ $project->template?->name }}</div>
            </div>
            <div class="hero__actions">
                <span class="chip {{ $statusChip[$project->status->value] ?? 'chip--todo' }}">{{ $project->status->label() }}</span>
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row"><span>Kode project</span><b>{{ $project->project_code }}</b></div>
            <div class="meta-row"><span>Template</span><b>{{ $project->template?->name }} ({{ $project->template?->code }} · {{ $project->template?->scheme }})</b></div>
            <div class="meta-row"><span>Jenis audit</span><b>{{ $project->audit_type }}</b></div>
            <div class="meta-row"><span>Periode audit</span><b>{{ $project->audit_start_date->locale('id')->translatedFormat('d F Y') }} – {{ $project->audit_end_date->locale('id')->translatedFormat('d F Y') }}</b></div>
            <div class="meta-row"><span>Luas lahan</span><b>{{ $project->land_area !== null ? number_format((float) $project->land_area, 2) : 'Belum diisi' }} ha</b></div>
            <div class="meta-row"><span>Klien</span><b>{{ $project->company?->name }}</b></div>
            <div class="meta-row"><span>Bidang usaha</span><b>{{ $project->company?->industry ?: 'Belum diisi' }}</b></div>
            <div class="meta-row"><span>Dibuat oleh</span><b>{{ $project->creator?->name }}</b></div>
            <div class="meta-row"><span>Dibuat pada</span><b>{{ $project->created_at->locale('id')->translatedFormat('d F Y H:i') }}</b></div>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Tim auditor</h2>
                <p>{{ $project->members->count() }} anggota terdaftar</p>
            </div>
            @can('manage-audit-projects')
                <a class="panel__link" href="{{ route('audit-projects.team', $project) }}">Kelola tim →</a>
            @endcan
        </div>

        @if ($project->members->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>Peran di project</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($project->members->sortByDesc(fn ($m) => $m->project_role->value) as $member)
                        <tr>
                            <td>
                                <div class="cell-company">
                                    <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($member->user?->name ?? '?') }}</div>
                                    <div>
                                        <strong>{{ $member->user?->name }}</strong>
                                        <span>{{ $member->user?->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($member->isLead())
                                    <span class="chip chip--done">Lead Auditor</span>
                                @else
                                    <span class="chip chip--todo">Auditor</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                <strong>Belum ada anggota</strong>
                <span>Tambahkan anggota tim lewat halaman Kelola Tim.</span>
            </div>
        @endif
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Struktur audit</h2>
                <p>
                    {{ $project->snapshot_principles_count }} prinsip ·
                    {{ $project->snapshot_sections_count }} kriteria ·
                    {{ $project->snapshot_indicators_count }} indikator ·
                    {{ $project->snapshot_parameters_count }} parameter
                </p>
            </div>
            <a class="panel__link" href="{{ route('audit-projects.structure', $project) }}">Lihat struktur →</a>
        </div>
        <div class="empty">
            <span>
                Salinan struktur dibuat saat project dibuat. Perubahan master template tidak mengubah strukturnya.
                {{
                    $project->snapshot_indicators_count > 0
                        ? 'Penugasan PIC: '.$project->assignments_count.' dari '.$project->snapshot_indicators_count.' indikator sudah ditugaskan.'
                        : ''
                }}
            </span>
            <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.assignments', $project) }}">Kelola pembagian auditor →</a>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Aktivitas terbaru</h2>
                <p>Riwayat perubahan project</p>
            </div>
        </div>

        @if ($project->activityLogs->isNotEmpty())
            <ul class="list-strip">
                @foreach ($project->activityLogs as $log)
                    <li>
                        <span class="list-strip__dot" aria-hidden="true"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div>{{ $log->description }}</div>
                            <small style="color: var(--quiet);">{{ $log->user?->name }} · {{ $log->activity }}</small>
                        </div>
                        <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty"><strong>Belum ada aktivitas</strong><span>Log akan tercatat saat project dibuat atau diubah.</span></div>
        @endif
    </section>
@endsection