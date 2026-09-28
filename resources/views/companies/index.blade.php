@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
@endphp

@section('title', 'Daftar Perusahaan | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Daftar perusahaan')

@section('actions')
    <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
@endsection

@section('content')
    <p class="page-intro">Kelola perusahaan (auditee) yang akan diverifikasi dan pantau progresnya dari satu tempat.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama perusahaan</th>
                        <th>Auditor / Tanggal</th>
                        <th>Progres verifikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($companies as $company)
                    @php
                        $audit = $company->audits->sortByDesc('created_at')->first();
                        $totalItems = $audit?->items->count() ?? 0;
                        $verifiedItems = $audit?->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count() ?? 0;
                        $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-company">
                                <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($company->name) }}</div>
                                <div>
                                    <strong>{{ $company->name }}</strong>
                                    <span>{{ $company->address ?: 'Alamat belum diisi' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $audit?->auditorNamesText() ?: 'Belum ditentukan' }}</strong>
                            <div class="cell-meta">{{ $audit?->due_date?->format('d F Y') ?: '-' }}</div>
                        </td>
                        <td>
                            <div class="progress">
                                <div class="progress__track">
                                    <div class="progress__bar" style="width:{{ $progress }}%"></div>
                                </div>
                                <span class="progress__pct">{{ $progress }}%</span>
                            </div>
                            <div class="cell-meta">{{ $verifiedItems }}/{{ $totalItems }} indikator</div>
                        </td>
                        <td>
                            <div class="cell-actions">
                                <a class="btn btn--ghost btn--sm" href="{{ $audit ? route('audits.show', $audit) : route('companies.show', $company) }}">Buka</a>
                                <a class="btn btn--ghost btn--sm" href="{{ route('companies.edit', $company) }}">Edit</a>
                                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Hapus perusahaan ini beserta auditnya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                                    <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                                </svg>
                                <strong>Belum ada perusahaan</strong>
                                <span>Tambahkan perusahaan terlebih dahulu untuk membuat audit verifikasi baru.</span>
                                <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection