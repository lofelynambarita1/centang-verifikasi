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

    /* Field Labels & Required Star */
    .field-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
    }

    .required-star {
        color: #ef4444;
        margin-left: 0.125rem;
    }

    /* Inputs & Textarea */
    .form-input,
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

    .form-input::placeholder,
    .form-textarea::placeholder {
        color: #94a3b8;
    }

    .form-input:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
    }

    /* Field Specific Error State */
    .form-input.has-error,
    .form-textarea.has-error {
        border-color: #ef4444;
        background-color: #fff5f5;
    }

    .form-input.has-error:focus,
    .form-textarea.has-error:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }

    .field-error-text {
        font-size: 0.75rem;
        color: #dc2626;
        margin-top: 0.125rem;
    }

    /* Responsive Grid Switch */
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
    {{-- Nama Perusahaan --}}
    <div class="field">
        <label class="field-label" for="name">
            Nama Perusahaan <span class="required-star">*</span>
        </label>
        <input 
            id="name" 
            type="text" 
            name="name" 
            class="form-input @error('name') has-error @enderror" 
            value="{{ old('name', $company?->name) }}" 
            placeholder="Contoh: PT Sawit Jaya Abadi"
            required
            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        >
        @error('name')
            <span class="field-error-text" id="name-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Bidang Usaha --}}
    <div class="field">
        <label class="field-label" for="industry">Bidang Usaha</label>
        <input 
            id="industry" 
            type="text" 
            name="industry" 
            class="form-input @error('industry') has-error @enderror" 
            value="{{ old('industry', $company?->industry) }}" 
            placeholder="Contoh: Perkebunan & Kelapa Sawit"
            @error('industry') aria-invalid="true" aria-describedby="industry-error" @enderror
        >
        @error('industry')
            <span class="field-error-text" id="industry-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Alamat --}}
    <div class="field field--wide">
        <label class="field-label" for="address">Alamat Perusahaan</label>
        <textarea 
            id="address" 
            name="address" 
            rows="3" 
            class="form-textarea @error('address') has-error @enderror" 
            placeholder="Tuliskan alamat lengkap kantor atau lokasi operasional..."
            @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
        >{{ old('address', $company?->address) }}</textarea>
        @error('address')
            <span class="field-error-text" id="address-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Nama PIC --}}
    <div class="field">
        <label class="field-label" for="contact_name">Nama PIC (Person in Charge)</label>
        <input 
            id="contact_name" 
            type="text" 
            name="contact_name" 
            class="form-input @error('contact_name') has-error @enderror" 
            value="{{ old('contact_name', $company?->contact_name) }}" 
            placeholder="Contoh: Budi Santoso"
            @error('contact_name') aria-invalid="true" aria-describedby="contact_name-error" @enderror
        >
        @error('contact_name')
            <span class="field-error-text" id="contact_name-error">{{ $message }}</span>
        @enderror
    </div>

    {{-- Email PIC --}}
    <div class="field">
        <label class="field-label" for="contact_email">Email PIC</label>
        <input 
            id="contact_email" 
            type="email" 
            name="contact_email" 
            class="form-input @error('contact_email') has-error @enderror" 
            value="{{ old('contact_email', $company?->contact_email) }}" 
            placeholder="Contoh: pic@perusahaan.com"
            @error('contact_email') aria-invalid="true" aria-describedby="contact_email-error" @enderror
        >
        @error('contact_email')
            <span class="field-error-text" id="contact_email-error">{{ $message }}</span>
        @enderror
    </div>
</div>