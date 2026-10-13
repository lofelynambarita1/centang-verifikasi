@extends('layouts.app')

@section('title', 'Struktur Acuan ISPO | Verifikasi ISPO')
@section('eyebrow', 'MASTER TEMPLATE')
@section('heading', 'Struktur Acuan ISPO')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('templates.index') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Daftar Template
    </a>
    @can('update', $template)
        <a class="btn btn--ghost" href="{{ route('templates.edit', $template) }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Edit Template
        </a>
        <a class="btn btn--primary" href="{{ route('templates.structure.create', [$template, 'principle']) }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Tambah Prinsip
        </a>
    @endcan
@endsection

@section('content')
    @php
        $counts = ['principles' => 0, 'sections' => 0, 'indicators' => 0, 'parameters' => 0];
        $countNodes = function (array $nodes) use (&$countNodes, &$counts): void {
            foreach ($nodes as $node) {
                $model = $node['model'];

                if ($model instanceof \App\Models\AuditTemplatePrinciple) {
                    $counts['principles']++;
                } elseif ($model instanceof \App\Models\AuditTemplateSection) {
                    $counts['sections']++;
                } elseif ($model instanceof \App\Models\AuditTemplateIndicator) {
                    $counts['indicators']++;
                } elseif ($model instanceof \App\Models\AuditTemplateParameter) {
                    $counts['parameters']++;
                }

                $countNodes($node['children']);
            }
        };
        $countNodes($tree);
    @endphp

    <style>
        .page-intro {
            color: #64748b;
            font-size: 0.9375rem;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        /* Tabbar Navigation */
        .tabbar {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .tabbar__link {
            padding: 0.75rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease-in-out;
            margin-bottom: -1px;
        }

        .tabbar__link:hover {
            color: #0f172a;
        }

        .tabbar__link--active {
            color: #059669;
            border-bottom-color: #059669;
            font-weight: 600;
        }

        /* Main Panel & Structure Layout */
        .panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .panel__head {
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .panel__head h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 0.25rem 0;
        }

        .panel__head p {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        /* Structure Counter Badges */
        .structure-summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 0.875rem 1rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }

        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            color: #334155;
        }

        .stat-chip strong {
            color: #059669;
            font-size: 0.9375rem;
        }

        /* Structure Tree Wrapper */
        .structure-tree-container {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background-color: #ffffff;
            padding: 1rem;
            overflow-x: auto;
        }

        .structure-tree {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* Buttons */
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
    </style>

    <p class="page-intro">
        Struktur Acuan ISPO <strong>{{ $template->name }}</strong> ({{ $template->code }} · {{ $template->scheme }} · v{{ $template->version ?? '-' }}).
        <br>
        <small style="color: #94a3b8;">Hierarki Standar: Prinsip &rarr; Kriteria/Sub-kriteria &rarr; Indikator &rarr; Parameter/Verifier</small>
    </p>

    {{-- Navigasi Tab Template --}}
    <div class="tabbar" role="tablist" aria-label="Navigasi template">
        <a class="tabbar__link" href="{{ route('templates.edit', $template) }}">Detail Template</a>
        <a class="tabbar__link tabbar__link--active" aria-current="page" href="{{ route('templates.structure.index', $template) }}">Struktur Acuan ISPO</a>
    </div>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2>Struktur Acuan ISPO</h2>
                <p>Kelola Prinsip, Kriteria, Indikator, dan Parameter yang digunakan sebagai acuan audit.</p>
            </div>
        </div>

        @if ($tree)
            {{-- Metric Summary Badges --}}
            <div class="structure-summary-grid">
                <div class="stat-chip">
                    <strong>{{ $counts['principles'] }}</strong> Prinsip
                </div>
                <div class="stat-chip">
                    <strong>{{ $counts['sections'] }}</strong> Kriteria
                </div>
                <div class="stat-chip">
                    <strong>{{ $counts['indicators'] }}</strong> Indikator
                </div>
                <div class="stat-chip">
                    <strong>{{ $counts['parameters'] }}</strong> Parameter
                </div>
            </div>

            {{-- Tree Node List --}}
            <div class="structure-tree-container">
                <ul class="structure-tree">
                    @include('templates.structure._node', ['nodes' => $tree, 'template' => $template, 'depth' => 0])
                </ul>
            </div>
        @else
            {{-- Empty State --}}
            <div class="empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <strong>Struktur Masih Kosong</strong>
                @can('update', $template)
                    <span>Mulai dengan menambahkan prinsip pertama pada acuan ISPO ini.</span>
                    <a class="btn btn--primary" href="{{ route('templates.structure.create', [$template, 'principle']) }}" style="margin-top: 0.5rem;">
                        + Tambah Prinsip
                    </a>
                @else
                    <span>Belum ada data struktur pada template acuan ini.</span>
                @endcan
            </div>
        @endif
    </section>
@endsection