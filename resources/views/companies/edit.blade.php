@extends('layouts.app')

@section('title', 'Edit '.$company->name.' | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Edit data perusahaan')

@section('content')
    <p class="page-intro">Perbarui data perusahaan. Perubahan langsung tersimpan pada semua audit yang terkait.</p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('companies.update', $company) }}">
            @csrf
            @method('PUT')
            @include('companies._form', ['company' => $company])
            <div class="form-actions">
                <a class="btn btn--ghost" href="{{ route('companies.show', $company) }}">Kembali</a>
                <button class="btn btn--primary form-submit" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>
@endsection