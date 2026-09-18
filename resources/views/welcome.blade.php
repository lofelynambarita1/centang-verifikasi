<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Verifikasi Audit ISPO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
    $avatarColors = ['dash-avatar--coral', 'dash-avatar--navy', 'dash-avatar--mint', 'dash-avatar--amber', 'dash-avatar--violet'];
    $totalItems = $stats['total_items'];
    $metPct = $totalItems ? round($stats['met'] / $totalItems * 100) : 0;
    $notPct = $totalItems ? round($stats['not_met'] / $totalItems * 100) : 0;
    $overallPct = $totalItems ? round($stats['completed_items'] / $totalItems * 100) : 0;
    $pendingCount = max(0, $totalItems - $stats['completed_items']);
@endphp
<div class="ispo-shell">
    <aside class="ispo-sidebar">
        <a class="ispo-brand" href="{{ route('dashboard') }}">
            <strong>Verifikasi Audit ISPO</strong>
            <span>Permentan No. 33/2025</span>
            <small>Mode offline · data tersimpan aman di browser ini</small>
        </a>
        <nav class="ispo-nav" aria-label="Navigasi utama">
            <a class="ispo-nav-link ispo-nav-active" href="{{ route('dashboard') }}"><b>▪</b> Dashboard</a>
            <a class="ispo-nav-link" href="{{ route('companies.index') }}"><b>▪</b> Daftar Perusahaan</a>
        </nav>
    </aside>

    <main class="ispo-main">
        <header class="ispo-header">
            <div>
                <p class="ispo-kicker">VERIFIKASI AUDIT ISPO</p>
                <h1>Dashboard</h1>
                <p>Ringkasan status verifikasi audit seluruh perusahaan</p>
            </div>
            <div class="dash-actions">
                <a class="dash-btn dash-btn--ghost" href="{{ route('companies.index') }}">Kelola perusahaan</a>
                <a class="dash-btn dash-btn--primary" href="{{ route('companies.create') }}">+ Perusahaan baru</a>
            </div>
        </header>

        <section class="dash-stats" aria-label="Ringkasan audit">
            <article class="dash-card dash-card--green">
                <div class="dash-card__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="2" width="16" height="20" rx="1.5"/>
                        <path d="M9 22v-4h6v4"/><path d="M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>
                    </svg>
                </div>
                <p class="dash-card__label">Total perusahaan</p>
                <strong class="dash-card__value">{{ $stats['companies'] }}</strong>
                <span class="dash-card__hint">Terdaftar sebagai auditee</span>
            </article>

            <article class="dash-card dash-card--teal">
                <div class="dash-card__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 7l-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>
                    </svg>
                </div>
                <p class="dash-card__label">Rata-rata progres</p>
                <strong class="dash-card__value">{{ $stats['average_progress'] }}%</strong>
                <span class="dash-card__hint">Seluruh perusahaan</span>
            </article>

            <article class="dash-card dash-card--amber">
                <div class="dash-card__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 6-6"/>
                    </svg>
                </div>
                <p class="dash-card__label">Audit selesai</p>
                <strong class="dash-card__value">{{ $stats['completed'] }}</strong>
                <span class="dash-card__hint">Dinyatakan 100% memenuhi</span>
            </article>

            <article class="dash-card dash-card--coral">
                <div class="dash-card__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
                        <path d="M12 9v4"/><path d="M12 17h.01"/>
                    </svg>
                </div>
                <p class="dash-card__label">Indikator belum memenuhi</p>
                <strong class="dash-card__value">{{ $stats['not_met'] }}</strong>
                <span class="dash-card__hint">Perlu tindak lanjut</span>
            </article>
        </section>

        <section class="dash-grid">
            <div class="dash-panel">
                <div class="dash-panel__head">
                    <div>
                        <h2>Progres perusahaan</h2>
                        <p>Status verifikasi per auditee</p>
                    </div>
                    <a class="dash-panel__link" href="{{ route('companies.index') }}">Lihat semua →</a>
                </div>

                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Perusahaan</th>
                                <th>Audit</th>
                                <th>Progres</th>
                                <th>Indikator</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($companyRows as $row)
                            @php
                                $audit = $row['audit'];
                                $statusClass = $audit?->status === 'completed' ? 'dash-chip--done' : ($audit?->status === 'in_progress' ? 'dash-chip--wip' : 'dash-chip--todo');
                                $statusLabel = $audit?->status === 'completed' ? 'Selesai' : ($audit?->status === 'in_progress' ? 'Proses verifikasi' : 'Belum mulai');
                                $auditMeta = $audit?->auditor_name
                                    ? 'Oleh '.$audit->auditor_name
                                    : ($audit?->due_date?->format('d F Y') ?: 'Auditor belum ditentukan');
                            @endphp
                            <tr>
                                <td>
                                    <div class="dash-cell-company">
                                        <div class="dash-avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($row['company']->name) }}</div>
                                        <div class="dash-company">
                                            <strong>{{ $row['company']->name }}</strong>
                                            <span>{{ $row['company']->industry ?: $row['company']->address ?: 'Data perusahaan belum lengkap' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="dash-chip {{ $statusClass }}">{{ $statusLabel }}</span>
                                    <div class="dash-cell-meta">{{ $auditMeta }}</div>
                                </td>
                                <td>
                                    <div class="dash-progress">
                                        <div class="dash-progress__track">
                                            <div class="dash-progress__bar" style="width:{{ $row['progress'] }}%"></div>
                                        </div>
                                        <span class="dash-progress__pct">{{ $row['progress'] }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="dash-cell-meta"><strong>{{ $row['verified_items'] }}</strong> / {{ $row['total_items'] }} indikator dinilai</div>
                                </td>
                                <td>
                                    <a class="dash-link" href="{{ $audit ? route('audits.show', $audit) : route('companies.show', $row['company']) }}">Rincian →</a>
                                </td>
                            </tr>
                        @empty
                            <tr class="dash-table__empty">
                                <td colspan="5">
                                    <div class="dash-empty">
                                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                                            <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                                        </svg>
                                        <strong>Belum ada perusahaan</strong>
                                        <span>Tambahkan perusahaan (auditee) untuk memulai proses verifikasi.</span>
                                        <a class="dash-btn dash-btn--primary" href="{{ route('companies.create') }}">+ Perusahaan baru</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-side">
                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <div>
                            <h2>Ringkasan indikator</h2>
                            <p>Total keseluruhan penilaian</p>
                        </div>
                    </div>
                    <div class="dash-donut">
                        <div class="dash-donut__ring" style="background: conic-gradient(#19a06e 0 {{ $metPct }}%, #e0a32c {{ $metPct }}% calc({{ $metPct }}% + {{ $notPct }}%), #e9eeeb calc({{ $metPct }}% + {{ $notPct }}%) 100%);">
                            <div class="dash-donut__inner">
                                <strong>{{ $overallPct }}%</strong>
                                <span>terverifikasi</span>
                            </div>
                        </div>
                    </div>
                    <ul class="dash-legend">
                        <li><i class="ld-green"></i>Memenuhi<strong>{{ $stats['met'] }}</strong></li>
                        <li><i class="ld-amber"></i>Belum memenuhi<strong>{{ $stats['not_met'] }}</strong></li>
                        <li><i class="ld-gray"></i>Menunggu penilaian<strong>{{ $pendingCount }}</strong></li>
                    </ul>
                    <div class="dash-donut-foot">{{ $stats['completed_items'] }} dari {{ $totalItems }} indikator telah dinilai</div>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel__head">
                        <div>
                            <h2>Audit terkini</h2>
                            <p>Kegiatan verifikasi terakhir</p>
                        </div>
                    </div>
                    <div class="dash-feed">
                        @forelse ($audits->take(4) as $audit)
                            @php
                                $feedTotal = $audit->items->count();
                                $feedVerified = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                                $feedPct = $feedTotal ? round($feedVerified / $feedTotal * 100) : 0;
                                $feedClass = $audit->status === 'completed' ? 'dash-chip--done' : ($audit->status === 'in_progress' ? 'dash-chip--wip' : 'dash-chip--todo');
                                $feedLabel = $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Proses verifikasi' : 'Belum mulai');
                            @endphp
                            <a class="dash-feed__item" href="{{ route('audits.show', $audit) }}">
                                <div class="dash-feed__avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($audit->company->name) }}</div>
                                <div class="dash-feed__body">
                                    <strong>{{ $audit->title ?: $audit->company->name }}</strong>
                                    <span>{{ $audit->company->name }}</span>
                                </div>
                                <div class="dash-feed__side">
                                    <span class="dash-chip {{ $feedClass }}">{{ $feedLabel }}</span>
                                    <strong class="dash-feed__pct">{{ $feedPct }}%</strong>
                                </div>
                            </a>
                        @empty
                            <div class="dash-feed__empty">
                                <p>Belum ada aktivitas audit.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>