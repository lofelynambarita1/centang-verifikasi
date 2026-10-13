@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];

    // Helper closure untuk inisial nama yang aman
    $getInitials = function (?string $name): string {
        if (!$name) return '?';
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };

    // Pemetaan status ke CSS class dan Label
    $statusChip = [
        'draft'       => ['class' => 'chip--project-start',     'label' => 'Belum Dimulai'],
        'team_setup'  => ['class' => 'chip--project-start',     'label' => 'Belum Dimulai'],
        'assignment'  => ['class' => 'chip--project-start',     'label' => 'Belum Dimulai'],
        'in_progress' => ['class' => 'chip--project-running',   'label' => 'Berjalan'],
        'review'      => ['class' => 'chip--project-running',   'label' => 'Berjalan'],
        'final'       => ['class' => 'chip--project-completed', 'label' => 'Selesai'],
        'archived'    => ['class' => 'chip--project-archived',  'label' => 'Diarsipkan'],
    ];

    // Status chip resolusi aman
    $statusValue = $project->status->value ?? $project->status;
    $projectStatus = $statusChip[$statusValue] ?? ['class' => 'chip--project-start', 'label' => 'Belum Dimulai'];

    // Label Kamus Aktivitas
    $activityLabels = [
        'project.created'      => 'Project Dibuat',
        'project.updated'      => 'Project Diperbarui',
        'template.snapshotted' => 'Struktur Audit Ditetapkan',
        'member.added'         => 'Anggota Ditambahkan',
        'member.removed'       => 'Anggota Dihapus',
        'lead.replaced'        => 'Lead Auditor Diubah',
        'assignment.created'   => 'Penugasan Dibuat',
        'assignment.auto'      => 'Penugasan Otomatis',
        'assignment.bulk'      => 'Penugasan Massal',
        'project.started'      => 'Project Dimulai',
        'status.changed'       => 'Status Diubah',
        'evidence.uploaded'    => 'Bukti Diunggah',
        'evidence.removed'     => 'Bukti Dihapus',
        'evidence.replaced'    => 'Bukti Diganti',
        'evidence.reordered'   => 'Urutan Bukti Diubah',
        'work.saved'           => 'Pekerjaan Disimpan',
        'work.submitted'       => 'Pekerjaan Dikirim',
        'work.approved'        => 'Pekerjaan Disetujui',
        'work.revision'        => 'Revisi Diminta',
    ];
@endphp

@section('title', $project->name . ' | Verifikasi ISPO')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Detail Project')
@section('project-role', $currentProjectRole ?: auth()->user()->system_role->label())

@section('actions')
    <div class="topbar-action-group" aria-label="Navigasi project">
        <a class="btn btn--ghost" href="{{ route('audit-projects.index') }}">← Daftar Project</a>
        @if ($project->members->contains('user_id', auth()->id()))
            <a class="btn btn--ghost" href="{{ route('audit-projects.my-work', $project) }}">Pekerjaan Saya</a>
        @endif
    </div>

    @can('manage-audit-projects')
        <div class="topbar-action-group" aria-label="Aksi pengelolaan project">
            @can('update', $project)
                <a class="btn btn--ghost" href="{{ route('audit-projects.edit', $project) }}">Edit Project</a>
            @endcan
            @can('manageMembers', $project)
                <a class="btn btn--ghost" href="{{ route('audit-projects.team', $project) }}">Kelola Tim</a>
            @endcan
        </div>
    @endcan
@endsection

@section('content')
    {{-- Hero Summary Card --}}
    <section class="hero" aria-label="Ringkasan Project">
        <div class="hero__head">
            <div>
                <h1 class="hero__title">{{ $project->name }}</h1>
                <div class="hero__sub">
                    {{ $project->company?->name ?? 'Klien Belum Ditentukan' }} ·
                    {{ $project->template?->name ?? 'Acuan Belum Ditentukan' }}
                </div>
            </div>
            <div class="hero__actions">
                <span class="chip {{ $projectStatus['class'] }}">{{ $projectStatus['label'] }}</span>
            </div>
        </div>

        <div class="hero__body">
            <div class="meta-row">
                <span>Kode project</span>
                <b>{{ $project->project_code }}</b>
            </div>
            <div class="meta-row">
                <span>Acuan ISPO</span>
                <b>
                    @if ($project->template)
                        {{ $project->template->name }} ({{ $project->template->code }} · {{ $project->template->scheme }})
                    @else
                        Belum tersedia
                    @endif
                </b>
            </div>
            <div class="meta-row">
                <span>Jenis audit</span>
                <b>{{ $project->audit_type ?: 'Belum diisi' }}</b>
            </div>
            <div class="meta-row">
                <span>Periode audit</span>
                <b>
                    @if ($project->audit_start_date && $project->audit_end_date)
                        {{ $project->audit_start_date->locale('id')->translatedFormat('d F Y') }} –
                        {{ $project->audit_end_date->locale('id')->translatedFormat('d F Y') }}
                    @else
                        -
                    @endif
                </b>
            </div>
            <div class="meta-row">
                <span>Luas lahan</span>
                <b>{{ $project->land_area !== null ? number_format((float) $project->land_area, 2) . ' ha' : 'Belum diisi' }}</b>
            </div>
            <div class="meta-row">
                <span>Klien</span>
                <b>{{ $project->company?->name ?? 'Belum diisi' }}</b>
            </div>
            <div class="meta-row">
                <span>Bidang usaha</span>
                <b>{{ $project->company?->industry ?: 'Belum diisi' }}</b>
            </div>
            <div class="meta-row">
                <span>Dibuat oleh</span>
                <b>{{ $project->creator?->name ?? 'Sistem' }}</b>
            </div>
            <div class="meta-row">
                <span>Dibuat pada</span>
                <b>{{ $project->created_at?->locale('id')->translatedFormat('d F Y H:i') ?? '-' }}</b>
            </div>
            @if ($currentProjectRole)
                <div class="meta-row">
                    <span>Peran Anda</span>
                    <b>{{ $currentProjectRole }}</b>
                </div>
            @endif
        </div>
    </section>

    {{-- Tim Auditor Panel --}}
    <section class="panel panel--pad" aria-label="Tim Auditor">
        <div class="panel__head">
            <div>
                <h2>Tim auditor</h2>
                <p>{{ $project->members->count() }} anggota terdaftar</p>
            </div>
            @can('manageMembers', $project)
                <a class="panel__link" href="{{ route('audit-projects.team', $project) }}">Kelola tim →</a>
            @endcan
        </div>

        @if ($project->members->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Anggota</th>
                            <th scope="col">Peran di project</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($project->members->sortByDesc(fn ($m) => $m->project_role->value ?? $m->project_role) as $member)
                            @php
                                $memberName = $member->user?->name;
                            @endphp
                            <tr>
                                <td>
                                    <div class="cell-company">
                                        <div class="avatar {{ $avatarColors[$loop->index % count($avatarColors)] }}" aria-hidden="true">
                                            {{ $getInitials($memberName) }}
                                        </div>
                                        <div>
                                            <strong>{{ $memberName ?? 'Pengguna Tidak Ditemukan' }}</strong>
                                            <span>{{ $member->user?->email ?? '-' }}</span>
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

    {{-- Struktur Audit Panel --}}
    <section class="panel panel--pad" aria-label="Struktur Audit">
        <div class="panel__head" style="margin-bottom: 0;">
            <div>
                <h2>Struktur audit</h2>
                <p>
                    {{ $project->snapshot_principles_count }} Prinsip •
                    {{ $project->snapshot_sections_count }} Kriteria •
                    {{ $project->snapshot_indicators_count }} Indikator •
                    {{ $project->snapshot_parameters_count }} Parameter
                </p>
                @if ($project->snapshot_indicators_count > 0)
                    <p style="margin-top: 4px; font-weight: 500;">
                        Progress assignment: {{ $project->assignments_count }} / {{ $project->snapshot_indicators_count }} indikator memiliki PIC
                    </p>
                @endif
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.structure', $project) }}">Struktur Audit</a>
                @can('manageAssignments', $project)
                    <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.assignments', $project) }}">Pembagian Auditor</a>
                @endcan
            </div>
        </div>
    </section>

    <section class="panel panel--pad" aria-label="Ringkasan Penilaian">
        <div class="panel__head">
            <div>
                <h2>RINGKASAN PENILAIAN</h2>
                <p>Ringkasan ini akan ditampilkan pada Laporan Verifikasi ISPO.</p>
            </div>
        </div>
        @can('updateAssessmentSummary', $project)
            <form method="POST" action="{{ route('audit-projects.assessment-summary.update', $project) }}">
                @csrf
                @method('PATCH')
                <textarea class="input" name="assessment_summary" rows="9" maxlength="10000">{{ old('assessment_summary', $project->assessment_summary) }}</textarea>
                @error('assessment_summary')
                    <div class="field-error">{{ $message }}</div>
                @enderror
                <button class="btn btn--primary" type="submit">Simpan Ringkasan</button>
            </form>
        @else
            <p class="assessment-summary">{{ $project->assessment_summary ?: 'Ringkasan penilaian belum diisi.' }}</p>
        @endcan
    </section>

    {{-- Monitoring & Laporan Panel --}}
    <section class="panel panel--pad" aria-label="Monitoring dan Laporan">
        <div class="panel__head" style="margin-bottom: 0;">
            <div>
                <h2>Monitoring &amp; Laporan</h2>
                <p>Pantau progress pekerjaan dan lihat laporan hasil audit.</p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @can('viewProgress', $project)
                    <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.progress', $project) }}">Monitoring</a>
                @endcan
                <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.report.preview', $project) }}">Preview Laporan</a>
            </div>
        </div>
    </section>

    {{-- Aktivitas Terbaru Panel --}}
    <section class="panel panel--pad" aria-label="Aktivitas Terbaru">
        <div class="panel__head">
            <div>
                <h2>Aktivitas terbaru</h2>
                <p>Riwayat perubahan project</p>
            </div>
        </div>

        @if ($project->activityLogs->isNotEmpty())
            <ul class="list-strip" id="activity-list">
                @foreach ($project->activityLogs as $log)
                    @php
                        $isExtraLog = $loop->index >= 5;
                        $label = $activityLabels[$log->activity] ?? $log->activity;
                    @endphp
                    <li
                        class="{{ $isExtraLog ? 'js-activity-extra' : '' }}"
                        style="{{ $isExtraLog ? 'display: none;' : '' }}"
                    >
                        <span class="list-strip__dot" aria-hidden="true"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div>{{ $log->description }}</div>
                            <small style="color: var(--quiet);">
                                {{ $log->user?->name ?? 'Sistem' }} • {{ $label }}
                            </small>
                        </div>
                        <time datetime="{{ $log->created_at?->toIso8601String() }}">
                            {{ $log->created_at?->diffForHumans() ?? '-' }}
                        </time>
                    </li>
                @endforeach
            </ul>

            @if ($project->activityLogs->count() > 5)
                <div style="text-align: center; margin-top: 16px;">
                    <button type="button" class="btn btn--ghost btn--sm" id="btn-show-all-activities">
                        Lihat semua aktivitas
                    </button>
                </div>
            @endif
        @else
            <div class="empty">
                <strong>Belum ada aktivitas</strong>
                <span>Log akan tercatat saat project dibuat atau diubah.</span>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnShowAll = document.getElementById('btn-show-all-activities');
        if (!btnShowAll) return;

        btnShowAll.addEventListener('click', () => {
            const extraItems = document.querySelectorAll('.js-activity-extra');
            extraItems.forEach(el => el.style.display = 'flex');
            btnShowAll.style.display = 'none';
        });
    });
</script>
@endpush