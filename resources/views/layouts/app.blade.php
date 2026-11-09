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
            @if (auth()->user()?->canManageAssignments() || auth()->user()?->isAuditor())
                <details class="app-nav-group" @if (request()->routeIs('certification-process.*')) open @endif>
                    <summary class="app-nav__link app-nav-group__trigger @if (request()->routeIs('certification-process.*')) active @endif" aria-label="Proses Sertifikasi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 3h10l4 4v14H3V3h4z"/>
                            <path d="M7 3v5h10V3M7 14h10M7 18h6"/>
                        </svg>
                        <span>Proses Sertifikasi</span>
                        <svg class="app-nav-group__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </summary>
                    <div class="app-nav-group__items">
                        @foreach (\App\Enums\CertificationCategory::cases() as $category)
                            <a class="app-nav__link app-nav-group__item @if (request()->route('category') === $category->value) active @endif" href="{{ route('certification-process.index', $category->value) }}">
                                <span>{{ $category->label() }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
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
            <div class="app-topbar__controls">
                <div class="app-topbar__actions">@yield('actions')</div>
                @auth
                    <div class="app-notification-menu" data-notification-menu>
                        <button
                            class="app-notification-trigger"
                            type="button"
                            data-menu-trigger
                            aria-expanded="false"
                            aria-controls="app-notification-dropdown"
                            aria-label="Notifikasi{{ $unreadNotificationCount > 0 ? ', '.$unreadNotificationCount.' belum dibaca' : '' }}"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                                <path d="M10 21h4"/>
                            </svg>
                            @if ($unreadNotificationCount > 0)
                                <span class="app-notification__badge">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                            @endif
                        </button>

                        <section class="app-notification-dropdown" id="app-notification-dropdown" data-menu-panel hidden aria-label="Notifikasi terbaru">
                            <header class="app-notification-dropdown__header">
                                <div>
                                    <strong>Notifikasi</strong>
                                    <span>{{ $unreadNotificationCount }} belum dibaca</span>
                                </div>
                                @if ($unreadNotificationCount > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit">Tandai dibaca</button>
                                    </form>
                                @endif
                            </header>

                            <div class="app-notification-dropdown__list">
                                @forelse ($recentNotifications as $notification)
                                    <form class="app-notification-entry {{ $notification->read_at ? '' : 'app-notification-entry--unread' }}" method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button class="app-notification-entry__button" type="submit">
                                            <span class="app-notification-entry__dot" aria-hidden="true"></span>
                                            <span class="app-notification-entry__content">
                                                <strong>{{ $notification->data['title'] ?? 'Pemberitahuan' }}</strong>
                                                <span>{{ $notification->data['message'] ?? '' }}</span>
                                                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->locale('id')->translatedFormat('d M, H:i') }}</time>
                                            </span>
                                        </button>
                                    </form>
                                @empty
                                    <p class="app-notification-dropdown__empty">Belum ada notifikasi baru.</p>
                                @endforelse
                            </div>

                            <a class="app-notification-dropdown__footer" href="{{ route('notifications.index') }}">Lihat semua aktivitas</a>
                        </section>
                    </div>

                    <div class="profile-menu" data-profile-menu>
                        <button class="profile-pill" type="button" data-menu-trigger aria-expanded="false" aria-controls="profile-dropdown" aria-label="Profil pengguna">
                            <span class="profile-pill__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div class="profile-pill__meta">
                                <strong>{{ auth()->user()->name }}</strong>
                                <span>{{ str_replace('_', ' ', ucfirst(auth()->user()->role)) }}</span>
                            </div>
                        </button>

                        <div class="profile-dropdown" id="profile-dropdown" data-menu-panel hidden>
                            <a href="{{ route('dashboard') }}" class="profile-dropdown__item">Masuk</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="profile-dropdown__item profile-dropdown__item--danger">Keluar</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        <main class="app-content">
            @yield('content')
        </main>
    </div>
</div>

<div class="toast-stack" aria-label="Notifikasi sistem">
    @if (session('success'))
        <div class="app-toast app-toast--success" data-app-toast data-toast-duration="8000" role="status">
            <span class="app-toast__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </span>
            <div class="app-toast__body">
                <strong>Aktivitas berhasil</strong>
                <p>{{ session('success') }}</p>
            </div>
            <button class="app-toast__close" type="button" data-toast-dismiss aria-label="Tutup notifikasi">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @foreach ($deadlineReminders as $reminder)
        <div
            class="app-toast {{ $reminder['overdue'] ? 'app-toast--overdue' : 'app-toast--deadline' }}"
            data-app-toast
            data-toast-key="{{ $reminder['key'] }}"
            data-toast-duration="{{ $reminder['overdue'] ? 14000 : 10000 }}"
            role="{{ $reminder['overdue'] ? 'alert' : 'status' }}"
        >
            <span class="app-toast__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4l2.5 2.5"/>
                    <circle cx="12" cy="12" r="9"/>
                </svg>
            </span>
            <div class="app-toast__body">
                <span class="app-toast__eyebrow">{{ $reminder['overdue'] ? 'TENGGAT TERLEWAT' : 'PENGINGAT DEADLINE ISPO' }}</span>
                <strong>{{ $reminder['company'] }}</strong>
                <p>{{ $reminder['title'] }}</p>
                <span class="app-toast__detail">{{ $reminder['status'] }} · Batas: {{ $reminder['due_date'] }}</span>
                <a class="app-toast__link" href="{{ $reminder['url'] }}">Buka checklist <span aria-hidden="true">&rarr;</span></a>
            </div>
            <button class="app-toast__close" type="button" data-toast-dismiss aria-label="Tutup pengingat tenggat">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endforeach
</div>
@stack('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menus = document.querySelectorAll('[data-profile-menu], [data-notification-menu]');
        menus.forEach((menu) => {
            const trigger = menu.querySelector('[data-menu-trigger]');
            const panel = menu.querySelector('[data-menu-panel]');
            if (!trigger || !panel) return;

            trigger.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = !panel.hidden;
                menus.forEach((other) => {
                    const otherPanel = other.querySelector('[data-menu-panel]');
                    const otherTrigger = other.querySelector('[data-menu-trigger]');
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

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    panel.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
</script>
</body>
</html>