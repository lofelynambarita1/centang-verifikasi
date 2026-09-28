@extends('layouts.app')
@section('title', 'Tambah Pengguna | Verifikasi Audit ISPO')
@section('eyebrow', 'AKSES TIM')
@section('heading', 'Tambah pengguna')
@section('actions')<a class="btn btn--ghost" href="{{ route('users.index') }}">← Kembali</a>@endsection
@section('content')
<p class="page-intro">Buat akun untuk admin, lead audit, atau auditor yang akan mengerjakan prinsip.</p>
<section class="panel panel--pad form-card"><form class="form" method="POST" action="{{ route('users.store') }}">@csrf @include('users._form', ['user' => null])<button class="btn btn--primary form-submit" type="submit">Simpan pengguna</button></form></section>
@endsection