@extends('layouts.app')

@section('title', 'Penanggung jawab prinsip | '.$audit->company->name)
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penanggung jawab prinsip')
@section('page-class', 'assignment-edit-page')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audits.assignments.index') }}">Kembali ke daftar audit</a>
@endsection

@section('content')
    <p class="page-intro assignment-context">
        <span class="assignment-context__label">Proyek audit</span>
        <strong>{{ $audit->company->name }}</strong>
        <span class="assignment-context__separator" aria-hidden="true">/</span>
        <span>{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</span>
    </p>

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

        $savedMemberIds = $audit->members->where('project_role', \App\Enums\ProjectRole::Auditor)->pluck('user_id')->all();
        $selectedMemberIds = old('team_members', $savedMemberIds);
        $currentLeadId = (int) old('lead_auditor_id', $audit->leadMember?->user_id);
    @endphp

    <section class="panel assignment-panel" aria-labelledby="assignment-panel-title">
        <div class="panel__head assignment-panel__head">
            <div>
                <span class="assignment-panel__eyebrow">Pengaturan proyek</span>
                <h2 id="assignment-panel-title">Atur tim dan penanggung jawab prinsip</h2>
                <p>Tentukan satu Lead Auditor, pilih anggota tim audit, lalu tetapkan penanggung jawab untuk Prinsip ISPO 1–7. Daftar auditor pada penugasan prinsip akan mengikuti pilihan tim di atas.</p>
            </div>
            <span class="role-badge role-badge--lead_audit">Admin / Lead audit</span>
        </div>

        <form method="POST" action="{{ route('audits.assignments.update', $audit) }}" class="assignment-form" id="assignmentForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="team_members_present" value="1">

            {{-- 1. Pilihan Lead Auditor Proyek --}}
            <fieldset class="assignment-row assignment-row--lead" aria-labelledby="lead-auditor-legend">
                <legend id="lead-auditor-legend">
                    <strong><span>01</span> Lead Auditor Proyek</strong>
                </legend>
                <p class="assignment-row__description">
                    Pilih satu auditor sebagai pemimpin proyek. Lead Auditor dapat mengisi checklist, menyetujui penyelesaian audit, dan mencetak laporan.
                </p>
                <div class="field assignment-lead-field">
                    <label for="lead_auditor_id">Pilih Lead Auditor</label>
                    <select id="lead_auditor_id" name="lead_auditor_id" class="input js-lead-auditor-select">
                        <option value="">-- Belum Ditentukan / Pilih Lead Auditor --</option>
                        @foreach ($availableAuditors as $auditor)
                            <option value="{{ $auditor->id }}" @selected($currentLeadId === $auditor->id) data-name="{{ $auditor->name }}">
                                {{ $auditor->name }} ({{ $auditor->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </fieldset>

            {{-- 2. Seleksi Anggota Tim Auditor Proyek --}}
            <fieldset class="assignment-row assignment-row--team" aria-labelledby="project-team-legend">
                <legend id="project-team-legend">
                    <strong><span>02</span> Tim Auditor Proyek</strong>
                </legend>
                <p class="assignment-row__description">
                    Centang semua auditor yang menjadi anggota proyek. Hanya Lead Auditor dan anggota yang dipilih di sini yang dapat ditugaskan ke Prinsip 1–7.
                </p>
                <div class="assignment-users" id="teamSelectionGrid">
                    @forelse ($availableAuditors as $auditor)
                        @php
                            $isMember = in_array($auditor->id, $selectedMemberIds, true);
                        @endphp
                        <label class="check-control" id="team-label-{{ $auditor->id }}">
                            <input type="checkbox"
                                   name="team_members[]"
                                   value="{{ $auditor->id }}"
                                   @checked($isMember)
                                   class="js-team-checkbox"
                                   data-auditor-id="{{ $auditor->id }}"
                                   data-auditor-name="{{ $auditor->name }}"
                            >
                            <span>{{ $auditor->name }}</span>
                            <small>{{ $auditor->email }}</small>
                        </label>
                    @empty
                        <span class="cell-meta">Belum ada auditor terdaftar di sistem.</span>
                    @endforelse
                </div>
            </fieldset>

            {{-- 3. Penugasan Prinsip ISPO (Prinsip 1 - 7) --}}
            <div class="assignment-form__section-heading">
                <span class="assignment-form__step">03</span>
                <div>
                    <h3>Penugasan Prinsip ISPO</h3>
                    <p>
                        Tetapkan satu penanggung jawab untuk setiap prinsip. Pilihan auditor berasal dari Lead Auditor dan anggota tim yang dipilih di atas.
                    </p>
                </div>
            </div>

            <div class="table-wrap assignment-matrix-wrap">
                <table class="table assignment-matrix">
                    <thead>
                        <tr>
                            <th scope="col">No / Prinsip</th>
                            <th scope="col">Auditor Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($principleTitles as $number => $title)
                            @php
                                $assignedUserId = old(
                                    'assignments.'.$number.'.user_id',
                                    $audit->assignees->firstWhere('pivot.principle_number', $number)?->id,
                                );
                            @endphp
                            <tr>
                                <td>
                                    <strong>Prinsip {{ $number }}</strong>
                                    <span class="assignment-matrix__title">{{ $title }}</span>
                                </td>
                                <td>
                                    <label class="sr-only" for="principle-{{ $number }}-assignee">
                                        Auditor penanggung jawab Prinsip {{ $number }}
                                    </label>
                                    <select
                                        id="principle-{{ $number }}-assignee"
                                        name="assignments[{{ $number }}][user_id]"
                                        class="assignment-matrix__select js-principle-assignee"
                                        data-principle="{{ $number }}"
                                    >
                                        <option value="">-- Belum Ditugaskan --</option>
                                        @foreach ($teamAuditors as $user)
                                            <option value="{{ $user->id }}" @selected((string) $assignedUserId === (string) $user->id)>
                                                {{ $user->name }}{{ $audit->userIsLead($user) ? ' (Lead Auditor)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="assignments[{{ $number }}][principle]" value="{{ $number }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="assignment-form__actions">
                <p>Pastikan lead auditor dan tim proyek sudah benar sebelum menyimpan.</p>
                <button class="btn btn--primary" type="submit">Simpan penugasan</button>
            </div>
        </form>
    </section>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const leadSelect = document.getElementById('lead_auditor_id');
            const teamCheckboxes = document.querySelectorAll('.js-team-checkbox');
            const principleSelects = document.querySelectorAll('.js-principle-assignee');

            const auditorData = new Map();
            teamCheckboxes.forEach(function (cb) {
                const label = cb.closest('label');
                auditorData.set(cb.value, {
                    id: cb.value,
                    name: cb.dataset.auditorName || (label ? label.querySelector('span').textContent.trim() : '')
                });
            });

            if (leadSelect) {
                Array.from(leadSelect.options).forEach(function (opt) {
                    if (opt.value && !auditorData.has(opt.value)) {
                        auditorData.set(opt.value, {
                            id: opt.value,
                            name: opt.dataset.name || opt.textContent.split('(')[0].trim()
                        });
                    }
                });
            }

            function syncTeamToPrinciples() {
                const leadId = leadSelect ? leadSelect.value : '';
                const selectedTeamIds = new Set();
                if (leadId) {
                    selectedTeamIds.add(leadId);
                }
                teamCheckboxes.forEach(function (cb) {
                    if (cb.checked) {
                        selectedTeamIds.add(cb.value);
                    }
                });

                principleSelects.forEach(function (select) {
                    const selectedUserId = select.value;
                    const principleNumber = select.dataset.principle;
                    const options = [new Option('-- Belum Ditugaskan --', '')];

                    selectedTeamIds.forEach(function (userId) {
                        const auditor = auditorData.get(userId);
                        if (auditor) {
                            const role = userId === leadId ? ' (Lead Auditor)' : '';
                            options.push(new Option(auditor.name + role, auditor.id));
                        }
                    });

                    select.replaceChildren(...options);
                    select.value = selectedTeamIds.has(selectedUserId) ? selectedUserId : '';
                    select.name = 'assignments[' + principleNumber + '][user_id]';
                });
            }

            if (leadSelect) {
                leadSelect.addEventListener('change', syncTeamToPrinciples);
            }
            teamCheckboxes.forEach(function (cb) {
                cb.addEventListener('change', syncTeamToPrinciples);
            });

            syncTeamToPrinciples();
        });
    </script>
    @endpush
@endsection
