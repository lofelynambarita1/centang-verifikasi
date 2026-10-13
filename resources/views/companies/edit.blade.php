@extends('layouts.app')

@section('title', 'Edit ' . $company->name . ' | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Edit Data Perusahaan')

@section('actions')
    <a class="btn-ghost" href="{{ route('companies.show', $company) }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Kembali</span>
    </a>
@endsection

@section('content')
    <style>
        /* Header Intro */
        .page-header-intro {
            margin-bottom: 1.5rem;
        }

        .page-intro-text {
            margin: 0;
            font-size: 0.9375rem;
            color: #64748b;
            max-width: 640px;
            line-height: 1.5;
        }

        /* Form Card Panel */
        .form-card-panel {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            max-width: 800px;
            overflow: hidden;
        }

        .form-card-body {
            padding: 1.75rem;
        }

        /* Form Footer Actions */
        .form-actions-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Button Styles */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background-color: #059669;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid transparent;
            border-radius: 0.5rem;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.15s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #047857;
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.25);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
            background-color: transparent;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .btn-ghost:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: color 0.15s ease-in-out;
        }

        .btn-cancel:hover {
            color: #0f172a;
        }

        /* Responsive Adjustment */
        @media (max-width: 640px) {
            .form-card-body {
                padding: 1.25rem;
            }

            .form-actions-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .btn-primary, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="page-header-intro">
        <p class="page-intro-text">Perbarui data master perusahaan tanpa mengubah histori project audit yang terkait.</p>
    </div>

    <section class="form-card-panel">
        <div class="form-card-body">
            <form method="POST" action="{{ route('companies.update', $company) }}">
                @csrf
                @method('PUT')
                
                {{-- Form fields partial --}}
                @include('companies._form', ['company' => $company])

                <div class="form-actions-footer">
                    <a href="{{ route('companies.show', $company) }}" class="btn-cancel">Batal</a>
                    <button class="btn-primary" type="submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection