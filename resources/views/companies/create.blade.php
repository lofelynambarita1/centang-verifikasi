@extends('layouts.app')

@section('title', 'Tambah Perusahaan | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Tambah perusahaan')

@section('content')
    <p class="page-intro">Lengkapi data perusahaan di bawah ini. Checklist audit ISPO (7 prinsip) otomatis dibuat untuk perusahaan ini.</p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('companies.store') }}">
            @csrf
            @include('companies._form', ['company' => null, 'audit' => null])
            <div class="form-actions">
                <a class="btn btn--ghost" href="{{ route('companies.index') }}">Kembali</a>
                <button class="btn btn--primary form-submit" type="submit">Simpan dan buat checklist</button>
            </div>
        </form>
    </section>
@endsection