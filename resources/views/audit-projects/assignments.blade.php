@extends('layouts.app')

@php
    $statusChip = [
        'draft' => ['class' => 'chip--project-start', 'label' => 'Belum Dimulai'],
        'team_setup' => ['class' => 'chip--project-start', 'label' => 'Belum Dimulai'],
        'assignment' => ['class' => 'chip--project-start', 'label' => 'Belum Dimulai'],
        'in_progress' => ['class' => 'chip--project-running', 'label' => 'Berjalan'],
        'review' => ['class' => 'chip--project-running', 'label' => 'Berjalan'],
        'final' => ['class' => 'chip--project-completed', 'label' => 'Selesai'],
        'archived' => ['class' => 'chip--project-archived', 'label' => 'Diarsipkan'],
    ];
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
@endphp

@section('title', 'Pembagian Auditor | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Pembagian Auditor')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.my-work', $project) }}">Pekerjaan Saya</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $project->name }}</div>
                <div class="hero__sub">{{ $project->company?->name }} · Atur PIC utama untuk setiap Prinsip. Pembagian per indikator dapat dilakukan jika diperlukan.</div>
            </div>
            <div class="hero__actions">
                @php($projectStatus = $statusChip[$project->status->value] ?? ['class' => 'chip--project-start', 'label' => 'Belum Dimulai'])
                <span class="chip {{ $projectStatus['class'] }}">{{ $projectStatus['label'] }}</span>
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row"><span>Lead Auditor</span><b>{{ $project->leadMember?->user?->name ?? '-' }}</b></div>
            <div class="meta-row"><span>Anggota tim</span><b>{{ $members->count() }} orang</b></div>
            <div class="meta-row"><span>Acuan ISPO</span><b>{{ $project->template?->name }}</b></div>
        </div>
    </section>

    @if ($canManage && $project->status->value === 'assignment')
        <section class="panel panel--pad" style="margin-bottom: 22px;">
            <div class="panel__head">
                <div>
                    <h2>Mulai audit</h2>
                    <p>Pastikan seluruh Prinsip sudah dibagi dan semua indikator memiliki auditor/PIC sebelum audit dimulai.</p>
                </div>
                @if ($stats['unassigned'] > 0)
                    <div class="form-warning" role="alert">
                        <strong>Audit belum dapat dimulai.</strong>
                        <span>{{ $stats['unassigned_principles'] }} Prinsip belum memiliki PIC ({{ $stats['unassigned'] }} indikator belum memiliki auditor/PIC).</span>
                    </div>
                @else
                    <form method="POST" action="{{ route('audit-projects.start', $project) }}">
                        @csrf
                        <button class="btn btn--primary" type="submit">Mulai Audit</button>
                    </form>
                @endif
            </div>
        </section>
    @endif

    <div class="stat-grid">
        <div class="stat-card stat-card--teal">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </div>
            <p class="stat-card__label">Total Prinsip</p>
            <span class="stat-card__value">{{ $stats['total_principles'] }}</span>
            <span class="stat-card__hint">{{ $stats['total'] }} indikator dalam project</span>
        </div>
        <div class="stat-card stat-card--green">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <p class="stat-card__label">Prinsip sudah dibagi</p>
            <span class="stat-card__value">{{ $stats['assigned_principles'] }} / {{ $stats['total_principles'] }}</span>
            <span class="stat-card__hint">{{ $stats['assigned'] }} / {{ $stats['total'] }} indikator memiliki PIC</span>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <p class="stat-card__label">Prinsip belum dibagi</p>
            <span class="stat-card__value">{{ $stats['unassigned_principles'] }}</span>
            <span class="stat-card__hint">{{ $stats['unassigned'] }} indikator belum memiliki PIC</span>
        </div>
        <div class="stat-card stat-card--coral">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
            </div>
            <p class="stat-card__label">Beban kerja</p>
            <span class="stat-card__value">{{ $workloads->sum(fn ($m) => (int) $m->assignments_count) }}</span>
            <span class="stat-card__hint">indikator yang sudah dibagikan</span>
        </div>
    </div>

    @if ($canManage && $project->status->value === 'assignment' && $members->isNotEmpty())
        <section class="panel panel--pad" style="margin-bottom: 22px;">
            <div class="panel__head">
                <div>
                    <h2>Bagi Prinsip Otomatis</h2>
                    <p>Bagikan Prinsip yang belum memiliki PIC secara merata ke seluruh auditor dalam tim project. Seluruh indikator di dalam Prinsip akan mengikuti PIC Prinsip tersebut.</p>
                </div>
            </div>
            <form
                class="form form-grid"
                method="POST"
                action="{{ route('audit-projects.assignments.auto', $project) }}"
                data-auto-assignment-form
                data-unassigned-count="{{ $stats['unassigned_principles'] }}"
            >
                @csrf
                <div class="field">
                    <span class="field-label">Tim yang ikut pembagian</span>
                    <div style="display: grid; gap: 8px;">
                        @foreach ($members as $member)
                            <span>{{ $member->user->name }} · {{ $member->isLead() ? 'Lead Auditor' : 'Auditor' }}</span>
                        @endforeach
                    </div>
                    <p class="field-hint">{{ $stats['unassigned_principles'] }} Prinsip belum dibagi · {{ $members->count() }} auditor dalam tim</p>
                </div>
                <div class="field" style="justify-content: flex-end; display: flex; align-items: end;">
                    <button class="btn btn--primary" type="submit">
                        Bagi Prinsip Otomatis
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if ($canManage && $project->status->value === 'assignment')
    <div class="assignment-confirmation" data-auto-assignment-modal hidden>
        <div class="assignment-confirmation__backdrop" data-auto-assignment-cancel></div>
        <div class="assignment-confirmation__dialog" role="dialog" aria-modal="true" aria-labelledby="auto-assignment-title">
            <h2 id="auto-assignment-title">Bagi Prinsip Otomatis?</h2>
            <p data-auto-assignment-message></p>
            <div class="assignment-confirmation__actions">
                <button class="btn btn--ghost" type="button" data-auto-assignment-cancel>Batal</button>
                <button class="btn btn--primary" type="button" data-auto-assignment-confirm>Ya, Bagi Prinsip</button>
            </div>
            @endif
        </div>
    </div>

    <section class="panel panel--pad" style="margin-bottom: 22px;">
        <div class="panel__head">
            <div>
                <h2>Beban kerja per auditor</h2>
                <p>Jumlah Prinsip utama yang menjadi tanggung jawab masing-masing anggota</p>
            </div>
        </div>
        @if ($members->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Auditor</th>
                            <th>Peran</th>
                            <th>Jumlah indikator</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($principleWorkloads->sortByDesc('principles_count') as $member)
                        <tr>
                            <td>
                                <div class="cell-company">
                                    <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($member->user?->name ?? '?') }}</div>
                                    <div>
                                        <strong>{{ $member->user?->name }}</strong>
                                        <span>{{ $member->user?->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($member->isLead())
                                    <span class="chip chip--done">Lead Auditor</span>
                                @else
                                    <span class="chip chip--todo">Auditor</span>
                                @endif
                            </td>
                            <td><span class="chip chip--wip">{{ $member->principles_count }} Prinsip</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                <strong>Belum ada anggota tim</strong>
                <span>Tambahkan auditor lewat halaman Kelola Tim sebelum membagi indikator.</span>
            </div>
        @endif
    </section>

    <section class="panel panel--pad principle-assignment-panel">
        <div class="panel__head">
            <div>
                <h2>PIC utama per Prinsip</h2>
                <p>Seluruh indikator dalam Prinsip akan mengikuti PIC utama. Buka detail hanya jika perlu membuat pengecualian.</p>
            </div>
        </div>

        <div class="principle-filters" role="group" aria-label="Filter Prinsip">
            <button class="principle-filter is-active" type="button" data-principle-filter="all">Semua</button>
            <button class="principle-filter" type="button" data-principle-filter="unassigned">Belum Dibagi</button>
            <button class="principle-filter" type="button" data-principle-filter="assigned">Sudah Dibagi</button>
        </div>

        <?php if ($tree): ?>
            <div class="principle-assignment-list" id="principle-assignment-list">
                <?php foreach ($principleSummaries as $principleId => $summary): ?>
                    <?php
                        $principle = $principleNodes[$principleId]['model'];
                        $picCounts = collect($summary['pic_counts']);
                    ?>
                    <article class="principle-assignment-card"
                        data-assignment-state="{{ $summary['unassigned'] > 0 ? 'unassigned' : 'assigned' }}">
                        <div class="principle-assignment-card__head">
                            <div class="principle-assignment-card__identity">
                                <span class="chip chip--done">Prinsip {{ $principle->code }}</span>
                                <h3>{{ $principle->title }}</h3>
                                @if ($principle->description)
                                    <p>{{ $principle->description }}</p>
                                @endif
                            </div>
                            <div class="principle-assignment-card__count">
                                <strong>{{ $summary['total'] }} indikator</strong>
                                <span>{{ $summary['assigned'] }} / {{ $summary['total'] }} sudah dibagi</span>
                            </div>
                        </div>

                        <div class="principle-assignment-card__body">
                            <div class="principle-assignment-card__pic">
                                <span class="field-label">PIC Prinsip</span>
                                @if ($canManage && $project->status->value === 'assignment')
                                    <form method="POST"
                                        action="{{ route('audit-projects.assignments.bulk', $project) }}"
                                        data-principle-form
                                        data-has-overrides="{{ $summary['exceptions'] > 0 ? 'true' : 'false' }}"
                                        data-principle-name="Prinsip {{ $principle->code }}">
                                        @csrf
                                        <input type="hidden" name="node_type" value="principle">
                                        <input type="hidden" name="node_id" value="{{ $principle->id }}">
                                        <select class="principle-assignment-select" name="pic_user_id" required aria-label="PIC Prinsip {{ $principle->code }}">
                                            <option value="" disabled @selected($summary['primary_pic_id'] === null)>Pilih Auditor</option>
                                            @foreach ($members as $member)
                                                <option value="{{ $member->user_id }}" @selected($summary['primary_pic_id'] === $member->user_id)>
                                                    {{ $member->user->name }}{{ $member->isLead() ? ' (Lead Auditor)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="sr-only" type="submit">Simpan PIC Prinsip</button>
                                    </form>
                                @else
                                    <strong>{{ $summary['primary_pic_id'] ? $picCounts->first()['name'] : 'Belum ada PIC' }}</strong>
                                @endif
                            </div>

                            <div class="principle-assignment-card__status">
                                @if ($summary['unassigned'] > 0)
                                    <span class="chip chip--todo">{{ $summary['unassigned'] }} indikator belum dibagi</span>
                                @else
                                    <span class="chip chip--done">{{ $summary['total'] }} / {{ $summary['total'] }} indikator sudah dibagi</span>
                                @endif
                                @if ($summary['exceptions'] > 0)
                                    <span class="principle-exceptions">{{ $summary['exceptions'] }} pengecualian</span>
                                    <span class="principle-pic-breakdown">
                                        {{ $picCounts->map(fn (array $pic): string => $pic['total'].' '.$pic['name'])->implode(' · ') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <button class="principle-detail-toggle" type="button" data-principle-open="{{ $principleId }}" aria-haspopup="dialog">
                            <span>{{ $project->status->value === 'assignment' ? 'Lihat / Atur Detail' : 'Lihat Detail' }}</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <strong>Belum ada struktur audit</strong>
                <span>Buat project dengan acuan ISPO terlebih dahulu.</span>
            </div>
        <?php endif; ?>
    </section>

    <div class="assignment-drawer" data-assignment-drawer hidden>
        <button class="assignment-drawer__backdrop" type="button" data-drawer-close aria-label="Tutup detail Prinsip"></button>
        <aside class="assignment-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="assignment-drawer-title">
            <div class="assignment-drawer__header">
                <div id="assignment-drawer-title" data-drawer-title></div>
                <button class="assignment-drawer__close" type="button" data-drawer-close aria-label="Tutup detail Prinsip">×</button>
            </div>
            <div class="assignment-drawer__body">
                <?php foreach ($principleSummaries as $principleId => $summary): ?>
                    <?php
                        $principle = $principleNodes[$principleId]['model'];
                        $picCounts = collect($summary['pic_counts']);
                    ?>
                    <section class="principle-drawer-content" data-drawer-content="{{ $principleId }}" hidden>
                        <div class="principle-drawer-content__summary">
                            <div>
                                <span class="chip chip--done">Prinsip {{ $principle->code }}</span>
                                <h2>{{ $principle->title }}</h2>
                            </div>
                            <div class="principle-drawer-content__stats">
                                <strong>{{ $summary['total'] }} indikator</strong>
                                <span>{{ $summary['assigned'] }} PIC utama{{ $summary['exceptions'] ? ' · '.$summary['exceptions'].' pengecualian' : '' }}</span>
                            </div>
                        </div>
                        <div class="principle-drawer-content__pic">
                            <span class="field-label">PIC Utama</span>
                            <strong>{{ $summary['primary_pic_id'] ? $picCounts->first()['name'] : 'Belum ada PIC' }}</strong>
                        </div>
                        <label class="assignment-detail-search">
                            <span class="sr-only">Cari indikator</span>
                            <input type="search" placeholder="Cari indikator..." data-detail-search="{{ $principleId }}">
                        </label>
                        <div class="detail-filters" role="group" aria-label="Filter detail indikator">
                            <button type="button" class="detail-filter is-active" data-detail-filter="all">Semua</button>
                            <button type="button" class="detail-filter" data-detail-filter="unassigned">Belum Dibagi</button>
                            <button type="button" class="detail-filter" data-detail-filter="exception">Pengecualian</button>
                        </div>
                        <div class="principle-detail-tree">
                            @include('audit-projects._assignment_detail', [
                                'nodes' => $principleNodes[$principleId]['children'],
                                'project' => $project,
                                'members' => $members,
                                'canManage' => $canManage && $project->status->value === 'assignment',
                                'primaryPicId' => $summary['primary_pic_id'],
                            ])
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-principle-filter]').forEach(function (filter) {
                filter.addEventListener('click', function () {
                    document.querySelectorAll('[data-principle-filter]').forEach(function (item) {
                        item.classList.toggle('is-active', item === filter);
                    });
                    document.querySelectorAll('[data-assignment-state]').forEach(function (card) {
                        card.hidden = filter.dataset.principleFilter !== 'all'
                            && card.dataset.assignmentState !== filter.dataset.principleFilter;
                    });
                });
            });

            var autoForm = document.querySelector('[data-auto-assignment-form]');
            var autoModal = document.querySelector('[data-auto-assignment-modal]');

            if (autoForm && autoModal) {
                var autoMessage = autoModal.querySelector('[data-auto-assignment-message]');
                var lastAutoTrigger = null;

                autoForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    var unassignedCount = Number(autoForm.dataset.unassignedCount || 0);

                    if (unassignedCount === 0) {
                        return;
                    }

                    lastAutoTrigger = document.activeElement;
                    autoMessage.textContent = unassignedCount + ' Prinsip yang belum memiliki PIC akan dibagi secara merata ke ' + {{ $members->count() }} + ' auditor dalam tim project. Seluruh indikator di dalam setiap Prinsip akan mengikuti PIC Prinsip tersebut. Pembagian yang sudah ada tidak akan diubah.';
                    autoModal.hidden = false;
                    document.body.classList.add('modal-open');
                    autoModal.querySelector('[data-auto-assignment-confirm]').focus();
                });

                autoModal.querySelector('[data-auto-assignment-confirm]').addEventListener('click', function () {
                    HTMLFormElement.prototype.submit.call(autoForm);
                });
                autoModal.querySelectorAll('[data-auto-assignment-cancel]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        autoModal.hidden = true;
                        document.body.classList.remove('modal-open');
                        if (lastAutoTrigger) {
                            lastAutoTrigger.focus();
                        }
                    });
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !autoModal.hidden) {
                        autoModal.querySelector('[data-auto-assignment-cancel]').click();
                    }
                });
            }

            var drawer = document.querySelector('[data-assignment-drawer]');
            var lastTrigger = null;

            function closeDrawer() {
                drawer.hidden = true;
                document.body.classList.remove('drawer-open');
                document.querySelectorAll('[data-drawer-content]').forEach(function (content) {
                    content.hidden = true;
                });
                if (lastTrigger) {
                    lastTrigger.focus();
                }
            }

            document.querySelectorAll('[data-principle-open]').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    lastTrigger = trigger;
                    var id = trigger.dataset.principleOpen;
                    var content = drawer.querySelector('[data-drawer-content="' + id + '"]');
                    var title = content.querySelector('h2').textContent.trim();
                    drawer.querySelector('[data-drawer-title]').textContent = 'Detail Prinsip';
                    content.hidden = false;
                    drawer.hidden = false;
                    document.body.classList.add('drawer-open');
                    content.querySelector('[data-detail-search]').focus();
                });
            });

            drawer.querySelectorAll('[data-drawer-close]').forEach(function (close) {
                close.addEventListener('click', closeDrawer);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !drawer.hidden) {
                    closeDrawer();
                }
            });

            drawer.querySelectorAll('[data-detail-filter]').forEach(function (filter) {
                filter.addEventListener('click', function () {
                    var group = filter.closest('.principle-drawer-content');
                    group.querySelectorAll('[data-detail-filter]').forEach(function (item) {
                        item.classList.toggle('is-active', item === filter);
                    });
                    group.querySelectorAll('[data-detail-indicator]').forEach(function (indicator) {
                        var state = filter.dataset.detailFilter;
                        indicator.hidden = state !== 'all' && indicator.dataset.detailState !== state;
                    });
                });
            });

            drawer.querySelectorAll('[data-detail-search]').forEach(function (search) {
                search.addEventListener('input', function () {
                    var group = search.closest('.principle-drawer-content');
                    var query = search.value.trim().toLowerCase();
                    group.querySelectorAll('[data-detail-indicator]').forEach(function (indicator) {
                        indicator.hidden = query !== ''
                            && !indicator.dataset.detailText.toLowerCase().includes(query);
                    });
                });
            });

            document.querySelectorAll('[data-principle-form]').forEach(function (form) {
                form.querySelector('select').addEventListener('change', function () {
                    if (form.dataset.hasOverrides === 'true' && !window.confirm(
                        form.dataset.principleName + ' memiliki pembagian indikator yang berbeda. Mengubah PIC utama akan menetapkan ulang seluruh indikator pada Prinsip ini.'
                    )) {
                        form.querySelector('select').value = '';
                        return;
                    }
                    form.submit();
                });
            });
        })();
    </script>
@endpush