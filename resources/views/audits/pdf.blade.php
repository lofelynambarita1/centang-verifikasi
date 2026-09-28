<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Verifikasi Hasil ISPO | {{ $audit->company->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm 16mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            background: #fff;
        }

        .pdf-document {
            width: 100%;
            min-height: 100%;
        }

        .pdf-header {
            display: table;
            width: 100%;
            table-layout: fixed;
            padding-bottom: 10px;
            border-bottom: 1px solid #111;
        }

        .gis-logo {
            display: table-cell;
            width: 78px;
            vertical-align: middle;
        }

        .gis-logo img {
            display: block;
            width: 70px;
            height: 70px;
        }

        .gis-brand {
            display: table-cell;
            vertical-align: middle;
        }

        .gis-brand h1 {
            margin: 0;
            color: #29346c;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .gis-brand p {
            margin: 4px 0 0;
            color: #d63d2f;
            font-size: 12px;
            font-style: italic;
            font-weight: 700;
        }

        .pdf-form-code {
            display: table-cell;
            width: 108px;
            vertical-align: top;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            color: #222;
            white-space: nowrap;
        }

        .pdf-title {
            margin: 18px 0 4px;
            text-align: center;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: .02em;
            color: #111;
        }

        .pdf-auditee {
            margin: 0 0 14px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .pdf-facts {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .pdf-facts td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }

        .pdf-facts td.k {
            width: 155px;
            font-weight: 700;
            color: #111;
        }

        .pdf-facts td.s {
            width: 14px;
            text-align: center;
        }

        .section-title {
            margin: 12px 0 8px;
            font-size: 13px;
            font-weight: 800;
            color: #29346c;
            text-transform: uppercase;
        }

        .summary-intro {
            margin: 0 0 8px;
            text-align: justify;
            font-size: 11px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 12px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #d7dce9;
            padding: 6px 8px;
            text-align: center;
            font-size: 11px;
        }

        .summary-table th {
            background: #e9edf9;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .principle-title {
            margin: 16px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #111;
            font-size: 12px;
            font-weight: 800;
            color: #1d2343;
            text-transform: uppercase;
        }

        .criteria-title {
            margin: 12px 0 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #b9bfd7;
            font-size: 11px;
            font-weight: 800;
            color: #111;
        }

        .criteria-desc {
            margin: 0 0 8px;
            font-size: 10.5px;
            color: #333;
            text-align: justify;
        }

        .verification-record {
            width: 100%;
            margin: 0 0 10px;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .verification-record td {
            border: 1px solid #111;
            vertical-align: top;
        }

        .record-index {
            width: 52px;
            padding: 8px 6px;
            font-size: 10.5px;
        }

        .record-content {
            padding: 0;
        }

        .record-head {
            margin: 0;
            padding: 7px 8px;
            font-size: 11px;
            font-weight: 800;
            background: #f6f6f7;
        }

        .record-rows {
            width: 100%;
            border-collapse: collapse;
        }

        .record-rows td {
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10.5px;
            border-top: 1px solid #111;
        }

        .record-rows td.lbl {
            width: 94px;
            font-weight: 800;
            border-right: 1px solid #111;
        }

        .status-compliant { color: #2f4bab; }
        .status-non_compliant { color: #c94a3e; }
        .status-not_applicable { color: #596176; }
        .status-pending { color: #8a8a8a; }

        .findings {
            margin: 4px 0 0;
            padding-left: 18px;
            font-size: 11px;
        }

        .findings li { margin-bottom: 6px; }

        .signatures {
            width: 100%;
            margin-top: 26px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding: 0 12px;
            font-size: 11px;
        }

        .sign-line {
            width: 180px;
            height: 1px;
            margin: 0 auto 6px;
            background: #333;
        }

        .sign-label {
            font-weight: 800;
        }

        .sign-role {
            color: #555;
            margin-top: 2px;
        }

        .pdf-footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #d9d9d9;
            font-size: 9.5px;
            color: #555;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $items = ($items ?? $audit->items)->sortBy('sort_order')->values();
        $totalItems = $items->count();
        $compliant = $items->where('status', 'compliant')->count();
        $nonCompliant = $items->where('status', 'non_compliant')->count();
        $notApplicable = $items->where('status', 'not_applicable')->count();
        $verified = $compliant + $nonCompliant + $notApplicable;
        $statusLabels = [
            'compliant' => 'Memenuhi',
            'non_compliant' => 'Tidak Memenuhi',
            'not_applicable' => 'NA',
            'pending' => 'Belum diperiksa',
        ];
        $principleTitles = config('ispo.principles', []);
        $criteriaMap = config('ispo.criteria', []);
        $logoPath = public_path('images/logogis.jpeg');
        $logoDataUri = is_file($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $groups = $isIspoChecklist
            ? $principles->map(fn ($group) => $group->sortBy('sort_order')->values())
            : collect([0 => $items]);
    @endphp

    <div class="pdf-document">
        <header class="pdf-header">
            <div class="gis-logo">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="GIS">
                @else
                    GIS
                @endif
            </div>
            <div class="gis-brand">
                <h1>PT. Global Inspeksi Sertifikasi</h1>
                <p>Inspection &amp; Certification Body</p>
            </div>
            <div class="pdf-form-code">FrO. 8603/GIS-1.1</div>
        </header>

        <h1 class="pdf-title">LAPORAN VERIFIKASI HASIL ISPO</h1>
        <div class="pdf-auditee">(AUDITEE / {{ strtoupper($audit->company->name ?: 'NAMA KLIEN') }})</div>

        <table class="pdf-facts">
            <tr><td class="k">Nama Perusahaan</td><td class="s">:</td><td>{{ $audit->company->name ?: '-' }}</td></tr>
            <tr><td class="k">Alamat Perusahaan</td><td class="s">:</td><td>{{ $audit->company->address ?: '-' }}</td></tr>
            <tr><td class="k">Luasan Lahan</td><td class="s">:</td><td>-</td></tr>
            <tr><td class="k">Auditor</td><td class="s">:</td><td>{{ $audit->auditorNamesText() ?: '-' }}</td></tr>
            <tr><td class="k">Tanggal Audit</td><td class="s">:</td><td>{{ $audit->due_date?->locale('id')->translatedFormat('d F Y') ?: '-' }}</td></tr>
        </table>

        <div class="section-title">I. HASIL PENILAIAN</div>
        <p class="summary-intro"><strong>RINGKASAN PENILAIAN</strong></p>
        <p class="summary-intro">Dalam kegiatan penilaian/audit ISPO Tahap 2 ini ditemukan beberapa ketidaksesuaian yang terkait dengan persyaratan yang ditetapkan oleh aturan ISPO dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit. Ketidaksesuaian tersebut dicatat dalam daftar untuk perbaikan, dan kemudian saat ini pihak {{ $audit->company->name ?: 'perusahaan' }} telah melakukan tindakan perbaikan untuk pemenuhan ketidaksesuaian tersebut seluruhnya.</p>
        <p class="summary-intro">Berikut ini merupakan ringkasan dari hasil penilaian yang dibuat berdasarkan Prinsip, Kriteria dan indikator ISPO seperti yang ditetapkan dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit.</p>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Total indikator</th>
                    <th>Memenuhi</th>
                    <th>Tidak memenuhi</th>
                    <th>NA</th>
                    <th>Terisi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $totalItems }}</td>
                    <td>{{ $compliant }}</td>
                    <td>{{ $nonCompliant }}</td>
                    <td>{{ $notApplicable }}</td>
                    <td>{{ $verified }} / {{ $totalItems }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">HASIL VERIFIKASI PENILAIAN</div>

        @forelse ($groups as $number => $group)
            @if ($group->isEmpty())
                @continue
            @endif

            <div class="principle-title">{{ $number }}. {{ $principleTitles[$number] ?? '' }}</div>

            @php $previousCriteria = null; @endphp

            @foreach ($group as $item)
                @php
                    preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/s', $item->title, $parts);
                    $code = $parts[1] ?? $loop->iteration;
                    $indicatorTitle = $parts[2] ?? $item->title;
                    $criteriaCode = \App\Support\IspoChecklist::criteriaCode($item->title);
                    $criteriaInfo = $criteriaCode ? ($criteriaMap[$criteriaCode] ?? null) : null;
                @endphp

                @if ($criteriaInfo && $criteriaCode !== $previousCriteria)
                    <div class="criteria-title">{{ $criteriaCode }} {{ $criteriaInfo['title'] }}</div>
                    @if (! empty($criteriaInfo['description']))
                        <p class="criteria-desc">{{ $criteriaInfo['description'] }}</p>
                    @endif
                    @php $previousCriteria = $criteriaCode; @endphp
                @endif

                <table class="verification-record">
                    <tr>
                        <td class="record-index" rowspan="2">{{ $code }}</td>
                        <td class="record-head">{{ $indicatorTitle }}</td>
                    </tr>
                    <tr>
                        <td class="record-content">
                            <table class="record-rows">
                                <tr>
                                    <td class="lbl">Parameter</td>
                                    <td>{!! nl2br(e($item->description ?: 'Tersedia dokumen dan bukti pendukung sesuai persyaratan.')) !!}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Hasil Audit</td>
                                    <td>
                                        @forelse ($item->hasils as $hasil)
                                            <div>{{ $hasil->content ?: '(tanpa keterangan)' }}</div>
                                            @if ($hasil->evidence_path)
                                                @php
                                                    $evidencePath = Storage::disk('public')->path($hasil->evidence_path);
                                                    $evidenceExt = strtolower(pathinfo($hasil->evidence_path, PATHINFO_EXTENSION));
                                                    $isImageEvidence = in_array($evidenceExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                                    $evidenceSize = is_file($evidencePath) ? filesize($evidencePath) : 0;
                                                    $evidenceDataUri = $isImageEvidence && $evidenceSize > 0 && $evidenceSize <= 2 * 1024 * 1024 && is_file($evidencePath)
                                                        ? 'data:'.mime_content_type($evidencePath).';base64,'.base64_encode(file_get_contents($evidencePath))
                                                        : null;
                                                @endphp
                                                @if ($evidenceDataUri)
                                                    <img src="{{ $evidenceDataUri }}" style="max-width: 180px; max-height: 120px; display: block; margin-top: 8px; border: 1px solid #dfe5ee;" alt="Bukti audit">
                                                @endif
                                                <div>Bukti: {{ basename($hasil->evidence_path) }}</div>
                                            @endif
                                        @empty
                                            {{ $item->note ?: '-' }}
                                        @endforelse
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lbl">Status</td>
                                    <td class="status-{{ $item->status }}"><strong>{{ $statusLabels[$item->status] ?? 'Belum diperiksa' }}</strong></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endforeach
        @empty
            <div class="record"><p class="record-head">Belum ada indikator verifikasi untuk audit ini.</p></div>
        @endforelse

        @if ($nonCompliant > 0)
            <div class="section-title">TEMUAN AUDIT</div>
            <ul class="findings">
                @foreach ($items->where('status', 'non_compliant') as $item)
                    <li><strong>{{ $item->title }}</strong> — {{ $item->note ?: 'Belum ada catatan tindak lanjut.' }}</li>
                @endforeach
            </ul>
        @endif

        <table class="signatures">
            <tr>
                <td>
                    <div class="sign-line"></div>
                    <div class="sign-label">{{ $audit->auditorNamesText() ?: '(Nama Auditor)' }}</div>
                    <div class="sign-role">Auditor</div>
                </td>
                <td>
                    <div class="sign-line"></div>
                    <div class="sign-label">(Nama Pimpinan Perusahaan)</div>
                    <div class="sign-role">{{ $audit->company->name ?: 'Pimpinan Perusahaan' }}</div>
                </td>
            </tr>
        </table>

        <div class="pdf-footer">
            Status audit: <strong>{{ $audit->status === 'completed' ? 'Selesai' : 'Belum selesai' }}</strong> — Dokumen ini dihasilkan otomatis dari data verifikasi audit ISPO yang tersimpan dalam sistem pada {{ now()->locale('id')->translatedFormat('d F Y · H:i') }}.
        </div>
    </div>
</body>
</html>
