@extends('layouts.app')

@section('title', $company->name . ' | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Detail Perusahaan')

@section('actions')
    <a class="btn-ghost" href="{{ route('companies.index') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Kembali</span>
    </a>
    <a class="btn-primary" href="{{ route('companies.edit', $company) }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        <span>Edit Data</span>
    </a>
@endsection

@section('content')
    <style>
        /* Profile Hero Card */
        .company-hero-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .company-hero-header {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 1.5rem;
        }

        .company-hero-avatar {
            width: 56px;
            height: 56px;
            border-radius: 0.625rem;
            background-color: #059669;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
        }

        .company-hero-title-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .company-hero-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1.3;
        }

        .company-badge-industry {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            background-color: #ecfdf5;
            color: #047857;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 0.375rem;
            width: fit-content;
        }

        /* Metadata Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem 2rem;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .meta-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .meta-value {
            font-size: 0.9375rem;
            color: #1e293b;
            font-weight: 500;
            word-break: break-word;
        }

        .text-muted {
            color: #94a3b8;
            font-style: italic;
        }

        /* Panel Structure */
        .data-card-panel {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .panel-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background-color: #ffffff;
        }

        .panel-title {
            font-size: 1.0625rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.25rem 0;
        }

        .panel-desc {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        /* Table Styling */
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

        /* Action Link */
        .link-action {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: #059669;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }

        .link-action:hover {
            color: #047857;
            text-decoration: underline;
        }

        /* Buttons Styling */
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

        /* Empty State */
        .empty-container {
            padding: 3.5rem 1.5rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .empty-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .empty-desc {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
            max-width: 380px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .meta-grid {
                grid-template-columns: 1fr;
            }

            .company-hero-card {
                padding: 1.25rem;
            }

            .custom-table, .custom-table thead, .custom-table tbody, .custom-table th, .custom-table td, .custom-table tr {
                display: block;
            }

            .custom-table thead {
                display: none;
            }

            .custom-table tbody tr {
                margin-bottom: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.625rem;
                padding: 1rem;
                background-color: #ffffff;
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
        }
    </style>

    <!-- Profile Hero Card -->
    <section class="company-hero-card">
        <div class="company-hero-header">
            <div class="company-hero-avatar">
                {{ strtoupper(substr($company->name, 0, 1)) }}
            </div>
            <div class="company-hero-title-wrap">
                <h1 class="company-hero-title">{{ $company->name }}</h1>
                @if ($company->industry)
                    <span class="company-badge-industry">{{ $company->industry }}</span>
                @else
                    <span class="text-muted">Bidang usaha belum diisi</span>
                @endif
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">Alamat Perusahaan</span>
                <span class="meta-value">
                    {{ $company->address ?: 'Belum diisi' }}
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Bidang Usaha</span>
                <span class="meta-value">
                    {{ $company->industry ?: 'Belum diisi' }}
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Nama PIC</span>
                <span class="meta-value">
                    {{ $company->contact_name ?: 'Belum diisi' }}
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Email PIC</span>
                <span class="meta-value">
                    @if ($company->contact_email)
                        <a href="mailto:{{ $company->contact_email }}" style="color: inherit; text-decoration: none;">{{ $company->contact_email }}</a>
                    @else
                        <span class="text-muted">Belum diisi</span>
                    @endif
                </span>
            </div>
        </div>
    </section>

    <!-- Panel Project Audit Terkait -->
    <section class="data-card-panel">
        <div class="panel-header">
            <h2 class="panel-title">Project Audit Terkait</h2>
            <p class="panel-desc">Daftar referensi project audit yang terhubung dengan perusahaan ini sebagai auditee.</p>
        </div>

        <div class="table-wrap">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th scope="col">Nama Project</th>
                        <th scope="col">Jenis Audit</th>
                        <th scope="col">Periode Audit</th>
                        <th scope="col" style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($company->auditProjects as $project)
                    <tr>
                        <td data-label="Nama Project">
                            <strong>{{ $project->name }}</strong>
                        </td>
                        <td data-label="Jenis Audit">
                            @if ($project->audit_type)
                                {{ $project->audit_type }}
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </td>
                        <td data-label="Periode">
                            {{ $project->audit_start_date->format('d M Y') }} – {{ $project->audit_end_date->format('d M Y') }}
                        </td>
                        <td data-label="Aksi" style="text-align: right;">
                            <a class="link-action" href="{{ route('audit-projects.show', $project) }}">
                                <span>Buka Project</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-container">
                                <div class="empty-icon-wrap" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <h3 class="empty-title">Belum Ada Project Audit</h3>
                                <p class="empty-desc">Project audit yang menggunakan perusahaan ini akan otomatis tampil sebagai referensi di sini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection