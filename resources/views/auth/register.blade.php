<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan akun | Verifikasi Audit ISPO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
<main class="auth-card">
    <img class="auth-logo" src="{{ asset('images/logogis.jpeg') }}" alt="Logo GIS">
    <h1>Ajukan akun</h1>
    <p class="auth-intro">Buat satu akun untuk digunakan saat mengerjakan audit di beberapa perusahaan.</p>
    @if ($errors->any())
        <div class="form-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form class="form auth-form" method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="field">
            <label for="name">Nama lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nama lengkap" autocomplete="name" required autofocus>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@contoh.com" autocomplete="email" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Konfirmasi password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi password" autocomplete="new-password" minlength="8" required>
        </div>
        <p class="auth-role-note"><strong>Persetujuan Master Admin diperlukan.</strong> Master Admin akan mengaktifkan akun dan menetapkan role. Akun yang sama dapat digunakan kembali untuk audit PT lain.</p>
        <button class="btn btn--primary btn--block" type="submit">Kirim pendaftaran akun</button>
    </form>
    <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</main>
</body>
</html>