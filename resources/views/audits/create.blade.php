@extends('layouts.app')

@section('title', 'Buat Proyek Audit Baru | Verifikasi Audit ISPO')
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Buat Proyek Audit Baru')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audits.assignments.index') }}">Daftar Penugasan</a>
@endsection

@section('content')
    <p class="page-intro">
        Inisialisasi proyek audit ISPO baru untuk perusahaan / unit kebun. Tentukan tanggal audit dan pilih Lead Auditor.
        Checklist 7 prinsip ISPO akan dibuat secara otomatis untuk proyek ini.
    </p>

    <section class="panel panel--pad form-card">
        @if ($errors->any())
            <div class="form-error" role="alert" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; border-radius: 6px; background-color: var(--danger-surface, #fee2e2); color: var(--danger-text, #991b1b); font-size: 0.9rem;">
                <strong>Periksa kembali formulir:</strong>
                <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('audits.store') }}">
            @csrf

            <div class="form-grid">
                <div class="field field--wide">
                    <label for="company_id">Perusahaan / Unit Kebun <span style="color: var(--danger, #dc2626);">*</span></label>
                    <select id="company_id" name="company_id" required>
                        <option value="">-- Pilih Perusahaan / Unit Kebun --</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) old('company_id', $selectedCompanyId) === $company->id)>
                                {{ $company->name }} {{ $company->industry ? '('.$company->industry.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($companies->isEmpty())
                        <div class="cell-meta" style="margin-top: 0.35rem;">
                            Belum ada perusahaan terdaftar. <a href="{{ route('companies.create') }}">Tambah perusahaan terlebih dahulu</a>.
                        </div>
                    @endif
                </div>

                <div class="field field--wide">
                    <label for="title">Judul Proyek Audit</label>
                    <input id="title" name="title" value="{{ old('title') }}" placeholder="Contoh: Audit Sertifikasi Awal / Surveillance 1 / Resertifikasi">
                    <div class="cell-meta" style="margin-top: 0.25rem; font-size: 0.8rem; color: var(--text-muted, #64748b);">
                        Jika dikosongkan, sistem akan otomatis menggunakan judul default: <em>Audit Verifikasi ISPO [Nama Perusahaan]</em>.
                    </div>
                </div>

                <div class="field">
                    <label for="start_date">Tanggal Mulai Audit <span style="color: var(--danger, #dc2626);">*</span></label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                </div>

                <div class="field">
                    <label for="end_date">Tanggal Selesai Audit <span style="color: var(--danger, #dc2626);">*</span></label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', now()->addDays(7)->format('Y-m-d')) }}" required>
                </div>

                <div class="field">
                    <label for="scope">Ruang Lingkup Verifikasi <span style="color: var(--danger, #dc2626);">*</span></label>
                    <input id="scope" name="scope" value="{{ old('scope', 'Standar ISPO') }}" placeholder="Standar ISPO" required>
                </div>

                <div class="field">
                    <label for="lead_auditor_id">Lead Auditor Proyek <span style="color: var(--danger, #dc2626);">*</span></label>
                    <select id="lead_auditor_id" name="lead_auditor_id" required>
                        <option value="">-- Pilih Lead Auditor --</option>
                        @foreach ($auditors as $auditor)
                            <option value="{{ $auditor->id }}" @selected((int) old('lead_auditor_id') === $auditor->id)>
                                {{ $auditor->name }} ({{ $auditor->email }})
                            </option>
                        @endforeach
                    </select>
                    <div class="cell-meta" style="margin-top: 0.25rem; font-size: 0.8rem; color: var(--text-muted, #64748b);">
                        Lead Auditor bertugas memimpin proyek ini, mengisi checklist, memvalidasi hasil audit, dan mencetak laporan ISPO.
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 0.75rem; align-items: center;">
                <a class="btn btn--ghost" href="{{ route('audits.assignments.index') }}">Batal</a>
                <button class="btn btn--primary form-submit" type="submit">Simpan & Lanjut ke Pembagian Prinsip</button>
            </div>
        </form>
    </section>
@endsection
