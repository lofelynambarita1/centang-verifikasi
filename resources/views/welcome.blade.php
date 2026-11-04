@extends('layouts.app')

@section('title', 'Dashboard | Verifikasi Audit ISPO')
@section('eyebrow', 'VERIFIKASI AUDIT ISPO')
@section('heading', 'Dashboard')

@section('actions')
@endsection

@section('content')
    <p class="page-intro">Ringkasan status verifikasi audit seluruh perusahaan dalam satu tampilan.</p>

    {{-- Pencarian --}}
    <section class="panel panel--pad filter-panel" aria-label="Pencarian proyek audit aktif">
        <form method="GET" action="{{ route('dashboard') }}" class="filter-form dashboard-filter-form">
            <div class="field filter-field">
                <label for="dashboard-search">Cari proyek audit aktif</label>
                <input
                    id="dashboard-search"
                    name="search"
                    type="search"
                    value="{{ old('search', $filters['search'] ?? '') }}"
                    placeholder="Nama perusahaan, bidang usaha, atau alamat"
                >
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn--primary">Terapkan</button>
                <a href="{{ route('dashboard') }}" class="btn btn--ghost">Reset</a>
            </div>
        </form>
    </section>

    {{-- Kartu statistik --}}
    <section class="stat-grid" aria-label="Ringkasan audit">
        <article class="stat-card stat-card--green">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="2" width="16" height="20" rx="1.5"/>
                    <path d="M9 22v-4h6v4"/>
                    <path d="M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>
                </svg>
            </div>
            <p class="stat-card__label">Total perusahaan</p>
            <strong class="stat-card__value">{{ $stats['companies'] }}</strong>
            <span class="stat-card__hint">Terdaftar sebagai auditee</span>
        </article>

        <article class="stat-card stat-card--teal">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 7l-8.5 8.5-5-5L2 17"/>
                    <path d="M16 7h6v6"/>
                </svg>
            </div>
            <p class="stat-card__label">Audit berjalan</p>
            <strong class="stat-card__value">{{ $stats['in_progress'] }}</strong>
            <span class="stat-card__hint">Sedang dalam proses verifikasi</span>
        </article>

        <article class="stat-card stat-card--amber">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 12l3 3 6-6"/>
                </svg>
            </div>
            <p class="stat-card__label">Audit selesai</p>
            <strong class="stat-card__value">{{ $stats['completed'] }}</strong>
            <span class="stat-card__hint">Proyek verifikasi yang telah selesai</span>
        </article>

        <article class="stat-card stat-card--coral">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
                    <path d="M12 9v4"/>
                    <path d="M12 17h.01"/>
                </svg>
            </div>
            <p class="stat-card__label">Temuan / ketidaksesuaian</p>
            <strong class="stat-card__value">{{ $stats['not_met'] }}</strong>
            <span class="stat-card__hint">Indikator yang perlu tindak lanjut</span>
        </article>
    </section>

    @if ($canViewAllProgress && ($filters['search'] ?? '') === '')
        @php
            $indicatorTotal = $stats['total_items'];
            $metEnd = $indicatorTotal > 0 ? $stats['met_items'] / $indicatorTotal * 100 : 0;
            $notMetEnd = $metEnd + ($indicatorTotal > 0 ? $stats['not_met_items'] / $indicatorTotal * 100 : 0);
            $notApplicableEnd = $notMetEnd + ($indicatorTotal > 0 ? $stats['not_applicable_items'] / $indicatorTotal * 100 : 0);
        @endphp

        <section class="panel panel--pad dashboard-overall" aria-labelledby="dashboard-overall-title">
            <div class="panel__head">
                <div class="dashboard-section-heading">
                    <span class="dashboard-section-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v9h9"/>
                            <path d="M20.5 15A9 9 0 1 1 9 3.5"/>
                        </svg>
                    </span>
                    <div>
                        <h2 id="dashboard-overall-title">Status Seluruh Indikator</h2>
                        <p>Total keseluruhan penilaian</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-overall__body">
                <div
                    class="dashboard-overall__chart"
                    role="img"
                    aria-label="Diagram lingkaran status indikator: Memenuhi {{ $stats['met_items'] }}, Belum memenuhi {{ $stats['not_met_items'] }}, Tidak berlaku {{ $stats['not_applicable_items'] }}, Menunggu penilaian {{ $stats['pending_items'] }}"
                    @if ($indicatorTotal > 0)
                        style="--dashboard-met-end: {{ $metEnd }}%; --dashboard-not-met-end: {{ $notMetEnd }}%; --dashboard-not-applicable-end: {{ $notApplicableEnd }}%;"
                    @endif
                >
                    <div class="dashboard-overall__chart-center">
                        <strong>{{ $stats['completion_percentage'] }}%</strong>
                        <span>terverifikasi</span>
                    </div>
                </div>

                <div class="dashboard-overall__details">
                    <ul class="dashboard-overall__legend">
                        <li>
                            <span class="dashboard-overall__swatch dashboard-overall__swatch--met" aria-hidden="true"></span>
                            <span>Memenuhi</span>
                            <strong>{{ $stats['met_items'] }}</strong>
                        </li>
                        <li>
                            <span class="dashboard-overall__swatch dashboard-overall__swatch--not-met" aria-hidden="true"></span>
                            <span>Belum memenuhi</span>
                            <strong>{{ $stats['not_met_items'] }}</strong>
                        </li>
                        <li>
                            <span class="dashboard-overall__swatch dashboard-overall__swatch--not-applicable" aria-hidden="true"></span>
                            <span>Tidak berlaku</span>
                            <strong>{{ $stats['not_applicable_items'] }}</strong>
                        </li>
                        <li>
                            <span class="dashboard-overall__swatch dashboard-overall__swatch--pending" aria-hidden="true"></span>
                            <span>Menunggu penilaian</span>
                            <strong>{{ $stats['pending_items'] }}</strong>
                        </li>
                    </ul>
                    <p class="dashboard-overall__summary">{{ $stats['completed_items'] }} dari {{ $stats['total_items'] }} indikator telah dinilai</p>
                </div>
            </div>
        </section>
    @endif

    <section class="panel panel--pad dashboard-projects" id="active-projects">
        <div class="panel__head">
            <div class="dashboard-section-heading">
                <span class="dashboard-section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>
                    </svg>
                </span>
                <div>
                    <h2>Proyek audit aktif</h2>
                    <p>Progres indikator dan penanggung jawab tiap proyek</p>
                </div>
            </div>
            @if ($canViewAllProgress)
                <a class="panel__link" href="{{ route('audits.assignments.index') }}">Lihat penugasan</a>
            @endif
        </div>

        <div class="table-wrap">
            <table class="table dashboard-projects__table">
                <thead>
                    <tr>
                        <th>Perusahaan dan judul audit</th>
                        <th>Lead Auditor dan tim</th>
                        <th>Rentang tanggal</th>
                        <th>Progres indikator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activeAudits as $audit)
                        @php
                            $progressSummary = $dashboardProgress[$audit->id]['summary'];
                            $principleProgress = $dashboardProgress[$audit->id]['principles'];
                            $teamAuditorNames = $audit->teamAuditors()
                                ->reject(fn ($user) => $user->id === $audit->leadMember?->user_id)
                                ->pluck('name')
                                ->implode(', ');
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $audit->company->name }}</strong>
                                <div class="cell-meta">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</div>
                            </td>
                            <td>
                                <strong class="dashboard-projects__team-lead">{{ $audit->leadMember?->user?->name ?: 'Lead belum ditentukan' }}</strong>
                                <div class="cell-meta dashboard-projects__team">Tim: {{ $teamAuditorNames ?: 'Belum ditentukan' }}</div>
                            </td>
                            <td class="dashboard-projects__dates">
                                <div>{{ $audit->start_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}</div>
                                <div class="dashboard-projects__date-end">
                                    {{ $audit->end_date ? 's/d '.$audit->end_date->translatedFormat('d M Y') : 'Tanggal akhir belum ditentukan' }}
                                </div>
                            </td>
                            <td>
                                <div class="progress dashboard-projects__progress">
                                    <div class="progress__track">
                                        <div class="progress__bar" style="width:{{ $progressSummary['percentage'] }}%"></div>
                                    </div>
                                    <span class="progress__pct">{{ $progressSummary['percentage'] }}%</span>
                                </div>
                                <div class="cell-meta"><strong>{{ $progressSummary['verified'] }}</strong> / {{ $progressSummary['total'] }} indikator selesai</div>
                                @if ($principleProgress !== [])
                                    <details class="dashboard-projects__principles">
                                        <summary>Progres per prinsip</summary>
                                        <ul>
                                            @foreach ($principleProgress as $principle)
                                                <li data-principle="{{ $principle['principle'] }}">
                                                    <div class="dashboard-projects__principle-label">
                                                        <span>Prinsip {{ $principle['principle'] }}</span>
                                                        <strong>{{ $principle['percentage'] }}%</strong>
                                                    </div>
                                                    <div
                                                        class="dashboard-projects__principle-track"
                                                        role="progressbar"
                                                        aria-label="Progres Prinsip {{ $principle['principle'] }}"
                                                        aria-valuenow="{{ $principle['percentage'] }}"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        aria-valuetext="{{ $principle['verified'] }} dari {{ $principle['total'] }} indikator selesai"
                                                    >
                                                        <span style="width: {{ $principle['percentage'] }}%"></span>
                                                    </div>
                                                    <span class="dashboard-projects__principle-count">{{ $principle['verified'] }} / {{ $principle['total'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td class="dashboard-projects__action">
                                <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', $audit) }}">Buka</a>
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
                                    <strong>Tidak ada proyek audit aktif</strong>
                                    <span>{{ ($filters['search'] ?? '') !== '' ? 'Tidak ditemukan proyek aktif yang sesuai dengan pencarian.' : 'Proyek yang belum mulai atau sedang diverifikasi akan muncul di sini.' }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endsection