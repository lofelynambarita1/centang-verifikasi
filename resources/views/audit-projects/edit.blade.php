@extends('layouts.app')

@section('title', 'Edit Project Audit | Collaborative Audit Report')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Edit project audit')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Kembali</a>
@endsection

@section('content')
    <p class="page-intro">Ubah data project. Pergantian tim (anggota &amp; Lead Auditor) dilakukan di halaman Kelola Tim.</p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('audit-projects.update', $project) }}">
            @csrf
            @method('PUT')
            @include('audit-projects._form', [
                'project' => $project,
                'templates' => $templates,
                'companies' => $companies,
                'auditors' => $auditors,
            ])
            <button class="btn btn--primary form-submit" type="submit">Simpan perubahan</button>
        </form>
    </section>
@endsection