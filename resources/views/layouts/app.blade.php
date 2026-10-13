<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Verifikasi ISPO')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://absen-gis-group.id/uploads/company_logo_1777657909.png?v=1777657909">
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Modern CSS Variables & Reset Extensions */
        :root {
            --color-primary: #059669;
            --color-primary-hover: #047857;
            --color-primary-light: #ecfdf5;
            --color-surface: #ffffff;
            --color-background: #f8fafc;
            --color-border: #e2e8f0;
            --color-text-main: #0f172a;
            --color-text-muted: #64748b;
            --color-danger: #dc2626;
            --color-danger-hover: #b91c1c;
            --color-danger-light: #fef2f2;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition-fast: 0.15s ease-in-out;
            --sidebar-width: 260px;
        }

        /* Accessibility Skip Link */
        .skip-link {
            position: absolute;
            top: -100px;
            left: 1rem;
            background: var(--color-primary);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            z-index: 9999;
            text-decoration: none;
            font-weight: 500;
            transition: top var(--transition-fast);
        }
        .skip-link:focus {
            top: 1rem;
        }

        /* Base Shell Layout */
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--color-background);
            color: var(--color-text-main);
            -webkit-font-smoothing: antialiased;
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .app-sidebar {
            width: var(--sidebar-width);
            background-color: var(--color-surface);
            border-right: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            transition: transform 0.25s ease-in-out;
        }

        .app-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            text-decoration: none;
            color: var(--color-text-main);
            border-bottom: 1px solid var(--color-border);
        }

        .app-brand__mark img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            border-radius: 6px;
        }

        .app-brand__text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .app-brand__text strong {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-text-main);
        }

        .app-brand__text small {
            font-size: 0.75rem;
            color: var(--color-text-muted);
        }

        .app-nav {
            padding: 1rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
        }

        .app-nav__link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            color: var(--color-text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .app-nav__link svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .app-nav__link:hover {
            background-color: var(--color-background);
            color: var(--color-text-main);
        }

        .app-nav__link.active {
            background-color: var(--color-primary-light);
            color: var(--color-primary);
            font-weight: 600;
        }

        .app-sidebar__foot {
            padding: 1rem 0.75rem;
            border-top: 1px solid var(--color-border);
        }

        /* Main Content Container */
        .app-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.25s ease-in-out;
        }

        /* Topbar Header */
        .app-topbar {
            background-color: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .app-topbar__left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .app-eyebrow {
            margin: 0;
            font-size: 0.70rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--color-primary);
            text-transform: uppercase;
        }

        .app-topbar h1 {
            margin: 0.125rem 0 0 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-text-main);
            line-height: 1.3;
        }

        .app-topbar__actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* User Chip */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.375rem 0.75rem 0.375rem 0.375rem;
            background-color: var(--color-background);
            border: 1px solid var(--color-border);
            border-radius: 9999px;
        }

        .user-chip__avatar {
            width: 32px;
            height: 32px;
            background-color: var(--color-primary);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .user-chip__text {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.15;
        }

        .user-chip__text strong {
            font-size: 0.8125rem;
            color: var(--color-text-main);
        }

        .user-chip__text span {
            font-size: 0.70rem;
            color: var(--color-text-muted);
        }

        /* Mobile Sidebar Toggle Button */
        .mobile-nav-toggle {
            display: none;
            background: none;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 0.5rem;
            color: var(--color-text-main);
            cursor: pointer;
        }

        .mobile-nav-toggle svg {
            width: 20px;
            height: 20px;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 35;
        }

        /* Content Area */
        .app-content {
            padding: 1.5rem;
            flex: 1;
        }

        /* Alert Box */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert--success {
            background-color: var(--color-primary-light);
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert__close {
            background: none;
            border: none;
            color: currentColor;
            cursor: pointer;
            opacity: 0.7;
            padding: 0.25rem;
        }

        .alert__close:hover {
            opacity: 1;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
        }

        .btn--ghost {
            background-color: transparent;
            border-color: var(--color-border);
            color: var(--color-text-main);
        }

        .btn--ghost:hover {
            background-color: var(--color-background);
        }

        .btn--danger {
            background-color: var(--color-danger);
            color: #ffffff;
        }

        .btn--danger:hover {
            background-color: var(--color-danger-hover);
        }

        /* Modal Dialog Styling */
        .logout-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .logout-modal[hidden] {
            display: none !important;
        }

        .logout-modal__backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
        }

        .logout-modal__dialog {
            position: relative;
            background-color: var(--color-surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 10;
        }

        .logout-modal__icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: var(--color-danger-light);
            color: var(--color-danger);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-modal__icon svg {
            width: 22px;
            height: 22px;
        }

        .logout-modal__dialog h2 {
            margin: 0 0 0.25rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .logout-modal__dialog p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }

        .logout-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .app-sidebar {
                transform: translateX(-100%);
            }

            .app-sidebar.is-open {
                transform: translateX(0);
            }

            .sidebar-backdrop.is-open {
                display: block;
            }

            .app-main {
                margin-left: 0;
            }

            .mobile-nav-toggle {
                display: inline-flex;
            }

            .user-chip__text {
                display: none;
            }

            .app-topbar {
                padding: 0.875rem 1rem;
            }

            .app-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Skip Link untuk Accessibility -->
<a href="#main-content" class="skip-link">Lompati ke konten utama</a>

<!-- Backdrop Sidebar Mobile -->
<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

<div class="app-shell">
    <!-- Sidebar Navigation -->
    <aside class="app-sidebar" id="app-sidebar" aria-label="Sidebar Navigasi">
        <a class="app-brand" href="{{ route('audit-projects.index') }}">
            <span class="app-brand__mark">
                <img src="https://absen-gis-group.id/uploads/company_logo_1777657909.png?v=1777657909" alt="Logo GIS Group">
            </span>
            <span class="app-brand__text">
                <strong>Verifikasi ISPO</strong>
                <small>Sistem Verifikasi ISPO</small>
            </span>
        </a>

        <nav class="app-nav" aria-label="Navigasi utama">
            <a class="app-nav__link @if (request()->routeIs('audit-projects.*')) active @endif" href="{{ route('audit-projects.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 2v6h6"/>
                    <path d="M16 13H8M16 17H8M10 9H8"/>
                </svg>
                <span>Project Audit</span>
            </a>

            @can('manage-templates')
            <a class="app-nav__link @if (request()->routeIs('templates.*')) active @endif" href="{{ route('templates.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M8 7h8M8 12h8M8 17h5"/>
                </svg>
                <span>Master ISPO</span>
            </a>
            @endcan

            @can('manage-audit-projects')
            <a class="app-nav__link @if (request()->routeIs('dashboard') || request()->routeIs('companies.*') || request()->routeIs('audits.*')) active @endif" href="{{ route('companies.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 21h18"/>
                    <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                    <path d="M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1"/>
                </svg>
                <span>Perusahaan</span>
            </a>
            @endcan
        </nav>

        <div class="app-sidebar__foot">
            @auth
                <div class="app-nav" style="margin-top: 0; padding: 0;">
                    <form id="logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="app-nav__link" type="button" data-logout-trigger aria-haspopup="dialog" style="background:none; border:0; width:100%; text-align:left;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <path d="M16 17l5-5-5-5M21 12H9"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="app-main">
        <header class="app-topbar">
            <div class="app-topbar__left">
                <!-- Hamburger Button (Mobile) -->
                <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-label="Buka Navigasi" aria-expanded="false" aria-controls="app-sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                
                <div>
                    <p class="app-eyebrow">@yield('eyebrow', 'VERIFIKASI AUDIT ISPO')</p>
                    <h1>@yield('heading', 'Dashboard')</h1>
                </div>
            </div>

            <div class="app-topbar__actions">
                @auth
                    <div class="user-chip">
                        <span class="user-chip__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="user-chip__text">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>@yield('project-role', auth()->user()->system_role->label())</span>
                        </span>
                    </div>
                @endauth
                @yield('actions')
            </div>
        </header>

        <main class="app-content" id="main-content" tabindex="-1">
            @if (session('success'))
                <div class="alert alert--success" role="status">
                    <span>{{ session('success') }}</span>
                    <button type="button" class="alert__close" onclick="this.parentElement.remove()" aria-label="Tutup notifikasi">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="logout-modal" data-logout-modal hidden>
    <div class="logout-modal__backdrop" data-logout-close tabindex="-1"></div>
    <section
        class="logout-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-modal-title"
        aria-describedby="logout-modal-message"
        tabindex="-1"
    >
        <div class="logout-modal__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <path d="m16 17 5-5-5-5M21 12H9"/>
            </svg>
        </div>
        <div>
            <h2 id="logout-modal-title">Keluar dari aplikasi?</h2>
            <p id="logout-modal-message">Anda yakin ingin keluar dari Verifikasi ISPO?</p>
        </div>
        <div class="logout-modal__actions">
            <button class="btn btn--ghost" type="button" data-logout-close>Batal</button>
            <button class="btn btn--danger" type="button" data-logout-confirm>Ya, Keluar</button>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle Mobile Navigation Toggle
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const toggleBtn = document.getElementById('mobile-nav-toggle');

        function toggleSidebar(show) {
            const isOpen = show !== undefined ? show : !sidebar.classList.contains('is-open');
            sidebar.classList.toggle('is-open', isOpen);
            backdrop.classList.toggle('is-open', isOpen);
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', isOpen);
            }
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => toggleSidebar());
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => toggleSidebar(false));
        }

        // Handle Logout Modal Interaction
        const logoutModal = document.querySelector('[data-logout-modal]');
        const logoutTriggers = document.querySelectorAll('[data-logout-trigger]');
        const logoutCloseBtns = document.querySelectorAll('[data-logout-close]');
        const logoutConfirmBtn = document.querySelector('[data-logout-confirm]');
        const logoutForm = document.getElementById('logout-form');

        function openLogoutModal() {
            if (logoutModal) {
                logoutModal.removeAttribute('hidden');
                const dialog = logoutModal.querySelector('.logout-modal__dialog');
                if (dialog) dialog.focus();
            }
        }

        function closeLogoutModal() {
            if (logoutModal) {
                logoutModal.setAttribute('hidden', '');
            }
        }

        logoutTriggers.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openLogoutModal();
            });
        });

        logoutCloseBtns.forEach(btn => {
            btn.addEventListener('click', closeLogoutModal);
        });

        if (logoutConfirmBtn && logoutForm) {
            logoutConfirmBtn.addEventListener('click', function () {
                logoutForm.submit();
            });
        }

        // Close Modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && logoutModal && !logoutModal.hasAttribute('hidden')) {
                closeLogoutModal();
            }
        });
    });

    // Page Reload Handling on Back/Forward Navigation
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>

@stack('scripts')
</body>
</html>