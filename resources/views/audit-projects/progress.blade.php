@extends('layouts.app')

@section('title', 'Monitoring Progress | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Monitoring Progress')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">Ringkasan read-only hasil audit dan penugasan auditor</div>
            </div>
        </div>
    </section>

    <section class="stat-grid">
        <div class="stat-card"><span>Total indicator</span><strong>{{ $summary['total'] }}</strong></div>
        <div class="stat-card"><span>Hasil audit terisi</span><strong>{{ $summary['indicators_with_result'] }}</strong></div>
        <div class="stat-card"><span>Belum dikerjakan</span><strong>{{ $summary['not_started'] }}</strong></div>
        <div class="stat-card"><span>Status belum dipilih</span><strong>{{ $summary['status_pending'] }}</strong></div>
        <div class="stat-card"><span>Draft lengkap</span><strong>{{ $summary['draft_complete'] }}</strong></div>
        <div class="stat-card"><span>Pengisian</span><strong>{{ $summary['filling_percentage'] }}%</strong></div>
        <div class="stat-card"><span>Kelengkapan</span><strong>{{ $summary['completeness_percentage'] }}%</strong></div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head"><div><h2>Breakdown per auditor</h2></div></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Auditor</th><th>Assigned</th><th>Belum Dikerjakan</th><th>Status Belum Dipilih</th><th>Draft Lengkap</th><th>Kelengkapan</th></tr></thead>
                <tbody>
                @forelse ($auditors as $row)
                    <tr>
                        <td>{{ $row['user']->name }}</td>
                        <td>{{ $row['assigned'] }}</td>
                        <td>{{ $row['not_started'] }}</td>
                        <td>{{ $row['status_pending'] }}</td>
                        <td>{{ $row['draft_complete'] }}</td>
                        <td>{{ $row['completeness_percentage'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada anggota project.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head"><div><h2>Breakdown per Principle</h2></div></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Principle</th><th>Total</th><th>Belum Dikerjakan</th><th>Status Belum Dipilih</th><th>Draft Lengkap</th><th>Kelengkapan</th></tr></thead>
                <tbody>
                @forelse ($principles as $row)
                    <tr>
                        <td>{{ $row['principle']->title }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['not_started'] }}</td>
                        <td>{{ $row['status_pending'] }}</td>
                        <td>{{ $row['draft_complete'] }}</td>
                        <td>{{ $row['completeness_percentage'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada principle snapshot.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head"><div><h2>Daftar indikator</h2><p>Monitoring read-only; pilih indikator untuk melihat workspace.</p></div></div>
        <form method="GET" class="filter-bar" style="margin-bottom: 1rem;">
            <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Cari kode atau judul">
            <select class="input" name="auditor">
                <option value="">Semua auditor</option>
                @foreach ($auditors as $row)
                    <option value="{{ $row['user']->id }}" @selected((string) request('auditor') === (string) $row['user']->id)>{{ $row['user']->name }}</option>
                @endforeach
            </select>
            <select class="input" name="principle">
                <option value="">Semua principle</option>
                @foreach ($principles as $row)
                    <option value="{{ $row['principle']->id }}" @selected((string) request('principle') === (string) $row['principle']->id)>{{ $row['principle']->title }}</option>
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
                <thead><tr><th>Kode</th><th>Indicator</th><th>Principle</th><th>PIC</th><th>Status Penilaian</th><th>Progress</th><th></th></tr></thead>
                <tbody>
                @forelse ($indicators->filter(function ($indicator) {
                    $search = strtolower((string) request('search'));
                    $matchesSearch = $search === '' || str_contains(strtolower($indicator->code.' '.$indicator->title), $search);
                    $matchesState = request('progress_state') === null || request('progress_state') === '' || $indicator->progress_state->value === request('progress_state');
                    $matchesAuditor = request('auditor') === null || request('auditor') === '' || (string) $indicator->assignment?->pic_user_id === (string) request('auditor');
                    $matchesPrinciple = request('principle') === null || request('principle') === '' || (string) $indicator->section?->audit_project_principle_id === (string) request('principle');
                    return $matchesSearch && $matchesState && $matchesAuditor && $matchesPrinciple;
                }) as $indicator)
                    <tr>
                        <td>{{ $indicator->code }}</td>
                        <td>{{ $indicator->title }}</td>
                        <td>{{ $indicator->section?->principle?->title ?? '-' }}</td>
                        <td>{{ $indicator->assignment?->pic?->name ?? '-' }}</td>
                        <td>{{ $indicator->result?->assessment_status?->label() ?? 'Belum Dipilih' }}</td>
                        <td><span class="chip chip--todo">{{ $indicator->progress_state->label() }}</span></td>
                        <td><a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.work.show', [$project, $indicator]) }}">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">Tidak ada indikator yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
