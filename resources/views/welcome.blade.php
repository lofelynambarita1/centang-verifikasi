@extends('layouts.app')

@php
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $totalItems = $stats['total_items'];
    $metPct = $totalItems ? round($stats['met'] / $totalItems * 100) : 0;
    $notPct = $totalItems ? round($stats['not_met'] / $totalItems * 100) : 0;
    $overallPct = $totalItems ? round($stats['completed_items'] / $totalItems * 100) : 0;
    $pendingCount = max(0, $totalItems - $stats['completed_items']);
@endphp

@section('title', 'Dashboard | Verifikasi Audit ISPO')
@section('eyebrow', 'VERIFIKASI AUDIT ISPO')
@section('heading', 'Dashboard')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('companies.index') }}">Kelola perusahaan</a>
    <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Perusahaan baru</a>
@endsection

@section('content')
    <p class="page-intro">Ringkasan status verifikasi audit seluruh perusahaan dalam satu tampilan.</p>

    <section class="stat-grid" aria-label="Ringkasan audit">
        <article class="stat-card stat-card--green">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="2" width="16" height="20" rx="1.5"/>
                    <path d="M9 22v-4h6v4"/><path d="M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>
                </svg>
            </div>
            <p class="stat-card__label">Total perusahaan</p>
            <strong class="stat-card__value">{{ $stats['companies'] }}</strong>
            <span class="stat-card__hint">Terdaftar sebagai auditee</span>
        </article>

        <article class="stat-card stat-card--teal">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 7l-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>
                </svg>
            </div>
            <p class="stat-card__label">Rata-rata progres</p>
            <strong class="stat-card__value">{{ $stats['average_progress'] }}%</strong>
            <span class="stat-card__hint">Seluruh perusahaan</span>
        </article>

        <article class="stat-card stat-card--amber">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 6-6"/>
                </svg>
            </div>
            <p class="stat-card__label">Audit selesai</p>
            <strong class="stat-card__value">{{ $stats['completed'] }}</strong>
            <span class="stat-card__hint">Dinyatakan 100% memenuhi</span>
        </article>

        <article class="stat-card stat-card--coral">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
                    <path d="M12 9v4"/><path d="M12 17h.01"/>
                </svg>
            </div>
            <p class="stat-card__label">Indikator belum memenuhi</p>
            <strong class="stat-card__value">{{ $stats['not_met'] }}</strong>
            <span class="stat-card__hint">Perlu tindak lanjut</span>
        </article>
    </section>

    <div class="dash-grid">
        <section class="panel panel--pad">
            <div class="panel__head">
                <div>
                    <h2>Progres perusahaan</h2>
                    <p>Status verifikasi per auditee</p>
                </div>
                <a class="panel__link" href="{{ route('companies.index') }}">Lihat semua →</a>
            </div>

            <div class="table-wrap">
                <table class="table">
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
                            $statusClass = $audit?->status === 'completed' ? 'chip--done' : ($audit?->status === 'in_progress' ? 'chip--wip' : 'chip--todo');
                            $statusLabel = $audit?->status === 'completed' ? 'Selesai' : ($audit?->status === 'in_progress' ? 'Proses verifikasi' : 'Belum mulai');
                            $auditMeta = $audit?->auditor_name
                                ? 'Oleh '.$audit->auditor_name
                                : ($audit?->due_date?->format('d F Y') ?: 'Auditor belum ditentukan');
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-company">
                                    <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($row['company']->name) }}</div>
                                    <div>
                                        <strong>{{ $row['company']->name }}</strong>
                                        <span>{{ $row['company']->industry ?: $row['company']->address ?: 'Data perusahaan belum lengkap' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="chip {{ $statusClass }}">{{ $statusLabel }}</span>
                                <div class="cell-meta">{{ $auditMeta }}</div>
                            </td>
                            <td>
                                <div class="progress">
                                    <div class="progress__track">
                                        <div class="progress__bar" style="width:{{ $row['progress'] }}%"></div>
                                    </div>
                                    <span class="progress__pct">{{ $row['progress'] }}%</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta"><strong>{{ $row['verified_items'] }}</strong> / {{ $row['total_items'] }} indikator dinilai</div>
                            </td>
                            <td>
                                <a class="link" href="{{ $audit ? route('audits.show', $audit) : route('companies.show', $row['company']) }}">Rincian →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty">
                                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                                        <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                                    </svg>
                                    <strong>Belum ada perusahaan</strong>
                                    <span>Tambahkan perusahaan (auditee) untuk memulai proses verifikasi.</span>
                                    <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Perusahaan baru</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="dash-side">
            <div class="panel panel--pad">
                <div class="panel__head">
                    <div>
                        <h2>Ringkasan indikator</h2>
                        <p>Total keseluruhan penilaian</p>
                    </div>
                </div>
                <div class="donut">
                    <div class="donut__ring" style="background: conic-gradient(#14875f 0 {{ $metPct }}%, #e0a32c {{ $metPct }}% calc({{ $metPct }}% + {{ $notPct }}%), #e9eeeb calc({{ $metPct }}% + {{ $notPct }}%) 100%);">
                        <div class="donut__inner">
                            <strong>{{ $overallPct }}%</strong>
                            <span>terverifikasi</span>
                        </div>
                    </div>
                </div>
                <ul class="legend">
                    <li><i class="ld-green"></i>Memenuhi<strong>{{ $stats['met'] }}</strong></li>
                    <li><i class="ld-amber"></i>Belum memenuhi<strong>{{ $stats['not_met'] }}</strong></li>
                    <li><i class="ld-gray"></i>Menunggu penilaian<strong>{{ $pendingCount }}</strong></li>
                </ul>
                <div class="donut__foot">{{ $stats['completed_items'] }} dari {{ $totalItems }} indikator telah dinilai</div>
            </div>

            <div class="panel panel--pad">
                <div class="panel__head">
                    <div>
                        <h2>Audit terkini</h2>
                        <p>Kegiatan verifikasi terakhir</p>
                    </div>
                </div>
                <div class="feed">
                    @forelse ($audits->take(4) as $audit)
                        @php
                            $feedTotal = $audit->items->count();
                            $feedVerified = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                            $feedPct = $feedTotal ? round($feedVerified / $feedTotal * 100) : 0;
                            $feedClass = $audit->status === 'completed' ? 'chip--done' : ($audit->status === 'in_progress' ? 'chip--wip' : 'chip--todo');
                            $feedLabel = $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Proses verifikasi' : 'Belum mulai');
                        @endphp
                        <a class="feed__item" href="{{ route('audits.show', $audit) }}">
                            <div class="feed__avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($audit->company->name) }}</div>
                            <div class="feed__body">
                                <strong>{{ $audit->title ?: $audit->company->name }}</strong>
                                <span>{{ $audit->company->name }}</span>
                            </div>
                            <div class="feed__side">
                                <span class="chip {{ $feedClass }}">{{ $feedLabel }}</span>
                                <strong class="feed__pct">{{ $feedPct }}%</strong>
                            </div>
                        </a>
                    @empty
                        <div class="feed__empty">
                            <p>Belum ada aktivitas audit.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
@endsection