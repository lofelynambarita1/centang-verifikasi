<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: letter portrait; margin: 37mm 25.4mm 25.4mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Calibri, Arial, sans-serif; font-size: 12pt; line-height: 1.3; margin: 0; }
        .pdf-header { left: 0; position: fixed; right: 0; text-align: center; top: -24.5mm; }
        .pdf-header img { height: auto; width: 82%; }
        .report-header { padding-bottom: 0; text-align: center; }
        .report-header h1 { color: #111; font-size: 16pt; font-weight: bold; margin: 0 0 4px; }
        .report-header__auditee { color: #111; font-size: 16pt; font-weight: bold; margin: 0; }
        .report-identity { font-size: 12pt; margin: 30px auto 18px; page-break-inside: avoid; width: 72%; }
        .identity-row { display: table; line-height: 1.3; width: 100%; }
        .identity-label, .identity-colon, .identity-value { display: table-cell; vertical-align: top; }
        .identity-label { width: 180px; }
        .identity-colon { text-align: center; width: 18px; }
        .identity-value { width: auto; }
        .report > h2, .report > h3 { font-size: 14pt; }
        .report-body-text { text-align: justify; }
        .report-heading { text-align: left; }
        .assessment-summary { white-space: pre-line; }
        .verification-section { page-break-before: always; }
        .verification-table { border-collapse: collapse; margin: 0; margin-right: 0; table-layout: fixed; width: 16.51cm; }
        .verification-table--first { margin-top: 14px; }
        .verification-table--continued tr:first-child td { border-top: 0; }
        .verification-table td { border: 1px solid #444; font-size: 12pt; padding: 2px 5px; vertical-align: top; word-wrap: break-word; }
        .report-assessment-table td:first-child { text-align: left; width: 2.35cm; width: 14.23%; }
        .report-assessment-table td:last-child { width: 14.16cm; width: 85.77%; }
        .parameter-row td, .verifier-row td { border-bottom: 0; border-top: 0; }
        .parameter-row-first td, .verifier-row-first td { border-top: 1px solid #444; }
        .parameter-row-last td, .verifier-row-last td { border-bottom: 1px solid #444; }
        .verification-table__principle td:last-child, .verification-table__section strong { text-align: left; }
        .verification-table__result-label { font-weight: bold; text-align: left; text-decoration: underline; }
        .report-text-justify { text-align: justify; }
        .verification-table__status { text-align: left; }
        .verification-table .result { margin: 2px 0 6px; text-align: justify; white-space: pre-line; }
        .evidence-image { display: block; max-height: 210px; max-width: 100%; margin-top: 5px; }
        figure { page-break-inside: avoid; margin: 8px 0; }
        figcaption { margin-top: 4px; text-align: center; }
        .attachment { margin: 3px 0; }
    </style>
</head>
<body>
<div class="pdf-header">
    <img src="{{ public_path('assets/reports/kop-ispo.jpg') }}" alt="PT GLOBAL INSPEKSI SERTIFIKASI">
</div>

<header class="report-header">
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
</body>
</html>
