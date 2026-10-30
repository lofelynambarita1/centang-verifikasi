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
    @if (auth()->user()->isAdmin())
        <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
    @endif
@endsection

@section('content')
    <p class="page-intro">Kelola master data perusahaan (auditee) dan riwayat auditnya.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama perusahaan</th>
                        <th>Bidang usaha</th>
                        <th>Kontak PIC</th>
                        <th>Total riwayat audit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($companies as $company)
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
                            {{ $company->industry ?: 'Belum diisi' }}
                        </td>
                        <td>
                            <strong>{{ $company->contact_name ?: 'Belum diisi' }}</strong>
                            <div class="cell-meta">{{ $company->contact_email ?: 'Email belum diisi' }}</div>
                        </td>
                        <td>{{ $company->audits_count }}</td>
                        <td>
                            <div class="cell-actions">
                                <a class="btn btn--ghost btn--sm" href="{{ route('companies.show', $company) }}">Detail / Riwayat</a>
                                @if (auth()->user()->isAdmin())
                                    <a class="btn btn--ghost btn--sm" href="{{ route('companies.edit', $company) }}">Edit</a>
                                    <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Hapus perusahaan ini beserta auditnya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </div>
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
                                <span>Tambahkan perusahaan terlebih dahulu untuk membuat audit verifikasi baru.</span>
                                @if (auth()->user()->isAdmin())
                                    <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection