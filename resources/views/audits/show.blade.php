<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verifikasi Audit ISPO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="audit-standalone-page">
    @php
        $totalItems = $allItems->count();
        $verifiedItems = $allItems->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
        $progress = $totalItems ? round(($verifiedItems / $totalItems) * 100) : 0;
        $nonCompliant = $allItems->where('status', 'non_compliant')->count();
        $compliant = $allItems->where('status', 'compliant')->count();
        $na = $allItems->where('status', 'not_applicable')->count();
        $statusTabs = [
            ['label' => 'Semua', 'count' => $totalItems],
            ['label' => 'Memenuhi', 'count' => $compliant],
            ['label' => 'Tidak memenuhi', 'count' => $nonCompliant],
            ['label' => 'NA', 'count' => $na],
        ];
    @endphp

    <div class="audit-shell">
        <aside class="audit-sidebar">
            <div class="audit-brand">
                <strong>Verifikasi Audit ISPO</strong>
                <small>Permen No. 33/2025</small>
                <span>Versi offline · data tersimpan di browser ini saja</span>
            </div>

            <nav class="audit-nav">
                <a href="#" class="nav-link">Dashboard</a>
                <a href="#" class="nav-link active">Daftar Perusahaan</a>
            </nav>
        </aside>

        <main class="audit-main">
            <div class="audit-company-card">
                <div class="company-card-header">
                    <div>
                        <div class="company-card-title">{{ $audit->company->name }}</div>
                        <div class="company-card-subtitle">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</div>
                    </div>
                    <div class="audit-toolbar">
                        <a class="secondary-action" href="{{ route('companies.edit', $audit->company) }}">Edit data</a>
                        <a class="primary-action" href="{{ route('audits.pdf', $audit) }}">Download PDF</a>
                        <a class="secondary-action" href="{{ route('audits.download', $audit) }}">Download Word</a>
                        <a class="secondary-action" href="{{ route('audits.report', $audit) }}">Cetak laporan</a>
                    </div>
                </div>
                <div class="company-card-body">
                    <div class="company-meta-row">
                        <span>Nama perusahaan</span>
                        <strong>:</strong>
                        <em>{{ $audit->company->name }}</em>
                    </div>
                    <div class="company-meta-row">
                        <span>Alamat</span>
                        <strong>:</strong>
                        <em>{{ $audit->company->address ?: 'Belum diisi' }}</em>
                    </div>
                    <div class="company-meta-row">
                        <span>Bidang usaha</span>
                        <strong>:</strong>
                        <em>{{ $audit->company->industry ?: 'Belum diisi' }}</em>
                    </div>
                    <div class="company-meta-row">
                        <span>Auditor</span>
                        <strong>:</strong>
                        <em>{{ $audit->auditor_name ?: 'Belum ditentukan' }}</em>
                    </div>
                    <div class="company-meta-row">
                        <span>Tanggal audit</span>
                        <strong>:</strong>
                        <em>{{ $audit->due_date?->translatedFormat('d F Y') ?: '-' }}</em>
                    </div>
                </div>
            </div>

            <section class="audit-summary-panel">
                <div class="summary-head">
                    <strong>Progres verifikasi keseluruhan</strong>
                    <div class="summary-metric">
                        <span>{{ $verifiedItems }} / {{ $totalItems }} indikator terisi</span>
                        <strong>{{ $progress }}%</strong>
                    </div>
                </div>
                <div class="progress-track">
                    <span style="width: {{ $progress }}%"></span>
                </div>
                <div class="summary-foot">
                    <span>Memenuhi: {{ $compliant }}</span>
                    <span> | </span>
                    <span>Tidak memenuhi: {{ $nonCompliant }}</span>
                    <span> | </span>
                    <span>NA: {{ $na }}</span>
                </div>
            </section>

            <div class="audit-principle-tabs">
                @if ($isIspoChecklist)
                    @foreach ($principles as $principleNumber => $principleItems)
                        <a class="tab-item {{ $currentPage === $principleNumber ? 'active' : '' }}" href="{{ route('audits.show', ['audit' => $audit, 'page' => $principleNumber]) }}">Prinsip {{ $principleNumber }} · {{ $principleItems->count() }}</a>
                    @endforeach
                @else
                    @foreach ($statusTabs as $index => $tab)
                        <button type="button" class="tab-item {{ $index === 0 ? 'active' : '' }}">{{ $tab['label'] }} · {{ $tab['count'] }}</button>
                        @endforeach
                    @endif
            </div>

            <section class="audit-principle-block" aria-label="Prinsip verifikasi">
                @php
                    $principleTitles = [
                        1 => 'KEPATUHAN TERHADAP PERATURAN PERUNDANG-UNDANGAN',
                        2 => 'PENERAPAN PRAKTEK PERKEBUNAN YANG BAIK',
                        3 => 'PENGELOLAAN LINGKUNGAN HIDUP, SUMBER DAYA ALAM, DAN KEANEKARAGAMAN HAYATI',
                        4 => 'TANGGUNG JAWAB KETENAGAKERJAAN',
                        5 => 'TANGGUNG JAWAB SOSIAL DAN PEMBERDAYAAN EKONOMI MASYARAKAT',
                        6 => 'PENERAPAN TRANSPARANSI',
                        7 => 'PENINGKATAN USAHA SECARA BERKELANJUTAN',
                    ];
                    $activePrinciple = $isIspoChecklist ? $currentPage : 1;
                @endphp
                <div class="principle-header">{{ $activePrinciple }}. {{ $principleTitles[$activePrinciple] }}</div>

                @php
                    $criteriaMap = config('ispo.criteria', []);
                    $previousCriteria = null;
                @endphp

                @forelse ($items as $item)
                    @php
                        preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/s', $item->title, $indicatorParts);
                        $indicatorCode = $indicatorParts[1] ?? $loop->iteration;
                        $indicatorTitle = $indicatorParts[2] ?? $item->title;
                        $criteriaCode = $isIspoChecklist ? \App\Support\IspoChecklist::criteriaCode($item->title) : null;
                        $criteriaInfo = $criteriaCode ? ($criteriaMap[$criteriaCode] ?? null) : null;
                    @endphp

                    @if ($criteriaInfo && $criteriaCode !== $previousCriteria)
                        <div class="criteria-heading">
                            <span class="criteria-code">{{ $criteriaCode }}</span>
                            <span class="criteria-title">{{ $criteriaInfo['title'] }}</span>
                        </div>
                        @if (! empty($criteriaInfo['description']))
                            <p class="criteria-desc">{{ $criteriaInfo['description'] }}</p>
                        @endif
                        @php $previousCriteria = $criteriaCode; @endphp
                    @endif

                    <div class="audit-checklist-item">
                        <div class="checklist-main">
                            <div class="checklist-title-row">
                                <div class="numbering">{{ $indicatorCode }}</div>
                                <div class="checklist-body">
                                    <p class="checklist-gist">{{ $indicatorTitle }}</p>
                                    <div class="parameter-box">
                                        <div class="parameter-label">Parameter</div>
                                        <div class="parameter-body">{!! nl2br(e($item->description ?: 'Tersedia dokumen dan bukti pendukung sesuai persyaratan.')) !!}</div>
                                    </div>

                                    <section class="hasil-block">
                                        <div class="hasil-head">
                                            <span class="field-label">Hasil Audit</span>
                                            <span class="hasil-count">{{ $item->hasils->count() }} hasil</span>
                                        </div>

                                        @forelse ($item->hasils as $hasil)
                                            <div class="hasil-card">
                                                <form method="POST" action="{{ route('audit-hasils.update', $hasil) }}" enctype="multipart/form-data" class="hasil-form">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                                    <textarea class="note-box" name="content" rows="2" placeholder="Tulis hasil audit...">{{ old('content', $hasil->content) }}</textarea>
                                                    <div class="hasil-actions">
                                                        <label class="hasil-file">Ganti bukti
                                                            <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png">
                                                        </label>
                                                        @if ($hasil->evidence_path)
                                                            <a class="hasil-evidence-link" href="{{ asset('storage/'.$hasil->evidence_path) }}" target="_blank" rel="noreferrer">Lihat bukti</a>
                                                        @endif
                                                        <button class="save-item-button" type="submit">Simpan</button>
                                                    </div>
                                                </form>
                                                <form method="POST" action="{{ route('audit-hasils.destroy', $hasil) }}" class="hasil-delete-form" onsubmit="return confirm('Hapus hasil audit ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                                    <button type="submit" class="hasil-delete-button">Hapus</button>
                                                </form>
                                            </div>
                                        @empty
                                            <p class="hasil-empty">Belum ada hasil audit. Tekan "Tambah hasil audit" untuk menambahkan.</p>
                                        @endforelse

                                        <details class="hasil-add">
                                            <summary>Tambah hasil audit</summary>
                                            <form method="POST" action="{{ route('audit-items.hasil.store', $item) }}" enctype="multipart/form-data" class="hasil-form">
                                                @csrf
                                                <input type="hidden" name="page" value="{{ $currentPage }}">
                                                <textarea class="note-box" name="content" rows="2" placeholder="Tulis hasil audit atau temuan baru..."></textarea>
                                                <div class="hasil-actions">
                                                    <label class="hasil-file">Bukti (opsional)
                                                        <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png">
                                                    </label>
                                                    <button class="save-item-button" type="submit">Tambah</button>
                                                </div>
                                            </form>
                                        </details>
                                    </section>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('audit-items.update', $item) }}" class="item-status-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="page" value="{{ $currentPage }}">
                                <div class="status-row">
                                    <label for="status-{{ $item->id }}">Status:</label>
                                    <select id="status-{{ $item->id }}" name="status">
                                        <option value="pending" @selected($item->status === 'pending')>Belum diperiksa</option>
                                        <option value="compliant" @selected($item->status === 'compliant')>Memenuhi</option>
                                        <option value="non_compliant" @selected($item->status === 'non_compliant')>Tidak memenuhi</option>
                                        <option value="not_applicable" @selected($item->status === 'not_applicable')>NA</option>
                                    </select>
                                    <button class="save-item-button" type="submit">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty-grid">Belum ada prinsip verifikasi untuk audit ini.</div>
                @endforelse

                @if ($isIspoChecklist || $items->hasPages())
                    <div class="pagination-row">
                        @if ($currentPage <= 1)
                            <button type="button" class="secondary-action ghost" disabled>← Prinsip sebelumnya</button>
                        @else
                            <a class="secondary-action ghost" href="{{ route('audits.show', ['audit' => $audit, 'page' => $currentPage - 1]) }}">← Prinsip sebelumnya</a>
                        @endif

                        @if ($isIspoChecklist)
                            <span>Prinsip {{ $currentPage }} dari 7</span>
                        @else
                            <span>Halaman {{ $currentPage }} dari {{ $items->lastPage() }} prinsip</span>
                        @endif

                        @if ($currentPage < ($isIspoChecklist ? 7 : $items->lastPage()))
                            <a class="secondary-action ghost" href="{{ route('audits.show', ['audit' => $audit, 'page' => $currentPage + 1]) }}">Prinsip berikutnya →</a>
                        @else
                            <button type="button" class="secondary-action ghost" disabled>Prinsip berikutnya →</button>
                        @endif
                    </div>
                @endif

                <div class="audit-completion">
                    <div>
                        <strong>{{ $verifiedItems === $totalItems && $totalItems > 0 ? 'Semua indikator sudah diisi' : 'Audit belum selesai' }}</strong>
                        <span>{{ $verifiedItems }} dari {{ $totalItems }} indikator sudah memiliki status.</span>
                        <span class="draft-note">Status audit: <b>{{ $audit->status === 'completed' ? 'Selesai' : 'Draft' }}</b> · data tersimpan otomatis setiap kali Anda menekan Simpan.</span>
                    </div>
                    <div class="completion-actions">
                        @if ($verifiedItems === $totalItems && $totalItems > 0)
                            <a class="primary-action" href="{{ route('audits.pdf', $audit) }}">Download 1 file lengkap (PDF)</a>
                        @else
                            <span class="draft-note">Lengkapi semua indikator untuk mengunduh seluruh prinsip dalam 1 file.</span>
                        @endif
                        <form method="POST" action="{{ route('audits.complete', $audit) }}">
                            @csrf
                            <button class="primary-action" type="submit" @disabled($verifiedItems !== $totalItems || $totalItems === 0)>Tandai audit selesai</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
