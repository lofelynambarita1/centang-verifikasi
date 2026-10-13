@extends('layouts.app')

@section('title', $indicator->code.' | '.$project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Workspace Auditor')
@section('project-role', $projectRoleLabel ?: auth()->user()->system_role->label())

@php
    $workflowStatus = $result?->workflow_status?->value;
    $workflowMeta = match ($workflowStatus) {
        'draft' => ['label' => 'Draft', 'class' => 'chip--wip'],
        'submitted' => ['label' => 'Menunggu Review', 'class' => 'chip--todo'],
        'revision_requested' => ['label' => 'Revisi Diminta', 'class' => 'chip--wip'],
        'approved' => ['label' => 'Approved', 'class' => 'chip--done'],
        default => ['label' => 'Belum Dikerjakan', 'class' => 'chip--none'],
    };
    $resultDocument = old('result_content', $result?->result_content);
    if (is_array($resultDocument) && array_key_exists('content', $resultDocument) && ! array_key_exists('ops', $resultDocument)) {
        $ops = [];
        foreach ($resultDocument['content'] as $block) {
            $attributes = [];
            foreach (($block['marks'] ?? []) as $mark) {
                $attributes[$mark] = true;
            }
            $ops[] = array_filter([
                'insert' => (string) ($block['text'] ?? ''),
                'attributes' => $attributes ?: null,
            ]);
            $lineAttributes = [];
            if (($block['type'] ?? null) === 'list_item') {
                $lineAttributes['list'] = ($block['list_type'] ?? 'ordered') === 'bullet' ? 'bullet' : 'ordered';
                if ((int) ($block['level'] ?? 1) > 1) {
                    $lineAttributes['indent'] = min(2, (int) $block['level'] - 1);
                }
            }
            if (($block['align'] ?? null) === 'justify') {
                $lineAttributes['align'] = 'justify';
            }
            $ops[] = array_filter([
                'insert' => "\n",
                'attributes' => $lineAttributes ?: null,
            ]);
        }
        $resultDocument = ['ops' => $ops];
    } elseif (! is_array($resultDocument) && trim((string) ($result?->result_text ?? '')) !== '') {
        $resultDocument = ['ops' => [
            ['insert' => $result?->result_text ?? ''],
            ['insert' => "\n"],
        ]];
    }
    $resultDocument ??= ['ops' => []];
    $imageEvidences = $evidences
        ->filter(fn ($evidence) => in_array(strtolower((string) $evidence->mime_type), ['image/jpeg', 'image/png', 'image/webp'], true))
        ->values();
    $editorEvidenceRegistry = $imageEvidences
        ->mapWithKeys(fn ($evidence) => [
            (string) $evidence->id => [
                'id' => $evidence->id,
                'name' => $evidence->original_name,
                'caption' => $evidence->caption,
                'preview_url' => route('audit-projects.evidences.download', [$project, $indicator, $evidence, 'preview' => 1]),
                'view_url' => route('audit-projects.evidences.download', [$project, $indicator, $evidence, 'preview' => 1]),
            ],
        ]);
@endphp

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.my-work', $project) }}">← Pekerjaan Saya</a>
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">Detail project</a>
@endsection

@section('content')
    <section class="hero workspace-hero">
        <div class="hero__head">
            <div>
                <div class="workspace-hero__eyebrow">
                    <span class="chip chip--todo">Indikator {{ $indicator->code }}</span>
                    <span class="chip {{ $workflowMeta['class'] }}">{{ $workflowMeta['label'] }}</span>
                </div>
                <h2 class="workspace-hero__title">{{ $indicator->title }}</h2>
                <div class="workspace-hero__sub">{{ $project->name }} · {{ $project->company?->name }}</div>
            </div>
            <div class="hero__actions">
                @if ($canEdit)
                    <span class="chip chip--wip">Dapat diedit</span>
                @else
                    <span class="chip chip--none">Read only</span>
                @endif
            </div>
        </div>
        <div class="hero__body workspace-hero__meta">
            <div class="meta-row">
                <span>Struktur</span>
                <b>
                    {{ $indicator->section?->principle?->title ?? 'Prinsip' }}
                    → {{ $indicator->section?->title ?? 'Kriteria' }}
                </b>
            </div>
            <div class="meta-row"><span>Kategori indikator</span><b>{{ $indicator->indicator_type ?: 'Belum tersedia' }}</b></div>
            @if (!$canEdit && $workflowStatus)
                <div class="meta-row"><span>Akses</span><b>{{ $workflowMeta['label'] === 'Approved' ? 'Indikator sudah disetujui.' : 'Menunggu proses review.' }}</b></div>
            @endif
            @if ($indicator->description)
                <div class="meta-row"><span>Deskripsi</span><b>{{ $indicator->description }}</b></div>
            @endif
        </div>
    </section>

    @if ($canReview && $result?->workflow_status?->value === 'submitted')
        <section class="panel panel--pad workspace-section">
            <div class="panel__head">
                <div>
                    <h2>Review Lead Auditor</h2>
                    <p>Hasil Audit dan evidence tidak dapat diubah selama proses review.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('audit-projects.work.review.revision', [$project, $indicator]) }}" style="margin-bottom: 12px;">
                @csrf
                <input type="hidden" name="lock_version" value="{{ $result->lock_version }}">
                <textarea class="textarea" name="note" rows="4" minlength="3" maxlength="2000" required placeholder="Catatan revisi"></textarea>
                <button class="btn btn--ghost" type="submit">Minta Revisi</button>
            </form>
            <form method="POST" action="{{ route('audit-projects.work.review.approve', [$project, $indicator]) }}">
                @csrf
                <input type="hidden" name="lock_version" value="{{ $result->lock_version }}">
                <textarea class="textarea" name="note" rows="3" maxlength="2000" placeholder="Catatan approval (opsional)"></textarea>
                <button class="btn btn--primary" type="submit" onclick="return confirm('Setujui hasil audit ini?\n\nHasil audit akan berstatus Approved.')">Approve</button>
            </form>
        </section>
    @endif

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Parameter & Verifier</h2>
                <p>Persyaratan yang perlu diverifikasi untuk indikator ini (read only).</p>
            </div>
        </div>

        @if ($indicator->parameters->isNotEmpty())
            <ul class="list-strip workspace-parameter-list">
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
                <span>Indikator ini belum memiliki Parameter/Verifier pada struktur project.</span>
            </div>
        @endif
    </section>

    <section class="panel panel--pad workspace-section workspace-evidence">
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
            <form class="workspace-upload" method="POST" action="{{ route('audit-projects.evidences.store', [$project, $indicator]) }}" enctype="multipart/form-data">
                @csrf
                @error('evidence')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                <label class="workspace-upload__dropzone">
                    <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx" required>
                    <strong>Pilih file evidence</strong>
                    <span>PDF, JPG, PNG, WEBP, DOCX, XLSX · maksimal {{ config('filesystems.audit_evidence.max_mb') }} MB</span>
                </label>
                <div class="form-actions workspace-upload__actions">
                    <button class="btn btn--primary" type="submit">Tambah Evidence</button>
                </div>
            </form>
        @elseif ($result?->workflow_status?->value === 'submitted' && $indicator->assignment?->pic_user_id === auth()->id())
            <section class="panel panel--pad workspace-section">
                <div class="alert alert--warning">Hasil audit ini menunggu reviewer lain karena Anda adalah auditor/PIC indikator ini.</div>
            </section>
        @endif

        @if ($evidences->isNotEmpty())
            <div class="evidence-list" style="margin-top: 18px;">
                @foreach ($evidences as $evidence)
                    @php
                        $size = $evidence->size_bytes >= 1048576
                            ? number_format($evidence->size_bytes / 1048576, 1).' MB'
                            : number_format($evidence->size_bytes / 1024, 1).' KB';
                        $isImage = str_starts_with((string) $evidence->mime_type, 'image/');
                        $fileType = match (true) {
                            $isImage => strtoupper(pathinfo($evidence->original_name, PATHINFO_EXTENSION)),
                            $evidence->mime_type === 'application/pdf' => 'PDF',
                            str_contains((string) $evidence->mime_type, 'spreadsheet') => 'XLSX',
                            str_contains((string) $evidence->mime_type, 'word') => 'DOCX',
                            default => strtoupper(pathinfo($evidence->original_name, PATHINFO_EXTENSION) ?: 'FILE'),
                        };
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
                    @endphp
                    <article class="evidence-card">
                        <div class="evidence-card__head">
                            <div class="evidence-card__preview">
                                @if ($isImage)
                                    <a href="{{ route('audit-projects.evidences.download', [$project, $indicator, $evidence, 'preview' => 1]) }}" target="_blank" rel="noopener" aria-label="Lihat {{ $evidence->original_name }}">
                                        <img class="evidence-card__thumbnail" src="{{ route('audit-projects.evidences.download', [$project, $indicator, $evidence, 'preview' => 1]) }}" alt="">
                                    </a>
                                @else
                                    <span class="evidence-card__file-icon" aria-hidden="true">{{ $fileType }}</span>
                                @endif
                            </div>
                            <div class="evidence-card__file">
                                <strong>{{ $evidence->original_name }}</strong>
                                <span>{{ $fileType }} · {{ $size }}</span>
                                <small>Diunggah oleh {{ $evidence->uploader?->name ?? 'Akun terhapus' }} · {{ $evidence->created_at->format('d M Y, H:i') }}</small>
                                <div class="evidence-card__view-actions">
                                    <a class="btn btn--primary btn--sm" href="{{ route('audit-projects.evidences.download', [$project, $indicator, $evidence, 'preview' => 1]) }}" target="_blank" rel="noopener">Lihat</a>
                                    <a class="btn btn--ghost btn--sm" href="{{ route('audit-projects.evidences.download', [$project, $indicator, $evidence]) }}">Download</a>
                                </div>
                            </div>
                        </div>

                        @if ($canEdit)
                            <form class="evidence-card__metadata" method="POST" action="{{ route('audit-projects.evidences.update', [$project, $indicator, $evidence]) }}">
                                @csrf
                                @method('PATCH')
                                <label class="evidence-card__caption">
                                    <span>Keterangan Evidence</span>
                                    <input class="input" type="text" name="caption" value="{{ $evidence->caption }}" maxlength="500" placeholder="Tambahkan keterangan singkat mengenai evidence ini...">
                                </label>
                                <label class="evidence-card__report">
                                    <input type="hidden" name="is_report_included" value="0">
                                    <input type="checkbox" name="is_report_included" value="1" @checked($evidence->is_report_included)>
                                    <span>Sertakan evidence ini dalam laporan</span>
                                </label>
                                <button class="btn btn--ghost btn--sm" type="submit">Simpan Perubahan</button>
                            </form>

                            @if ($evidences->count() > 1)
                                <div class="evidence-card__actions">
                                    @if (!$loop->first)
                                        <form method="POST" action="{{ route('audit-projects.evidences.order', [$project, $indicator]) }}">
                                            @csrf
                                            @method('PATCH')
                                            @foreach ($upIds as $orderedId)
                                                <input type="hidden" name="evidence_ids[]" value="{{ $orderedId }}">
                                            @endforeach
                                            <button class="btn btn--ghost btn--sm" type="submit">↑ Pindah ke Atas</button>
                                        </form>
                                    @endif
                                    @if (!$loop->last)
                                        <form method="POST" action="{{ route('audit-projects.evidences.order', [$project, $indicator]) }}">
                                            @csrf
                                            @method('PATCH')
                                            @foreach ($downIds as $orderedId)
                                                <input type="hidden" name="evidence_ids[]" value="{{ $orderedId }}">
                                            @endforeach
                                            <button class="btn btn--ghost btn--sm" type="submit">↓ Pindah ke Bawah</button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            <details class="evidence-card__details">
                                <summary>Ganti File</summary>
                                <form method="POST" action="{{ route('audit-projects.evidences.replace', [$project, $indicator, $evidence]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="evidence" required accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx">
                                    <button class="btn btn--ghost btn--sm" type="submit">Ganti File</button>
                                </form>
                            </details>
                            <details class="evidence-card__details evidence-card__details--danger">
                                <summary>Hapus Evidence</summary>
                                <form id="evidence-delete-form-{{ $evidence->id }}" method="POST" action="{{ route('audit-projects.evidences.remove', [$project, $indicator, $evidence]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--ghost btn--sm" type="button" data-evidence-delete-trigger data-form-id="evidence-delete-form-{{ $evidence->id }}">Hapus Evidence</button>
                                </form>
                            </details>
                        @else
                            <div class="evidence-card__readonly">Sertakan evidence ini dalam laporan: {{ $evidence->is_report_included ? 'Ya' : 'Tidak' }}</div>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty empty--compact">
                <strong>Belum ada evidence</strong>
                <span>Evidence belum diunggah untuk indikator ini.</span>
            </div>
        @endif

        @if ($canEdit)
            <dialog class="evidence-delete-dialog" data-evidence-delete-dialog aria-labelledby="evidence-delete-title">
                <h2 id="evidence-delete-title">Hapus evidence ini?</h2>
                <p>Evidence akan dihapus dari indikator ini. Tindakan tetap tercatat pada audit trail.</p>
                <div class="evidence-delete-dialog__actions">
                    <button class="btn btn--ghost btn--sm" type="button" data-evidence-delete-cancel>Batal</button>
                    <button class="btn btn--danger btn--sm" type="button" data-evidence-delete-confirm>Ya, Hapus</button>
                </div>
            </dialog>
        @endif
    </section>

    <section class="panel panel--pad workspace-section workspace-result">
        <div class="panel__head">
            <div>
                <h2>Hasil Audit</h2>
                <p>
                    @if ($canEdit)
                        @if ($result?->workflow_status?->value === 'revision_requested')
                            <span class="chip chip--wip">REVISI DIMINTA</span>
                            @php($latestRevision = $result->reviewEvents->where('action.value', 'revision_requested')->last())
                            @if ($latestRevision)
                                <span class="cell-meta">{{ $latestRevision->note }} · {{ $latestRevision->actor?->name ?? 'Akun terhapus' }} · {{ $latestRevision->created_at?->format('d M Y H:i') }}</span>
                            @endif
                        @else
                            Tulis hasil audit seperti dokumen biasa. Anda dapat menggunakan paragraf, daftar, dan subdaftar bila diperlukan.
                        @endif
                    @elseif ($result?->workflow_status?->value === 'submitted')
                        <span class="chip chip--todo">Menunggu Review</span>
                        <span class="cell-meta">
                            {{ $result->submitted_at?->format('d M Y H:i') }} ·
                            {{ $result->submitter?->name ?? 'Akun terhapus' }}
                        </span>
                    @elseif ($result?->workflow_status?->value === 'revision_requested')
                        <span class="chip chip--wip">REVISI DIMINTA</span>
                        @php($latestRevision = $result->reviewEvents->where('action.value', 'revision_requested')->last())
                        @if ($latestRevision)
                            <span class="cell-meta">
                                {{ $latestRevision->note }} · {{ $latestRevision->actor?->name ?? 'Akun terhapus' }} · {{ $latestRevision->created_at?->format('d M Y H:i') }}
                            </span>
                        @endif
                    @elseif ($result?->workflow_status?->value === 'approved')
                        <span class="chip chip--done">Approved</span>
                        <span class="cell-meta">
                            {{ $result->approved_at?->format('d M Y H:i') }} ·
                            {{ $result->approver?->name ?? 'Akun terhapus' }}
                        </span>
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
                data-rich-result-form
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="lock_version" value="{{ $result?->lock_version ?? 0 }}" data-lock-version>
                <input type="hidden" name="result_text" value="{{ old('result_text', $result?->result_text ?? '') }}" data-rich-result-text>
                <input type="hidden" name="result_content" value="{{ e(json_encode($resultDocument, JSON_UNESCAPED_UNICODE)) }}" data-rich-result-content>

                @error('result_text')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                @error('result_content')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                @error('result_blocks')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                @error('lock_version')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror
                @error('assessment_status')
                    <div class="alert alert--error">{{ $message }}</div>
                @enderror

                <div class="audit-result-editor-shell quill-result-editor" data-audit-result-editor>
                    <div id="audit-result-toolbar" class="quill-result-editor__toolbar" role="toolbar" aria-label="Toolbar Hasil Audit" data-quill-toolbar>
                        <button type="button" class="quill-result-editor__button audit-quill-undo" data-editor-action="undo" title="Undo" aria-label="Undo">↶</button>
                        <button type="button" class="quill-result-editor__button audit-quill-redo" data-editor-action="redo" title="Redo" aria-label="Redo">↷</button>
                        <span class="quill-result-editor__divider" aria-hidden="true"></span>
                        <button type="button" class="quill-result-editor__button ql-bold" title="Bold" aria-label="Bold"><strong>B</strong></button>
                        <button type="button" class="quill-result-editor__button ql-italic" title="Italic" aria-label="Italic"><em>I</em></button>
                        <button type="button" class="quill-result-editor__button ql-underline" title="Underline" aria-label="Underline"><u>U</u></button>
                        <span class="quill-result-editor__divider" aria-hidden="true"></span>
                        <button type="button" class="quill-result-editor__button ql-list" value="ordered" title="Daftar Bernomor" aria-label="Daftar Bernomor">1.</button>
                        <button type="button" class="quill-result-editor__button ql-list" value="bullet" title="Daftar Poin" aria-label="Daftar Poin">Poin</button>
                        <button type="button" class="quill-result-editor__button ql-indent" value="-1" title="Kurangi Indent" aria-label="Kurangi Indent">Outdent</button>
                        <button type="button" class="quill-result-editor__button ql-indent" value="+1" title="Tambah Indent" aria-label="Tambah Indent">Indent</button>
                        <span class="quill-result-editor__divider" aria-hidden="true"></span>
                        <button type="button" class="quill-result-editor__button ql-align" value="" title="Rata Kiri" aria-label="Rata Kiri">Left</button>
                        <button type="button" class="quill-result-editor__button ql-align" value="justify" title="Rata Kiri-Kanan" aria-label="Rata Kiri-Kanan">Justify</button>
                        <span class="quill-result-editor__divider" aria-hidden="true"></span>
                        <button type="button" class="quill-result-editor__button ql-clean" title="Hapus Format" aria-label="Hapus Format">Clear</button>
                        <span class="quill-result-editor__divider" aria-hidden="true"></span>
                        <button type="button" class="quill-result-editor__button audit-quill-image" data-editor-action="add-image" title="Tambah Gambar" aria-label="Tambah Gambar">▧ <span>Tambah Gambar</span></button>
                    </div>
                    <div id="audit-result-editor" class="quill-result-editor__surface" data-quill-editor></div>
                </div>
                <textarea
                    class="textarea textarea--lg workspace-result__textarea is-hidden"
                    data-legacy-result-text
                    rows="10"
                    placeholder="Tulis hasil audit untuk indikator ini…"
                >{{ old('result_text', $result?->result_text ?? '') }}</textarea>

                <fieldset class="workspace-conclusion">
                    <legend style="font-weight: 700; margin-bottom: 8px;">Kesimpulan Audit</legend>
                    <div class="workspace-conclusion__options">
                        @foreach (\App\Enums\AuditAssessmentStatus::cases() as $status)
                            <label class="workspace-conclusion__option">
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

                <div class="form-actions workspace-actions">
                    <button class="btn btn--ghost" type="submit">Simpan Draft</button>
                    <span class="cell-meta" data-autosave-state aria-live="polite">Belum disimpan</span>
                    <a class="link is-hidden" href="{{ url()->current() }}" data-autosave-reload>Muat Ulang</a>
                </div>
            </form>
            @if ($canFinalizeOwnIndicator)
                <form method="POST" action="{{ route('audit-projects.work.finalize-own-indicator', [$project, $indicator]) }}" style="margin-top: 14px;">
                    @csrf
                    <input type="hidden" name="lock_version" value="{{ $result->lock_version }}">
                    <textarea class="textarea" name="note" rows="3" maxlength="2000" placeholder="Catatan finalisasi (opsional)" style="margin-bottom: 12px;"></textarea>
                    <button class="btn btn--primary" type="submit" onclick="return confirm('Selesaikan audit indikator Anda dan langsung setujui?\n\nStatus akan menjadi Approved.')">
                        Selesaikan Audit
                    </button>
                </form>
            @elseif ($canSubmit)
                <form method="POST" action="{{ route('audit-projects.work.submit', [$project, $indicator]) }}" style="margin-top: 14px;">
                    @csrf
                    <input type="hidden" name="lock_version" value="{{ $result->lock_version }}">
                    <button class="btn btn--primary" type="submit" onclick="return confirm('Kirim Hasil Audit untuk review? Setelah dikirim, draft dan evidence tidak dapat diubah.')">
                        {{ $result?->workflow_status?->value === 'revision_requested' ? 'Kirim Ulang untuk Review' : 'Kirim untuk Review' }}
                    </button>
                </form>
            @endif
            <dialog class="audit-image-dialog" data-audit-image-dialog data-upload-url="{{ route('audit-projects.evidences.editor-images.store', [$project, $indicator]) }}">
                <form class="audit-image-dialog__panel" data-audit-image-upload-form>
                    <div class="audit-image-dialog__head">
                        <div>
                            <h3>Tambah Gambar ke Hasil Audit</h3>
                            <p>Pilih gambar evidence aktif atau unggah gambar baru.</p>
                        </div>
                        <button class="audit-image-dialog__close" type="button" data-audit-image-close aria-label="Tutup">×</button>
                    </div>

                    <div class="audit-image-dialog__tabs" role="tablist" aria-label="Pilihan tambah gambar">
                        <button class="is-active" type="button" data-audit-image-tab="upload">Upload Gambar Baru</button>
                        <button type="button" data-audit-image-tab="existing">Pilih dari Evidence</button>
                    </div>

                    <div class="audit-image-dialog__body" data-audit-image-panel="upload">
                        <label class="field">
                            <span>File Gambar</span>
                            <input class="input" type="file" name="evidence" accept=".jpg,.jpeg,.png,.webp" required>
                        </label>
                        <label class="field">
                            <span>Keterangan Evidence</span>
                            <input class="input" type="text" name="caption" maxlength="500" placeholder="Keterangan singkat gambar">
                        </label>
                        <div class="alert alert--error is-hidden" data-audit-image-error></div>
                    </div>

                    <div class="audit-image-dialog__body is-hidden" data-audit-image-panel="existing">
                        @if ($imageEvidences->isNotEmpty())
                            <div class="audit-image-picker">
                                @foreach ($imageEvidences as $imageEvidence)
                                    <article class="audit-image-picker__item">
                                        <img src="{{ route('audit-projects.evidences.download', [$project, $indicator, $imageEvidence, 'preview' => 1]) }}" alt="">
                                        <div>
                                            <strong>{{ $imageEvidence->original_name }}</strong>
                                            @if ($imageEvidence->caption)
                                                <span>Keterangan: {{ $imageEvidence->caption }}</span>
                                            @endif
                                        </div>
                                        <button class="btn btn--ghost btn--sm" type="button" data-audit-image-select="{{ $imageEvidence->id }}">Pilih</button>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="empty empty--compact">
                                <strong>Belum ada gambar</strong>
                                <span>Unggah gambar baru untuk menyisipkannya ke Hasil Audit.</span>
                            </div>
                        @endif
                    </div>

                    <div class="audit-image-dialog__actions">
                        <button class="btn btn--ghost" type="button" data-audit-image-cancel>Batal</button>
                        <button class="btn btn--primary" type="submit" data-audit-image-submit>Upload & Sisipkan</button>
                    </div>
                </form>
            </dialog>
        @else
                    <div class="audit-result-editor-shell quill-result-editor quill-result-editor--readonly" data-readonly-quill data-result-content='@json($resultDocument)'>
                <div class="quill-result-editor__surface" data-quill-editor></div>
            </div>
            <div class="workspace-readonly-conclusion">
                <strong>Kesimpulan Audit</strong>
                <div class="workspace-readonly-conclusion__value">
                    @if ($result?->assessment_status)
                        <span class="chip chip--done">{{ $result->assessment_status->label() }}</span>
                    @else
                        <span class="chip chip--none">Belum diisi</span>
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
                                    <span class="chip chip--none">Belum diisi</span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty empty--compact">
                <strong>Belum ada versi</strong>
                <span>Versi akan dibuat setelah Hasil Audit disimpan.</span>
            </div>
        @endif
    </section>

    <section class="panel workspace-navigation">
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
        <script>
            window.auditEvidenceRegistry = @json($editorEvidenceRegistry);

            document.addEventListener('DOMContentLoaded', function () {
                var deleteDialog = document.querySelector('[data-evidence-delete-dialog]');
                var deleteForm = null;
                if (deleteDialog) {
                    document.querySelectorAll('[data-evidence-delete-trigger]').forEach(function (trigger) {
                        trigger.addEventListener('click', function () {
                            deleteForm = document.getElementById(trigger.dataset.formId);
                            deleteDialog.showModal();
                        });
                    });
                    deleteDialog.querySelector('[data-evidence-delete-cancel]').addEventListener('click', function () {
                        deleteDialog.close();
                        deleteForm = null;
                    });
                    deleteDialog.querySelector('[data-evidence-delete-confirm]').addEventListener('click', function () {
                        if (deleteForm) deleteForm.submit();
                    });
                    deleteDialog.addEventListener('close', function () {
                        deleteForm = null;
                    });
                }

                var richForm = document.querySelector('[data-rich-result-form]');
                var richEditor = document.querySelector('[data-audit-result-editor]');
                var quill = null;

                function parseDelta(value) {
                    try {
                        var parsed = JSON.parse(value || '{"ops":[]}');
                        if (typeof parsed === 'string') {
                            parsed = JSON.parse(parsed || '{"ops":[]}');
                        }

                        return parsed && Array.isArray(parsed.ops) ? parsed : { ops: [] };
                    } catch (error) {
                        return { ops: [] };
                    }
                }

                if (richForm && richEditor && window.Quill) {
                    var resultTextInput = richForm.querySelector('[data-rich-result-text]');
                    var resultContentInput = richForm.querySelector('[data-rich-result-content]');
                    var imageDialog = document.querySelector('[data-audit-image-dialog]');
                    var imageUploadForm = document.querySelector('[data-audit-image-upload-form]');
                    var imageError = document.querySelector('[data-audit-image-error]');
                    var imageSubmit = document.querySelector('[data-audit-image-submit]');
                    var lastSelection = null;
                    var initialDelta = parseDelta(resultContentInput.value);

                    quill = new window.Quill('#audit-result-editor', {
                        bounds: richEditor,
                        modules: {
                            toolbar: '#audit-result-toolbar',
                            history: {
                                delay: 800,
                                maxStack: 100,
                                userOnly: true,
                            },
                            clipboard: {
                                matchVisual: false,
                            },
                        },
                        placeholder: 'Tulis hasil audit di sini...',
                        readOnly: false,
                        theme: 'snow',
                        formats: ['bold', 'italic', 'underline', 'list', 'indent', 'align', 'auditEvidence'],
                    });

                    function insertEvidence(evidenceId) {
                        var index = lastSelection ? lastSelection.index : Math.max(0, quill.getLength() - 1);
                        quill.insertEmbed(index, 'auditEvidence', { evidence_id: Number(evidenceId) }, 'user');
                        quill.insertText(index + 1, '\n', 'user');
                        quill.setSelection(index + 2, 0, 'silent');
                        syncEditor(true);
                    }

                    function openImageDialog() {
                        lastSelection = quill.getSelection();
                        if (!lastSelection) {
                            lastSelection = { index: Math.max(0, quill.getLength() - 1), length: 0 };
                        }
                        if (imageError) {
                            imageError.classList.add('is-hidden');
                            imageError.textContent = '';
                        }
                        if (imageDialog) {
                            imageDialog.showModal();
                        }
                    }

                    function closeImageDialog() {
                        if (imageDialog) {
                            imageDialog.close();
                        }
                        quill.focus();
                    }

                    function setImageTab(name) {
                        if (!imageDialog) return;
                        imageDialog.querySelectorAll('[data-audit-image-tab]').forEach(function (tab) {
                            tab.classList.toggle('is-active', tab.dataset.auditImageTab === name);
                        });
                        imageDialog.querySelectorAll('[data-audit-image-panel]').forEach(function (panel) {
                            panel.classList.toggle('is-hidden', panel.dataset.auditImagePanel !== name);
                        });
                        if (imageSubmit) {
                            imageSubmit.classList.toggle('is-hidden', name !== 'upload');
                        }
                    }

                    quill.clipboard.addMatcher(Node.ELEMENT_NODE, function (node, delta) {
                        delta.ops = delta.ops.map(function (op) {
                            if (!op.attributes) {
                                return op;
                            }

                            var clean = {};
                            ['bold', 'italic', 'underline', 'list', 'indent', 'align'].forEach(function (key) {
                                if (Object.prototype.hasOwnProperty.call(op.attributes, key)) {
                                    clean[key] = op.attributes[key];
                                }
                            });

                            var cleanedOp = Object.assign({}, op);
                            if (Object.keys(clean).length > 0) {
                                cleanedOp.attributes = clean;
                            } else {
                                delete cleanedOp.attributes;
                            }

                            return cleanedOp;
                        });

                        return delta;
                    });

                    if (initialDelta.ops && initialDelta.ops.length > 0) {
                        quill.setContents(initialDelta, 'silent');
                    }

                    function syncEditor(emitChange) {
                        var delta = quill.getContents();
                        resultContentInput.value = JSON.stringify(delta);
                        resultTextInput.value = quill.getText().trim();
                        if (emitChange) {
                            resultTextInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }

                    richEditor.querySelector('[data-editor-action="undo"]').addEventListener('click', function () {
                        quill.history.undo();
                        syncEditor(true);
                    });
                    richEditor.querySelector('[data-editor-action="redo"]').addEventListener('click', function () {
                        quill.history.redo();
                        syncEditor(true);
                    });
                    richEditor.querySelector('[data-editor-action="add-image"]').addEventListener('click', openImageDialog);
                    richEditor.addEventListener('click', function (event) {
                        var removeButton = event.target.closest('[data-remove-audit-evidence]');
                        if (!removeButton) {
                            return;
                        }

                        var blot = window.Quill.find(removeButton.closest('.audit-evidence-embed'));
                        if (!blot) {
                            return;
                        }

                        var index = quill.getIndex(blot);
                        quill.deleteText(index, 1, 'user');
                        syncEditor(true);
                    });

                    if (imageDialog && imageUploadForm) {
                        imageDialog.querySelectorAll('[data-audit-image-tab]').forEach(function (tab) {
                            tab.addEventListener('click', function () {
                                setImageTab(tab.dataset.auditImageTab);
                            });
                        });
                        imageDialog.querySelector('[data-audit-image-close]').addEventListener('click', closeImageDialog);
                        imageDialog.querySelector('[data-audit-image-cancel]').addEventListener('click', closeImageDialog);
                        imageDialog.querySelectorAll('[data-audit-image-select]').forEach(function (button) {
                            button.addEventListener('click', function () {
                                insertEvidence(button.dataset.auditImageSelect);
                                closeImageDialog();
                            });
                        });
                        imageUploadForm.addEventListener('submit', function (event) {
                            event.preventDefault();
                            if (imageError) {
                                imageError.classList.add('is-hidden');
                                imageError.textContent = '';
                            }
                            if (imageSubmit) {
                                imageSubmit.disabled = true;
                                imageSubmit.textContent = 'Mengunggah...';
                            }

                            fetch(imageDialog.dataset.uploadUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: new FormData(imageUploadForm),
                            }).then(function (response) {
                                return response.json().then(function (payload) {
                                    if (!response.ok) {
                                        var message = payload.message || 'Upload gambar gagal.';
                                        if (payload.errors) {
                                            message = Object.values(payload.errors).flat()[0] || message;
                                        }
                                        throw new Error(message);
                                    }

                                    return payload;
                                });
                            }).then(function (payload) {
                                var evidence = payload.evidence;
                                window.auditEvidenceRegistry[String(evidence.id)] = evidence;
                                insertEvidence(evidence.id);
                                imageUploadForm.reset();
                                closeImageDialog();
                            }).catch(function (error) {
                                if (imageError) {
                                    imageError.textContent = error.message;
                                    imageError.classList.remove('is-hidden');
                                }
                            }).finally(function () {
                                if (imageSubmit) {
                                    imageSubmit.disabled = false;
                                    imageSubmit.textContent = 'Upload & Sisipkan';
                                }
                            });
                        });
                    }

                    quill.on('text-change', function () {
                        syncEditor(true);
                    });
                    richForm.addEventListener('submit', function () {
                        syncEditor(false);
                    });
                    syncEditor(false);
                }

                document.querySelectorAll('[data-readonly-quill]').forEach(function (readonlyEditor) {
                    if (!window.Quill) return;

                    var readonlyQuill = new window.Quill(readonlyEditor.querySelector('[data-quill-editor]'), {
                        modules: {
                            toolbar: false,
                        },
                        readOnly: true,
                        theme: 'snow',
                    });
                    var readonlyDelta = parseDelta(readonlyEditor.dataset.resultContent);
                    if (readonlyDelta.ops && readonlyDelta.ops.length > 0) {
                        readonlyQuill.setContents(readonlyDelta, 'silent');
                    } else {
                        readonlyQuill.setText('BELUM DIKERJAKAN', 'silent');
                    }
                    readonlyQuill.enable(false);
                });

                var form = document.querySelector('[data-rich-result-form]');
                if (!form) return;

                var textarea = form.querySelector('[name="result_text"]');
                var contentInput = form.querySelector('[name="result_content"]');
                var statuses = form.querySelectorAll('[name="assessment_status"]');
                var lockVersion = form.querySelector('[data-lock-version]');
                var autosavePrefix = 'data-' + 'autosave';
                var state = form.querySelector('[' + autosavePrefix + '-state]');
                var reloadLink = form.querySelector('[' + autosavePrefix + '-reload]');
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

                    fetch(form.getAttribute(autosavePrefix + '-url'), {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                        },
                        body: JSON.stringify({
                            result_text: textarea.value,
                            result_content: JSON.parse(contentInput.value || '{"ops":[]}'),
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
            });
        </script>
@endpush
