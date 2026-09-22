@extends('layouts.app')

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
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">{{ $project->company?->name }} · Indikator yang menjadi tanggung jawab Anda</div>
            </div>
            <div class="hero__actions">
                <span class="chip chip--todo">{{ $summary['total'] }} total</span>
                <span class="chip chip--none">{{ $summary['notStarted'] }} belum dikerjakan</span>
                <span class="chip chip--done">{{ $summary['draft'] }} draft</span>
            </div>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Indikator saya</h2>
                <p>Klik indikator untuk membuka workspace dan menulis Hasil Audit.</p>
            </div>
        </div>

        @if ($indicators->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Indikator</th>
                            <th>Lokasi struktur</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($indicators as $indicator)
                        <tr>
                            <td>
                                <div class="cell-company">
                                    <div>
                                        <strong>{{ $indicator->title }}</strong>
                                        <span>Kode {{ $indicator->code }} · {{ $indicator->indicator_type ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="cell-meta">
                                    {{ $indicator->section?->principle?->title ?? 'Prinsip' }} →
                                    {{ $indicator->section?->title ?? 'Kriteria' }}
                                </span>
                            </td>
                            <td>
                                @if ($indicator->result)
                                    <span class="chip chip--done">DRAFT</span>
                                @else
                                    <span class="chip chip--none">BELUM DIKERJAKAN</span>
                                @endif
                                @if ($indicator->result?->assessment_status)
                                    <span class="chip chip--todo">{{ $indicator->result->assessment_status->label() }}</span>
                                @else
                                    <span class="chip chip--none">Belum Dipilih</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.work.show', [$project, $indicator]) }}">Kerjakan</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                <strong>Belum ada indikator</strong>
                <span>Anda belum menjadi PIC untuk indikator apa pun pada project ini.</span>
            </div>
        @endif
    </section>
@endsection