@php
    $selectedAuditorIds = collect(old('auditor_ids', []))
        ->map(fn ($id): int => (int) $id)
        ->all();
@endphp

@if ($errors->any())
    <div class="form-error" role="alert">{{ $errors->first() }}</div>
@endif

<div class="form-grid">
    <div class="field field--wide">
        <label for="name">Nama project</label>
        <input id="name" name="name" value="{{ old('name', $project?->name) }}" required placeholder="cth: Audit ISPO PT Sawit Makmur 2026">
    </div>

    <div class="field">
        <label for="template_id">Acuan audit ISPO</label>
        <select id="template_id" name="template_id" required>
            <option value="">— Pilih template —</option>
            @foreach ($templates as $template)
                <option value="{{ $template->id }}" @selected((int) old('template_id', $project?->template_id) === $template->id)>
                    {{ $template->name }} ({{ $template->code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="company_id">Klien (perusahaan auditee)</label>
        <select id="company_id" name="company_id" required>
            <option value="">— Pilih klien —</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('company_id', $project?->company_id) === $company->id)>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
        <span class="cell-meta">Belum ada perusahaan? Tambahkan melalui menu Perusahaan.</span>
    </div>

    <div class="field">
        <label for="audit_type">Jenis audit</label>
        <input id="audit_type" name="audit_type" value="{{ old('audit_type', $project?->audit_type) }}" required placeholder="cth: Audit Lapangan">
    </div>

    <div class="field">
        <label for="land_area">Luas lahan (ha)</label>
        <input id="land_area" name="land_area" type="number" step="0.01" min="0" value="{{ old('land_area', $project?->land_area) }}" placeholder="cth: 1250.50">
    </div>

    <div class="field">
        <label for="audit_start_date">Tanggal mulai audit</label>
        <input id="audit_start_date" type="date" name="audit_start_date" value="{{ old('audit_start_date', $project?->audit_start_date?->format('Y-m-d')) }}" required>
    </div>

    <div class="field">
        <label for="audit_end_date">Tanggal selesai audit</label>
        <input id="audit_end_date" type="date" name="audit_end_date" value="{{ old('audit_end_date', $project?->audit_end_date?->format('Y-m-d')) }}" required>
        <span class="cell-meta">Tidak boleh sebelum tanggal mulai.</span>
    </div>

    @if (isset($withTeam) && $withTeam)
        <div class="field">
            <label for="lead_auditor_id">Lead Auditor</label>
            <select id="lead_auditor_id" name="lead_auditor_id" required>
                <option value="">— Pilih lead auditor —</option>
                @foreach ($auditors as $auditor)
                    <option value="{{ $auditor->id }}">{{ $auditor->name }} &lt;{{ $auditor->email }}&gt;</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="auditor-search">Anggota Auditor</label>
            <div class="auditor-picker" data-auditor-picker>
                <button class="auditor-picker__trigger" type="button" aria-expanded="false" aria-controls="auditor-picker-options">
                    <span data-auditor-summary>Belum ada anggota dipilih</span>
                    <span aria-hidden="true">⌄</span>
                </button>
                <div class="auditor-picker__menu" id="auditor-picker-options" hidden>
                    <input id="auditor-search" class="auditor-picker__search" type="search" placeholder="Cari nama atau email..." autocomplete="off" data-auditor-search>
                    <div class="auditor-picker__options" role="group" aria-label="Daftar auditor">
                        @foreach ($auditors as $auditor)
                            <label class="auditor-picker__option" data-auditor-option data-auditor-text="{{ strtolower($auditor->name.' '.$auditor->email) }}" data-auditor-id="{{ $auditor->id }}">
                                <input type="checkbox" name="auditor_ids[]" value="{{ $auditor->id }}" @checked(in_array($auditor->id, $selectedAuditorIds, true)) data-auditor-checkbox>
                                <span>
                                    <strong>{{ $auditor->name }}</strong>
                                    <small>{{ $auditor->email }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="auditor-picker__empty" data-auditor-empty hidden>Auditor tidak ditemukan.</p>
                </div>
            </div>
            <span class="cell-meta">Lead Auditor otomatis menjadi bagian dari tim.</span>
        </div>
    @endif

</div>