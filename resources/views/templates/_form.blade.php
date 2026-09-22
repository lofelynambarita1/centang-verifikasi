@if ($errors->any())
    <div class="form-error" role="alert">{{ $errors->first() }}</div>
@endif

<div class="form-grid">
    <div class="field">
        <label for="code">Kode template</label>
        <input id="code" name="code" value="{{ old('code', $template?->code) }}" required placeholder="cth: FrO.8603/GIS-1.1">
    </div>

    <div class="field">
        <label for="scheme">Scheme</label>
        <input id="scheme" name="scheme" value="{{ old('scheme', $template?->scheme) }}" required placeholder="cth: ISPO">
    </div>

    <div class="field">
        <label for="name">Nama template</label>
        <input id="name" name="name" value="{{ old('name', $template?->name) }}" required placeholder="cth: Laporan Verifikasi Hasil ISPO">
    </div>

    <div class="field">
        <label for="version">Versi</label>
        <input id="version" name="version" value="{{ old('version', $template?->version) }}" placeholder="cth: 1.1">
    </div>

    <div class="field">
        <label for="is_active">Status</label>
        <select id="is_active" name="is_active" required>
            <option value="1" @selected((int) old('is_active', $template?->is_active) === 1)>Aktif</option>
            <option value="0" @selected((int) old('is_active', $template?->is_active) === 0)>Nonaktif</option>
        </select>
    </div>

    <div class="field field--wide">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="3" placeholder="Keterangan singkat standar/ruang lingkup template">{{ old('description', $template?->description) }}</textarea>
    </div>
</div>