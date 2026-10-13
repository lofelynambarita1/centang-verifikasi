@extends('layouts.app')

@section('title', 'Buat Project Audit | Verifikasi ISPO')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Buat Project Audit')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.index') }}">
        ← Kembali
    </a>
@endsection

@section('content')
    <p class="page-intro">
        Pilih acuan ISPO, perusahaan, jenis dan periode audit, Lead Auditor, serta anggota tim.
    </p>

    {{-- Alert Peringatan Duplikasi Project --}}
    @if (session('duplicate_warning'))
        <div 
            class="alert alert--warning" 
            role="alert" 
            style="margin-bottom: 1.5rem; padding: 1rem; background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 0.375rem; color: #92400e;"
        >
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: #d97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <strong style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        Periksa kemungkinan project serupa
                    </strong>
                    <span style="font-size: 0.875rem;">
                        {{ session('duplicate_warning') }}
                    </span>
                    <p style="font-size: 0.75rem; margin-top: 0.5rem; color: #b45309;">
                        * Jika Anda tetap ingin melanjutkan pembuatan project ini, silakan tekan tombol "Simpan project dan tim" sekali lagi.
                    </p>
                </div>
            </div>
            
            {{-- Input hidden dikirimkan bersama form untuk mengonfirmasi bypass duplikasi --}}
            <input type="hidden" name="confirm_duplicate" value="1" form="create-project-form">
        </div>
    @endif

    {{-- Form Container --}}
    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('audit-projects.store') }}" id="create-project-form">
            @csrf

            @include('audit-projects._form', [
                'project'   => null,
                'withTeam'  => true,
                'templates' => $templates,
                'companies' => $companies,
                'auditors'  => $auditors,
            ])

            <div class="form-actions" style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button class="btn btn--primary form-submit" type="submit">
                    Simpan project dan tim
                </button>
            </div>
        </form>
    </section>
@endsection