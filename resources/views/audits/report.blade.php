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
        $leadAuditor = $audit->leadMember?->user;
        $principleAuditors = collect(range(1, 7))->mapWithKeys(fn (int $number) => [
            $number => $audit->assignees
                ->filter(fn ($user) => (int) $user->pivot->principle_number === $number)
                ->pluck('name')
                ->unique()
                ->values(),
        ]);
    @endphp

    <main class="ispo-report">
        <div class="page-actions" style="margin-bottom: 1rem;">
            <a class="btn btn--primary" href="{{ route('audits.pdf', $audit) }}">Unduh Laporan PDF</a>
        </div>
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
                <div><span>Bidang Usaha</span><b>:</b><strong>{{ $audit->company->industry ?: '-' }}</strong></div>
                <div><span>PIC Perusahaan</span><b>:</b><strong>{{ $audit->company->contact_name ?: '-' }}</strong></div>
                <div><span>Judul / Jenis Audit</span><b>:</b><strong>{{ $audit->title ?: '-' }}</strong></div>
                <div><span>Tanggal Pelaksanaan</span><b>:</b><strong>{{ $audit->start_date?->locale('id')->translatedFormat('d F Y') ?: '-' }} – {{ $audit->end_date?->locale('id')->translatedFormat('d F Y') ?: '-' }}</strong></div>
                <div><span>Ruang Lingkup</span><b>:</b><strong>{{ $audit->scope ?: 'Standar ISPO' }}</strong></div>
                <div><span>Lead Auditor</span><b>:</b><strong>{{ $leadAuditor?->name ?: $audit->auditorNamesText() ?: '-' }}</strong></div>
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

            <h3 class="report-principle">Auditor yang Ditugaskan per Prinsip</h3>
            <table class="summary-table">
                <thead>
                    <tr><th>Prinsip</th><th>Auditor</th></tr>
                </thead>
                <tbody>
                    @foreach (range(1, 7) as $principleNumber)
                        <tr>
                            <td>{{ $principleNumber }}. {{ $principleTitles[$principleNumber] ?? '' }}</td>
                            <td>{{ $principleAuditors[$principleNumber]->join(', ') ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

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
                                            @php
                                                $evidenceExt = strtolower(pathinfo($hasil->evidence_path, PATHINFO_EXTENSION));
                                                $imageEvidence = in_array($evidenceExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                            @endphp
                                            @if ($imageEvidence)
                                                <img class="hasil-evidence-image" src="{{ Storage::disk('public')->url($hasil->evidence_path) }}" alt="Bukti audit" style="display:block; max-width:220px; max-height:160px; margin-top:8px; border:1px solid #dfe5ee; border-radius:8px;" />
                                            @endif
                                            <a class="hasil-bukti" href="{{ Storage::disk('public')->url($hasil->evidence_path) }}" target="_blank" rel="noreferrer">Bukti: {{ basename($hasil->evidence_path) }}</a>
                                        @endif
                                    @empty
                                        <p>-</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="record-row">
                                <div class="record-label">Catatan Evaluasi</div>
                                <div class="record-value">{{ $item->note ?: '-' }}</div>
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

        <section class="ispo-results">
            <h2>RINGKASAN KETIDAKSESUAIAN / TEMUAN</h2>
            @if ($audit->findings->isEmpty() && $items->where('status', 'non_compliant')->isEmpty())
                <p>Tidak ada temuan ketidaksesuaian yang dicatat.</p>
            @else
                @foreach ($audit->findings as $finding)
                    <article class="verification-record">
                        <h3>{{ $finding->note }}</h3>
                        @if ($finding->recommendation)
                            <p>{{ $finding->recommendation }}</p>
                        @endif
                    </article>
                @endforeach
                @foreach ($items->where('status', 'non_compliant') as $item)
                    <article class="verification-record">
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->note ?: 'Belum ada catatan tindak lanjut.' }}</p>
                    </article>
                @endforeach
            @endif
        </section>

        <footer class="ispo-report-ending">
            <p>Status audit: <strong>{{ $audit->status === 'completed' ? 'Selesai' : 'Belum selesai' }}</strong></p>
            <p>Dokumen ini dibuat dari data verifikasi audit ISPO yang tersimpan dalam sistem.</p>
        </footer>
    </main>
</body>
</html>
