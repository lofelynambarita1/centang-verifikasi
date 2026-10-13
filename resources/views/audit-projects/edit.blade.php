@extends('layouts.app')

@section('title', 'Edit Project Audit | Verifikasi ISPO')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Edit Project Audit')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">
        ← Kembali
    </a>
@endsection

@section('content')
    <p class="page-intro">
        Ubah data project. Pergantian tim (anggota &amp; Lead Auditor) dilakukan di halaman Kelola Tim.
    </p>

    {{-- Form Container --}}
    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('audit-projects.update', $project) }}">
            @csrf
            @method('PUT')

            @include('audit-projects._form', [
                'project'   => $project,
                'withTeam'  => false,
                'templates' => $templates,
                'companies' => $companies,
                'auditors'  => $auditors,
            ])

            <div class="form-actions" style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button class="btn btn--primary form-submit" type="submit">
                    Simpan perubahan
                </button>
            </div>
        </form>
    </section>
@endsection