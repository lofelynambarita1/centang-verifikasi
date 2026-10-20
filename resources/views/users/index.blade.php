@extends('layouts.app')

@section('title', 'Pengguna & Role | Verifikasi Audit ISPO')
@section('eyebrow', 'AKSES TIM')
@section('heading', 'Pengguna & role')

@section('actions')
    <a class="btn btn--primary" href="{{ route('users.create') }}">+ Tambah pengguna</a>
@endsection

@section('content')
    @php
        $pendingUsersCount = $users->whereNotNull('requested_role')->count();
        $activeUsersCount = $users->count() - $pendingUsersCount;
    @endphp

    <p class="page-intro users-intro">Kelola akses tim audit, tinjau pendaftaran baru, dan tetapkan role untuk setiap pengguna.</p>

    <section class="users-overview" aria-label="Ringkasan pengguna">
        <div class="users-overview__card">
            <span class="users-overview__icon users-overview__icon--active" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
            <span><strong>{{ number_format($activeUsersCount) }}</strong><small>Pengguna aktif</small></span>
        </div>
        <div class="users-overview__card">
            <span class="users-overview__icon users-overview__icon--pending" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 2"/>
                </svg>
            </span>
            <span><strong>{{ number_format($pendingUsersCount) }}</strong><small>Menunggu persetujuan</small></span>
        </div>
    </section>

    <section class="panel panel--pad users-panel">
        <div class="users-panel__heading">
            <div>
                <span class="users-panel__eyebrow">AKSES TIM AUDIT</span>
                <h2>Daftar pengguna</h2>
            </div>
            <span class="users-panel__total">{{ number_format($users->count()) }} akun</span>
        </div>
        <div class="table-wrap">
            <table class="table user-table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Status &amp; role</th>
                        <th>Email</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="users-identity">
                                <span class="users-identity__avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td>
                            @if ($user->requested_role)
                                <div class="users-pending">
                                    <span class="users-pending__badge"><span aria-hidden="true"></span>Menunggu persetujuan</span>
                                    <small>Role ditetapkan oleh Master Admin</small>
                                </div>
                            @else
                                <span class="role-badge role-badge--{{ $user->role }}">{{ $user->role === 'admin' ? 'Master Admin' : ($user->role === 'lead_audit' ? 'Lead Auditor' : 'Auditor') }}</span>
                            @endif
                        </td>
                        <td><span class="users-email">{{ $user->email }}</span></td>
                        <td>
                            <div class="users-actions">
                                @if ($user->requested_role)
                                    <form class="users-approval" method="POST" action="{{ route('users.approve-role', $user) }}">
                                        @csrf
                                        <label class="sr-only" for="role-{{ $user->id }}">Role untuk {{ $user->name }}</label>
                                        <select id="role-{{ $user->id }}" name="role" required>
                                            <option value="" disabled @selected(old('role') === null)>Pilih role akun</option>
                                            <option value="auditor" @selected(old('role') === 'auditor')>Auditor</option>
                                            <option value="lead_audit" @selected(old('role') === 'lead_audit')>Lead Auditor</option>
                                            <option value="admin" @selected(old('role') === 'admin')>Master Admin</option>
                                        </select>
                                        <button class="btn btn--primary btn--sm" type="submit">
                                            Setujui akun
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m5 12 4 4L19 6"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                <div class="users-actions__secondary">
                                    <a class="btn btn--ghost btn--sm" href="{{ route('users.edit', $user) }}">Edit</a>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection