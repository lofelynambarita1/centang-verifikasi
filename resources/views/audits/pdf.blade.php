<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Verifikasi Hasil ISPO | {{ $audit->company->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 16mm 15mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, 'DejaVu Sans', sans-serif; font-size: 10px; color: #000000; line-height: 1.5; margin: 0; }
        .letterhead { width: 100%; margin-bottom: 16px; padding-bottom: 6px; border-bottom: 2px solid #1b2450; text-align: center; }
        .letterhead-image { display: block; width: 100%; max-height: 75px; margin: 0 auto; object-fit: contain; }
        .brand-table { width: 100%; border-collapse: collapse; }
        .brand-table td { border-bottom: 3px solid #1b2450; padding-bottom: 8px; vertical-align: middle; }
        .gis-mark { width: 44px; }
        .gis-circle { display: inline-block; width: 34px; height: 34px; border: 2px solid #1b2450; border-radius: 50%; text-align: center; line-height: 30px; font-weight: bold; font-size: 13px; color: #1b2450; }
        .brand-name { font-size: 15px; font-weight: bold; color: #29346c; text-transform: uppercase; letter-spacing: .02em; }
        .brand-sub { font-size: 10px; color: #666; font-style: italic; }
        h1.title { text-align: center; font-size: 13pt; font-weight: bold; text-transform: uppercase; margin: 0 0 4px; }
        .auditee { text-align: center; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0 0 20px; }
        table.facts { width: 100%; border: none; border-collapse: collapse; margin-bottom: 18px; font-size: 10pt; line-height: 1.6; }
        table.facts, table.facts tr, table.facts td { border: none; }
        table.facts td { padding: 0; vertical-align: top; text-align: left; }
        table.facts td.k { width: 170px; font-weight: normal; }
        table.facts td.s { width: 15px; text-align: center; }
        h2.section { font-size: 12px; margin: 16px 0 6px; color: #29346c; }
        h2.principle { font-size: 11px; margin: 16px 0 6px; color: #1b2450; text-transform: uppercase; border-bottom: 1px solid #1b2450; padding-bottom: 3px; }
        h3.criteria { font-size: 10.5px; margin: 12px 0 3px; padding-bottom: 3px; border-bottom: 1px solid #bbb; }
        p { margin: 0 0 8px; text-align: justify; }
        .criteria-desc { font-size: 9.5px; color: #333; margin: 0 0 7px; }
        table.summary { width: 100%; border-collapse: collapse; margin: 10px 0 8px; }
        table.summary th, table.summary td { border: 1px solid #d5d9ec; padding: 6px 8px; text-align: center; }
        table.summary th { background: #e8ebf6; font-size: 8px; text-transform: uppercase; letter-spacing: .04em; }
        table.summary td { font-size: 12px; font-weight: bold; }
        .record { margin: 0 0 10px; page-break-inside: avoid; border: 1px solid #111; }
        .record .req { margin: 0; padding: 6px 8px; font-weight: bold; text-align: left; }
        table.record-rows { width: 100%; border-collapse: collapse; }
        table.record-rows td { border-top: 1px solid #111; padding: 5px 8px; vertical-align: top; }
        table.record-rows td.lbl { width: 88px; font-weight: bold; border-right: 1px solid #111; }
        .status-compliant { color: #2f4bab; }
        .status-non_compliant { color: #c94a3e; }
        .status-not_applicable { color: #596176; }
        .status-pending { color: #8a8a8a; }
        ul.findings { margin: 6px 0 0; padding-left: 16px; }
        ul.findings li { margin-bottom: 6px; }
        .empty-record { border: 1px solid #111; padding: 10px; }
        table.signatures { width: 100%; margin-top: 48px; border-collapse: collapse; }
        table.signatures td { width: 50%; text-align: center; padding: 0 8px; }
        .sign-line { border-top: 1px solid #333; width: 170px; margin: 0 auto 4px; }
        .sign-label { font-weight: bold; font-size: 10px; margin-top: 3px; }
        .sign-role { font-size: 9px; color: #555; margin-top: 2px; }
        .footer-note { margin-top: 20px; font-size: 8px; color: #777; border-top: 1px solid #ddd; padding-top: 6px; text-align: center; }
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
    <div class="auditee">(AUDITEE/{{ strtoupper($audit->company->name ?: 'NAMA KLIEN') }})</div>

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

    <h2 class="section">I. HASIL PENILAIAN</h2>
    <p><strong>RINGKASAN PENILAIAN</strong></p>
    <p>Dalam kegiatan penilaian/ audit ISPO Tahap 2 ini ditemukan beberapa ketidaksesuaian yang terkait dengan persyaratan yang ditetapkan oleh aturan ISPO dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit. Ketidaksesuaian tersebut dicatat dalam daftar untuk perbaikan, dan kemudian saat ini pihak {{ $audit->company->name ?: 'perusahaan' }} telah melakukan tindakan perbaikan untuk pemenuhan ketidaksesuaian tersebut seluruhnya.</p>
    <p>Berikut ini merupakan ringkasan dari hasil penilaian yang dibuat berdasarkan Prinsip, Kriteria dan indikator ISPO seperti yang ditetapkan dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit.</p>

    <table class="summary">
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

    <h2 class="section">HASIL VERIFIKASI PENILAIAN</h2>

    @forelse ($groups as $number => $group)
        @if ($group->isEmpty())
            @continue
        @endif

        <h2 class="principle">{{ $number }}. {{ $principleTitles[$number] ?? '' }}</h2>

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
                <h3 class="criteria">{{ $criteriaCode }} {{ $criteriaInfo['title'] }}</h3>
                @if (! empty($criteriaInfo['description']))
                    <p class="criteria-desc">{{ $criteriaInfo['description'] }}</p>
                @endif
                @php $previousCriteria = $criteriaCode; @endphp
            @endif

            <div class="record">
                <p class="req">{{ $code }} {{ $indicatorTitle }}</p>
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
                                    <div class="hasil-bukti">Bukti: {{ basename($hasil->evidence_path) }}</div>
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
            </div>
        @endforeach
    @empty
        <div class="empty-record">Belum ada indikator verifikasi untuk audit ini.</div>
    @endforelse

    @if ($nonCompliant > 0)
        <h2 class="section">TEMUAN AUDIT</h2>
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
                <div class="sign-label">{{ $audit->auditor_name ?: '(Nama Auditor)' }}</div>
                <div class="sign-role">Auditor</div>
            </td>
            <td>
                <div class="sign-line"></div>
                <div class="sign-label">(Nama Pimpinan Perusahaan)</div>
                <div class="sign-role">{{ $audit->company->name ?: 'Pimpinan Perusahaan' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Status audit: <strong>{{ $audit->status === 'completed' ? 'Selesai' : 'Belum selesai' }}</strong> — Dokumen ini dihasilkan otomatis dari data verifikasi audit ISPO yang tersimpan dalam sistem pada {{ now()->locale('id')->translatedFormat('d F Y · H:i') }}.
    </div>
</body>
</html>
