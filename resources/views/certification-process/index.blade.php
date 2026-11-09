@extends('layouts.app')

@section('title', 'Proses Sertifikasi '.$category->label().' | Verifikasi Audit ISPO')
@section('eyebrow', 'PROSES SERTIFIKASI')
@section('heading', $category->label())

@section('content')
    <p class="page-intro">
        @if ($canManageProcess)
            Pilih perusahaan untuk mengelola dokumen dan tahapan sertifikasi {{ $category->label() }}.
        @else
            Perusahaan yang ditampilkan adalah perusahaan dengan penugasan prinsip audit kepada Anda.
        @endif
    </p>

    <section class="certification-company-grid" aria-label="Perusahaan dalam proses sertifikasi {{ $category->label() }}">
        @forelse ($companies as $company)
            @php
                if ($canManageProcess) {
                    $process = $company->certificationProcesses->first();
                    $completedStages = collect([
                        filled($process?->summary_document_path),
                        filled($process?->audit_correction_document_path),
                        filled($process?->audit_report),
                        filled($process?->review_panel),
                        filled($process?->certificate_document_path),
                    ])->filter()->count();
                }
            @endphp
            <article class="certification-company-card">
                <div class="certification-company-card__top">
                    <span class="certification-company-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 7h1m4 0h1M9 11h1m4 0h1M9 15h1m4 0h1"/>
                        </svg>
                    </span>
                    <span class="certification-company-card__category">{{ $category->label() }}</span>
                </div>
                <h2>{{ $company->name }}</h2>
                <p>{{ $company->industry ?: 'Informasi bidang usaha belum tersedia' }}</p>
                <div class="certification-company-card__footer">
                    @if ($canManageProcess)
                        <span>{{ $completedStages }} dari 5 bagian terisi</span>
                        <a class="btn btn--outline btn--sm" href="{{ route('certification-process.show', [$category->value, $company]) }}">Kelola proses</a>
                    @else
                        <span>{{ $company->assigned_audits_count }} audit ditugaskan</span>
                        <a class="btn btn--outline btn--sm" href="{{ route('certification-process.show', [$category->value, $company]) }}">Lihat progres</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="certification-empty">
                <strong>Belum ada perusahaan untuk ditampilkan</strong>
                <p>
                    @if ($canManageProcess)
                        Perusahaan akan muncul di sini setelah terdaftar di sistem.
                    @else
                        Perusahaan akan muncul di sini setelah Lead Auditor menugaskan prinsip audit kepada Anda.
                    @endif
                </p>
            </div>
        @endforelse
    </section>
@endsection
