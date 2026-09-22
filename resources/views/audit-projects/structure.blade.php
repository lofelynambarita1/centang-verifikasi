@extends('layouts.app')

@section('title', 'Struktur Audit | Collaborative Audit Report')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Struktur audit')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.team', $project) }}">Kelola Tim</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">{{ $project->company?->name }} · Salinan struktur dari {{ $project->template?->name }}</div>
            </div>
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Struktur audit project</h2>
                <p>Snapshot dibuat saat project dibuat. Perubahan master template tidak mengubah struktur ini (read only).</p>
            </div>
        </div>

        @if ($tree)
            <ul class="structure-tree">
                @include('audit-projects._structure_node', ['nodes' => $tree, 'depth' => 0])
            </ul>
        @else
            <div class="empty">
                <strong>Belum ada struktur snapshot</strong>
                <span>Struktur mengikuti template yang dipilih saat project dibuat.</span>
            </div>
        @endif
    </section>
@endsection