@extends('layouts.app')

@section('title', 'Buat Project Audit | Collaborative Audit Report')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Buat project audit')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.index') }}">← Kembali</a>
@endsection

@section('content')
    <p class="page-intro">Pilih template, klien, jenis &amp; periode audit, Lead Auditor, dan anggota tim. Lead Auditor otomatis menjadi anggota project.</p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('audit-projects.store') }}" id="create-project-form">
            @csrf
            @include('audit-projects._form', [
                'project' => null,
                'withTeam' => true,
                'templates' => $templates,
                'companies' => $companies,
                'auditors' => $auditors,
            ])
            <button class="btn btn--primary form-submit" type="submit">Simpan project dan tim</button>
        </form>
    </section>
@endsection