<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Verifikasi Hasil ISPO | {{ $audit->company->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 25.4mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.2;
            background: #fff;
        }

        .pdf-document {
            width: 100%;
            min-height: 100%;
        }

        .pdf-header {
            position: fixed;
            top: -23.5mm;
            left: 0;
            width: 100%;
            height: 73.5pt;
        }

        .gis-letterhead {
            display: block;
            width: 100%;
            height: auto;
        }

        .pdf-form-code {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
            font-size: 11pt;
            font-weight: 400;
            color: #222;
            white-space: nowrap;
        }

        .pdf-title {
            margin: 13pt 0 0;
            text-align: center;
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: .02em;
            color: #111;
        }

        .pdf-auditee {
            margin: 0 0 20pt;
            text-align: center;
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .pdf-facts {
            width: 85%;
            margin: 0 auto 20pt;
            border-collapse: collapse;
        }

        .pdf-facts td {
            padding: 0;
            vertical-align: top;
            font-size: 12pt;
            line-height: 1.3;
        }

        .pdf-facts td.k {
            width: 108pt;
            font-weight: 400;
            color: #111;
        }

        .pdf-facts td.s {
            width: 8pt;
            text-align: center;
        }

        .section-title {
            margin: 12pt 0 8pt;
            font-size: 14pt;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
        }

        .verification-title {
            margin: 0 0 8pt 36pt;
            font-size: 12pt;
        }

        .summary-intro {
            margin: 0 0 8pt;
            text-align: left;
            font-size: 12pt;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10pt 0 12pt;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #111;
            padding: 4pt 6pt;
            text-align: center;
            font-size: 10pt;
        }

        .summary-table th {
            background: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .principle-title {
            margin: 12pt 0 0 36pt;
            font-size: 11pt;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
        }

        .criteria-title {
            margin: 0 0 4pt 36pt;
            font-size: 11pt;
            font-weight: 700;
            color: #111;
        }

        .principle-code,
        .criteria-code {
            display: inline-block;
            width: 68pt;
            padding-left: 5pt;
            vertical-align: top;
        }

        .criteria-desc {
            margin: 0 0 8pt;
            margin-left: 104pt;
            font-size: 10pt;
            color: #333;
            text-align: left;
        }

        .verification-record {
            width: 92%;
            margin: 0 0 10pt;
            margin-left: 8%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .verification-record td {
            border: 1px solid #111;
            vertical-align: top;
        }

        .record-index {
            width: 68pt;
            padding: 0 0 0 5pt;
            font-size: 11pt;
        }

        .record-head {
            margin: 0;
            padding: 0;
            font-size: 11pt;
            font-weight: 400;
            background: #fff;
        }

        .record-label,
        .record-value {
            padding: 2pt 0;
            vertical-align: top;
            font-size: 11pt;
        }

        .record-label {
            width: 68pt;
            padding-left: 0;
            font-weight: 400;
            border-right: 1px solid #111;
            background: #fff;
        }

        .record-value {
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .evidence-filename {
            margin-top: 4pt;
            color: #555;
            font-size: 9pt;
            word-wrap: break-word;
        }

        .record-value img {
            display: block;
            max-width: 100pt;
            max-height: 75pt;
            object-fit: contain;
        }

        .status-compliant,
        .status-non_compliant,
        .status-not_applicable,
        .status-pending { color: #111; }

        .findings {
            margin: 4pt 0 0;
            padding-left: 18px;
            font-size: 10pt;
        }

        .findings li { margin-bottom: 6pt; }

        .signatures {
            width: 100%;
            margin-top: 26pt;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding: 0 12pt;
            font-size: 10pt;
        }

        .sign-line {
            width: 180pt;
            height: 1px;
            margin: 0 auto 6pt;
            background: #333;
        }

        .sign-label {
            font-weight: 800;
        }

        .sign-role {
            color: #555;
            margin-top: 2pt;
        }

        .pdf-footer {
            margin-top: 18pt;
            padding-top: 8pt;
            border-top: 1px solid #d9d9d9;
            font-size: 8pt;
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
        $letterheadPath = public_path('images/gis-letterhead.jpg');
        $letterheadDataUri = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($letterheadPath));
        $groups = $isIspoChecklist
            ? $principles->map(fn ($group) => $group->sortBy('sort_order')->values())
            : collect([0 => $items]);
    @endphp

    <div class="pdf-document">
        <header class="pdf-header">
            <img class="gis-letterhead" src="{{ $letterheadDataUri }}" alt="PT. Global Inspeksi Sertifikasi">
            <div class="pdf-form-code">FrO. 8603/GIS-1.1</div>
        </header>

        <h1 class="pdf-title">LAPORAN VERIFIKASI HASIL ISPO</h1>
        <div class="pdf-auditee">({{ strtoupper($audit->company->name ?: 'NAMA KLIEN') }})</div>

        <table class="pdf-facts">
            <tr><td class="k">Nama Perusahaan</td><td class="s">:</td><td>{{ $audit->company->name ?: '-' }}</td></tr>
            <tr><td class="k">Alamat Perusahaan</td><td class="s">:</td><td>{{ $audit->company->address ?: '-' }}</td></tr>
            <tr><td class="k">Luasan Lahan</td><td class="s">:</td><td>{{ $audit->company->land_area ?: '-' }}</td></tr>
            <tr><td class="k">Auditor</td><td class="s">:</td><td>{{ $audit->auditorNamesText() ?: '-' }}</td></tr>
            <tr><td class="k">Tanggal Audit</td><td class="s">:</td><td>{{ $audit->due_date?->locale('id')->translatedFormat('d F Y') ?: '-' }}</td></tr>
        </table>

        <div class="section-title">I. HASIL PENILAIAN</div>
        <p class="summary-intro"><strong>RINGKASAN PENILAIAN</strong></p>
        <p class="summary-intro" style="white-space:pre-line">{{ $audit->reportSummaryParagraphOne() }}</p>
        <p class="summary-intro" style="white-space:pre-line">{{ $audit->reportSummaryParagraphTwo() }}</p>

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

        <div class="section-title verification-title">HASIL VERIFIKASI PENILAIAN</div>

        @forelse ($groups as $number => $group)
            @if ($group->isEmpty())
                @continue
            @endif

            <div class="principle-title">
                <span class="principle-code">{{ $number }}.</span><span>{{ $principleTitles[$number] ?? '' }}</span>
            </div>

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
                    <div class="criteria-title">
                        <span class="criteria-code">{{ $criteriaCode }}</span><span>{{ $criteriaInfo['title'] }}</span>
                    </div>
                    @if (! empty($criteriaInfo['description']))
                        <p class="criteria-desc">{{ $criteriaInfo['description'] }}</p>
                    @endif
                    @php $previousCriteria = $criteriaCode; @endphp
                @endif

                <table class="verification-record">
                    <colgroup>
                        <col style="width: 68pt">
                        <col>
                    </colgroup>
                    <tr>
                        <td class="record-index">{{ $code }}</td>
                        <td class="record-head">{{ $indicatorTitle }}</td>
                    </tr>
                    <tr>
                        <td class="record-label">Parameter</td>
                        <td class="record-value">{!! nl2br(e($item->description ?: 'Tersedia dokumen dan bukti pendukung sesuai persyaratan.')) !!}</td>
                    </tr>
                    @forelse ($item->hasils as $hasil)
                        <tr>
                            <td class="record-label">{{ $loop->first ? 'Hasil Audit' : '' }}</td>
                            <td class="record-value">
                                {{ $hasil->content ?: '(tanpa keterangan)' }}
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
                                        <img src="{{ $evidenceDataUri }}" style="display: block; margin-top: 8px; border: 1px solid #dfe5ee;" alt="Bukti audit">
                                    @endif
                                    <div class="evidence-filename">Bukti: {{ basename($hasil->evidence_path) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="record-label">Hasil Audit</td>
                            <td class="record-value">{{ $item->note ?: '-' }}</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td class="record-label">Status</td>
                        <td class="record-value status-{{ $item->status }}"><strong>{{ $statusLabels[$item->status] ?? 'Belum diperiksa' }}</strong></td>
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
