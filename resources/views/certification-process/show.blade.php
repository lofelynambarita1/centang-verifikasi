@extends('layouts.app')

@section('title', 'Proses Sertifikasi '.$category->label().' | '.$company->name)
@section('eyebrow', 'PROSES SERTIFIKASI · '.$category->label())
@section('heading', $company->name)

@section('content')
    <div class="certification-page">
        <a class="certification-back-link" href="{{ route('certification-process.index', $category->value) }}">
            <span aria-hidden="true">&larr;</span> Kembali ke daftar {{ $category->label() }}
        </a>
        <p class="page-intro">Kelola dokumen, laporan, review, dan sertifikat untuk proses {{ $category->label() }} perusahaan ini.</p>

        @if ($errors->any())
            <div class="form-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form class="certification-form" method="POST" action="{{ route('certification-process.update', [$category->value, $company]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="certification-section" aria-labelledby="summary-document-title">
                <div class="certification-section__heading">
                    <span class="certification-section__number">01</span>
                    <div>
                        <h2 id="summary-document-title">Laporan Ringkas</h2>
                        <p>Unggah laporan ringkas dalam format PDF atau Word.</p>
                    </div>
                </div>
                <label class="certification-upload" for="summary_document">
                    <span>Pilih dokumen</span>
                    <input id="summary_document" name="summary_document" type="file" accept=".pdf,.doc,.docx">
                    <small>Maksimal 20 MB · PDF, DOC, DOCX</small>
                </label>
                @error('summary_document')
                    <p class="certification-field-error">{{ $message }}</p>
                @enderror
                @if ($process->summary_document_path)
                    <a class="certification-file-link" href="{{ route('certification-process.documents.download', [$category->value, $company, 'summary']) }}">Lihat / unduh laporan ringkas</a>
                @endif
            </section>

            <section class="certification-section" aria-labelledby="correction-document-title">
                <div class="certification-section__heading">
                    <span class="certification-section__number">02</span>
                    <div>
                        <h2 id="correction-document-title">Perbaikan Audit</h2>
                        <p>Unggah dokumen tindak lanjut perbaikan dalam format PDF atau Word.</p>
                    </div>
                </div>
                <label class="certification-upload" for="audit_correction_document">
                    <span>Pilih dokumen</span>
                    <input id="audit_correction_document" name="audit_correction_document" type="file" accept=".pdf,.doc,.docx">
                    <small>Maksimal 20 MB · PDF, DOC, DOCX</small>
                </label>
                @error('audit_correction_document')
                    <p class="certification-field-error">{{ $message }}</p>
                @enderror
                @if ($process->audit_correction_document_path)
                    <a class="certification-file-link" href="{{ route('certification-process.documents.download', [$category->value, $company, 'correction']) }}">Lihat / unduh dokumen perbaikan</a>
                @endif
            </section>

            <section class="certification-section" aria-labelledby="audit-report-title">
                <div class="certification-section__heading">
                    <span class="certification-section__number">03</span>
                    <div>
                        <h2 id="audit-report-title">Laporan Audit</h2>
                        <p>Isi atau perbarui ringkasan laporan audit langsung di aplikasi.</p>
                    </div>
                </div>
                <label class="certification-text-label" for="audit_report">Isi laporan audit</label>
                <textarea id="audit_report" name="audit_report" rows="7" maxlength="30000" placeholder="Tuliskan hasil dan ringkasan audit...">{{ old('audit_report', $process->audit_report) }}</textarea>
                @error('audit_report')
                    <p class="certification-field-error">{{ $message }}</p>
                @enderror
            </section>

            <section class="certification-section" aria-labelledby="review-panel-title">
                <div class="certification-section__heading">
                    <span class="certification-section__number">04</span>
                    <div>
                        <h2 id="review-panel-title">Review / Panel</h2>
                        <p>Catat hasil review atau keputusan panel sertifikasi.</p>
                    </div>
                </div>
                <label class="certification-text-label" for="review_panel">Catatan review / panel</label>
                <textarea id="review_panel" name="review_panel" rows="7" maxlength="30000" placeholder="Tuliskan hasil review atau keputusan panel...">{{ old('review_panel', $process->review_panel) }}</textarea>
                @error('review_panel')
                    <p class="certification-field-error">{{ $message }}</p>
                @enderror
            </section>

            <section class="certification-section" aria-labelledby="certificate-title">
                <div class="certification-section__heading">
                    <span class="certification-section__number">05</span>
                    <div>
                        <h2 id="certificate-title">Sertifikasi</h2>
                        <p>Unggah sertifikat PDF. QR code akan dibuat otomatis setelah disimpan.</p>
                    </div>
                </div>
                <label class="certification-upload" for="certificate_document">
                    <span>Pilih sertifikat PDF</span>
                    <input id="certificate_document" name="certificate_document" type="file" accept=".pdf,application/pdf">
                    <small>Maksimal 20 MB · PDF</small>
                </label>
                @error('certificate_document')
                    <p class="certification-field-error">{{ $message }}</p>
                @enderror
                @if ($process->certificate_document_path)
                    <a class="certification-file-link" href="{{ route('certification-process.documents.download', [$category->value, $company, 'certificate']) }}">Lihat / unduh sertifikat</a>
                @endif

                @if ($qrCodeDataUri)
                    <div class="certification-qr">
                        <img src="{{ $qrCodeDataUri }}" alt="QR code untuk melihat sertifikat {{ $company->name }}">
                        <div>
                            <strong>QR sertifikat siap dipindai</strong>
                            <p>Pemindaian membuka berkas sertifikat ini saja.</p>
                            <a class="btn btn--outline btn--sm" href="{{ $qrCodeDataUri }}" download="qr-sertifikat-{{ \Illuminate\Support\Str::slug($company->name) }}.svg">Unduh QR code</a>
                        </div>
                    </div>
                @endif
            </section>

            <div class="certification-form__actions">
                <span>Dokumen tersimpan privat. Hanya sertifikat dengan QR khusus yang dapat dilihat publik.</span>
                <button class="btn btn--primary" type="submit">Simpan proses sertifikasi</button>
            </div>
        </form>
    </div>
@endsection
