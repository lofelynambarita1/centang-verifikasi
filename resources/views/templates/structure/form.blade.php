@extends('layouts.app')

@php
    $label = ucfirst($type);
@endphp

@section('title', ($entity ? 'Edit' : 'Tambah').' '.$label.' | Collaborative Audit Report')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', ($entity ? 'Edit ' : 'Tambah ').$label)

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.structure.index', $template) }}">← Kembali ke struktur</a>
@endsection

@section('content')
    <p class="page-intro">
        {{ $entity ? 'Ubah' : 'Tambah' }} {{ strtolower($label) }} pada template
        <strong>{{ $template->name }}</strong> ({{ $template->code }}).
    </p>

    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ $entity
            ? route('templates.structure.update', [$template, $type, $entity->id])
            : route('templates.structure.store', [$template, $type]) }}">
            @csrf
            @if ($entity)
                @method('PUT')
            @endif

            @include('templates.structure._fields', [
                'template' => $template,
                'type' => $type,
                'entity' => $entity,
                'principles' => $principles ?? null,
                'sections' => $sections ?? null,
                'indicators' => $indicators ?? null,
            ])

            <button class="btn btn--primary form-submit" type="submit">{{ $entity ? 'Simpan perubahan' : 'Tambah' }}</button>
        </form>
    </section>
@endsection