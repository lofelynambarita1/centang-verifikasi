<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Masuk | Verifikasi ISPO')</title>
    <link rel="icon" type="image/png" href="https://absen-gis-group.id/uploads/company_logo_1777657909.png?v=1777657909">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-page">
    <div class="guest-shell">
        <div class="guest-card">
            <div class="guest-brand">
                <span class="app-brand__mark">
                    <img src="https://absen-gis-group.id/uploads/company_logo_1777657909.png?v=1777657909" alt="Logo perusahaan">
                </span>
                <strong>Verifikasi ISPO</strong>
                <small>Sistem Verifikasi ISPO</small>
            </div>

            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
 @stack('scripts')
</body>
</html>