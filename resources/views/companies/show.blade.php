@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
@endphp

@section('title', $company->name.' | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Detail perusahaan')
@section('page-class', 'app-main--company-detail')

@section('actions')
    <div class="app-topbar__actions">
        <a class="btn btn--ghost" href="{{ route('companies.index') }}">Kembali</a>
        <a class="btn btn--primary" href="{{ route('companies.edit', $company) }}">Edit data</a>
    </div>
@endsection

@section('content')
    <div class="company-detail-page">
        <section class="hero company-profile" aria-labelledby="company-profile-title">
            <header class="company-profile__header">
                <div class="company-profile__identity">
                    <span class="company-profile__eyebrow">Profil perusahaan</span>
                    <h2 class="company-profile__title" id="company-profile-title">{{ $company->name }}</h2>
                    <span class="company-profile__industry">{{ $company->industry ?: 'Bidang usaha belum diisi' }}</span>
                </div>
                @if ($company->audits->isNotEmpty())
                    <a class="btn btn--light" href="{{ route('audits.show', $company->audits->sortByDesc('created_at')->first()) }}">Buka audit terbaru</a>
                @endif
            </header>

            <dl class="company-profile__details">
                <div class="company-profile__item">
                    <dt>Alamat</dt>
                    <dd>{{ $company->address ?: 'Belum diisi' }}</dd>
                </div>
                <div class="company-profile__item">
                    <dt>Bidang usaha</dt>
                    <dd>{{ $company->industry ?: 'Belum diisi' }}</dd>
                </div>
                <div class="company-profile__item">
                    <dt>Nama PIC</dt>
                    <dd>{{ $company->contact_name ?: 'Belum diisi' }}</dd>
                </div>
                <div class="company-profile__item">
                    <dt>Email PIC</dt>
                    <dd>{{ $company->contact_email ?: 'Belum diisi' }}</dd>
                </div>
            </dl>
        </section>

        <section class="panel panel--pad company-history" aria-labelledby="company-history-title">
            <div class="panel__head company-history__head">
                <div>
                    <h2 id="company-history-title">Riwayat audit</h2>
                    <p>Seluruh kegiatan verifikasi untuk perusahaan ini</p>
                </div>
                <span class="company-history__count">
                    {{ $company->audits->count() }} audit
                </span>
            </div>

            <div class="table-wrap company-history__table-wrap">
                <table class="table company-audit-table">
                    <thead>
                        <tr>
                            <th>Judul audit</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Indikator</th>
                            <th>Progres</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($company->audits->sortByDesc('created_at') as $audit)
                        @php
                            $totalItems = $audit->items->count();
                            $verifiedItems = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                            $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
                            $statusClass = $audit->status === 'completed' ? 'chip--done' : ($audit->status === 'in_progress' ? 'chip--wip' : 'chip--todo');
                            $statusLabel = $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Proses verifikasi' : 'Belum mulai');
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-company">
                                    <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($audit->title ?: $company->name) }}</div>
                                    <div>
                                        <strong>{{ $audit->title ?: 'Audit verifikasi' }}</strong>
                                        <span>{{ $audit->auditorNamesText() ?: 'Auditor belum ditentukan' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="chip {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td><span class="company-audit-table__date">{{ $audit->due_date?->translatedFormat('d M Y') ?: 'Tanpa deadline' }}</span></td>
                            <td><span class="company-audit-table__indicators"><strong>{{ $verifiedItems }}</strong><span>/ {{ $totalItems }} dinilai</span></span></td>
                            <td>
                                <div class="progress company-audit-progress">
                                    <div
                                        class="progress__track"
                                        role="progressbar"
                                        aria-label="Progres indikator audit {{ $audit->title ?: 'verifikasi' }}"
                                        aria-valuenow="{{ $progress }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuetext="{{ $verifiedItems }} dari {{ $totalItems }} indikator dinilai"
                                    >
                                        <div class="progress__bar" style="width:{{ $progress }}%"></div>
                                    </div>
                                    <span class="progress__pct">{{ $progress }}%</span>
                                </div>
                            </td>
                            <td><a class="btn btn--ghost btn--sm company-audit-table__action" href="{{ route('audits.show', $audit) }}">Buka audit</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty">
                                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 11l3 3L22 4"/>
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                    </svg>
                                    <strong>Belum ada audit</strong>
                                    <span>Audit baru dibuat otomatis saat perusahaan pertama kali ditambahkan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection