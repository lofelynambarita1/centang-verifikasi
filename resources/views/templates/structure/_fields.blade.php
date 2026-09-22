@if ($errors->any())
    <div class="form-error" role="alert">{{ $errors->first() }}</div>
@endif

@php
    $val = function (string $key, $default = '') use ($entity) {
        return old($key, $entity?->{$key} ?? $default);
    };
@endphp

@if ($type === 'principle')
    <div class="form-grid">
        <div class="field">
            <label for="code">Kode prinsip</label>
            <input id="code" name="code" value="{{ $val('code') }}" required placeholder="cth: 1">
        </div>
        <div class="field">
            <label for="sort_order">Urutan</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $val('sort_order', 0) }}">
        </div>
        <div class="field field--wide">
            <label for="title">Judul prinsip</label>
            <input id="title" name="title" value="{{ $val('title') }}" required placeholder="cth: Prinsip 1 — Manajemen dan komitmen">
        </div>
        <div class="field field--wide">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3">{{ $val('description') }}</textarea>
        </div>
    </div>

@elseif ($type === 'section')
    <div class="form-grid">
        @if ($entity)
            <div class="field field--wide structure-context">
                <span>Prinsip</span>
                <strong>P {{ $entity->principle->code }} — {{ $entity->principle->title }}</strong>
            </div>
            @if ($entity->parent)
                <div class="field field--wide structure-context">
                    <span>Kriteria induk</span>
                    <strong>{{ $entity->parent->code }} — {{ $entity->parent->title }}</strong>
                </div>
            @endif
        @else
            <div class="field">
                <label for="principle_id">Prinsip</label>
                <select id="principle_id" name="principle_id" required>
                    <option value="">— Pilih prinsip —</option>
                    @foreach ($principles as $principle)
                        <option value="{{ $principle->id }}" @selected((string) old('principle_id', request('principle_id')) === (string) $principle->id)>
                            P {{ $principle->code }} — {{ $principle->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="parent_id">Kriteria induk (opsional)</label>
                <select id="parent_id" name="parent_id">
                    <option value="">— Tidak ada (top level) —</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((string) old('parent_id', request('parent_id')) === (string) $section->id)>
                            [P {{ $section->principle->code }}] {{ $section->code }} — {{ $section->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="field">
            <label for="code">Kode kriteria</label>
            <input id="code" name="code" value="{{ $val('code') }}" required placeholder="cth: 1.1">
        </div>
        <div class="field">
            <label for="sort_order">Urutan</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $val('sort_order', 0) }}">
        </div>
        <div class="field field--wide">
            <label for="title">Judul kriteria</label>
            <input id="title" name="title" value="{{ $val('title') }}" required placeholder="cth: Kriteria 1.1 — Manajemen dan komitmen">
        </div>
        <div class="field field--wide">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3">{{ $val('description') }}</textarea>
        </div>
    </div>

@elseif ($type === 'indicator')
    <div class="form-grid">
        @if ($entity)
            <div class="field field--wide structure-context">
                <span>Kriteria</span>
                <strong>{{ $entity->section->code }} — {{ $entity->section->title }}</strong>
            </div>
        @else
            <div class="field field--wide">
                <label for="section_id">Kriteria</label>
                <select id="section_id" name="section_id" required>
                    <option value="">— Pilih kriteria —</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((string) old('section_id', request('section_id')) === (string) $section->id)>
                            [P {{ $section->principle->code }}] {{ $section->code }} — {{ $section->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="field">
            <label for="code">Kode indikator</label>
            <input id="code" name="code" value="{{ $val('code') }}" required placeholder="cth: 1.1.1">
        </div>
        <div class="field">
            <label for="indicator_type">Tipe indikator</label>
            <input id="indicator_type" name="indicator_type" value="{{ $val('indicator_type') }}" placeholder="cth: Tunggal / Ganda">
        </div>
        <div class="field">
            <label for="sort_order">Urutan</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $val('sort_order', 0) }}">
        </div>
        <div class="field field--wide">
            <label for="title">Judul indikator</label>
            <input id="title" name="title" value="{{ $val('title') }}" required placeholder="cth: Indikator 1.1.1 — ...">
        </div>
        <div class="field field--wide">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3">{{ $val('description') }}</textarea>
        </div>
    </div>

@elseif ($type === 'parameter')
    <div class="form-grid">
        @if ($entity)
            <div class="field field--wide structure-context">
                <span>Indikator</span>
                <strong>{{ $entity->indicator->code }} — {{ $entity->indicator->title }}</strong>
            </div>
        @else
            <div class="field field--wide">
                <label for="indicator_id">Indikator</label>
                <select id="indicator_id" name="indicator_id" required>
                    <option value="">— Pilih indikator —</option>
                    @foreach ($indicators as $indicator)
                        <option value="{{ $indicator->id }}" @selected((string) old('indicator_id', request('indicator_id')) === (string) $indicator->id)>
                            [P {{ $indicator->section->principle->code }} · {{ $indicator->section->code }}] {{ $indicator->code }} — {{ $indicator->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="field">
            <label for="parameter_type">Jenis</label>
            <select id="parameter_type" name="parameter_type" required>
                <option value="parameter" @selected($val('parameter_type') === 'parameter')>Parameter</option>
                <option value="verifier" @selected($val('parameter_type') === 'verifier')>Verifier</option>
            </select>
        </div>
        <div class="field">
            <label for="number">Nomor (opsional)</label>
            <input id="number" name="number" value="{{ $val('number') }}" placeholder="cth: 1, 2, atau 1a">
        </div>
        <div class="field">
            <label for="sort_order">Urutan</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $val('sort_order', 0) }}">
        </div>
        <div class="field field--wide">
            <label for="content">Isi</label>
            <textarea id="content" name="content" rows="3" required>{{ $val('content') }}</textarea>
        </div>
    </div>
@endif