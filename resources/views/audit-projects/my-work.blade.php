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
                <span class="chip chip--done">{{ $indicators->count() }} indikator</span>
            </div>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Indikator saya</h2>
                <p>Klik indikator untuk melihat struktur persyaratan dan Parameter/Verifier (read only).</p>
            </div>
        </div>

        @if ($indicators->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Indikator</th>
                            <th>Lokasi struktur</th>
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
                            <td style="text-align: right;">
                                <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.indicators.show', [$project, $indicator]) }}">Lihat detail</a>
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