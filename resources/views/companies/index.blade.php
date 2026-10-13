@extends('layouts.app')

@php
    $avatarColors = [
        'bg-coral', 
        'bg-navy', 
        'bg-mint', 
        'bg-amber', 
        'bg-violet'
    ];

    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
@endphp

@section('title', 'Daftar Perusahaan | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Daftar Perusahaan')

@section('actions')
    <a class="btn-primary" href="{{ route('companies.create') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Perusahaan</span>
    </a>
@endsection

@section('content')
    <style>
        /* Modern Container & Intro Header */
        .page-header-intro {
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-intro-text {
            margin: 0;
            font-size: 0.9375rem;
            color: #64748b;
            max-width: 600px;
        }

        /* Search Filter Box */
        .table-filter-box {
            position: relative;
            min-width: 260px;
        }

        .table-filter-input {
            width: 100%;
            padding: 0.5rem 0.875rem 0.5rem 2.25rem;
            font-size: 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            background-color: #ffffff;
            transition: all 0.15s ease-in-out;
            box-sizing: border-box;
        }

        .table-filter-input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        .table-filter-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        /* Panel & Table Structure */
        .data-card-panel {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }

        .custom-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .custom-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #1e293b;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .custom-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Avatar System */
        .cell-company {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .company-avatar {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        /* Avatar Color Palettes */
        .bg-coral { background-color: #e11d48; }
        .bg-navy { background-color: #1e3a8a; }
        .bg-mint { background-color: #059669; }
        .bg-amber { background-color: #d97706; }
        .bg-violet { background-color: #7c3aed; }

        .company-info {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .company-info strong {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
        }

        .company-info span {
            font-size: 0.8125rem;
            color: #64748b;
        }

        /* Value & Muted Helper */
        .text-muted {
            color: #94a3b8;
            font-style: italic;
        }

        .contact-email {
            font-weight: 500;
            color: #334155;
        }

        /* Action Buttons */
        .cell-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #475569;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .btn-action:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #059669;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            text-decoration: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: background-color 0.15s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #047857;
        }

        /* Empty State */
        .empty-container {
            padding: 4rem 1.5rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .empty-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: #ecfdf5;
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .empty-desc {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
            max-width: 400px;
        }

        /* Mobile Card Responsive View (< 768px) */
        @media (max-width: 768px) {
            .custom-table, .custom-table thead, .custom-table tbody, .custom-table th, .custom-table td, .custom-table tr {
                display: block;
            }

            .custom-table thead {
                display: none; /* Sembunyikan header tabel tradisional */
            }

            .custom-table tbody tr {
                margin-bottom: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.625rem;
                padding: 1rem;
                background-color: #ffffff;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            }

            .custom-table td {
                padding: 0.5rem 0;
                border-bottom: 1px dashed #f1f5f9;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .custom-table td:last-child {
                border-bottom: none;
                padding-top: 0.75rem;
            }

            .custom-table td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 0.75rem;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                margin-right: 1rem;
            }

            .cell-company {
                width: 100%;
            }

            .cell-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .page-header-intro {
                flex-direction: column;
                align-items: stretch;
            }

            .table-filter-box {
                width: 100%;
            }
        }
    </style>

    <div class="page-header-intro">
        <p class="page-intro-text">Kelola data klien atau auditee yang akan dipilih saat membuat project audit.</p>
        
        <!-- Filter Pencarian Realtime -->
        <div class="table-filter-box">
            <svg class="table-filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="company-search-input" class="table-filter-input" placeholder="Cari nama, bidang, atau PIC..." aria-label="Cari daftar perusahaan">
        </div>
    </div>

    <section class="data-card-panel">
        <div class="table-wrap">
            <table class="custom-table" id="companies-table">
                <thead>
                    <tr>
                        <th scope="col">Nama Perusahaan</th>
                        <th scope="col">Bidang Usaha</th>
                        <th scope="col">Kontak Email</th>
                        <th scope="col">PIC</th>
                        <th scope="col" style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($companies as $company)
                    <tr class="company-row">
                        <td data-label="Perusahaan">
                            <div class="cell-company">
                                <div class="company-avatar {{ $avatarColors[$loop->index % 5] }}">
                                    {{ $initials($company->name) }}
                                </div>
                                <div class="company-info">
                                    <strong>{{ $company->name }}</strong>
                                    <span>{{ $company->address ?: 'Alamat belum diisi' }}</span>
                                </div>
                            </div>
                        </td>
                        <td data-label="Bidang Usaha">
                            @if ($company->industry)
                                {{ $company->industry }}
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </td>
                        <td data-label="Kontak">
                            @if ($company->contact_email)
                                <span class="contact-email">{{ $company->contact_email }}</span>
                            @else
                                <span class="text-muted">Email belum diisi</span>
                            @endif
                        </td>
                        <td data-label="PIC">
                            @if ($company->contact_name)
                                {{ $company->contact_name }}
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </td>
                        <td data-label="Aksi">
                            <div class="cell-actions">
                                <a class="btn-action" href="{{ route('companies.show', $company) }}" title="Lihat detail perusahaan">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <span>Lihat</span>
                                </a>
                                <a class="btn-action" href="{{ route('companies.edit', $company) }}" title="Edit data perusahaan">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-container">
                                <div class="empty-icon-wrap" aria-hidden="true">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 21h18"></path>
                                        <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path>
                                        <path d="M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-title">Belum Ada Perusahaan</h3>
                                <p class="empty-desc">Tambahkan data klien terlebih dahulu untuk membuat project audit baru.</p>
                                <a class="btn-primary" href="{{ route('companies.create') }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>Tambah Perusahaan Pertama</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Client-side Search Filter Logic
            const searchInput = document.getElementById('company-search-input');
            const tableRows = document.querySelectorAll('.company-row');

            if (searchInput && tableRows.length > 0) {
                searchInput.addEventListener('input', function (e) {
                    const searchTerm = e.target.value.toLowerCase().trim();

                    tableRows.forEach(row => {
                        const textContent = row.textContent.toLowerCase();
                        if (textContent.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
@endsection