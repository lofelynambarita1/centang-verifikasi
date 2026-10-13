{{-- Pesan Error Umum/Global --}}
@if ($errors->any())
    <div class="alert alert--danger" role="alert" style="margin-bottom: 1.5rem; padding: 0.875rem 1rem; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 0.375rem; color: #991b1b; font-size: 0.875rem;">
        <strong>Terjadi kesalahan input:</strong>
        <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    // Helper closure untuk mengambil nilai default old() atau $entity
    $val = function (string $key, $default = '') use ($entity) {
        return old($key, $entity?->{$key} ?? $default);
    };
@endphp

<style>
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

    .field label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
    }

    .field input[type="text"],
    .field input[type="number"],
    .field select,
    .field textarea {
        width: 100%;
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        background-color: #ffffff;
        color: #0f172a;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        outline: none;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
    }

    .field input.is-invalid,
    .field select.is-invalid,
    .field textarea.is-invalid {
        border-color: #dc2626;
    }

    .field-error {
        font-size: 0.75rem;
        color: #dc2626;
        margin-top: 0.125rem;
    }

    /* Context Banner */
    .structure-context {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .structure-context span {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    .structure-context strong {
        font-size: 0.9375rem;
        color: #0f172a;
    }
</style>

{{-- ========================================================================= --}}
{{-- 1. PRINSIP ISPO --}}
{{-- ========================================================================= --}}
@if ($type === 'principle')
    <div class="form-grid">
        <div class="field">
            <label for="code">Kode Prinsip <span style="color: #dc2626;">*</span></label>
            <input id="code" name="code" value="{{ $val('code') }}" required placeholder="cth: 1" class="@error('code') is-invalid @enderror">
            @error('code') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="title">Nama Prinsip <span style="color: #dc2626;">*</span></label>
            <input id="title" name="title" value="{{ $val('title') }}" required placeholder="cth: Prinsip 1 — Manajemen dan komitmen" class="@error('title') is-invalid @enderror">
            @error('title') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field field--wide">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3" placeholder="Tambahkan deskripsi atau lingkup prinsip (opsional)" class="@error('description') is-invalid @enderror">{{ $val('description') }}</textarea>
            @error('description') <span class="field-error">{{ $message }}</span> @enderror
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 2. KRITERIA / SUB-KRITERIA --}}
{{-- ========================================================================= --}}
@elseif ($type === 'section')
    <div class="form-grid">
        {{-- Konteks Parent --}}
        @if ($entity)
            <div class="field field--wide structure-context">
                <span>Prinsip</span>
                <strong>P {{ $entity->principle->code }} — {{ $entity->principle->title }}</strong>
            </div>
            @if ($entity->parent)
                <div class="field field--wide structure-context">
                    <span>Kriteria Induk</span>
                    <strong>{{ $entity->parent->code }} — {{ $entity->parent->title }}</strong>
                </div>
            @endif
        @else
            <div class="field field--wide structure-context">
                <span>{{ $parentSection ? 'Kriteria Induk' : 'Prinsip Induk' }}</span>
                <strong>{{ $parentSection ? $parentSection->code.' — '.$parentSection->title : 'Prinsip '.$parentPrinciple->code.' — '.$parentPrinciple->title }}</strong>
            </div>
            <input type="hidden" name="principle_id" value="{{ $parentPrinciple->id }}">
            @if ($parentSection)
                <input type="hidden" name="parent_id" value="{{ $parentSection->id }}">
            @endif
        @endif

        <div class="field">
            <label for="code">{{ $isSubcriterion ? 'Kode Sub-kriteria' : 'Kode Kriteria' }} <span style="color: #dc2626;">*</span></label>
            <input id="code" name="code" value="{{ $val('code') }}" required placeholder="{{ $isSubcriterion ? 'cth: 2.1.1' : 'cth: 2.1' }}" class="@error('code') is-invalid @enderror">
            @error('code') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field field--wide">
            <label for="title">{{ $isSubcriterion ? 'Nama Sub-kriteria' : 'Nama Kriteria' }} <span style="color: #dc2626;">*</span></label>
            <input id="title" name="title" value="{{ $val('title') }}" required class="@error('title') is-invalid @enderror">
            @error('title') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field field--wide">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3" placeholder="Deskripsi atau rincian kriteria (opsional)" class="@error('description') is-invalid @enderror">{{ $val('description') }}</textarea>
            @error('description') <span class="field-error">{{ $message }}</span> @enderror
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 3. INDIKATOR --}}
{{-- ========================================================================= --}}
@elseif ($type === 'indicator')
    <div class="form-grid">
        @if ($entity)
            <div class="field field--wide structure-context">
                <span>Kriteria Induk</span>
                <strong>{{ $entity->section->code }} — {{ $entity->section->title }}</strong>
            </div>
        @else
            <div class="field field--wide structure-context">
                <span>Kriteria Induk</span>
                <strong>{{ $parentSection->code }} — {{ $parentSection->title }}</strong>
            </div>
            <input type="hidden" name="section_id" value="{{ $parentSection->id }}">
        @endif

        <div class="field">
            <label for="code">Kode Indikator <span style="color: #dc2626;">*</span></label>
            <input id="code" name="code" value="{{ $val('code') }}" required placeholder="cth: 1.1.1" class="@error('code') is-invalid @enderror">
            @error('code') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field field--wide">
            <label for="title">Nama Indikator <span style="color: #dc2626;">*</span></label>
            <input id="title" name="title" value="{{ $val('title') }}" required placeholder="cth: Indikator 1.1.1 — ..." class="@error('title') is-invalid @enderror">
            @error('title') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field field--wide">
            <label for="description">Deskripsi (Opsional)</label>
            <textarea id="description" name="description" rows="3" class="@error('description') is-invalid @enderror">{{ $val('description') }}</textarea>
            @error('description') <span class="field-error">{{ $message }}</span> @enderror
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 4. PARAMETER / VERIFIER --}}
{{-- ========================================================================= --}}
@elseif ($type === 'parameter')
    <div class="form-grid">
        @if ($entity)
            <div class="field field--wide structure-context">
                <span>Indikator Induk</span>
                <strong>{{ $entity->indicator->code }} — {{ $entity->indicator->title }}</strong>
            </div>
        @else
            <div class="field field--wide">
                <label for="indicator_id">Indikator <span style="color: #dc2626;">*</span></label>
                <select id="indicator_id" name="indicator_id" required class="@error('indicator_id') is-invalid @enderror">
                    <option value="">— Pilih Indikator —</option>
                    @foreach ($indicators as $indicator)
                        <option value="{{ $indicator->id }}" @selected((string) old('indicator_id', request('indicator_id')) === (string) $indicator->id)>
                            [P {{ $indicator->section->principle->code }} · {{ $indicator->section->code }}] {{ $indicator->code }} — {{ $indicator->title }}
                        </option>
                    @endforeach
                </select>
                @error('indicator_id') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="field">
            <label for="parameter_type">Jenis <span style="color: #dc2626;">*</span></label>
            <select id="parameter_type" name="parameter_type" required class="@error('parameter_type') is-invalid @enderror">
                <option value="parameter" @selected($val('parameter_type') === 'parameter')>Parameter</option>
                <option value="verifier" @selected($val('parameter_type') === 'verifier')>Verifier</option>
            </select>
            @error('parameter_type') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="number">Nomor (Opsional)</label>
            <input id="number" name="number" value="{{ $val('number') }}" placeholder="cth: 1, 2, atau 1a" class="@error('number') is-invalid @enderror">
            @error('number') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="sort_order">Urutan <span style="color: #dc2626;">*</span></label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $val('sort_order', 0) }}" required class="@error('sort_order') is-invalid @enderror">
            @error('sort_order') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="field field--wide">
            <label for="content">Isi Parameter / Verifier <span style="color: #dc2626;">*</span></label>
            <textarea id="content" name="content" rows="4" required placeholder="Tuliskan detail butir parameter atau bukti verifier..." class="@error('content') is-invalid @enderror">{{ $val('content') }}</textarea>
            @error('content') <span class="field-error">{{ $message }}</span> @enderror
        </div>
    </div>
@endif