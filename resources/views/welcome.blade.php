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
            <a class="panel__link" href="{{ route('audits.assignments.index') }}">Lihat penugasan</a>
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
                            $totalItems = $audit->items->count();
                            $verifiedItems = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                            $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
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
                                        <div class="progress__bar" style="width:{{ $progress }}%"></div>
                                    </div>
                                    <span class="progress__pct">{{ $progress }}%</span>
                                </div>
                                <div class="cell-meta"><strong>{{ $verifiedItems }}</strong> / {{ $totalItems }} indikator selesai</div>
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