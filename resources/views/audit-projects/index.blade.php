@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
    $statusChip = [
        'draft' => 'chip--todo',
        'team_setup' => 'chip--wip',
        'assignment' => 'chip--wip',
        'in_progress' => 'chip--wip',
        'review' => 'chip--wip',
        'final' => 'chip--done',
        'archived' => 'chip--red',
    ];
@endphp

@section('title', 'Daftar Project Audit | Collaborative Audit Report')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Daftar project audit')

@section('actions')
    @can('manage-audit-projects')
        <a class="btn btn--primary" href="{{ route('audit-projects.create') }}">+ Buat project</a>
    @endcan
@endsection

@section('content')
    <p class="page-intro">Kelola project audit kolaboratif. Auditor hanya melihat project tempat mereka terdaftar.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Klien</th>
                        <th>Template</th>
                        <th>Lead Auditor</th>
                        <th>Periode audit</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($projects as $project)
                    <tr>
                        <td>
                            <div class="cell-company">
                                <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($project->name) }}</div>
                                <div>
                                    <strong>{{ $project->name }}</strong>
                                    <span>Dibuat oleh {{ $project->creator?->name ?? $project->company?->name }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $project->company?->name }}</strong>
                            <div class="cell-meta">{{ $project->company?->industry ?: 'Bidang usaha tidak diisi' }}</div>
                        </td>
                        <td>
                            <strong>{{ $project->template?->name }}</strong>
                            <div class="cell-meta">{{ $project->template?->code }}</div>
                        </td>
                        <td>
                            @if ($project->leadMember?->user)
                                <strong>{{ $project->leadMember->user->name }}</strong>
                            @else
                                <span class="chip chip--red">Tanpa lead</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $project->audit_start_date->format('d M Y') }} – {{ $project->audit_end_date->format('d M Y') }}</strong>
                            <div class="cell-meta">{{ $project->audit_type }}</div>
                        </td>
                        <td><span class="chip {{ $statusChip[$project->status->value] ?? 'chip--todo' }}">{{ $project->status->label() }}</span></td>
                        <td>
                            <div class="cell-actions">
                                <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.show', $project) }}">Buka</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6"/>
                                </svg>
                                <strong>Belum ada project audit</strong>
                                @can('manage-audit-projects')
                                    <span>Buat project audit pertama dengan memilih template dan tim auditor.</span>
                                    <a class="btn btn--primary" href="{{ route('audit-projects.create') }}">+ Buat project</a>
                                @else
                                    <span>Anda belum terdaftar pada project manapun.</span>
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