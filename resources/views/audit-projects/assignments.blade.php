@extends('layouts.app')

@php
    $statusChip = [
        'draft' => 'chip--todo',
        'team_setup' => 'chip--wip',
        'assignment' => 'chip--wip',
        'in_progress' => 'chip--wip',
        'review' => 'chip--wip',
        'final' => 'chip--done',
        'archived' => 'chip--red',
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
                <div class="hero__sub">{{ $project->company?->name }} · Snapshot dari {{ $project->template?->name }}</div>
            </div>
            <div class="hero__actions">
                <span class="chip {{ $statusChip[$project->status->value] ?? 'chip--todo' }}">{{ $project->status->label() }}</span>
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row"><span>Lead Auditor</span><b>{{ $project->leadMember?->user?->name ?? '-' }}</b></div>
            <div class="meta-row"><span>Anggota tim</span><b>{{ $members->count() }} orang</b></div>
            <div class="meta-row"><span>Template</span><b>{{ $project->template?->name }}</b></div>
        </div>
    </section>

    <div class="stat-grid">
        <div class="stat-card stat-card--teal">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </div>
            <p class="stat-card__label">Total indikator</p>
            <span class="stat-card__value">{{ $stats['total'] }}</span>
            <span class="stat-card__hint">dari snapshot project</span>
        </div>
        <div class="stat-card stat-card--green">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <p class="stat-card__label">Sudah ditugaskan</p>
            <span class="stat-card__value">{{ $stats['assigned'] }}</span>
            <span class="stat-card__hint">indikator dengan PIC</span>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <p class="stat-card__label">Belum ditugaskan</p>
            <span class="stat-card__value">{{ $stats['unassigned'] }}</span>
            <span class="stat-card__hint">belum memiliki PIC</span>
        </div>
        <div class="stat-card stat-card--coral">
            <div class="stat-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
            </div>
            <p class="stat-card__label">Beban kerja</p>
            <span class="stat-card__value">{{ $workloads->sum(fn ($m) => (int) $m->assignments_count) }}</span>
            <span class="stat-card__hint">total assignment aktif</span>
        </div>
    </div>

    @if ($canManage && $members->isNotEmpty())
        <section class="panel panel--pad" style="margin-bottom: 22px;">
            <div class="panel__head">
                <div>
                    <h2>Bagi Otomatis</h2>
                    <p>Distribusikan indikator tanpa PIC secara merata ke auditor terpilih. Assignment existing tidak diubah.</p>
                </div>
            </div>
            <form class="form form-grid" method="POST" action="{{ route('audit-projects.assignments.auto', $project) }}">
                @csrf
                <div class="field">
                    <label>Auditor yang ikut pembagian</label>
                    <div style="display: grid; gap: 8px;">
                        @foreach ($members as $member)
                            <label class="cell-check">
                                <input type="checkbox" name="pic_user_ids[]" value="{{ $member->user_id }}">
                                {{ $member->user->name }} ({{ $member->assignments_count ?? 0 }} dimiliki)
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field" style="justify-content: flex-end; display: flex; align-items: end;">
                    <button class="btn btn--primary" type="submit">Bagi Otomatis</button>
                </div>
            </form>
        </section>
    @endif

    <section class="panel panel--pad" style="margin-bottom: 22px;">
        <div class="panel__head">
            <div>
                <h2>Beban kerja per auditor</h2>
                <p>Jumlah indikator yang menjadi tanggung jawab masing-masing anggota</p>
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
                    @foreach ($workloads->sortByDesc('assignments_count') as $member)
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
                            <td><span class="chip chip--wip">{{ $member->assignments_count }} indikator</span></td>
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

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Struktur & PIC indikator</h2>
                <p>Pilih PIC pada setiap indikator, atau tetapkan seluruh unit Prinsip/Kriteria sekaligus.</p>
            </div>
        </div>

        <div class="assignment-filters">
            <div class="field">
                <label for="filter-pic">Filter PIC</label>
                <select id="filter-pic" name="filter-pic">
                    <option value="all">Semua PIC</option>
                    <option value="unassigned">Belum Ditugaskan</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="filter-principle">Filter Prinsip</label>
                <select id="filter-principle" name="filter-principle">
                    <option value="all">Semua Prinsip</option>
                    @foreach ($tree as $node)
                        <option value="{{ $node['model']->id }}">{{ $node['model']->code }} — {{ $node['model']->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field assignment-filters__search">
                <label for="filter-search">Cari indikator</label>
                <input id="filter-search" name="filter-search" type="search" placeholder="Cari kode atau judul indikator…">
            </div>
        </div>

        @if ($tree)
            <ul class="structure-tree" id="assignment-tree">
                @include('audit-projects._assignment_node', [
                    'nodes' => $tree,
                    'depth' => 0,
                    'childPrinciple' => null,
                    'project' => $project,
                    'members' => $members,
                    'canManage' => $canManage,
                    'indicatorCountsBySection' => $indicatorCountsBySection,
                    'indicatorCountsByPrinciple' => $indicatorCountsByPrinciple,
                ])
            </ul>
        @else
            <div class="empty">
                <strong>Belum ada struktur snapshot</strong>
                <span>Buat project dengan template terlebih dahulu.</span>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            function applyFilters() {
                var pic = document.getElementById('filter-pic').value;
                var principle = document.getElementById('filter-principle').value;
                var query = document.getElementById('filter-search').value.trim().toLowerCase();

                document.querySelectorAll('.assignment-indicator').forEach(function (node) {
                    var nodePic = node.dataset.pic || '';
                    var nodePrinciple = node.dataset.principle || '';
                    var code = node.dataset.code || '';
                    var title = node.dataset.title || '';

                    var picOk = pic === 'all'
                        ? true
                        : (pic === 'unassigned' ? nodePic === '' : nodePic === pic);
                    var principleOk = principle === 'all' || nodePrinciple === principle;
                    var searchOk = !query || code.indexOf(query) !== -1 || title.indexOf(query) !== -1;

                    node.classList.toggle('is-hidden', !(picOk && principleOk && searchOk));
                });

                document.querySelectorAll('#assignment-tree .structure-node').forEach(function (node) {
                    if (node.querySelector('.assignment-indicator')) {
                        node.classList.toggle('is-hidden', !node.querySelector('.assignment-indicator:not(.is-hidden)'));
                    }
                });
            }

            document.getElementById('filter-pic').addEventListener('change', applyFilters);
            document.getElementById('filter-principle').addEventListener('change', applyFilters);
            document.getElementById('filter-search').addEventListener('input', applyFilters);
        })();
    </script>
@endpush