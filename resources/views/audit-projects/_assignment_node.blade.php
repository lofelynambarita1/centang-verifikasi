@foreach ($nodes as $node)
    @php
        $model = $node['model'];
        $children = $node['children'];
    @endphp

    @if ($model instanceof \App\Models\AuditProjectPrinciple)
        @php $childPrinciple = $model->id; @endphp
        <li class="structure-node" data-principle-block="{{ $childPrinciple }}">
            <div class="structure-node__row">
                <div class="structure-node__main">
                    <div>
                        <span class="chip chip--done">Prinsip {{ $model->code }}</span>
                        <strong>{{ $model->title }}</strong>
                    </div>
                    @if ($model->description)
                        <p>{{ $model->description }}</p>
                    @endif
                    <span class="structure-node__meta">{{ $indicatorCountsByPrinciple[$model->id] ?? 0 }} indikator</span>
                </div>
                @if ($canManage && ($showBulkControls ?? true))
                    <form class="assignment-bulk" method="POST" action="{{ route('audit-projects.assignments.bulk', $project) }}">
                        @csrf
                        <input type="hidden" name="node_type" value="principle">
                        <input type="hidden" name="node_id" value="{{ $model->id }}">
                        <select class="assignment-bulk__select" name="pic_user_id" required>
                            <option value="" disabled selected>— Pilih PIC —</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn--ghost btn--sm" type="submit">Tetapkan semua ke PIC</button>
                    </form>
                @endif
            </div>
            @if ($children)
                <ul class="structure-tree">
                    @include('audit-projects._assignment_node', [
                        'nodes' => $children,
                        'depth' => $depth + 1,
                        'childPrinciple' => $childPrinciple,
                    ])
                </ul>
            @endif
        </li>

    @elseif ($model instanceof \App\Models\AuditProjectSection)
        <li class="structure-node">
            <div class="structure-node__row">
                <div class="structure-node__main">
                    <div>
                        <span class="chip chip--wip">Kriteria {{ $model->code }}</span>
                        <strong>{{ $model->title }}</strong>
                    </div>
                    @if ($model->description)
                        <p>{{ $model->description }}</p>
                    @endif
                    <span class="structure-node__meta">{{ $indicatorCountsBySection[$model->id] ?? 0 }} indikator</span>
                </div>
                @if ($canManage && ($showBulkControls ?? true))
                    <form class="assignment-bulk" method="POST" action="{{ route('audit-projects.assignments.bulk', $project) }}">
                        @csrf
                        <input type="hidden" name="node_type" value="section">
                        <input type="hidden" name="node_id" value="{{ $model->id }}">
                        <select class="assignment-bulk__select" name="pic_user_id" required>
                            <option value="" disabled selected>— Pilih PIC —</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn--ghost btn--sm" type="submit">Tetapkan semua ke PIC</button>
                    </form>
                @endif
            </div>
            @if ($children)
                <ul class="structure-tree">
                    @include('audit-projects._assignment_node', [
                        'nodes' => $children,
                        'depth' => $depth + 1,
                        'childPrinciple' => $childPrinciple,
                    ])
                </ul>
            @endif
        </li>

    @elseif ($model instanceof \App\Models\AuditProjectIndicator)
        @php
            $assignment = $model->assignment;
            $picId = $assignment?->pic_user_id;
        @endphp
        <li class="structure-node assignment-indicator"
            data-pic="{{ $picId ?? '' }}"
            data-principle="{{ $childPrinciple ?? '' }}"
            data-code="{{ mb_strtolower($model->code) }}"
            data-title="{{ mb_strtolower($model->title) }}">
            <div class="structure-node__row">
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
                    @if ($assignment)
                        <span class="structure-node__meta">Ditugaskan ke {{ $assignment->pic?->name ?? '-' }} · {{ optional($assignment->assigned_at)->diffForHumans() }}</span>
                    @else
                        <span class="structure-node__meta">Belum ada PIC</span>
                    @endif
                </div>
                @if ($canManage)
                    <form class="assignment-pic" method="POST" action="{{ route('audit-projects.assignments.store', [$project, $model]) }}">
                        @csrf
                        <select class="assignment-pic__select" name="pic_user_id" onchange="this.form.submit()">
                            <option value="" disabled @selected(! $picId)>— Pilih auditor —</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->user_id }}" @selected((int) $picId === (int) $member->user_id)>
                                    {{ $member->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @else
                    @if ($assignment)
                        <span class="chip chip--done">PIC: {{ $assignment->pic?->name }}</span>
                    @else
                        <span class="chip chip--todo">Belum ditugaskan</span>
                    @endif
                @endif
            </div>
        </li>
    @endif
@endforeach