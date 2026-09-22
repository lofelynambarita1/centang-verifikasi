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
        <a class="app-brand" href="{{ route('audit-projects.index') }}">
            <span class="app-brand__mark" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </span>
            <span class="app-brand__text">
                <strong>Centang</strong>
                <small>Collaborative Audit Report</small>
            </span>
        </a>
        <p class="app-brand__sub">Collaborative Audit Report System · Tahap 1</p>

        <nav class="app-nav" aria-label="Navigasi utama">
            <a class="app-nav__link @if (request()->routeIs('audit-projects.*')) active @endif" href="{{ route('audit-projects.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 2v6h6"/>
                    <path d="M16 13H8M16 17H8M10 9H8"/>
                </svg>
                <span>Project Audit</span>
            </a>
            <a class="app-nav__link @if (request()->routeIs('templates.*')) active @endif" href="{{ route('templates.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M8 7h8M8 12h8M8 17h5"/>
                </svg>
                <span>Master Template</span>
            </a>
            <a class="app-nav__link @if (request()->routeIs('dashboard') || request()->routeIs('companies.*') || request()->routeIs('audits.*')) active @endif" href="{{ route('companies.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 21h18"/>
                    <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                    <path d="M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1"/>
                </svg>
                <span>Perusahaan (legacy)</span>
            </a>
        </nav>

        <div class="app-sidebar__foot">
            @auth
                <div class="app-nav" style="margin-top: 0;">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="app-nav__link" type="submit" style="background:none;border:0;width:100%;text-align:left;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <path d="M16 17l5-5-5-5M21 12H9"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            @endauth
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
                @auth
                    <div class="user-chip">
                        <span class="user-chip__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="user-chip__text">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->system_role->label() }}</span>
                        </span>
                    </div>
                @endauth
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