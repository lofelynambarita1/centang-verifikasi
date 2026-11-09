@extends('layouts.app')
@section('title', 'Edit Pengguna | Verifikasi Audit ISPO')
@section('eyebrow', 'AKSES TIM')
@section('heading', 'Edit pengguna')
@section('content')
<p class="page-intro">Perbarui identitas dan kewenangan pengguna.</p>
<section class="panel panel--pad form-card">
    <form class="form" method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users._form', ['user' => $user, 'showUserFormActions' => true])
    </form>
</section>
@endsection