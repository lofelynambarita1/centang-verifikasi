@extends('layouts.app')

@section('title', 'Struktur Template | Collaborative Audit Report')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', 'Struktur template')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.index') }}">← Daftar template</a>
    @can('update', $template)
        <a class="btn btn--ghost" href="{{ route('templates.edit', $template) }}">Edit template</a>
        <a class="btn btn--primary" href="{{ route('templates.structure.create', [$template, 'principle']) }}">+ Tambah prinsip</a>
    @endcan
@endsection

@section('content')
    <p class="page-intro">
        Struktur master <strong>{{ $template->name }}</strong> ({{ $template->code }} · {{ $template->scheme }} · v{{ $template->version ?? '-' }}).
        Hierarki: Prinsip → Kriteria/Sub-kriteria → Indikator → Parameter/Verifier.
    </p>

    <div class="tabbar" role="tablist" aria-label="Navigasi template">
        <a class="tabbar__link" href="{{ route('templates.edit', $template) }}">Detail template</a>
        <a class="tabbar__link tabbar__link--active" aria-current="page" href="{{ route('templates.structure.index', $template) }}">Struktur template</a>
    </div>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Struktur template</h2>
                <p>Menyusun Prinsip, Kriteria, Indikator, serta Parameter/Verifier sistem penilaian.</p>
            </div>
        </div>

        @if ($tree)
            <ul class="structure-tree">
                @include('templates.structure._node', ['nodes' => $tree, 'template' => $template, 'depth' => 0])
            </ul>
        @else
            <div class="empty">
                <strong>Struktur masih kosong</strong>
                @can('update', $template)
                    <span>Mulai dengan menambahkan prinsip pertama.</span>
                    <a class="btn btn--primary" href="{{ route('templates.structure.create', [$template, 'principle']) }}">+ Tambah prinsip</a>
                @else
                    <span>Belum ada data struktur pada template ini.</span>
                @endcan
            </div>
        @endif
    </section>
@endsection