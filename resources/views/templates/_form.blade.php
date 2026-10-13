<style>
    /* Alert Notification */
    .alert-error {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        color: #991b1b;
        font-size: 0.875rem;
    }

    .alert-error-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        margin-bottom: 0.375rem;
    }

    .alert-error-list {
        margin: 0;
        padding-left: 1.25rem;
    }

    .alert-error-list li {
        margin-bottom: 0.25rem;
    }

    .alert-error-list li:last-child {
        margin-bottom: 0;
    }

    /* Form Grid Layout */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }

    .field--wide {
        grid-column: span 2 / span 2;
    }

    /* Labels & Required Star */
    .field-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
    }

    .required-star {
        color: #ef4444;
        margin-left: 0.125rem;
    }

    /* Inputs, Select & Textarea */
    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        color: #0f172a;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        transition: all 0.15s ease-in-out;
        box-sizing: border-box;
        font-family: inherit;
    }

    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.25em 1.25em;
        padding-right: 2.5rem;
    }

    .form-input::placeholder,
    .form-textarea::placeholder {
        color: #94a3b8;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
    }

    /* Field Error State */
    .form-input.has-error,
    .form-select.has-error,
    .form-textarea.has-error {
        border-color: #ef4444;
        background-color: #fff5f5;
    }

    .form-input.has-error:focus,
    .form-select.has-error:focus,
    .form-textarea.has-error:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }

    .field-error-text {
        font-size: 0.75rem;
        color: #dc2626;
        margin-top: 0.125rem;
    }

    /* Responsive Grid */
    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .field--wide {
            grid-column: span 1 / span 1;
        }
    }
</style>

{{-- Banner Pesan Error Keseluruhan --}}
@if ($errors->any())
    <div class="alert-error" role="alert">
        <div class="alert-error-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>Terdapat kesalahan pada input form:</span>
        </div>
        <ul class="alert-error-list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-grid">
    {{-- Kode Acuan --}}
    <div class="field">
        <label class="field-label" for="code">
            Kode Acuan <span class="required-star">*</span>
        </label>
        <input 
            id="code" 
            type="text" 
            name="code" 
            class="form-input @error('code') has-error @enderror" 
            value="{{ old('code', $template?->code) }}" 
            placeholder="cth: FrO.8603/GIS-1.1" 
            required
            @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
        >
        @error('code')
            <span class="field-error-text" id="code-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Scheme --}}
    <div class="field">
        <label class="field-label" for="scheme">
            Scheme <span class="required-star">*</span>
        </label>
        <input 
            id="scheme" 
            type="text" 
            name="scheme" 
            class="form-input @error('scheme') has-error @enderror" 
            value="{{ old('scheme', $template?->scheme) }}" 
            placeholder="cth: ISPO" 
            required
            @error('scheme') aria-invalid="true" aria-describedby="scheme-error" @enderror
        >
        @error('scheme')
            <span class="field-error-text" id="scheme-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Nama Acuan --}}
    <div class="field">
        <label class="field-label" for="name">
            Nama Acuan <span class="required-star">*</span>
        </label>
        <input 
            id="name" 
            type="text" 
            name="name" 
            class="form-input @error('name') has-error @enderror" 
            value="{{ old('name', $template?->name) }}" 
            placeholder="cth: Laporan Verifikasi Hasil ISPO" 
            required
            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        >
        @error('name')
            <span class="field-error-text" id="name-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Versi --}}
    <div class="field">
        <label class="field-label" for="version">Versi</label>
        <input 
            id="version" 
            type="text" 
            name="version" 
            class="form-input @error('version') has-error @enderror" 
            value="{{ old('version', $template?->version) }}" 
            placeholder="cth: 1.1"
            @error('version') aria-invalid="true" aria-describedby="version-error" @enderror
        >
        @error('version')
            <span class="field-error-text" id="version-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Status --}}
    <div class="field">
        <label class="field-label" for="is_active">
            Status <span class="required-star">*</span>
        </label>
        <select 
            id="is_active" 
            name="is_active" 
            class="form-select @error('is_active') has-error @enderror" 
            required
            @error('is_active') aria-invalid="true" aria-describedby="is_active-error" @enderror
        >
            <option value="1" @selected((int) old('is_active', $template?->is_active) === 1)>Aktif</option>
            <option value="0" @selected((int) old('is_active', $template?->is_active) === 0)>Nonaktif</option>
        </select>
        @error('is_active')
            <span class="field-error-text" id="is_active-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Deskripsi --}}
    <div class="field field--wide">
        <label class="field-label" for="description">Deskripsi</label>
        <textarea 
            id="description" 
            name="description" 
            rows="3" 
            class="form-textarea @error('description') has-error @enderror" 
            placeholder="Keterangan singkat standar/ruang lingkup template"
            @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
        >{{ old('description', $template?->description) }}</textarea>
        @error('description')
            <span class="field-error-text" id="description-error">{{ $message }}</span>
        @enderror
    </div>
</div>