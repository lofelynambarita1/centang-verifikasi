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

@section('actions')
    <a class="btn btn--ghost" href="{{ route('companies.index') }}">← Kembali</a>
    <a class="btn btn--primary" href="{{ route('companies.edit', $company) }}">Edit data</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $company->name }}</div>
                <div class="hero__sub">{{ $company->industry ?: 'Bidang usaha belum diisi' }}</div>
            </div>
            <div class="hero__actions">
                @if ($company->audits->isNotEmpty())
                    <a class="btn btn--light" href="{{ route('audits.show', $company->audits->sortByDesc('created_at')->first()) }}">Buka audit terbaru</a>
                @endif
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row"><span>Alamat</span><b>{{ $company->address ?: 'Belum diisi' }}</b></div>
            <div class="meta-row"><span>Bidang usaha</span><b>{{ $company->industry ?: 'Belum diisi' }}</b></div>
            <div class="meta-row"><span>Nama PIC</span><b>{{ $company->contact_name ?: 'Belum diisi' }}</b></div>
            <div class="meta-row"><span>Email PIC</span><b>{{ $company->contact_email ?: 'Belum diisi' }}</b></div>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Audit perusahaan</h2>
                <p>Seluruh kegiatan verifikasi untuk perusahaan ini</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul audit</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Indikator</th>
                        <th>Progres</th>
                        <th></th>
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
                        <td>{{ $audit->due_date?->format('d M Y') ?: 'Tanpa deadline' }}</td>
                        <td><div class="cell-meta"><strong>{{ $verifiedItems }}</strong> / {{ $totalItems }} dinilai</div></td>
                        <td>
                            <div class="progress">
                                <div class="progress__track">
                                    <div class="progress__bar" style="width:{{ $progress }}%"></div>
                                </div>
                                <span class="progress__pct">{{ $progress }}%</span>
                            </div>
                        </td>
                        <td><a class="link" href="{{ route('audits.show', $audit) }}">Buka audit →</a></td>
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
@endsection