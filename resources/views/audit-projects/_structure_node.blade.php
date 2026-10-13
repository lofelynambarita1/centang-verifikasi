@foreach ($nodes as $node)
    @php
        $model = $node['model'];
        $children = $node['children'];
        $isPrinciple = $model instanceof \App\Models\AuditProjectPrinciple;
        $isSection = $model instanceof \App\Models\AuditProjectSection;
        $isIndicator = $model instanceof \App\Models\AuditProjectIndicator;
        $isParameter = $model instanceof \App\Models\AuditProjectParameter;

        $currentPath = $path ?? [];
        if ($isPrinciple) $currentPath[] = "Prinsip {$model->code}";
        elseif ($isSection) $currentPath[] = "Kriteria {$model->code}";
        elseif ($isIndicator) $currentPath[] = "Indikator {$model->code}";
        $pathString = implode(' → ', $currentPath);
    @endphp

    @if ($isPrinciple || $isSection || $isIndicator)
        <details class="structure-accordion" style="margin-bottom: 8px; border: 1px solid var(--border{{ $isPrinciple ? '-strong' : '' }}); border-radius: 8px; background: #fff;">
            <summary style="padding: 12px 16px; cursor: pointer; display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    @if ($isPrinciple)
                        <span class="chip chip--done" style="white-space: nowrap;">Prinsip {{ $model->code }}</span>
                        <div>
                            <strong style="display: block; font-size: 15px;">{{ $model->title }}</strong>
                            <div style="font-size: 13px; color: var(--muted); margin-top: 4px;">
                                {{ $model->sections->count() }} Kriteria &bull; {{ $model->sections->sum(fn($s) => $s->indicators->count()) }} Indikator
                            </div>
                        </div>
                    @elseif ($isSection)
                        <span class="chip chip--wip" style="white-space: nowrap;">Kriteria {{ $model->code }}</span>
                        <div>
                            <strong style="display: block; font-size: 14px;">{{ $model->title }}</strong>
                            @if ($model->description)
                                <div style="font-size: 13px; color: var(--muted); margin-top: 2px;">{{ $model->description }}</div>
                            @endif
                        </div>
                    @elseif ($isIndicator)
                        <span class="chip chip--todo" style="white-space: nowrap;">Indikator {{ $model->code }}</span>
                        <div class="searchable-indicator" data-path="{{ $pathString }}" data-code="{{ $model->code }}" data-title="{{ $model->title }}">
                            <strong style="display: block; font-size: 14px;">{{ $model->title }}</strong>
                            @if ($model->description)
                                <div style="font-size: 13px; color: var(--muted); margin-top: 2px;">{{ $model->description }}</div>
                            @endif
                        </div>
                    @endif
                </div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted); flex-shrink: 0; transition: transform 0.2s;" class="acc-icon"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </summary>

            @if ($children)
                <div style="padding: 0 16px 16px; border-top: 1px solid var(--border); margin-top: 8px; padding-top: 16px;">
                    @include('audit-projects._structure_node', ['nodes' => $children, 'path' => $currentPath])
                </div>
            @endif
        </details>
    @elseif ($isParameter)
        <div class="searchable-parameter" data-path="{{ $pathString }} → Parameter {{ $model->number ? $model->number . '.' : '' }}" data-content="{{ $model->content }}" style="padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; margin-bottom: 6px; background: #fafafa; display: flex; gap: 10px; align-items: flex-start;">
            @if ($model->parameter_type->value === 'verifier')
                <span class="chip chip--red" style="font-size: 11px; padding: 2px 6px;">Verifier</span>
            @else
                <span class="chip chip--wip" style="font-size: 11px; padding: 2px 6px;">Parameter</span>
            @endif
            <div style="font-size: 13.5px; line-height: 1.5; color: var(--text);">
                @if ($model->number)
                    <strong>{{ $model->number }}.</strong>
                @endif
                {{ $model->content }}
            </div>
        </div>
    @endif
@endforeach