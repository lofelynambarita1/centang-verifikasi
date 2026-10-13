@extends('layouts.app')

@section('title', 'Master ISPO | Verifikasi ISPO')
@section('eyebrow', 'MASTER ISPO')
@section('heading', 'Master ISPO')

@section('actions')
    @can('manage-templates')
        <a class="btn btn--primary" href="{{ route('templates.create') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Acuan ISPO Baru
        </a>
    @endcan
@endsection

@section('content')
    <style>
        .page-intro {
            color: #64748b;
            font-size: 0.9375rem;
            margin-bottom: 1.25rem;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* Filter Toolbar */
        .toolbar {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .toolbar-search {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            max-width: 400px;
        }

        .input-search {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            background-color: #ffffff;
        }

        .input-search:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        /* Table Design */
        .table-wrap {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }

        .table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Cell Content Helpers */
        .code-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 600;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8125rem;
        }

        .cell-meta {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.25rem;
            line-height: 1.4;
        }

        /* Chips / Status Badges */
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
        }

        .chip--wip {
            background-color: #f1f5f9;
            color: #475569;
        }

        .chip--done {
            background-color: #dcfce7;
            color: #15803d;
        }

        .chip--todo {
            background-color: #fef2f2;
            color: #b91c1c;
        }

        .cell-actions {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        /* Empty State */
        .empty {
            padding: 3rem 1.5rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .empty strong {
            font-size: 1rem;
            color: #1e293b;
        }

        .empty span {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
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

        .btn--sm {
            padding: 0.375rem 0.625rem;
            font-size: 0.75rem;
        }
    </style>

    <p class="page-intro">Acuan standar yang digunakan untuk menyusun struktur verifikasi ISPO.</p>

    <section class="panel">
        {{-- Optional Toolbar Search --}}
        @if ($templates->isNotEmpty())
            <div class="toolbar">
                <form method="GET" action="{{ route('templates.index') }}" class="toolbar-search">
                    <input 
                        type="text" 
                        name="search" 
                        class="input-search" 
                        placeholder="Cari acuan ISPO..." 
                        value="{{ request('search') }}"
                    >
                </form>
            </div>
        @endif

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Kode</th>
                        <th style="width: 130px;">Scheme</th>
                        <th>Nama & Deskripsi</th>
                        <th style="width: 90px;">Versi</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 140px; text-align: center;">Project Terpakai</th>
                        <th style="width: 150px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($templates as $template)
                    <tr>
                        <td>
                            <span class="code-badge">{{ $template->code }}</span>
                        </td>
                        <td>
                            <span class="chip chip--wip">{{ $template->scheme ?: '-' }}</span>
                        </td>
                        <td>
                            <strong>{{ $template->name }}</strong>
                            @if ($template->description)
                                <div class="cell-meta" title="{{ $template->description }}">
                                    {{ Str::limit($template->description, 90) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $template->version ?: '-' }}</td>
                        <td>
                            @if ($template->is_active)
                                <span class="chip chip--done">Aktif</span>
                            @else
                                <span class="chip chip--todo">Nonaktif</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <strong>{{ $template->projects_count ?? 0 }}</strong>
                        </td>
                        <td>
                            <div class="cell-actions" style="justify-content: flex-end;">
                                <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.index', $template) }}">
                                    Struktur
                                </a>
                                @can('manage-templates')
                                    <a class="btn btn--ghost btn--sm" href="{{ route('templates.edit', $template) }}">
                                        Edit
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                <strong>Belum ada acuan ISPO</strong>
                                @can('manage-templates')
                                    <span>Tambahkan acuan audit ISPO pertama untuk memulai.</span>
                                    <a class="btn btn--primary" href="{{ route('templates.create') }}" style="margin-top: 0.5rem;">
                                        + Acuan ISPO Baru
                                    </a>
                                @else
                                    <span>Belum ada data acuan ISPO yang dapat ditampilkan.</span>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection