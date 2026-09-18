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
        $items = $audit->items->values();
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
        </header>
        <section class="ispo-report-cover">
            <h2>LAPORAN VERIFIKASI HASIL ISPO</h2>
            <h3>({{ strtoupper($audit->company->name ?: 'AUDITEE / NAMA KLIEN') }})</h3>
            <div class="report-facts">
                <div><span>Nama Perusahaan</span><b>:</b><strong>{{ $audit->company->name ?: '-' }}</strong></div>
                <div><span>Alamat Perusahaan</span><b>:</b><strong>{{ $audit->company->address ?: '-' }}</strong></div>
                <div><span>Luasan Lahan</span><b>:</b><strong>-</strong></div>
                <div><span>Auditor</span><b>:</b><strong>{{ $audit->auditor_name ?: '-' }}</strong></div>
                <div><span>Tanggal Audit</span><b>:</b><strong>{{ $audit->due_date?->translatedFormat('d F Y') ?: '-' }}</strong></div>
            </div>

            <div class="report-introduction">
                <h4>I. HASIL PENILAIAN</h4>
                <h5>RINGKASAN PENILAIAN</h5>
                <p>Dalam kegiatan penilaian atau audit ISPO tahap 2 ini ditemukan beberapa ketidaksesuaian yang terkait dengan persyaratan yang ditetapkan oleh aturan ISPO dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit.</p>
                <p>Ketidaksesuaian tersebut dicatat dalam daftar untuk perbaikan, dan pihak {{ $audit->company->name ?: 'perusahaan' }} telah melakukan tindakan perbaikan untuk pemenuhan ketidaksesuaian tersebut.</p>
                <p>Berikut merupakan ringkasan hasil penilaian berdasarkan prinsip, kriteria, dan indikator ISPO yang berlaku.</p>
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

            @forelse ($items as $item)
                <article class="verification-record">
                    <div class="record-index">{{ $loop->iteration }}.</div>
                    <div class="record-content">
                        <h3>{{ $item->title }}</h3>
                        <div class="record-row">
                            <div class="record-label">Parameter</div>
                            <div class="record-value">{{ $item->description ?: 'Tersedia dokumen dan bukti pendukung sesuai persyaratan audit.' }}</div>
                        </div>
                        <div class="record-row audit-result">
                            <div class="record-label">Hasil Audit</div>
                            <div class="record-value">
                                @if ($item->note)
                                    <p>{{ $item->note }}</p>
                                @else
                                    <p>-</p>
                                @endif
                                <strong>Status: {{ $statusLabels[$item->status] ?? 'Belum diperiksa' }}</strong>
                            </div>
                        </div>
                    </div>
                </article>
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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laporan Verifikasi Hasil ISPO | {{ $audit->company->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="report-body">
    <main class="report-page-shell">
        <section class="report-sheet">
            <header class="report-top">
                <div class="gis-badge" aria-label="Logo PT Global Inspeksi Sertifikasi">
                    <span>GIS</span>
                </div>

                <div class="report-branding">
                    <h1>PT. GLOBAL INSPEKSI SERTIFIKASI</h1>
                    <p>Inspection &amp; Certification Body</p>
                </div>
            </header>

            <div class="report-title-wrap">
                <h2>LAPORAN VERIFIKASI HASIL ISPO<br><span>(AUDITEE/NAMA KLIEN)</span></h2>
            </div>

            <div class="report-company-meta">
                <div class="field-row">
                    <span>Nama Perusahaan</span>
                    <strong>:</strong>
                    <em>{{ $audit->company->name ?? '-' }}</em>
                </div>
                <div class="field-row">
                    <span>Alamat Perusahaan</span>
                    <strong>:</strong>
                    <em>{{ $audit->company->address ?? 'Luas' }}</em>
                </div>
                <div class="field-row">
                    <span>Bidang Usaha</span>
                    <strong>:</strong>
                    <em>{{ $audit->company->industry ?? 'Auditor' }}</em>
                </div>
                <div class="field-row">
                    <span>Tanggal Audit</span>
                    <strong>:</strong>
                    <em>{{ $audit->due_date?->format('d F Y') ?? '-' }}</em>
                </div>
            </div>

            <div class="report-workbook">
                <div class="report-column">
                    <div class="report-block">
                        <div class="report-block-head">1. Hasil Penilaian</div>
                        <div class="report-block-content">
                            <div class="report-block-subhead">Ringkasan Penilaian</div>
                            <p>Dalam kegiatan penilaian audit ISPO, pihak perusahaan telah memaparkan data dan dokumen pendukung yang diperlukan untuk menilai kepatuhan terhadap persyaratan ISPO.</p>
                            <p>Berikut ini merupakan ringkasan dari hasil penilaian yang diperoleh berdasarkan prinsip verifikasi yang berlaku.</p>

                            @foreach ($audit->items->take(6) as $item)
                                <div class="report-item-box">
                                    <div class="report-item-title">{{ $loop->iteration }}. {{ $item->title }}</div>
                                    <div class="report-item-body">
                                        <div class="report-item-label">Parameter</div>
                                        <div class="report-item-value">{{ $item->description ?: 'Tersedia dokumen dan bukti pendukung yang sesuai dengan persyaratan audit.' }}</div>
                                    </div>
                                    <div class="report-item-body">
                                        <div class="report-item-label">Hasil Audit</div>
                                        <div class="report-item-value result-{{ $item->status ?? 'pending' }}">
                                            {{ match ($item->status ?? 'pending') {
                                                'compliant' => 'Sesuai',
                                                'non_compliant' => 'Perlu perbaikan',
                                                'not_applicable' => 'Tidak berlaku',
                                                default => 'Belum diputuskan',
                                            } }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="report-column">
                    <div class="report-block">
                        <div class="report-block-head">Status: Memenuhi / Tidak Memenuhi / NA</div>
                        <div class="report-block-content">
                            <div class="report-side-card">
                                <div class="report-side-title">Temuan Audit</div>
                                <div class="report-side-body">
                                    @if ($audit->items->where('status', 'non_compliant')->isNotEmpty())
                                        @foreach ($audit->items->where('status', 'non_compliant')->take(3) as $item)
                                            <div class="finding-item">
                                                <span class="warning-mark">!</span>
                                                <p>{{ $item->title }}<br>{{ $item->note ?: 'Belum ada catatan hasil pemeriksaan.' }}</p>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="finding-item">
                                            <span class="warning-mark">!</span>
                                            <p>Belum ada temuan audit yang perlu ditindaklanjuti.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="report-side-info">
                                <div class="info-row">
                                    <span>1.</span>
                                    <p>Perusahaan telah menyusun dokumen pendukung dan data operasional sesuai kebutuhan audit.</p>
                                </div>
                                <div class="info-row">
                                    <span>2.</span>
                                    <p>Perusahaan menyadari bahwa hasil validasi tetap bergantung pada bukti yang tersedia dan kondisi lapangan.</p>
                                </div>
                                <div class="info-row">
                                    <span>3.</span>
                                    <p>Dokumen dan catatan yang disiapkan dapat dipertanggungjawabkan sesuai dengan prinsip audit ISPO.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
