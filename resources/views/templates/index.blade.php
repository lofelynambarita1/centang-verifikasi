@extends('layouts.app')

@section('title', 'Master Template | Collaborative Audit Report')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', 'Master template')

@section('actions')
    @can('manage-templates')
        <a class="btn btn--primary" href="{{ route('templates.create') }}">+ Template baru</a>
    @endcan
@endsection

@section('content')
    <p class="page-intro">Template standar yang dipakai sebagai acuan project audit, misalnya ISPO FrO.8603/GIS-1.1.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Scheme</th>
                        <th>Nama</th>
                        <th>Versi</th>
                        <th>Status</th>
                        <th>Project terpakai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($templates as $template)
                    <tr>
                        <td><strong>{{ $template->code }}</strong></td>
                        <td><span class="chip chip--wip">{{ $template->scheme ?: '-' }}</span></td>
                        <td>
                            <strong>{{ $template->name }}</strong>
                            <div class="cell-meta">{{ Str::limit($template->description, 90) }}</div>
                        </td>
                        <td>{{ $template->version ?: '-' }}</td>
                        <td>
                            @if ($template->is_active)
                                <span class="chip chip--done">Aktif</span>
                            @else
                                <span class="chip chip--todo">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $template->projects_count ?? 0 }}</td>
                        <td>
                            @can('manage-templates')
                                <div class="cell-actions">
                                    <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.index', $template) }}">Struktur</a>
                                    <a class="btn btn--ghost btn--sm" href="{{ route('templates.edit', $template) }}">Edit</a>
                                </div>
                            @else
                                <div class="cell-actions">
                                    <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.index', $template) }}">Struktur</a>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty">
                                <strong>Belum ada template</strong>
                                @can('manage-templates')
                                    <span>Tambahkan template audit pertama.</span>
                                    <a class="btn btn--primary" href="{{ route('templates.create') }}">+ Template baru</a>
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