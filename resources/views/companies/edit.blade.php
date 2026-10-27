@extends('layouts.app')

@section('title', 'Edit '.$company->name.' | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Edit data perusahaan')

@section('content')
    <p class="page-intro">Perbarui data profil dan kontak master perusahaan di bawah ini.</p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('companies.update', $company) }}">
            @csrf
            @method('PUT')
            @include('companies._form', ['company' => $company])
            <div class="form-actions">
                <a class="btn btn--ghost" href="{{ route('companies.index') }}">Kembali</a>
                <button class="btn btn--primary form-submit" type="submit">Simpan Perusahaan</button>
            </div>
        </form>
    </section>
@endsection
