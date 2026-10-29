@extends('layouts.app')

@section('title', 'Penugasan auditor | Verifikasi Audit ISPO')
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penugasan auditor')

@section('actions')
    <div class="app-topbar__actions">
        @if (auth()->user()?->isAdmin())
            <a class="btn btn--primary" href="{{ route('audits.create') }}">+ Buat Proyek Audit Baru</a>
        @endif
        <a class="btn btn--ghost" href="{{ route('companies.index') }}">Perusahaan</a>
    </div>
@endsection

@section('content')
    <div class="assignment-page">
        <p class="page-intro">Tetapkan auditor yang bertanggung jawab pada tiap prinsip audit ISPO.</p>

        <section class="assignment-list" aria-label="Daftar proyek audit">
            @forelse ($audits as $audit)
                @php
                    $totalItems = $audit->items->count();
                    $verifiedItems = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                    $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
                    $teamAuditorNames = $audit->teamAuditors()
                        ->reject(fn ($user) => $user->id === $audit->leadMember?->user_id)
                        ->pluck('name')
                        ->implode(', ');
                    $auditStatus = $audit->status === 'completed'
                        ? 'Selesai'
                        : ($audit->status === 'in_progress' ? 'Sedang diverifikasi' : 'Belum mulai');
                    $statusClass = $audit->status === 'completed'
                        ? 'assignment-status--complete'
                        : ($audit->status === 'in_progress' ? 'assignment-status--active' : 'assignment-status--pending');
                @endphp

                <article class="assignment-card" aria-labelledby="audit-{{ $audit->id }}-title">
                    <header class="assignment-card__header">
                        <div class="assignment-card__identity">
                            <span class="assignment-card__eyebrow">Perusahaan</span>
                            <h2 id="audit-{{ $audit->id }}-title">{{ $audit->company->name }}</h2>
                            <p>{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</p>
                        </div>
                        <span class="assignment-status {{ $statusClass }}">{{ $auditStatus }}</span>
                    </header>

                    <dl class="assignment-card__details">
                        <div class="assignment-card__detail">
                            <dt>Periode audit</dt>
                            <dd>
                                @if ($audit->start_date || $audit->end_date)
                                    {{ $audit->start_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                                    – {{ $audit->end_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                                @else
                                    Belum ditentukan
                                @endif
                            </dd>
                        </div>
                        <div class="assignment-card__detail">
                            <dt>Lead auditor</dt>
                            <dd>{{ $audit->leadMember?->user?->name ?: 'Belum ditugaskan' }}</dd>
                            <span class="assignment-card__meta">Tim: {{ $teamAuditorNames ?: 'Belum ditentukan' }}</span>
                        </div>
                        <div class="assignment-card__detail assignment-card__detail--progress">
                            <dt>Progres indikator</dt>
                            <dd class="assignment-progress">
                                <span
                                    class="assignment-progress__track"
                                    role="progressbar"
                                    aria-label="Progres indikator audit"
                                    aria-valuenow="{{ $progress }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuetext="{{ $verifiedItems }} dari {{ $totalItems }} indikator selesai"
                                >
                                    <span class="assignment-progress__bar" style="width: {{ $progress }}%"></span>
                                </span>
                                <strong>{{ $progress }}%</strong>
                            </dd>
                            <span class="assignment-card__meta">
                                <strong>{{ $verifiedItems }}</strong> / {{ $totalItems }} Indikator Selesai
                            </span>
                        </div>
                    </dl>

                    <footer class="assignment-card__actions">
                        <a class="btn btn--primary btn--sm" href="{{ route('audits.assignments.edit', $audit) }}">Atur Tim/Penugasan</a>
                        <div class="assignment-card__secondary-actions">
                            <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', $audit) }}">Buka Checklist</a>
                            <a class="btn btn--ghost btn--sm" href="{{ route('audits.report', $audit) }}">Laporan</a>
                        </div>
                    </footer>
                </article>
            @empty
                <div class="assignment-empty">
                    <span class="assignment-empty__icon" aria-hidden="true">✓</span>
                    <strong>Belum ada audit ISPO</strong>
                    <span>Audit ISPO yang tersedia untuk penugasan akan muncul di sini.</span>
                    @if (auth()->user()?->isAdmin())
                        <a class="btn btn--primary" href="{{ route('audits.create') }}">+ Buat Proyek Audit Baru</a>
                    @else
                        <a class="btn btn--primary" href="{{ route('companies.index') }}">Lihat perusahaan</a>
                    @endif
                </div>
            @endforelse
        </section>
    </div>
@endsection
