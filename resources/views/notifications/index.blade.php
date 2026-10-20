@extends('layouts.app')

@section('title', 'Notifikasi | Verifikasi Audit ISPO')
@section('eyebrow', 'PEMBERITAHUAN')
@section('heading', 'Notifikasi')

@section('actions')
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn--ghost" type="submit">Tandai semua dibaca</button>
        </form>
    @endif
@endsection

@section('content')
    <p class="page-intro notification-intro">Pantau pembaruan penugasan, hasil, dan aktivitas audit Anda di satu tempat.</p>

    <section class="notification-overview" aria-label="Ringkasan notifikasi">
        <div class="notification-overview__item">
            <span class="notification-overview__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                    <path d="M10 21h4"/>
                </svg>
            </span>
            <span><strong>{{ number_format($notifications->total()) }}</strong><small>Total notifikasi</small></span>
        </div>
        <div class="notification-overview__item notification-overview__item--unread">
            <span class="notification-overview__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </span>
            <span><strong>{{ number_format($unreadCount) }}</strong><small>Belum dibaca</small></span>
        </div>
    </section>

    <section class="notification-list" aria-label="Daftar notifikasi">
        @forelse ($notifications as $notification)
            @php $isAccountRequest = ($notification->data['event'] ?? null) === 'role_request'; @endphp
            <article class="notification-item {{ $notification->read_at ? '' : 'notification-item--unread' }} {{ $isAccountRequest ? 'notification-item--account-request' : '' }}">
                <span class="notification-item__icon" aria-hidden="true">
                    @if ($isAccountRequest)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="8" r="4"/>
                            <path d="M3 20v-1a6 6 0 0 1 12 0v1"/>
                            <path d="M19 8v6m-3-3h6"/>
                        </svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M10 21h4"/>
                        </svg>
                    @endif
                </span>
                <div class="notification-item__body">
                    <div class="notification-item__heading">
                        <h2>{{ $notification->data['title'] ?? 'Pemberitahuan' }}</h2>
                        <span class="notification-item__new {{ $notification->read_at ? 'notification-item__new--read' : '' }}">
                            {{ $notification->read_at ? 'Sudah dibaca' : 'Belum dibaca' }}
                        </span>
                    </div>
                    @if ($isAccountRequest)
                        <span class="notification-item__type">Permintaan akun baru</span>
                    @endif
                    <p>{{ $notification->data['message'] ?? '' }}</p>
                    <time datetime="{{ $notification->created_at->toIso8601String() }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                        {{ $notification->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                    </time>
                </div>
                <form class="notification-item__action" method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button class="btn btn--ghost btn--sm" type="submit" aria-label="Buka notifikasi: {{ $notification->data['title'] ?? 'Pemberitahuan' }}">
                        {{ $isAccountRequest ? 'Tinjau akun' : 'Buka' }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </form>
            </article>
        @empty
            <div class="panel panel--pad">
                <div class="empty">
                    <strong>Belum ada notifikasi</strong>
                    <span>Pemberitahuan penugasan dan aktivitas audit akan muncul di sini.</span>
                </div>
            </div>
        @endforelse
    </section>

    {{ $notifications->links() }}
@endsection