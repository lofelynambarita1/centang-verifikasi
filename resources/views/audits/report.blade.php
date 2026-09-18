<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Verifikasi Hasil ISPO | {{ $audit->company->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ispo-report-body">
    @php
        $items = $audit->items->sortBy('sort_order')->values();
        $totalItems = $items->count();
        $compliant = $items->where('status', 'compliant')->count();
        $nonCompliant = $items->where('status', 'non_compliant')->count();
        $notApplicable = $items->where('status', 'not_applicable')->count();
        $verified = $compliant + $nonCompliant + $notApplicable;
        $principleTitles = config('ispo.principles', []);
        $criteriaMap = config('ispo.criteria', []);
        $statusLabels = [
            'compliant' => 'Memenuhi',
            'non_compliant' => 'Tidak Memenuhi',
            'not_applicable' => 'NA',
            'pending' => 'Belum diperiksa',
        ];
        $groups = $items->groupBy(fn ($item) => (int) $item->title)->filter(fn ($group) => $group->isNotEmpty());
    @endphp

    <main class="ispo-report">
        <header class="ispo-report-header">
            <div class="gis-logo" aria-label="GIS">
                <span>GIS</span>
            </div>
            <div class="gis-heading">
                <h1>PT. GLOBAL INSPEKSI SERTIFIKASI</h1>
                <p>Inspection &amp; Certification Body</p>
            </div>
            <div class="report-form-code">FrO. 8603/GIS-1.1</div>
        </header>

        <section class="ispo-report-cover">
            <h2>LAPORAN VERIFIKASI HASIL ISPO</h2>
            <h3>({{ strtoupper($audit->company->name ?: 'AUDITEE/NAMA KLIEN') }})</h3>

            <div class="report-facts">
                <div><span>Nama Perusahaan</span><b>:</b><strong>{{ $audit->company->name ?: '-' }}</strong></div>
                <div><span>Alamat Perusahaan</span><b>:</b><strong>{{ $audit->company->address ?: '-' }}</strong></div>
                <div><span>Luasan Lahan</span><b>:</b><strong>-</strong></div>
                <div><span>Auditor</span><b>:</b><strong>{{ $audit->auditor_name ?: '-' }}</strong></div>
                <div><span>Tanggal Audit</span><b>:</b><strong>{{ $audit->due_date?->locale('id')->translatedFormat('d F Y') ?: '-' }}</strong></div>
            </div>

            <div class="report-introduction">
                <h4>I. HASIL PENILAIAN</h4>
                <h5>RINGKASAN PENILAIAN</h5>
                <p>
                    Dalam kegiatan penilaian/ audit ISPO Tahap 2 ini ditemukan beberapa ketidaksesuaian yang
                    terkait dengan persyaratan yang ditetapkan oleh aturan ISPO dalam Peraturan Menteri Pertanian
                    Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha
                    Perkebunan Kelapa Sawit. Ketidaksesuaian tersebut dicatat dalam daftar untuk perbaikan, dan
                    kemudian saat ini pihak {{ $audit->company->name ?: 'perusahaan' }} telah melakukan tindakan
                    perbaikan untuk pemenuhan ketidaksesuaian tersebut seluruhnya.
                </p>
                <p>
                    Berikut ini merupakan ringkasan dari hasil penilaian yang dibuat berdasarkan Prinsip, Kriteria
                    dan indikator ISPO seperti yang ditetapkan dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025
                    tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit.
                </p>
            </div>

            <div class="report-summary">
                <div><span>Total indikator</span><strong>{{ $totalItems }}</strong></div>
                <div><span>Memenuhi</span><strong>{{ $compliant }}</strong></div>
                <div><span>Tidak memenuhi</span><strong>{{ $nonCompliant }}</strong></div>
                <div><span>NA</span><strong>{{ $notApplicable }}</strong></div>
                <div><span>Terisi</span><strong>{{ $verified }} / {{ $totalItems }}</strong></div>
            </div>
        </section>

        <section class="ispo-results">
            <h2>HASIL VERIFIKASI PENILAIAN</h2>

            @forelse ($groups as $number => $group)
                <h3 class="report-principle">{{ $number }}. {{ $principleTitles[$number] ?? '' }}</h3>

                @php $previousCriteria = null; @endphp

                @foreach ($group as $item)
                    @php
                        preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/s', $item->title, $indicatorParts);
                        $indicatorCode = $indicatorParts[1] ?? $loop->iteration;
                        $indicatorTitle = $indicatorParts[2] ?? $item->title;
                        $criteriaCode = \App\Support\IspoChecklist::criteriaCode($item->title);
                        $criteriaInfo = $criteriaCode ? ($criteriaMap[$criteriaCode] ?? null) : null;
                    @endphp

                    @if ($criteriaInfo && $criteriaCode !== $previousCriteria)
                        <div class="report-criteria">{{ $criteriaCode }} {{ $criteriaInfo['title'] }}</div>
                        @if (! empty($criteriaInfo['description']))
                            <p class="report-criteria-desc">{{ $criteriaInfo['description'] }}</p>
                        @endif
                        @php $previousCriteria = $criteriaCode; @endphp
                    @endif

                    <article class="verification-record">
                        <div class="record-index">{{ $indicatorCode }}</div>
                        <div class="record-content">
                            <h3>{{ $indicatorTitle }}</h3>
                            <div class="record-row">
                                <div class="record-label">Parameter</div>
                                <div class="record-value">{!! nl2br(e($item->description ?: 'Tersedia dokumen dan bukti pendukung sesuai persyaratan.')) !!}</div>
                            </div>
                            <div class="record-row">
                                <div class="record-label">Hasil Audit</div>
                                <div class="record-value">
                                    @forelse ($item->hasils as $hasil)
                                        <p>{{ $hasil->content ?: '(tanpa keterangan)' }}</p>
                                        @if ($hasil->evidence_path)
                                            <a class="hasil-bukti" href="{{ asset('storage/'.$hasil->evidence_path) }}" target="_blank" rel="noreferrer">Bukti: {{ basename($hasil->evidence_path) }}</a>
                                        @endif
                                    @empty
                                        <p>{{ $item->note ?: '-' }}</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="record-row audit-result status-{{ $item->status }}">
                                <div class="record-label">Status</div>
                                <div class="record-value"><strong>: {{ $statusLabels[$item->status] ?? 'Belum diperiksa' }}</strong></div>
                            </div>
                        </div>
                    </article>
                @endforeach
            @empty
                <div class="report-empty">Belum ada indikator verifikasi untuk audit ini.</div>
            @endforelse
        </section>

        <footer class="ispo-report-ending">
            <p>Status audit: <strong>{{ $audit->status === 'completed' ? 'Selesai' : 'Belum selesai' }}</strong></p>
            <p>Dokumen ini dibuat dari data verifikasi audit ISPO yang tersimpan dalam sistem.</p>
        </footer>
    </main>
</body>
</html>
