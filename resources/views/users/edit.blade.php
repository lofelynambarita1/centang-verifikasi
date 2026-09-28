@extends('layouts.app')
@section('title', 'Edit Pengguna | Verifikasi Audit ISPO')
@section('eyebrow', 'AKSES TIM')
@section('heading', 'Edit pengguna')
@section('actions')<a class="btn btn--ghost" href="{{ route('users.index') }}">← Kembali</a>@endsection
@section('content')
<p class="page-intro">Perbarui identitas dan kewenangan pengguna.</p>
<section class="panel panel--pad form-card"><form class="form" method="POST" action="{{ route('users.update', $user) }}">@csrf @method('PUT') @include('users._form', ['user' => $user])<button class="btn btn--primary form-submit" type="submit">Simpan perubahan</button></form></section>
@endsection