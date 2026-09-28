<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Verifikasi Audit ISPO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
<main class="auth-card">
    <div class="app-brand__mark auth-mark" aria-hidden="true">✓</div>
    <p class="app-eyebrow">CENTANG · AUDIT ISPO</p>
    <h1>Masuk ke ruang audit</h1>
    <p class="auth-intro">Kelola penugasan prinsip dan proses verifikasi sesuai peran tim Anda.</p>
    @if ($errors->any())
        <div class="form-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form class="form auth-form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@contoh.com" autocomplete="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
        </div>
        <label class="check-control"><input type="checkbox" name="remember"> Ingat perangkat ini</label>
        <button class="btn btn--primary btn--block" type="submit">Masuk ke dashboard</button>
    </form>
</main>
</body>
</html>