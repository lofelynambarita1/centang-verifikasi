<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Verifikasi Hasil ISPO - {{ $project->name }}</title>
    <style>
        @page { size: letter; margin: 25.4mm; }
        :root { color: #111; font-family: Calibri, Carlito, Arial, sans-serif; }
        body { font-size: 12pt; line-height: 1.3; margin: 0; }
        .report { max-width: 816px; margin: 0 auto; min-height: 1056px; }
        .report-header { padding-bottom: 0; text-align: center; }
        .report-header__logo { display: block; height: auto; margin: 0 auto 22px; max-width: 100%; width: 82%; }
        .report-header h1 { color: #111; font-size: 16pt; font-weight: 700; margin: 0 0 4px; }
        .report-header__auditee { color: #111; font-size: 16pt; font-weight: 700; margin: 0; }
        .report-actions { display: flex; justify-content: space-between; margin: 16px 0; }
        .report-actions a, .report-actions button { background: #111; border: 0; color: #fff; cursor: pointer; font: inherit; padding: 9px 14px; text-decoration: none; }
        .report-identity { font-size: 12pt; margin: 30px auto 18px; width: 72%; }
        .identity-row { display: grid; grid-template-columns: 180px 18px minmax(0, 1fr); line-height: 1.3; }
        .identity-label { font-weight: 400; }
        .identity-colon { text-align: center; }
        .identity-value { min-width: 0; }
        .report > h2, .report > h3 { font-size: 14pt; }
        .report-body-text { text-align: justify; }
        .report-heading { text-align: left; }
        .assessment-summary { white-space: pre-line; }
        .verification-section { break-before: page; page-break-before: always; }
        .verification-table { border-collapse: collapse; margin: 0; margin-right: 0; table-layout: fixed; width: 16.51cm; }
        .verification-table--first { margin-top: 18px; }
        .verification-table--continued tr:first-child td { border-top: 0; }
        .verification-table td { border: 1px solid #444; font-size: 12pt; padding: 2px 5px; vertical-align: top; overflow-wrap: break-word; }
        .report-assessment-table td:first-child { text-align: left; width: 2.35cm; width: 14.23%; }
        .report-assessment-table td:last-child { width: 14.16cm; width: 85.77%; }
        .parameter-row td, .verifier-row td { border-bottom: 0; border-top: 0; }
        .parameter-row-first td, .verifier-row-first td { border-top: 1px solid #444; }
        .parameter-row-last td, .verifier-row-last td { border-bottom: 1px solid #444; }
        .verification-table__principle td:last-child, .verification-table__section strong { text-align: left; }
        .verification-table__result-label { font-weight: 700; text-align: left; text-decoration: underline; }
        .report-text-justify { text-align: justify; }
        .verification-table__status { text-align: left; }
        .verification-table .result { margin: 2px 0 6px; text-align: justify; white-space: pre-line; }
        .evidence-image { display: block; max-height: 230px; max-width: 100%; margin-top: 6px; }
        .attachment { margin: 4px 0; }
        @media print {
            @page { size: letter; margin: 25.4mm; }
            .report-actions, .app-sidebar, .app-topbar, nav { display: none !important; }
            .report { max-width: none; }
            .report-identity { width: 72%; }
        }
    </style>
</head>
<body>
<div class="report">
    <div class="report-actions">
        <a href="{{ route('audit-projects.show', $project) }}">← Kembali ke Project</a>
        <a href="{{ route('audit-projects.report.word', $project) }}">Download Word</a>
        <a href="{{ route('audit-projects.report.pdf', $project) }}">Download PDF</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
    <header class="report-header">
        <img class="report-header__logo" src="{{ asset('assets/reports/kop-ispo.jpg') }}" alt="PT GLOBAL INSPEKSI SERTIFIKASI">
        <h1>LAPORAN VERIFIKASI HASIL ISPO</h1>
        <p class="report-header__auditee">({{ $company?->name ?? '-' }})</p>
    </header>

    <div class="report-identity">
        <div class="identity-row">
            <div class="identity-label">Nama Perusahaan</div>
            <div class="identity-colon">:</div>
            <div class="identity-value">{{ $company?->name ?? '-' }}</div>
        </div>
        <div class="identity-row">
            <div class="identity-label">Alamat Perusahaan</div>
            <div class="identity-colon">:</div>
            <div class="identity-value">{{ $company?->address ?? '-' }}</div>
        </div>
        <div class="identity-row">
            <div class="identity-label">Luasan Lahan</div>
            <div class="identity-colon">:</div>
            <div class="identity-value">{{ $project->land_area !== null ? $project->land_area.' ha' : '-' }}</div>
        </div>
        <div class="identity-row">
            <div class="identity-label">Auditor</div>
            <div class="identity-colon">:</div>
            <div class="identity-value">
                @forelse ($auditors as $index => $auditor)
                    <div>{{ $index + 1 }}. {{ $auditor->name }}</div>
                @empty
                    -
                @endforelse
            </div>
        </div>
        <div class="identity-row">
            <div class="identity-label">Tanggal Audit</div>
            <div class="identity-colon">:</div>
            <div class="identity-value">{{ $audit_date ?: '-' }}</div>
        </div>
    </div>

    @include('audit-projects._report-content')
</div>
</body>
</html>
