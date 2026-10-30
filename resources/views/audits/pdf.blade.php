<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Verifikasi Hasil ISPO | {{ $audit->company->name }}</title>
    <style>
        @page {
            size: letter portrait;
            margin-top: 2.54cm;
            margin-bottom: 2.54cm;
            margin-left: 2.54cm;
            margin-right: 2.54cm;
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, 'DejaVu Sans', sans-serif; font-size: 10pt; color: #000; line-height: 1.5; margin: 0; }
        .footer { position: fixed; bottom: -1.29cm; left: 0; right: 0; height: 1cm; font-family: Arial, 'DejaVu Sans', sans-serif; text-align: right; font-size: 8.5pt; color: #000000; }
        .letterhead { width: 100%; margin-top: 0; margin-bottom: 20px; text-align: center; }
        .letterhead-image { display: block; width: 92%; max-width: 95%; height: auto; margin: 0 auto; }
        .brand-table { width: 100%; border-collapse: collapse; }
        .letterhead td { vertical-align: top; }
        .brand-table td { padding-bottom: 8px; vertical-align: middle; }
        .gis-mark { width: 44px; }
        .gis-circle { display: inline-block; width: 34px; height: 34px; border: 2px solid #1b2450; border-radius: 50%; text-align: center; line-height: 30px; font-weight: bold; font-size: 13px; color: #1b2450; }
        .brand-name { font-size: 15px; font-weight: bold; color: #29346c; text-transform: uppercase; letter-spacing: .02em; }
        .brand-sub { font-size: 10px; color: #666; font-style: italic; }
        h1.title { text-align: center; font-size: 13pt; font-weight: bold; text-transform: uppercase; margin: 0 0 4px; }
        .auditee { text-align: center; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0 0 16px; }
        .facts-wrapper { padding-left: 24px; }
        table.facts { width: 100%; border: none; border-collapse: collapse; margin-bottom: 18px; font-size: 10pt; font-weight: normal; line-height: 1.6; }
        table.facts, table.facts tr, table.facts td { border: none; }
        table.facts td { padding: 2px 0; vertical-align: top; text-align: left; }
        table.facts td.k { width: 160px; font-weight: normal; }
        table.facts td.s { width: 15px; text-align: left; }
        h2.section { font-size: 12pt; font-weight: bold; text-transform: uppercase; color: #000000; margin: 18px 0 8px; }
        .results-page { page-break-before: always; }
        .results-letterhead { width: 100%; margin: 0 0 20px; text-align: center; }
        .results-letterhead td { vertical-align: top; }
        .results-letterhead img { display: block; width: 92%; max-width: 95%; height: auto; margin: 0 auto; }
        h2.results-title { font-size: 11pt; font-weight: bold; text-transform: uppercase; text-align: left; margin: 0 0 8px; }
        table.assessment-table { width: 100%; border-collapse: collapse; border: 1px solid #000000; font-size: 9.5pt; color: #000000; }
        table.assessment-table th, table.assessment-table td { border: 1px solid #000000; padding: 4px 6px; vertical-align: top; }
        table.assessment-table th:first-child, table.assessment-table td:first-child { width: 90px; text-align: left; }
        table.assessment-table tr { page-break-inside: avoid; }
        table.assessment-table ol { margin: 0; padding-left: 20px; }
        table.assessment-table li { margin-bottom: 3px; }
        .section-summary-heading { font-size: 10.5pt; font-weight: bold; margin: 0 0 6px; }
        p { margin: 0 0 8px; text-align: justify; }
        ul.findings { margin: 6px 0 0; padding-left: 16px; }
        ul.findings li { margin-bottom: 6px; }
        .empty-record { border: 1px solid #111; padding: 10px; }
        body, body * { color: #000000 !important; border-color: #000000 !important; }
    </style>
</head>
<body>
    <div class="footer">FrO. 8603/GIS-1.1</div>
    @php
        $items = ($items ?? $audit->items)->sortBy('sort_order')->values();
        $nonCompliant = $items->where('status', 'non_compliant')->count();
        $statusLabels = [
            'compliant' => 'Memenuhi',
            'non_compliant' => 'Tidak Memenuhi',
            'not_applicable' => 'NA',
            'pending' => 'Belum diperiksa',
        ];
        $principleTitles = config('ispo.principles', []);
        $criteriaMap = config('ispo.criteria', []);
        $logoPath = public_path('images/logo-gis-kop.jpg');
        $logoContents = is_file($logoPath) ? file_get_contents($logoPath) : false;
        $logoDataUri = $logoContents !== false
            ? 'data:image/jpeg;base64,'.base64_encode($logoContents)
            : null;
        $groups = $isIspoChecklist
            ? $principles->map(fn ($group) => $group->sortBy('sort_order')->values())
            : collect([0 => $items]);
    @endphp

    @if ($logoDataUri)
        <div class="letterhead">
            <img class="letterhead-image" src="{{ $logoDataUri }}" alt="Kop PT. Global Inspeksi Sertifikasi">
        </div>
    @else
        <div class="letterhead">
            <table class="brand-table">
                <tr>
                    <td class="gis-mark"><span class="gis-circle">GIS</span></td>
                    <td>
                        <div class="brand-name">PT. GLOBAL INSPEKSI SERTIFIKASI</div>
                        <div class="brand-sub">Inspection &amp; Certification Body</div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <h1 class="title">LAPORAN VERIFIKASI HASIL ISPO</h1>
    <div class="auditee">({{ strtoupper($audit->company->name) }})</div>

    <div class="facts-wrapper">
        <table class="facts">
            <tr><td class="k">Nama Perusahaan</td><td class="s">:</td><td>{{ $audit->company->name ?: '-' }}</td></tr>
            <tr><td class="k">Alamat Perusahaan</td><td class="s">:</td><td>{{ $audit->company->address ?: '-' }}</td></tr>
            <tr><td class="k">Luasan Lahan</td><td class="s">:</td><td>-</td></tr>
            <tr><td class="k">Auditor</td><td class="s">:</td><td>{{ $audit->leadMember?->user?->name ?? $audit->auditor_name ?? '-' }}</td></tr>
            <tr>
                <td class="k">Tanggal Audit</td>
                <td class="s">:</td>
                <td>{{ $audit->start_date && $audit->end_date
                    ? ($audit->start_date->locale('id')->translatedFormat('d F Y').' – '.$audit->end_date->locale('id')->translatedFormat('d F Y'))
                    : ($audit->due_date?->locale('id')->translatedFormat('d F Y') ?: '-') }}</td>
            </tr>
        </table>
    </div>

    <h2 class="section">I. HASIL PENILAIAN</h2>
    <p class="section-summary-heading">RINGKASAN PENILAIAN</p>
    <p>@if (filled($audit->summary_notes))
        {!! nl2br(e($audit->summary_notes)) !!}
    @else
        Dalam kegiatan penilaian/ audit ISPO Tahap 2 ini ditemukan beberapa ketidaksesuaian yang terkait dengan persyaratan yang ditetapkan oleh aturan ISPO dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit. Ketidaksesuaian tersebut dicatat dalam daftar untuk perbaikan, dan kemudian saat ini pihak {{ $audit->company->name ?: 'perusahaan' }} telah melakukan tindakan perbaikan untuk pemenuhan ketidaksesuaian tersebut seluruhnya.
    @endif</p>
    <div class="results-page">
        @if ($logoDataUri)
            <div class="results-letterhead">
                <img src="{{ $logoDataUri }}" alt="Kop PT. Global Inspeksi Sertifikasi">
            </div>
        @else
            <div class="results-letterhead">
                <table class="brand-table">
                    <tr>
                        <td class="gis-mark"><span class="gis-circle">GIS</span></td>
                        <td>
                            <div class="brand-name">PT. GLOBAL INSPEKSI SERTIFIKASI</div>
                            <div class="brand-sub">Inspection &amp; Certification Body</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        <h2 class="results-title">HASIL VERIFIKASI PENILAIAN</h2>
        @forelse ($groups as $number => $group)
            @if ($group->isEmpty())
                @continue
            @endif

            <table class="assessment-table">
                <tbody>
                    @if ((int) $number > 0)
                        <tr>
                            <td><strong>{{ $number }}.</strong></td>
                            <td><strong>{{ strtoupper($principleTitles[$number] ?? '') }}</strong></td>
                        </tr>
                    @endif

                    @php $previousCriteria = null; @endphp

                    @foreach ($group as $item)
                        @php
                            preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/s', $item->title, $parts);
                            $code = $parts[1] ?? $loop->iteration;
                            $indicatorTitle = $parts[2] ?? $item->title;
                            $criteriaCode = \App\Support\IspoChecklist::criteriaCode($item->title);
                            $criteriaInfo = $criteriaCode ? ($criteriaMap[$criteriaCode] ?? null) : null;
                            $parameters = preg_split('/\r\n|\r|\n/', trim((string) $item->description)) ?: [];
                            $parameters = array_values(array_filter(array_map(
                                fn (string $parameter): string => trim(preg_replace('/^\s*\d+[.)]\s*/', '', $parameter)),
                                $parameters,
                            ), fn (string $parameter): bool => $parameter !== ''));
                        @endphp

                        @if ($criteriaInfo && $criteriaCode !== $previousCriteria)
                            <tr>
                                <td><strong>{{ $criteriaCode }}</strong></td>
                                <td><strong>{{ $criteriaInfo['title'] }}</strong></td>
                            </tr>
                            @if (! empty($criteriaInfo['description']))
                                <tr>
                                    <td></td>
                                    <td>{{ $criteriaInfo['description'] }}</td>
                                </tr>
                            @endif
                            @php $previousCriteria = $criteriaCode; @endphp
                        @endif

                        <tr>
                            <td>{{ $code }}</td>
                            <td>{{ $indicatorTitle }}</td>
                        </tr>
                        <tr>
                            <td>Parameter</td>
                            <td>
                                @if ($parameters !== [])
                                    <ol>
                                        @foreach ($parameters as $parameter)
                                            <li>{{ $parameter }}</li>
                                        @endforeach
                                    </ol>
                                @else
                                    Tersedia dokumen dan bukti pendukung sesuai persyaratan.
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <strong><u>Hasil Audit:</u></strong>
                                @forelse ($item->hasils as $hasil)
                                    <div>{{ $hasil->content ?: '(tanpa keterangan)' }}</div>
                                    @if ($hasil->evidence_path)
                                        <div>Bukti: {{ basename($hasil->evidence_path) }}</div>
                                    @endif
                                @empty
                                    <div>{{ $item->note ?: '-' }}</div>
                                @endforelse
                                <div><strong><u>Status :</u></strong> <strong>{{ $statusLabels[$item->status] ?? 'Belum diperiksa' }}</strong></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <table class="assessment-table">
                <tbody>
                    <tr>
                        <td colspan="2">Belum ada indikator verifikasi untuk audit ini.</td>
                    </tr>
                </tbody>
            </table>
        @endforelse
    </div>

    @if ($nonCompliant > 0)
        <h2 class="section">TEMUAN AUDIT</h2>
        <ul class="findings">
            @foreach ($items->where('status', 'non_compliant') as $item)
                <li><strong>{{ $item->title }}</strong> — {{ $item->note ?: 'Belum ada catatan tindak lanjut.' }}</li>
            @endforeach
        </ul>
    @endif

</body>
</html>
