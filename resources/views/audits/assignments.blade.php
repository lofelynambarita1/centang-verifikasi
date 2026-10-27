@extends('layouts.app')

@section('title', 'Penugasan auditor | Verifikasi Audit ISPO')
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penugasan auditor')

@section('actions')
    @if (auth()->user()?->isAdmin())
        <a class="btn btn--primary" href="{{ route('audits.create') }}">+ Buat Proyek Audit Baru</a>
    @endif
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
                        <th>Rentang tanggal dan status</th>
                        <th>Lead Auditor dan tim</th>
                        <th>Progres indikator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        @php
                            $totalItems = $audit->items->count();
                            $verifiedItems = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                            $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
                            $teamAuditorNames = $audit->teamAuditors()
                                ->reject(fn ($user) => $user->id === $audit->leadMember?->user_id)
                                ->pluck('name')
                                ->implode(', ');
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $audit->company->name }}</strong>
                                <div class="cell-meta">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</div>
                            </td>
                            <td>
                                <strong>
                                    @if ($audit->start_date || $audit->end_date)
                                        {{ $audit->start_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                                        – {{ $audit->end_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                                    @else
                                        Belum ditentukan
                                    @endif
                                </strong>
                                <div class="cell-meta">
                                    {{ $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Sedang diverifikasi' : 'Belum mulai') }}
                                </div>
                            </td>
                            <td>
                                <div>Lead Auditor: <strong>{{ $audit->leadMember?->user?->name ?: 'Belum ditugaskan' }}</strong></div>
                                <div class="cell-meta">Tim: {{ $teamAuditorNames ?: 'Belum ditentukan' }}</div>
                            </td>
                            <td>
                                <div class="progress">
                                    <div class="progress__track">
                                        <div class="progress__bar" style="width:{{ $progress }}%"></div>
                                    </div>
                                    <span class="progress__pct">{{ $progress }}%</span>
                                </div>
                                <div class="cell-meta"><strong>{{ $verifiedItems }}</strong> / {{ $totalItems }} Indikator Selesai</div>
                            </td>
                            <td>
                                <div class="cell-actions">
                                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', $audit) }}">Buka Checklist</a>
                                    <a class="btn btn--primary btn--sm" href="{{ route('audits.assignments.edit', $audit) }}">Atur Tim/Penugasan</a>
                                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.report', $audit) }}">Laporan</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty">
                                    <strong>Belum ada audit ISPO</strong>
                                    <span>Audit ISPO yang tersedia untuk penugasan akan muncul di sini.</span>
                                    @if (auth()->user()?->isAdmin())
                                        <a class="btn btn--primary" href="{{ route('audits.create') }}">+ Buat Proyek Audit Baru</a>
                                    @else
                                        <a class="btn btn--primary" href="{{ route('companies.index') }}">Lihat perusahaan</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
