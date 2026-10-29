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
            <img class="app-brand__logo" src="{{ asset('images/logogis.jpeg') }}" alt="Centang GIS logo" aria-hidden="true">
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
            <a class="app-nav__link @if (request()->routeIs('companies.*') || (request()->routeIs('audits.*') && !request()->routeIs('audits.assignments.*'))) active @endif" href="{{ route('companies.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 21h18"/>
                    <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                    <path d="M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1"/>
                </svg>
                <span>Perusahaan</span>
            </a>
            <a class="app-nav__link @if (request()->routeIs('notifications.*')) active @endif" href="{{ route('notifications.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                    <path d="M10 21h4"/>
                </svg>
                <span>Notifikasi</span>
                @if ($unreadNotificationCount > 0)
                    <span class="app-nav__badge">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                @endif
            </a>
            @if (auth()->user()?->canManageAssignments())
                <a class="app-nav__link @if (request()->routeIs('audits.assignments.*')) active @endif" href="{{ route('audits.assignments.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <path d="M9 3.5h6M8 9h8M8 13h8M8 17h5"/>
                    </svg>
                    <span>Penugasan auditor</span>
                </a>
            @endif
            @if (auth()->user()?->isAdmin())
                <a class="app-nav__link @if (request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span>Pengguna & role</span>
                </a>
            @endif
        </nav>

        <div class="app-sidebar__foot">
            <p class="app-sidebar__note">Data verifikasi tersimpan aman di perangkat ini.</p>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="app-sidebar__logout-form">
                    @csrf
                    <button class="app-sidebar__logout" type="submit">Keluar</button>
                </form>
            @endauth
        </div>
    </aside>

    <div class="app-main @yield('page-class')">
        <header class="app-topbar">
            <div>
                <p class="app-eyebrow">@yield('eyebrow', 'VERIFIKASI AUDIT ISPO')</p>
                <h1>@yield('heading', 'Dashboard')</h1>
            </div>
            @yield('actions')
        </header>

        <main class="app-content">
            @if (session('success'))
                <div class="alert alert--success" role="status">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

@auth
    <div class="profile-menu" data-profile-menu>
        <button class="profile-pill" type="button" aria-expanded="false" aria-controls="profile-dropdown" aria-label="Profil pengguna">
            <span class="profile-pill__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <div class="profile-pill__meta">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ str_replace('_', ' ', ucfirst(auth()->user()->role)) }}</span>
            </div>
        </button>

        <div class="profile-dropdown" id="profile-dropdown" hidden>
            <a href="{{ route('dashboard') }}" class="profile-dropdown__item">Masuk</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="profile-dropdown__item profile-dropdown__item--danger">Keluar</button>
            </form>
        </div>
    </div>
@endauth

@stack('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menus = document.querySelectorAll('[data-profile-menu]');
        menus.forEach((menu) => {
            const trigger = menu.querySelector('.profile-pill');
            const panel = menu.querySelector('.profile-dropdown');
            if (!trigger || !panel) return;

            trigger.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = !panel.hidden;
                menus.forEach((other) => {
                    const otherPanel = other.querySelector('.profile-dropdown');
                    const otherTrigger = other.querySelector('.profile-pill');
                    if (other !== menu && otherPanel && otherTrigger) {
                        otherPanel.hidden = true;
                        otherTrigger.setAttribute('aria-expanded', 'false');
                    }
                });

                panel.hidden = isOpen;
                trigger.setAttribute('aria-expanded', String(!isOpen));
            });

            document.addEventListener('click', function (event) {
                if (!menu.contains(event.target)) {
                    panel.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
</script>
</body>
</html>