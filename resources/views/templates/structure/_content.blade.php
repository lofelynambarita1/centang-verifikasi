@php
    $isPrinciple = $model instanceof \App\Models\AuditTemplatePrinciple;
    $isSection   = $model instanceof \App\Models\AuditTemplateSection;
    $isIndicator = $model instanceof \App\Models\AuditTemplateIndicator;
    $isParameter = $model instanceof \App\Models\AuditTemplateParameter;

    // Tentukan jenis entitas untuk parameter rute (string key)
    $typeKey = match (true) {
        $isPrinciple => 'principle',
        $isSection   => 'section',
        $isIndicator => 'indicator',
        default      => 'parameter',
    };

    // Pesan konfirmasi hapus berdasarkan jenis entitas
    $confirmMessage = match (true) {
        $isPrinciple => 'Hapus prinsip beserta seluruh anaknya?',
        $isSection   => 'Hapus kriteria beserta seluruh anaknya?',
        $isIndicator => 'Hapus indikator beserta parameternya?',
        default      => 'Hapus parameter?',
    };
@endphp

<div class="structure-node__body">
    @can('update', $template)
        <div class="structure-node__actions">
            {{-- Tombol Tambah Anak (Add Action) --}}
            @if ($isPrinciple)
                <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'section', 'principle_id' => $model->id]) }}">
                    + Kriteria
                </a>
            @elseif ($isSection)
                <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'section', 'principle_id' => $model->principle_id, 'parent_id' => $model->id]) }}">
                    + Sub-kriteria
                </a>
                <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'indicator', 'section_id' => $model->id]) }}">
                    + Indikator
                </a>
            @elseif ($isIndicator)
                <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'parameter', 'indicator_id' => $model->id]) }}">
                    + Parameter
                </a>
            @endif

            {{-- Tombol Edit --}}
            <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.edit', [$template, $typeKey, $model->id]) }}">
                Edit
            </a>

            {{-- Form Hapus (Delete) --}}
            <form method="POST" action="{{ route('templates.structure.destroy', [$template, $typeKey, $model->id]) }}" onsubmit="return confirm('{{ $confirmMessage }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--ghost btn--sm btn--danger">
                    Hapus
                </button>
            </form>
        </div>
    @endcan

    {{-- Render Anak Secara Rekursif --}}
    @if (!empty($children))
        <ul class="structure-tree">
            @include('templates.structure._node', [
                'nodes' => $children,
                'template' => $template,
                'depth' => $depth + 1
            ])
        </ul>
    @endif
</div>