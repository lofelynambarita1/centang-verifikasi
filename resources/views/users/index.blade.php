@extends('layouts.app')

@section('title', 'Pengguna & Role | Verifikasi Audit ISPO')
@section('eyebrow', 'AKSES TIM')
@section('heading', 'Pengguna & role')

@section('actions')
    <a class="btn btn--primary" href="{{ route('users.create') }}">+ Tambah pengguna</a>
@endsection

@section('content')
    <p class="page-intro">Atur siapa yang dapat mengelola sistem, memimpin audit, atau mengerjakan prinsip tertentu.</p>
    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table user-table">
                <thead><tr><th>Pengguna</th><th>Role</th><th>Email</th><th>Aksi</th></tr></thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td><span class="role-badge role-badge--{{ $user->role }}">{{ str_replace('_', ' ', ucfirst($user->role)) }}</span></td>
                        <td>{{ $user->email }}</td>
                        <td><div class="cell-actions"><a class="btn btn--ghost btn--sm" href="{{ route('users.edit', $user) }}">Edit</a><form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Hapus</button></form></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection