@extends('layouts.app')

@section('title', 'Penanggung jawab prinsip | '.$audit->company->name)
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penanggung jawab prinsip')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audits.assignments.index') }}">Kembali ke daftar audit</a>
@endsection

@section('content')
    @php
        $principleTitles = [
            1 => 'Kepatuhan terhadap peraturan',
            2 => 'Praktik perkebunan yang baik',
            3 => 'Lingkungan hidup dan keanekaragaman hayati',
            4 => 'Tanggung jawab ketenagakerjaan',
            5 => 'Tanggung jawab sosial masyarakat',
            6 => 'Transparansi',
            7 => 'Peningkatan usaha berkelanjutan',
        ];
        $roleLabels = [
            'lead_audit' => 'Lead Auditor',
            'auditor' => 'Auditor',
        ];
        $assignedPrincipleCount = $audit->assignees->pluck('pivot.principle_number')->unique()->count();
        $totalAssignments = $audit->assignees->count();
    @endphp

    <section class="assignment-context" aria-label="Ringkasan audit">
        <div class="assignment-context__identity">
            <span class="assignment-context__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="3" width="16" height="18" rx="2"/>
                    <path d="M9 3.5h6M8 9h8M8 13h8M8 17h5"/>
                </svg>
            </span>
            <div>
                <span class="assignment-context__label">Penugasan audit</span>
                <h2>{{ $audit->company->name }}</h2>
                <p>{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</p>
            </div>
        </div>
        <div class="assignment-context__stats">
            <div class="assignment-stat">
                <strong>{{ count($principleTitles) }}</strong>
                <span>Prinsip</span>
            </div>
            <div class="assignment-stat">
                <strong>{{ $users->count() }}</strong>
                <span>Auditor tersedia</span>
            </div>
            <div class="assignment-stat">
                <strong data-assigned-total>{{ $totalAssignments }}</strong>
                <span>Penugasan aktif</span>
            </div>
        </div>
    </section>

    <section class="panel panel--pad assignment-panel">
        <div class="panel__head">
            <div>
                <span class="assignment-section__eyebrow">LANGKAH 1 · BAGI TANGGUNG JAWAB</span>
                <h2>Pilih auditor untuk setiap prinsip</h2>
                <p>Anda dapat menugaskan satu atau beberapa auditor pada setiap prinsip.</p>
            </div>
            <span class="assignment-permission">
                <span aria-hidden="true"></span>
                Master Admin / Lead Auditor
            </span>
        </div>

        <form method="POST" action="{{ route('audits.assignments.update', $audit) }}" class="assignment-form" data-assignment-form>
            @csrf
            @method('PUT')
            <div class="assignment-toolbar">
                <label class="assignment-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-4-4"/>
                    </svg>
                    <span class="sr-only">Cari auditor</span>
                    <input type="search" placeholder="Cari nama auditor..." autocomplete="off" data-assignment-search>
                </label>
                <div class="assignment-filters" role="group" aria-label="Filter berdasarkan peran">
                    <button class="assignment-filter is-active" type="button" data-role-filter="all" aria-pressed="true">Semua</button>
                    <button class="assignment-filter" type="button" data-role-filter="auditor" aria-pressed="false">Auditor</button>
                    <button class="assignment-filter" type="button" data-role-filter="lead_audit" aria-pressed="false">Lead Auditor</button>
                </div>
                <div class="assignment-toolbar__summary">
                    <span><strong data-principle-total>{{ $assignedPrincipleCount }}</strong> / {{ count($principleTitles) }} prinsip terisi</span>
                    <span><strong data-assigned-total>{{ $totalAssignments }}</strong> penugasan dipilih</span>
                </div>
            </div>

            @foreach ($principleTitles as $number => $title)
                @php $assignedUserIds = $audit->assignees->where('pivot.principle_number', $number)->pluck('id')->all(); @endphp
                <fieldset class="assignment-row {{ count($assignedUserIds) > 0 ? 'has-assignees' : '' }}" data-principle="{{ $number }}" data-principle-card aria-label="Penugasan Prinsip {{ $number }}: {{ $title }}">
                    <legend class="sr-only">Prinsip {{ $number }}: {{ $title }}</legend>
                    <div class="assignment-row__header">
                        <span class="assignment-row__number">Prinsip {{ $number }}</span>
                        <h3>{{ $title }}</h3>
                        <span class="assignment-row__count"><b data-principle-count>{{ count($assignedUserIds) }}</b> ditugaskan</span>
                    </div>
                    <div class="assignment-users">
                        @forelse ($users as $user)
                            <label class="check-control" data-assignment-user data-role="{{ $user->role }}">
                                <input type="checkbox" name="assignments[{{ $number }}][users][]" value="{{ $user->id }}" @checked(in_array($user->id, $assignedUserIds, true))>
                                <span class="assignment-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <span>{{ $user->name }}</span>
                                <small class="assignment-role assignment-role--{{ $user->role }}">{{ $roleLabels[$user->role] ?? str_replace('_', ' ', ucfirst($user->role)) }}</small>
                            </label>
                        @empty
                            <span class="assignment-users__empty">Belum ada auditor atau lead auditor aktif.</span>
                        @endforelse
                    </div>
                    <p class="assignment-filter-empty" data-filter-empty hidden>Tidak ada auditor yang cocok dengan pencarian ini.</p>
                    <input type="hidden" name="assignments[{{ $number }}][principle]" value="{{ $number }}">
                </fieldset>
            @endforeach
            <div class="assignment-actions">
                <div>
                    <strong>Siap menyimpan perubahan?</strong>
                    <span><b data-assigned-total>{{ $totalAssignments }}</b> penugasan aktif pada <b data-principle-total>{{ $assignedPrincipleCount }}</b> prinsip.</span>
                </div>
                <button class="btn btn--primary" type="submit">
                    Simpan penugasan
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-assignment-form]');
            if (!form) {
                return;
            }

            const search = form.querySelector('[data-assignment-search]');
            const filterButtons = form.querySelectorAll('[data-role-filter]');
            const cards = form.querySelectorAll('[data-principle-card]');
            let activeRole = 'all';

            const updateAssignments = () => {
                const searchTerm = search.value.trim().toLocaleLowerCase('id');
                let totalAssignments = 0;
                let assignedPrinciples = 0;

                cards.forEach((card) => {
                    const users = card.querySelectorAll('[data-assignment-user]');
                    let visibleUsers = 0;
                    let assignedUsers = 0;

                    users.forEach((user) => {
                        const checkbox = user.querySelector('input[type="checkbox"]');
                        const matchesName = user.textContent.toLocaleLowerCase('id').includes(searchTerm);
                        const matchesRole = activeRole === 'all' || user.dataset.role === activeRole;
                        const isVisible = matchesName && matchesRole;

                        user.hidden = !isVisible;
                        visibleUsers += isVisible ? 1 : 0;
                        assignedUsers += checkbox.checked ? 1 : 0;
                    });

                    card.querySelector('[data-filter-empty]').hidden = users.length === 0 || visibleUsers > 0;
                    card.querySelector('[data-principle-count]').textContent = String(assignedUsers);
                    card.classList.toggle('has-assignees', assignedUsers > 0);
                    totalAssignments += assignedUsers;
                    assignedPrinciples += assignedUsers > 0 ? 1 : 0;
                });

                document.querySelectorAll('[data-assigned-total]').forEach((element) => {
                    element.textContent = String(totalAssignments);
                });
                document.querySelectorAll('[data-principle-total]').forEach((element) => {
                    element.textContent = String(assignedPrinciples);
                });
            };

            search.addEventListener('input', updateAssignments);
            form.addEventListener('change', function (event) {
                if (event.target.matches('input[type="checkbox"]')) {
                    updateAssignments();
                }
            });

            filterButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    activeRole = button.dataset.roleFilter;
                    filterButtons.forEach((filterButton) => {
                        const isActive = filterButton === button;
                        filterButton.classList.toggle('is-active', isActive);
                        filterButton.setAttribute('aria-pressed', String(isActive));
                    });
                    updateAssignments();
                });
            });

            updateAssignments();
        });
    </script>
@endpush