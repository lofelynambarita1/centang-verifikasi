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
    @php
        $eventLabels = [
            'audit_created' => 'Audit',
            'audit_completed' => 'Audit',
            'audit_result' => 'Hasil audit',
            'audit_result_updated' => 'Hasil audit',
            'audit_result_deleted' => 'Hasil audit',
            'audit_evidence_uploaded' => 'Bukti audit',
            'audit_finding' => 'Temuan audit',
            'checklist_review_updated' => 'Review checklist',
            'principle_assignment' => 'Penugasan',
            'company_created' => 'Perusahaan',
            'company_updated' => 'Perusahaan',
            'company_deleted' => 'Perusahaan',
            'user_created' => 'Pengguna',
            'user_updated' => 'Pengguna',
            'user_deleted' => 'Pengguna',
            'role_approved' => 'Pengguna',
        ];
    @endphp

    <div class="notification-page">
        <section class="notification-overview" aria-label="Ringkasan notifikasi">
            <div class="notification-overview__copy">
                <span class="notification-overview__eyebrow">Aktivitas sistem</span>
                <h2>Semua kabar penting, dalam satu tempat</h2>
                <p>Perubahan perusahaan, audit, penugasan tim, review checklist, dan pengguna dicatat di sini untuk semua pengguna.</p>
            </div>
            <div class="notification-overview__stats">
                <div>
                    <strong>{{ $notifications->total() }}</strong>
                    <span>Total aktivitas</span>
                </div>
                <div class="notification-overview__unread">
                    <strong>{{ $unreadCount }}</strong>
                    <span>Belum dibaca</span>
                </div>
            </div>
        </section>

        <div class="notification-section-heading">
            <div>
                <h2>Aktivitas terbaru</h2>
                <p>Urutan terbaru ditampilkan lebih dahulu.</p>
            </div>
        </div>

        <section class="notification-list" aria-label="Daftar notifikasi">
            @forelse ($notifications as $notification)
                @php
                    $event = $notification->data['event'] ?? '';
                    $kind = $eventLabels[$event] ?? 'Pemberitahuan';
                @endphp
                <article class="notification-item {{ $notification->read_at ? '' : 'notification-item--unread' }}">
                    <span class="notification-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M10 21h4"/>
                        </svg>
                    </span>
                    <div class="notification-item__body">
                        <div class="notification-item__heading">
                            <span class="notification-item__category">{{ $kind }}</span>
                            @unless ($notification->read_at)
                                <span class="notification-item__new">Belum dibaca</span>
                            @endunless
                        </div>
                        <h3>{{ $notification->data['title'] ?? 'Pemberitahuan' }}</h3>
                        <p>{{ $notification->data['message'] ?? '' }}</p>
                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</time>
                    </div>
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button class="btn btn--ghost btn--sm" type="submit">
                            {{ $notification->read_at ? 'Lihat detail' : 'Buka notifikasi' }}
                        </button>
                    </form>
                </article>
            @empty
                <div class="notification-empty">
                    <span class="notification-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M10 21h4"/>
                        </svg>
                    </span>
                    <h3>Belum ada aktivitas</h3>
                    <p>Notifikasi akan muncul saat data perusahaan, audit, penugasan, review checklist, atau pengguna berubah.</p>
                </div>
            @endforelse
        </section>

        {{ $notifications->links() }}
    </div>
@endsection