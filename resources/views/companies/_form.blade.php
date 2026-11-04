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
        <label for="client_year">Klien sejak (tahun)</label>
        <input id="client_year" type="number" name="client_year" min="1900" max="{{ now()->year }}" step="1" value="{{ old('client_year', $company?->client_year) }}">
    </div>
    <div class="field">
        <label for="land_area">Luasan lahan (Ha)</label>
        <input id="land_area" type="number" name="land_area" min="0" step="0.01" value="{{ old('land_area', $company?->land_area) }}">
    </div>
    <div class="field">
        <label for="contact_name">Nama PIC</label>
        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $company?->contact_name) }}">
    </div>
    <div class="field">
        <label for="contact_email">Email PIC</label>
        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}">
    </div>
</div>
