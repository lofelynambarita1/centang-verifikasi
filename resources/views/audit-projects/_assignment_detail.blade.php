@foreach ($nodes as $node)
    @php
        $model = $node['model'];
        $children = $node['children'];
    @endphp

    @if ($model instanceof \App\Models\AuditProjectSection)
        <details class="detail-criterion">
            <summary>
                <span class="detail-chevron" aria-hidden="true">▸</span>
                <span>
                    <strong>Kriteria {{ $model->code }}</strong>
                    <small>{{ $model->title }}</small>
                </span>
            </summary>
            <div class="detail-criterion__body">
                @include('audit-projects._assignment_detail', [
                    'nodes' => $children,
                    'project' => $project,
                    'members' => $members,
                    'canManage' => $canManage,
                    'primaryPicId' => $primaryPicId,
                ])
            </div>
        </details>
    @elseif ($model instanceof \App\Models\AuditProjectIndicator)
        @php
            $assignment = $model->assignment;
            $picId = $assignment?->pic_user_id;
            $isException = $picId !== null && (int) $picId !== (int) ($primaryPicId ?? 0);
        @endphp
        <div class="detail-indicator"
            data-detail-indicator
            data-detail-state="{{ $assignment ? ($isException ? 'exception' : 'assigned') : 'unassigned' }}"
            data-detail-text="{{ $model->code }} {{ $model->title }}">
            <div class="detail-indicator__identity" title="{{ $model->title }}">
                <strong>{{ $model->code }}</strong>
                <span>{{ $model->title }}</span>
            </div>
            <div class="detail-indicator__assignment">
                @if ($canManage)
                    <form method="POST" action="{{ route('audit-projects.assignments.store', [$project, $model]) }}">
                        @csrf
                        <label class="sr-only" for="detail-pic-{{ $model->id }}">PIC {{ $model->code }}</label>
                        <select id="detail-pic-{{ $model->id }}" class="assignment-pic__select" name="pic_user_id" onchange="this.form.submit()">
                            <option value="" disabled @selected(! $picId)>Pilih auditor</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->user_id }}" @selected((int) $picId === (int) $member->user_id)>{{ $member->user->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span>{{ $assignment?->pic?->name ?? 'Belum ditugaskan' }}</span>
                @endif
                @if ($isException)
                    <span class="chip chip--todo">Pengecualian</span>
                @endif
            </div>
        </div>
    @endif
@endforeach
