@extends('layouts.app')

@section('title', 'Penugasan auditor | Verifikasi Audit ISPO')
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penugasan auditor')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('companies.index') }}">Perusahaan</a>
@endsection

@section('content')
    <p class="page-intro">Tetapkan auditor yang bertanggung jawab pada tiap prinsip audit ISPO.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Perusahaan dan audit</th>
                        <th>Tanggal dan status</th>
                        <th>Penugasan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr>
                            <td>
                                <strong>{{ $audit->company->name }}</strong>
                                <div class="cell-meta">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</div>
                            </td>
                            <td>
                                <strong>{{ $audit->due_date?->translatedFormat('d F Y') ?: 'Belum ditentukan' }}</strong>
                                <div class="cell-meta">
                                    {{ $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Sedang diverifikasi' : 'Belum mulai') }}
                                </div>
                            </td>
                            <td>
                                <strong>{{ $audit->assignees_count }}</strong>
                                <div class="cell-meta">penugasan prinsip</div>
                            </td>
                            <td>
                                <div class="cell-actions">
                                    <a class="btn btn--primary btn--sm" href="{{ route('audits.assignments.edit', $audit) }}">Atur penanggung jawab</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty">
                                    <strong>Belum ada audit ISPO</strong>
                                    <span>Audit ISPO yang tersedia untuk penugasan akan muncul di sini.</span>
                                    <a class="btn btn--primary" href="{{ route('companies.index') }}">Lihat perusahaan</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection