@extends('layouts.app')

@section('title', 'Penugasan auditor | Verifikasi Audit ISPO')
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penugasan auditor')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('companies.index') }}">Perusahaan</a>
@endsection

@section('content')
    <p class="page-intro">Pilih audit untuk membagi tanggung jawab setiap prinsip kepada auditor dan lead auditor.</p>

    <section class="assignment-list" aria-label="Daftar audit yang dapat ditugaskan">
        @forelse ($audits as $audit)
            @php
                $statusLabel = match ($audit->status) {
                    'completed' => 'Selesai',
                    'in_progress' => 'Sedang berjalan',
                    default => 'Belum dimulai',
                };
            @endphp
            <article class="assignment-list__card">
                <div class="assignment-list__top">
                    <span class="assignment-list__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                            <path d="M9 7h1m4 0h1M9 11h1m4 0h1M9 15h1m4 0h1"/>
                        </svg>
                    </span>
                    <span class="assignment-list__status assignment-list__status--{{ $audit->status }}">{{ $statusLabel }}</span>
                </div>

                <div class="assignment-list__identity">
                    <span class="assignment-list__eyebrow">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</span>
                    <h2>{{ $audit->company->name }}</h2>
                </div>

                <div class="assignment-list__details">
                    <div>
                        <span>Tenggat audit</span>
                        <strong>{{ $audit->due_date?->translatedFormat('d F Y') ?: 'Belum ditentukan' }}</strong>
                    </div>
                    <div>
                        <span>Penugasan prinsip</span>
                        <strong>{{ $audit->assignees_count }}</strong>
                    </div>
                </div>

                <a class="btn btn--primary assignment-list__action" href="{{ route('audits.assignments.edit', $audit) }}">
                    Atur penanggung jawab
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </article>
        @empty
            <div class="empty assignment-list__empty">
                <strong>Belum ada audit ISPO</strong>
                <span>Audit yang tersedia untuk penugasan akan muncul di sini.</span>
                <a class="btn btn--primary" href="{{ route('companies.index') }}">Lihat perusahaan</a>
            </div>
        @endforelse
    </section>
@endsection