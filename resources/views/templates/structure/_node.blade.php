@foreach ($nodes as $node)
    @php
        $model = $node['model'];
        $children = $node['children'];
    @endphp
    <li class="structure-node" @style(['padding-left' => ($depth * 22).'px'])>
        <div class="structure-node__row">

            @if ($model instanceof \App\Models\AuditTemplatePrinciple)
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
                @can('update', $template)
                    <div class="structure-node__actions">
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'section', 'principle_id' => $model->id]) }}">+ Kriteria</a>
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.edit', [$template, 'principle', $model->id]) }}">Edit</a>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'principle', $model->id]) }}" title="Naik">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn--ghost btn--sm">↑</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'principle', $model->id]) }}" title="Turun">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn--ghost btn--sm">↓</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.destroy', [$template, 'principle', $model->id]) }}" onsubmit="return confirm('Hapus prinsip beserta seluruh anaknya?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn--ghost btn--sm btn--danger">Hapus</button>
                        </form>
                    </div>
                @endcan

            @elseif ($model instanceof \App\Models\AuditTemplateSection)
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
                @can('update', $template)
                    <div class="structure-node__actions">
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'section', 'principle_id' => $model->principle_id, 'parent_id' => $model->id]) }}">+ Sub-kriteria</a>
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'indicator', 'section_id' => $model->id]) }}">+ Indikator</a>
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.edit', [$template, 'section', $model->id]) }}">Edit</a>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'section', $model->id]) }}" title="Naik">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn--ghost btn--sm">↑</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'section', $model->id]) }}" title="Turun">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn--ghost btn--sm">↓</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.destroy', [$template, 'section', $model->id]) }}" onsubmit="return confirm('Hapus kriteria beserta seluruh anaknya?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn--ghost btn--sm btn--danger">Hapus</button>
                        </form>
                    </div>
                @endcan

            @elseif ($model instanceof \App\Models\AuditTemplateIndicator)
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
                @can('update', $template)
                    <div class="structure-node__actions">
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.create', [$template, 'parameter', 'indicator_id' => $model->id]) }}">+ Parameter</a>
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.edit', [$template, 'indicator', $model->id]) }}">Edit</a>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'indicator', $model->id]) }}" title="Naik">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn--ghost btn--sm">↑</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'indicator', $model->id]) }}" title="Turun">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn--ghost btn--sm">↓</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.destroy', [$template, 'indicator', $model->id]) }}" onsubmit="return confirm('Hapus indikator beserta parameternya?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn--ghost btn--sm btn--danger">Hapus</button>
                        </form>
                    </div>
                @endcan

            @elseif ($model instanceof \App\Models\AuditTemplateParameter)
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
                @can('update', $template)
                    <div class="structure-node__actions">
                        <a class="btn btn--ghost btn--sm" href="{{ route('templates.structure.edit', [$template, 'parameter', $model->id]) }}">Edit</a>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'parameter', $model->id]) }}" title="Naik">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="up">
                            <button class="btn btn--ghost btn--sm">↑</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.move', [$template, 'parameter', $model->id]) }}" title="Turun">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="direction" value="down">
                            <button class="btn btn--ghost btn--sm">↓</button>
                        </form>
                        <form method="POST" action="{{ route('templates.structure.destroy', [$template, 'parameter', $model->id]) }}" onsubmit="return confirm('Hapus parameter?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn--ghost btn--sm btn--danger">Hapus</button>
                        </form>
                    </div>
                @endcan
            @endif
        </div>

        @if ($children)
            <ul class="structure-tree">
                @include('templates.structure._node', ['nodes' => $children, 'template' => $template, 'depth' => $depth + 1])
            </ul>
        @endif
    </li>
@endforeach