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
            <form method="POST" action="{{ route('audit-projects.work.save-draft', [$project, $indicator]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="lock_version" value="{{ $result?->lock_version ?? 0 }}">

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