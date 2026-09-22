@extends('layouts.app')

@section('title', 'Edit Template | Collaborative Audit Report')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', 'Edit template')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.index') }}">← Kembali</a>
@endsection

@section('content')
    <section class="panel panel--pad form-card">
        <form class="form" method="POST" action="{{ route('templates.update', $template) }}">
            @csrf
            @method('PUT')
            @include('templates._form', ['template' => $template])
            <button class="btn btn--primary form-submit" type="submit">Simpan perubahan</button>
        </form>
    </section>
@endsection