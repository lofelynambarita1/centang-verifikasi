@extends('layouts.app')

@section('title', $indicator->code.' | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Workspace Auditor')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.my-work', $project) }}">← Pekerjaan Saya</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">Detail project</a>
@endsection

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">
                    <span class="chip chip--todo">Indikator {{ $indicator->code }}</span>
                    {{ $indicator->title }}
                </div>
                <div class="hero__sub">{{ $project->name }} · {{ $project->company?->name }}</div>
            </div>
            <div class="hero__actions">
                @if ($canEdit)
                    <span class="chip chip--wip">Mode edit</span>
                @else
                    <span class="chip chip--done">Read only</span>
                @endif
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row">
                <span>Struktur</span>
                <b>
                    {{ $indicator->section?->principle?->title ?? 'Prinsip' }}
                    → {{ $indicator->section?->title ?? 'Kriteria' }}
                </b>
            </div>
            <div class="meta-row"><span>Jenis indikator</span><b>{{ $indicator->indicator_type ?? '-' }}</b></div>
            @if ($indicator->description)
                <div class="meta-row"><span>Deskripsi</span><b>{{ $indicator->description }}</b></div>
            @endif
        </div>
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Parameter & Verifier</h2>
                <p>Persyaratan yang perlu diverifikasi untuk indikator ini (read only).</p>
            </div>
        </div>

        @if ($indicator->parameters->isNotEmpty())
            <ul class="list-strip">
                @foreach ($indicator->parameters as $parameter)
                    <li>
                        <span class="list-strip__dot" aria-hidden="true"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div>
                                @if ($parameter->parameter_type->value === 'verifier')
                                    <span class="chip chip--red">Verifier</span>
                                @else
                                    <span class="chip chip--wip">Parameter</span>
                                @endif
                                @if ($parameter->number)
                                    <strong>{{ $parameter->number }}.</strong>
                                @endif
                                {{ $parameter->content }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty">
                <strong>Tidak ada parameter</strong>
                <span>Indikator ini belum memiliki Parameter/Verifier pada snapshot project.</span>
            </div>
        @endif
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Evidence</h2>
                <p>File pendukung tersimpan secara private dan hanya dapat diunduh oleh pengguna berwenang.</p>
            </div>
        </div>

        @if ($canEdit)
            <div class="alert" style="margin-bottom: 14px;">
                Penghapusan evidence hanya mencatat perubahan pada audit trail. File fisik tidak langsung dimusnahkan.
            </div>
            <form method="POST" action="{{ route('audit-projects.evidences.store', [$project, $indicator]) }}" enctype="multipart/form-data">
                @csrf
                @error('evidence')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx" required>
                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Upload Evidence</button>
                    <span class="cell-meta">PDF, JPG, PNG, WEBP, DOCX, XLSX · maksimal {{ config('filesystems.audit_evidence.max_mb') }} MB</span>
                </div>
            </form>
        @endif

        @if ($evidences->isNotEmpty())
            <div class="table-wrap" style="margin-top: 18px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama file</th>
                            <th>Tipe</th>
                            <th>Ukuran</th>
                            <th>Uploader</th>
                            <th>Diunggah</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($evidences as $evidence)
                        <tr>
                            <td>
                                <strong>{{ $evidence->original_name }}</strong>
                                @if ($evidence->caption)
                                    <span class="cell-meta">{{ $evidence->caption }}</span>
                                @endif
                            </td>
                            <td>{{ $evidence->mime_type }}</td>
                            <td>{{ number_format($evidence->size_bytes / 1024, 1) }} KB</td>
                            <td>{{ $evidence->uploader?->name ?? 'Akun terhapus' }}</td>
                            <td>{{ $evidence->created_at->format('d M Y H:i') }}</td>
                            <td style="text-align: right;">
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('audit-projects.evidences.update', [$project, $indicator, $evidence]) }}" style="display: inline-flex; gap: 8px; align-items: center;">
                                        @csrf
                                        @method('PATCH')
                                        <input class="input" type="text" name="caption" value="{{ $evidence->caption }}" maxlength="500" placeholder="Caption">
                                        <label>
                                            <input type="hidden" name="is_report_included" value="0">
                                            <input type="checkbox" name="is_report_included" value="1" @checked($evidence->is_report_included)>
                                            Tampilkan di Laporan
                                        </label>
                                        <button class="btn btn--ghost btn--sm" type="submit">Simpan Metadata</button>
                                    </form>
                                    @php
                                        $orderedIds = $evidences->pluck('id')->values()->all();
                                        $currentIndex = $loop->index;
                                        $upIds = $orderedIds;
                                        $downIds = $orderedIds;
                                        if ($currentIndex > 0) {
                                            [$upIds[$currentIndex - 1], $upIds[$currentIndex]] = [$upIds[$currentIndex], $upIds[$currentIndex - 1]];
                                        }
                                        if ($currentIndex < count($downIds) - 1) {
                                            [$downIds[$currentIndex], $downIds[$currentIndex + 1]] = [$downIds[$currentIndex + 1], $downIds[$currentIndex]];
                                        }
                                        $isFirstEvidence = $loop->first;
                                        $isLastEvidence = $loop->last;
                                    @endphp
                                    <form method="POST" action="{{ route('audit-projects.evidences.order', [$project, $indicator]) }}" style="display: inline-flex; gap: 4px;">
                                        @csrf
                                        @method('PATCH')
                                        @foreach ($upIds as $orderedId)
                                            <input type="hidden" name="evidence_ids[]" value="{{ $orderedId }}">
                                        @endforeach
                                        <button class="btn btn--ghost btn--sm" type="submit" @disabled($isFirstEvidence)>Naik</button>
                                    </form>
                                    <form method="POST" action="{{ route('audit-projects.evidences.order', [$project, $indicator]) }}" style="display: inline-flex; gap: 4px;">
                                        @csrf
                                        @method('PATCH')
                                        @foreach ($downIds as $orderedId)
                                            <input type="hidden" name="evidence_ids[]" value="{{ $orderedId }}">
                                        @endforeach
                                        <button class="btn btn--ghost btn--sm" type="submit" @disabled($isLastEvidence)>Turun</button>
                                    </form>
                                    <form method="POST" action="{{ route('audit-projects.evidences.replace', [$project, $indicator, $evidence]) }}" enctype="multipart/form-data" style="display: inline-flex; gap: 4px; align-items: center;">
                                        @csrf
                                        <input type="file" name="evidence" required accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx">
                                        <button class="btn btn--ghost btn--sm" type="submit">Ganti File</button>
                                    </form>
                                    <form method="POST" action="{{ route('audit-projects.evidences.remove', [$project, $indicator, $evidence]) }}" style="display: inline-flex; gap: 4px; align-items: center;">
                                        @csrf
                                        @method('DELETE')
                                        <input class="input" type="text" name="removal_reason" required minlength="3" maxlength="500" placeholder="Alasan penghapusan">
                                        <button class="btn btn--ghost btn--sm" type="submit" onclick="return confirm('Penghapusan ini hanya pencatatan audit; file fisik tidak langsung dimusnahkan.')">Hapus Evidence</button>
                                    </form>
                                @else
                                    <span class="cell-meta">Tampilkan di Laporan: {{ $evidence->is_report_included ? 'Ya' : 'Tidak' }}</span>
                                @endif
                                <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.evidences.download', [$project, $indicator, $evidence]) }}">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                <strong>Belum ada evidence</strong>
                <span>Evidence belum diunggah untuk indikator ini.</span>
            </div>
        @endif
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Hasil Audit</h2>
                <p>
                    @if ($canEdit)
                        Tulis temuan audit dalam bentuk teks biasa. Setiap penyimpanan membuat versi baru.
                    @elseif ($result)
                        Draft tersimpan. Anda hanya dapat melihat karena bukan PIC indikator ini (read only).
                    @else
                        Belum ada Hasil Audit untuk indikator ini (read only).
                    @endif
                </p>
            </div>
        </div>

        @if ($canEdit)
            <form
                method="POST"
                action="{{ route('audit-projects.work.save-draft', [$project, $indicator]) }}"
                data-autosave-url="{{ route('audit-projects.work.autosave', [$project, $indicator]) }}"
                data-autosave
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="lock_version" value="{{ $result?->lock_version ?? 0 }}" data-lock-version>

                @error('result_text')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                @error('lock_version')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                @error('assessment_status')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror

                <textarea
                    class="textarea textarea--lg"
                    name="result_text"
                    rows="10"
                    placeholder="Tulis hasil audit untuk indikator ini…"
                >{{ old('result_text', $result?->result_text ?? '') }}</textarea>

                <fieldset style="border: 0; padding: 0; margin: 20px 0 0;">
                    <legend style="font-weight: 700; margin-bottom: 8px;">Status Penilaian</legend>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        @foreach (\App\Enums\AuditAssessmentStatus::cases() as $status)
                            <label style="display: inline-flex; align-items: center; gap: 7px;">
                                <input
                                    type="radio"
                                    name="assessment_status"
                                    value="{{ $status->value }}"
                                    @checked(old('assessment_status', $result?->assessment_status?->value) === $status->value)
                                >
                                {{ $status->label() }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Simpan Draft</button>
                    <span class="cell-meta" data-autosave-state aria-live="polite">Belum disimpan</span>
                    <a class="link is-hidden" href="{{ url()->current() }}" data-autosave-reload>Muat Ulang</a>
                </div>
            </form>
        @else
            <div class="result-text">
                {{ $result?->result_text ?? 'BELUM DIKERJAKAN' }}
            </div>
            <div style="margin-top: 16px;">
                <strong>Status Penilaian</strong>
                <div style="margin-top: 6px;">
                    @if ($result?->assessment_status)
                        <span class="chip chip--done">{{ $result->assessment_status->label() }}</span>
                    @else
                        <span class="chip chip--none">Belum Dipilih</span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Riwayat versi</h2>
                <p>Beberapa versi terakhir dari Hasil Audit indikator ini.</p>
            </div>
        </div>

        @if ($versions->isNotEmpty())
            <ul class="list-strip">
                @foreach ($versions as $version)
                    <li>
                        <span class="list-strip__dot" aria-hidden="true"></span>
                        <div style="flex: 1; min-width: 0;">
                            <div>
                                <span class="chip chip--done">v{{ $version->version_number }}</span>
                                <span class="cell-meta">
                                    {{ $version->created_at->format('d M Y H:i') }} ·
                                    {{ $version->changer?->name ?? 'Akun terhapus' }}
                                </span>
                                @if ($version->assessment_status)
                                    <span class="chip chip--todo">{{ $version->assessment_status->label() }}</span>
                                @else
                                    <span class="chip chip--none">Belum Dipilih</span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty">
                <strong>Belum ada versi</strong>
                <span>Versi akan dibuat setelah Hasil Audit disimpan.</span>
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="nav-between">
            @if (! empty($navigation['prev']))
                <a class="btn btn--ghost" href="{{ route('audit-projects.work.show', [$project, $navigation['prev']]) }}">
                    ← Sebelumnya: {{ $navigation['prev']->code }}
                </a>
            @else
                <span></span>
            @endif

            @if (! empty($navigation['next']))
                <a class="btn btn--ghost" href="{{ route('audit-projects.work.show', [$project, $navigation['next']]) }}">
                    Berikutnya: {{ $navigation['next']->code }} →
                </a>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    @if ($canEdit)
        <script>
            (function () {
                var form = document.querySelector('[data-autosave]');
                if (!form) return;

                var textarea = form.querySelector('[name="result_text"]');
                var statuses = form.querySelectorAll('[name="assessment_status"]');
                var lockVersion = form.querySelector('[data-lock-version]');
                var state = form.querySelector('[data-autosave-state]');
                var reloadLink = form.querySelector('[data-autosave-reload]');
                var timer = null;
                var revision = 0;
                var requestInFlight = false;
                var queued = false;
                var conflict = false;

                function setState(text) {
                    state.textContent = text;
                }

                function selectedStatus() {
                    var selected = form.querySelector('[name="assessment_status"]:checked');
                    return selected ? selected.value : null;
                }

                function schedule() {
                    if (conflict) return;
                    queued = true;
                    setState('Belum disimpan');
                    window.clearTimeout(timer);
                    timer = window.setTimeout(send, 1800);
                }

                function send() {
                    if (conflict || requestInFlight || !queued) return;
                    queued = false;
                    requestInFlight = true;
                    var requestRevision = revision;
                    setState('Menyimpan...');

                    fetch(form.dataset.autosaveUrl, {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                        },
                        body: JSON.stringify({
                            result_text: textarea.value,
                            assessment_status: selectedStatus(),
                            lock_version: Number(lockVersion.value),
                        }),
                    }).then(function (response) {
                        return response.json().then(function (payload) {
                            return { response: response, payload: payload };
                        });
                    }).then(function (result) {
                        lockVersion.value = result.payload.lock_version || lockVersion.value;

                        if (result.response.status === 409) {
                            conflict = true;
                            setState('Konflik data — muat ulang');
                            reloadLink.classList.remove('is-hidden');
                            return;
                        }

                        if (!result.response.ok) {
                            setState('Gagal menyimpan');
                            return;
                        }

                        if (revision > requestRevision) {
                            queued = true;
                            setState('Belum disimpan');
                            window.clearTimeout(timer);
                            timer = window.setTimeout(send, 1800);
                            return;
                        }

                        setState('Tersimpan ' + new Date(result.payload.saved_at).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit',
                        }));
                    }).catch(function () {
                        setState('Gagal menyimpan');
                    }).finally(function () {
                        requestInFlight = false;
                        if (queued && !conflict) send();
                    });
                }

                function changed() {
                    revision += 1;
                    schedule();
                }

                textarea.addEventListener('input', changed);
                statuses.forEach(function (radio) {
                    radio.addEventListener('change', changed);
                });

                form.addEventListener('submit', function () {
                    window.clearTimeout(timer);
                    queued = false;
                });
            })();
        </script>
    @endif
@endpush