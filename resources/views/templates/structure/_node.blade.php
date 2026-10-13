@foreach ($nodes as $node)
    @php
        $model = $node['model'];
        $children = $node['children'];
        $hasChildren = count($children) > 0;

        // Tentukan tipe model
        $isPrinciple = $model instanceof \App\Models\AuditTemplatePrinciple;
        $isSection   = $model instanceof \App\Models\AuditTemplateSection;
        $isIndicator = $model instanceof \App\Models\AuditTemplateIndicator;
        $isParameter = $model instanceof \App\Models\AuditTemplateParameter;

        // Tentukan CSS class berdasarkan level hierarki
        $levelClass = match (true) {
            $isPrinciple => 'structure-node--principle',
            $isSection   => $model->parent_id ? 'structure-node--subcriterion' : 'structure-node--criterion',
            $isIndicator => 'structure-node--indicator',
            default      => 'structure-node--parameter',
        };

        // Tentukan label dan jenis chip
        if ($isPrinciple) {
            $label = 'Prinsip ' . $model->code;
            $chipClass = 'chip--done';
        } elseif ($isSection) {
            $label = ($model->parent_id ? 'Sub-kriteria ' : 'Kriteria ') . $model->code;
            $chipClass = 'chip--wip';
        } elseif ($isIndicator) {
            $label = 'Indikator ' . $model->code;
            $chipClass = 'chip--todo';
        } else {
            $isVerifier = ($model->parameter_type->value ?? $model->parameter_type) === 'verifier';
            $label = $isVerifier ? 'Verifier' : 'Parameter';
            $chipClass = $isVerifier ? 'chip--red' : 'chip--wip';
        }

        // Tentukan teks judul/konten utama
        $titleText = ($isPrinciple || $isSection || $isIndicator) ? $model->title : $model->content;
    @endphp

    <li class="structure-node {{ $levelClass }}" data-structure-depth="{{ $depth }}">
        @if ($hasChildren)
            <details class="structure-node__details">
                <summary class="structure-node__summary" data-structure-summary>
                    <span class="structure-node__chevron" aria-hidden="true">▸</span>
                    <span class="chip {{ $chipClass }}">{{ $label }}</span>

                    @if ($isParameter && $model->number)
                        <strong class="structure-node__number">{{ $model->number }}.</strong>
                    @endif

                    <strong class="structure-node__title">{{ $titleText }}</strong>

                    @if (!empty($model->description))
                        <span class="structure-node__summary-description">{{ $model->description }}</span>
                    @endif
                </summary>

                @include('templates.structure._content', [
                    'model' => $model,
                    'children' => $children,
                    'template' => $template,
                    'depth' => $depth
                ])
            </details>
        @else
            <div class="structure-node__content">
                <div class="structure-node__summary structure-node__summary--leaf">
                    <span class="structure-node__chevron structure-node__chevron--empty" aria-hidden="true"></span>
                    <span class="chip {{ $chipClass }}">{{ $label }}</span>

                    @if ($isParameter && $model->number)
                        <strong class="structure-node__number">{{ $model->number }}.</strong>
                    @endif

                    <strong class="structure-node__title">{{ $titleText }}</strong>

                    @if (!empty($model->description))
                        <span class="structure-node__summary-description">{{ $model->description }}</span>
                    @endif
                </div>

                @include('templates.structure._content', [
                    'model' => $model,
                    'children' => [],
                    'template' => $template,
                    'depth' => $depth
                ])
            </div>
        @endif
    </li>
@endforeach