@foreach ($nodes as $node)
    @php
        $model = $node['model'];
        $children = $node['children'];
    @endphp
    <li class="structure-node" @style(['padding-left' => ($depth * 22).'px'])>
        <div class="structure-node__row">

            @if ($model instanceof \App\Models\AuditProjectPrinciple)
                <div class="structure-node__main">
                    <div>
                        <span class="chip chip--done">Prinsip {{ $model->code }}</span>
                        <strong>{{ $model->title }}</strong>
                    </div>
                    @if ($model->description)
                        <p>{{ $model->description }}</p>
                    @endif
                    <span class="structure-node__meta">Urutan {{ $model->sort_order }}</span>
                </div>

            @elseif ($model instanceof \App\Models\AuditProjectSection)
                <div class="structure-node__main">
                    <div>
                        <span class="chip chip--wip">Kriteria {{ $model->code }}</span>
                        <strong>{{ $model->title }}</strong>
                    </div>
                    @if ($model->description)
                        <p>{{ $model->description }}</p>
                    @endif
                    <span class="structure-node__meta">Urutan {{ $model->sort_order }}</span>
                </div>

            @elseif ($model instanceof \App\Models\AuditProjectIndicator)
                <div class="structure-node__main">
                    <div>
                        <span class="chip chip--todo">Indikator {{ $model->code }}</span>
                        <strong>{{ $model->title }}</strong>
                        @if ($model->indicator_type)
                            <span class="chip chip--todo">{{ $model->indicator_type }}</span>
                        @endif
                    </div>
                    @if ($model->description)
                        <p>{{ $model->description }}</p>
                    @endif
                    <span class="structure-node__meta">Urutan {{ $model->sort_order }}</span>
                </div>

            @elseif ($model instanceof \App\Models\AuditProjectParameter)
                <div class="structure-node__main">
                    <div>
                        @if ($model->parameter_type->value === 'verifier')
                            <span class="chip chip--red">Verifier</span>
                        @else
                            <span class="chip chip--wip">Parameter</span>
                        @endif
                        @if ($model->number)
                            <strong>{{ $model->number }}.</strong>
                        @endif
                        <span>{{ $model->content }}</span>
                    </div>
                    <span class="structure-node__meta">Urutan {{ $model->sort_order }}</span>
                </div>
            @endif
        </div>

        @if ($children)
            <ul class="structure-tree">
                @include('audit-projects._structure_node', ['nodes' => $children, 'depth' => $depth + 1])
            </ul>
        @endif
    </li>
@endforeach