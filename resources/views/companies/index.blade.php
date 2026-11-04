@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
@endphp

@section('title', 'Daftar Perusahaan | Verifikasi Audit ISPO')
@section('eyebrow', 'PERUSAHAAN')
@section('heading', 'Daftar perusahaan')

@section('actions')
    @if (auth()->user()->isAdmin())
        <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
    @endif
@endsection

@section('content')
    <p class="page-intro">Kelola master data perusahaan (auditee) dan riwayat auditnya.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama perusahaan</th>
                        <th>Klien Sejak (Tahun)</th>
                        <th>Status Audit Terkini</th>
                        <th>Kontak PIC</th>
                        <th>Total riwayat audit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($companies as $company)
                    @php
                        $latestAudit = $company->latestAudit;
                        $auditYear = $latestAudit?->start_date?->format('Y')
                            ?? $latestAudit?->created_at?->format('Y');
                        $auditLabel = $latestAudit?->title;
                        if ($latestAudit && $auditYear && ! str_contains($auditLabel, $auditYear)) {
                            $auditLabel .= ' - '.$auditYear;
                        }
                        $auditStatus = $latestAudit?->status === 'completed'
                            ? 'Selesai'
                            : ($latestAudit?->status === 'in_progress' ? 'Sedang berlangsung' : 'Belum mulai');
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-company">
                                <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($company->name) }}</div>
                                <div>
                                    <strong>{{ $company->name }}</strong>
                                    <span class="company-address">📍 {{ $company->address ?: '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="company-client-year">{{ $company->client_year ?? '-' }}</td>
                        <td>
                            @if ($latestAudit)
                                <strong>{{ $auditLabel }}</strong>
                                <div class="cell-meta">Status: {{ $auditStatus }}</div>
                            @else
                                <span class="cell-meta">Belum ada audit</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $company->contact_name ?: 'Belum diisi' }}</strong>
                            <div class="cell-meta">{{ $company->contact_email ?: 'Email belum diisi' }}</div>
                        </td>
                        <td>{{ $company->audits_count }}</td>
                        <td>
                            <div class="company-actions" data-company-actions>
                                <button
                                    class="company-actions__trigger"
                                    type="button"
                                    aria-label="Aksi untuk {{ $company->name }}"
                                    aria-expanded="false"
                                    aria-controls="company-actions-{{ $company->id }}"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <circle cx="12" cy="5" r="1.8"/>
                                        <circle cx="12" cy="12" r="1.8"/>
                                        <circle cx="12" cy="19" r="1.8"/>
                                    </svg>
                                </button>
                                <div class="company-action-menu" id="company-actions-{{ $company->id }}" role="menu" aria-label="Aksi perusahaan {{ $company->name }}" hidden>
                                    <a class="company-action-menu__item" href="{{ route('companies.show', $company) }}" role="menuitem">Detail / Riwayat</a>
                                    @if (auth()->user()->isAdmin())
                                        <a class="company-action-menu__item" href="{{ route('companies.edit', $company) }}" role="menuitem">Edit</a>
                                        <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Hapus perusahaan ini beserta auditnya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="company-action-menu__item company-action-menu__item--danger" type="submit" role="menuitem">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                                    <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                                </svg>
                                <strong>Belum ada perusahaan</strong>
                                <span>Tambahkan perusahaan terlebih dahulu untuk membuat audit verifikasi baru.</span>
                                @if (auth()->user()->isAdmin())
                                    <a class="btn btn--primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionMenus = Array.from(document.querySelectorAll('[data-company-actions]'))
                .map((wrapper) => {
                    const trigger = wrapper.querySelector('.company-actions__trigger');
                    const panel = wrapper.querySelector('.company-action-menu');

                    document.body.appendChild(panel);

                    return { wrapper, trigger, panel };
                });

            function closeMenu(menu, restoreFocus = false) {
                menu.panel.hidden = true;
                menu.trigger.setAttribute('aria-expanded', 'false');

                if (restoreFocus) {
                    menu.trigger.focus();
                }
            }

            actionMenus.forEach((menu) => {
                const { wrapper, trigger, panel } = menu;

                trigger.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const isOpen = !panel.hidden;

                    actionMenus.forEach((otherMenu) => {
                        if (otherMenu !== menu) {
                            closeMenu(otherMenu);
                        }
                    });

                    if (isOpen) {
                        closeMenu(menu);
                        return;
                    }

                    panel.hidden = false;
                    trigger.setAttribute('aria-expanded', 'true');

                    const triggerBounds = trigger.getBoundingClientRect();
                    const panelBounds = panel.getBoundingClientRect();
                    const gap = 8;
                    const left = Math.max(
                        gap,
                        Math.min(triggerBounds.right - panelBounds.width, window.innerWidth - panelBounds.width - gap),
                    );
                    const top = triggerBounds.bottom + panelBounds.height + gap <= window.innerHeight
                        ? triggerBounds.bottom + gap
                        : Math.max(gap, triggerBounds.top - panelBounds.height - gap);

                    panel.style.left = `${left}px`;
                    panel.style.top = `${top}px`;
                });

                document.addEventListener('click', function (event) {
                    if (!wrapper.contains(event.target) && !panel.contains(event.target)) {
                        closeMenu(menu);
                    }
                });

                panel.addEventListener('click', function (event) {
                    if (event.target.closest('a, button')) {
                        closeMenu(menu);
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    actionMenus.forEach((menu) => {
                        if (!menu.panel.hidden) {
                            closeMenu(menu, true);
                        }
                    });
                }
            });

            window.addEventListener('resize', function () {
                actionMenus.forEach((menu) => closeMenu(menu));
            });

            window.addEventListener('scroll', function () {
                actionMenus.forEach((menu) => closeMenu(menu));
            }, true);
        });
    </script>
@endpush