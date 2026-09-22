<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Masuk | Collaborative Audit Report')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-page">
    <div class="guest-shell">
        <div class="guest-card">
            <div class="guest-brand">
                <span class="app-brand__mark" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                </span>
                <strong>Centang Verifikasi</strong>
                <small>Collaborative Audit Report System</small>
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