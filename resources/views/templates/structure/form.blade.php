@extends('layouts.app')

@php
    $isSubcriterion = $type === 'section' && ($parentSection ?? $entity?->parent);
    
    $label = match ($type) {
        'principle' => 'Prinsip ISPO',
        'section' => $isSubcriterion ? 'Sub-kriteria' : 'Kriteria ISPO',
        'indicator' => 'Indikator',
        default => ucfirst($type),
    };

    $submitLabel = $entity 
        ? 'Simpan Perubahan' 
        : match ($type) {
            'principle' => 'Simpan Prinsip',
            'section'   => $isSubcriterion ? 'Simpan Sub-kriteria' : 'Simpan Kriteria',
            'indicator' => 'Simpan Indikator',
            default     => 'Tambah Data',
        };
@endphp

@section('title', ($entity ? 'Edit' : 'Tambah').' '.$label.' | Verifikasi ISPO')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', ($entity ? 'Edit ' : 'Tambah ').$label)

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.structure.index', $template) }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        {{ $entity ? 'Kembali ke Struktur' : 'Batal' }}
    </a>
@endsection

@section('content')
    <style>
        .page-intro {
            color: #64748b;
            font-size: 0.9375rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1.75rem;
            max-width: 800px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
        }

        /* Styles Tombol */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
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

        /* Dynamic Context Box */
        .context-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #f1f5f9;
            border-radius: 0.25rem;
            color: #0f172a;
            font-weight: 600;
        }
    </style>

    <p class="page-intro">
        @if ($type === 'principle' && ! $entity)
            Tambahkan prinsip baru ke Acuan ISPO.
            <br><small style="color: #64748b;">Setelah prinsip dibuat, Anda dapat menambahkan Kriteria, Indikator, dan Parameter di dalamnya.</small>
        @elseif ($type === 'section' && ! $entity)
            {{ $isSubcriterion ? 'Tambahkan sub-kriteria baru di bawah' : 'Tambahkan kriteria baru ke' }}
            <span class="context-badge">
                {{ $isSubcriterion ? 'Kriteria '.$parentSection->code.' — '.$parentSection->title : 'Prinsip '.$parentPrinciple->code.' — '.$parentPrinciple->title }}
            </span>
        @elseif ($type === 'indicator' && ! $entity)
            Tambahkan indikator baru ke
            <span class="context-badge">
                Kriteria {{ $parentSection->code }} — {{ $parentSection->title }}
            </span>
        @else
            {{ $entity ? 'Ubah data' : 'Tambah' }} {{ strtolower($label) }} pada template
            <strong>{{ $template->name }}</strong> ({{ $template->code }}).
        @endif
    </p>

    <section class="form-card">
        <form class="form" method="POST" action="{{ $entity
            ? route('templates.structure.update', [$template, $type, $entity->id])
            : route('templates.structure.store', [$template, $type]) }}">
            @csrf
            @if ($entity)
                @method('PUT')
            @endif

            {{-- Dynamic Partial Input Fields --}}
            @include('templates.structure._fields', [
                'template'   => $template,
                'type'       => $type,
                'entity'     => $entity,
                'principles' => $principles ?? null,
                'sections'   => $sections ?? null,
                'indicators' => $indicators ?? null,
            ])

            <div class="form-actions">
                <a class="btn btn--ghost" href="{{ route('templates.structure.index', $template) }}">
                    Batal
                </a>
                <button class="btn btn--primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    {{ $submitLabel }}
                </button>
            </div>
        </form>
    </section>
@endsection