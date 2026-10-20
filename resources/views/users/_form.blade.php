@if ($errors->any())
    <div class="form-error" role="alert">{{ $errors->first() }}</div>
@endif
<div class="form-grid">
    <div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $user?->name) }}" required></div>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required></div>
    <div class="field"><label for="role">Role</label><select id="role" name="role" required><option value="admin" @selected(old('role', $user?->requested_role ?? $user?->role) === 'admin')>Master Admin</option><option value="lead_audit" @selected(old('role', $user?->requested_role ?? $user?->role) === 'lead_audit')>Lead Auditor</option><option value="auditor" @selected(old('role', $user?->requested_role ?? $user?->role) === 'auditor')>Auditor</option></select></div>
    <div class="field"><label for="password">Password {{ $user ? '(kosongkan jika tidak diubah)' : '' }}</label><input id="password" type="password" name="password" {{ $user ? '' : 'required' }}></div>
    <div class="field"><label for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" type="password" name="password_confirmation" {{ $user ? '' : 'required' }}></div>
</div>