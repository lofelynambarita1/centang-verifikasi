@if($errors->any())<div class="finding-note"><span>!</span><p>{{ $errors->first() }}</p></div>@endif
<div class="form-grid">
	<label>Nama perusahaan<input name="name" value="{{ old('name', $company?->name) }}" required></label>
	<label>Bidang usaha<input name="industry" value="{{ old('industry', $company?->industry) }}"></label>
	<label class="form-field-wide">Alamat<textarea name="address" rows="3">{{ old('address', $company?->address) }}</textarea></label>
	<label>Nama PIC<input name="contact_name" value="{{ old('contact_name', $company?->contact_name) }}"></label>
	<label>Email PIC<input type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}"></label>
	<label>Deadline audit<input type="date" name="due_date" value="{{ old('due_date', $company?->audits->first()?->due_date?->format('Y-m-d')) }}"></label>
</div>
