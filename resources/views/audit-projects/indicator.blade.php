@extends('layouts.app')

@section('title', $indicator->code.' | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Detail indikator')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.my-work', $project) }}">← Pekerjaan Saya</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">Detail project</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">
                    <span class="chip chip--todo">Indikator {{ $indicator->code }}</span>
                    {{ $indicator->title }}
                </div>
                <div class="hero__sub">{{ $project->name }} · {{ $project->company?->name }}</div>
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row">
                <span>Struktur</span>
                <b>
                    {{ $indicator->section?->principle?->title ?? 'Prinsip' }}
                    → {{ $indicator->section?->title ?? 'Kriteria' }}
                </b>
            </div>
            <div class="meta-row"><span>Kategori indikator</span><b>{{ $indicator->indicator_type ?: 'Belum tersedia' }}</b></div>
            @if ($indicator->description)
                <div class="meta-row"><span>Deskripsi</span><b>{{ $indicator->description }}</b></div>
            @endif
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Parameter & Verifier</h2>
                <p>Persyaratan yang perlu diverifikasi untuk indikator ini (read only).</p>
            </div>
        </div>

        @if ($indicator->parameters->isNotEmpty())
            <ul class="list-strip">
                @foreach ($indicator->parameters as $parameter)
                    <li>
                        <span class="list-strip__dot" aria-hidden="true"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div>
                                @if ($parameter->parameter_type->value === 'verifier')
                                    <span class="chip chip--red">Verifier</span>
                                @else
                                    <span class="chip chip--wip">Parameter</span>
                                @endif
                                @if ($parameter->number)
                                    <strong>{{ $parameter->number }}.</strong>
                                @endif
                                {{ $parameter->content }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty">
                <strong>Tidak ada parameter</strong>
                <span>Indikator ini belum memiliki Parameter/Verifier pada struktur project.</span>
            </div>
        @endif
    </section>
@endsection