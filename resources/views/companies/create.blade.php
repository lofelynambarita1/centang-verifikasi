@extends('layouts.app')

@section('title', 'Tambah Perusahaan | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Tambah perusahaan')

@section('content')
    <p class="page-intro">Lengkapi data master perusahaan di bawah ini. Pembuatan proyek audit dan penugasan auditor dilakukan terpisah pada menu Penugasan Auditor.</p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('companies.store') }}">
            @csrf
            @include('companies._form', ['company' => null])
            <div class="form-actions">
                <a class="btn btn--ghost" href="{{ route('companies.index') }}">Kembali</a>
                <button class="btn btn--primary form-submit" type="submit">Simpan Perusahaan</button>
            </div>
        </form>
    </section>
@endsection
