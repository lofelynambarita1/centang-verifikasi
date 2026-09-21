<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Verifikasi Audit ISPO')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <a class="app-brand" href="{{ route('dashboard') }}">
            <span class="app-brand__mark" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </span>
            <span class="app-brand__text">
                <strong>Centang</strong>
                <small>Verifikasi Audit ISPO</small>
            </span>
        </a>
        <p class="app-brand__sub">Permentan No. 33/2025 · Mode offline</p>

        <nav class="app-nav" aria-label="Navigasi utama">
            <a class="app-nav__link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a class="app-nav__link @if (request()->routeIs('companies.*') || request()->routeIs('audits.*')) active @endif" href="{{ route('companies.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 21h18"/>
                    <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                    <path d="M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1"/>
                </svg>
                <span>Perusahaan</span>
            </a>
        </nav>

        <div class="app-sidebar__foot">
            <p class="app-sidebar__note">Data verifikasi tersimpan aman di perangkat ini.</p>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div>
                <p class="app-eyebrow">@yield('eyebrow', 'VERIFIKASI AUDIT ISPO')</p>
                <h1>@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="app-topbar__actions">
                @yield('actions')
            </div>
        </header>

        <main class="app-content">
            @if (session('success'))
                <div class="alert alert--success" role="status">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>