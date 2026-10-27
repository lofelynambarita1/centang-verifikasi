@extends('layouts.app')

@section('title', 'Penanggung jawab prinsip | '.$audit->company->name)
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penanggung jawab prinsip')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audits.assignments.index') }}">Kembali ke daftar audit</a>
@endsection

@section('content')
    <p class="page-intro"><strong>{{ $audit->company->name }}</strong> · {{ $audit->title ?: 'Audit Verifikasi ISPO' }}</p>

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

    <section class="panel panel--pad assignment-panel">
        <div class="panel__head">
            <div>
                <h2>Atur Tim dan Penanggung Jawab Prinsip</h2>
                <p>Tentukan Lead Auditor proyek, pilih tim auditor proyek, lalu bagi tugas penanggung jawab untuk setiap prinsip ISPO. Perubahan berlaku setelah disimpan.</p>
            </div>
            <span class="role-badge role-badge--lead_audit">Admin / Lead audit</span>
        </div>

        <form method="POST" action="{{ route('audits.assignments.update', $audit) }}" class="assignment-form" id="assignmentForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="team_members_present" value="1">

            {{-- 1. Pilihan Lead Auditor Proyek --}}
            <fieldset class="assignment-row" style="margin-bottom: 2rem; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; padding: 1.25rem;">
                <legend style="font-weight: 700; font-size: 1.05rem;">
                    <strong>Lead Auditor Proyek</strong>
                </legend>
                <p style="margin-top: 0.25rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-muted, #64748b);">
                    Anggota yang ditugaskan sebagai Lead Auditor pada proyek ini berhak mengisi checklist verifikasi, melakukan finalisasi/approval audit, dan mencetak laporan hasil ISPO.
                </p>
                <div class="field" style="max-width: 480px;">
                    <label for="lead_auditor_id" style="font-weight: 600; display: block; margin-bottom: 0.35rem;">Pilih Lead Auditor</label>
                    <select id="lead_auditor_id" name="lead_auditor_id" class="input js-lead-auditor-select" style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid var(--border-color, #cbd5e1); font-size: 0.95rem;">
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
            <fieldset class="assignment-row" style="margin-bottom: 2rem; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; padding: 1.25rem;">
                <legend style="font-weight: 700; font-size: 1.05rem;">
                    <strong>Tim Auditor Proyek</strong>
                </legend>
                <p style="margin-top: 0.25rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-muted, #64748b);">
                    Pilih auditor yang ditugaskan ke dalam tim proyek ini. Hanya user yang dipilih ke dalam tim proyek inilah yang terdaftar di AuditProjectMember dan dapat ditugaskan pada Prinsip 1–7.
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
                <h3 style="font-size: 1rem; font-weight: 700;">Penugasan Prinsip ISPO (Prinsip 1 - 7)</h3>
                <p style="font-size: 0.875rem; color: var(--text-muted, #64748b);">
                    Pilih penanggung jawab Prinsip 1 s/d 7 dari anggota tim proyek yang telah dipilih (Lead Auditor + Anggota Auditor terpilih).
                </p>
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

            <button class="btn btn--primary" type="submit">Simpan penugasan</button>
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
