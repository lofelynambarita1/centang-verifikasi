@extends('layouts.app')

@section('title', 'Template Baru | Collaborative Audit Report')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', 'Template baru')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.index') }}">← Kembali</a>
@endsection

@section('content')
    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('templates.store') }}">
            @csrf
            @include('templates._form', ['template' => null])
            <button class="btn btn--primary form-submit" type="submit">Simpan template</button>
        </form>
    </section>
@endsection