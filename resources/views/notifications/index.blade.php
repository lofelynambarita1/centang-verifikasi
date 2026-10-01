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
    <section class="notification-list" aria-label="Daftar notifikasi">
        @forelse ($notifications as $notification)
            <article class="notification-item {{ $notification->read_at ? '' : 'notification-item--unread' }}">
                <div class="notification-item__body">
                    <div class="notification-item__heading">
                        <h2>{{ $notification->data['title'] ?? 'Pemberitahuan' }}</h2>
                        @unless ($notification->read_at)
                            <span class="notification-item__new">Baru</span>
                        @endunless
                    </div>
                    <p>{{ $notification->data['message'] ?? '' }}</p>
                    <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</time>
                </div>
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button class="btn btn--ghost btn--sm" type="submit">Buka</button>
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