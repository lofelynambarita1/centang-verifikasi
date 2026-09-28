@if ($errors->any())
    <div class="form-error" role="alert">{{ $errors->first() }}</div>
@endif
<div class="form-grid">
    <div class="field">
        <label for="name">Nama perusahaan</label>
        <input id="name" name="name" value="{{ old('name', $company?->name) }}" required>
    </div>
    <div class="field">
        <label for="industry">Bidang usaha</label>
        <input id="industry" name="industry" value="{{ old('industry', $company?->industry) }}">
    </div>
    <div class="field field--wide">
        <label for="address">Alamat</label>
        <textarea id="address" name="address" rows="3">{{ old('address', $company?->address) }}</textarea>
    </div>
    <div class="field">
        <label for="contact_name">Nama PIC</label>
        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $company?->contact_name) }}">
    </div>
    <div class="field">
        <label for="contact_email">Email PIC</label>
        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}">
    </div>
    <div class="field">
        <label for="auditor_name">Nama auditor</label>
        <textarea id="auditor_name" name="auditor_name" rows="3" placeholder="Pisahkan nama auditor dengan koma atau baris baru">{{ old('auditor_name', $company?->audits->first()?->auditor_name) }}</textarea>
    </div>
    <div class="field">
        <label for="due_date">Deadline audit</label>
        <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $company?->audits->first()?->due_date?->format('Y-m-d')) }}">
    </div>
</div>