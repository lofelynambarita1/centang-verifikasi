@extends('layouts.app')

@section('title', 'Acuan ISPO Baru | Verifikasi ISPO')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', 'Acuan ISPO Baru')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.index') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>
@endsection

@section('content')
    <style>
        .form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            max-width: 800px;
        }

        .form-card-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .form-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 0.25rem 0;
        }

        .form-card-subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        .form-actions {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn--primary {
            background-color: #059669;
            color: #ffffff;
        }

        .btn--primary:hover {
            background-color: #047857;
        }

        .btn--ghost {
            background-color: transparent;
            color: #475569;
            border-color: #cbd5e1;
        }

        .btn--ghost:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        @media (max-width: 640px) {
            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>

    <section class="form-card">
        <div class="form-card-header">
            <h2 class="form-card-title">Informasi Acuan Standar</h2>
            <p class="form-card-subtitle">Isi formulir di bawah ini untuk menambahkan acuan acuan ISPO baru ke sistem.</p>
        </div>

        <form class="form" method="POST" action="{{ route('templates.store') }}">
            @csrf

            {{-- Include Form Fields Partial --}}
            @include('templates._form', ['template' => null])

            <div class="form-actions">
                <a class="btn btn--ghost" href="{{ route('templates.index') }}">Batal</a>
                <button class="btn btn--primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Simpan Acuan
                </button>
            </div>
        </form>
    </section>
@endsection