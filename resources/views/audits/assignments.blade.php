@extends('layouts.app')

@section('title', 'Penugasan auditor | Verifikasi Audit ISPO')
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penugasan auditor')

@section('actions')
    @if (auth()->user()?->isAdmin())
        <a class="btn btn--primary" href="{{ route('audits.create') }}">+ Buat Proyek Audit Baru</a>
    @endif
    <a class="btn btn--ghost" href="{{ route('companies.index') }}">Perusahaan</a>
@endsection

@section('content')
    <p class="page-intro">Tetapkan auditor yang bertanggung jawab pada tiap prinsip audit ISPO.</p>

    <section class="panel panel--pad">
        <div class="table-wrap">
            <table class="table audits-assignment-table">
                <thead>
                    <tr>
                        <th>Perusahaan dan audit</th>
                        <th>Rentang tanggal dan status</th>
                        <th>Lead Auditor dan tim</th>
                        <th>Progres indikator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        @php
                            $totalItems = $audit->items->count();
                            $verifiedItems = $audit->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
                            $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
                            $teamAuditorNames = $audit->teamAuditors()
                                ->reject(fn ($user) => $user->id === $audit->leadMember?->user_id)
                                ->pluck('name')
                                ->implode(', ');
                            $membersByFirstSubmission = $audit->members
                                ->filter(fn ($member) => $member->first_submitted_at !== null)
                                ->sortBy(fn ($member) => $member->first_submitted_at->getTimestamp());
                            $membersWithoutSubmission = $audit->members
                                ->filter(fn ($member) => $member->first_submitted_at === null);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $audit->company->name }}</strong>
                                <div class="cell-meta">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</div>
                            </td>
                            <td>
                                <strong>
                                    @if ($audit->start_date || $audit->end_date)
                                        {{ $audit->start_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                                        – {{ $audit->end_date?->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                                    @else
                                        Belum ditentukan
                                    @endif
                                </strong>
                                <div class="cell-meta">
                                    {{ $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Sedang diverifikasi' : 'Belum mulai') }}
                                </div>
                            </td>
                            <td>
                                <div class="audit-team-summary">
                                    <div><span>Lead:</span> <strong>{{ $audit->leadMember?->user?->name ?: 'Belum ditugaskan' }}</strong></div>
                                    <div class="audit-team-summary__members" title="{{ $teamAuditorNames ?: 'Belum ditentukan' }}">
                                        <span>Tim:</span> {{ $teamAuditorNames ?: 'Belum ditentukan' }}
                                    </div>
                                </div>
                                @if (auth()->user()->isAdmin() && $audit->members->isNotEmpty())
                                    <details class="audit-team-details">
                                        <summary>Detail Tim &amp; Deadline</summary>
                                        <div class="audit-member-timeline" aria-label="Timeline pengerjaan auditor">
                                            @foreach ($membersByFirstSubmission as $member)
                                                <div class="audit-member-timeline__item">
                                                    <strong>{{ $member->user?->name ?? 'Auditor' }}</strong>
                                                    @if ($member->isLate($audit->status))
                                                        <span class="late-badge">Terlambat</span>
                                                    @endif
                                                    <div>Deadline: {{ $member->deadline_at?->format('d M Y H:i') ?? 'Belum ditetapkan' }}</div>
                                                    <div>Pengiriman ke-{{ $loop->iteration }}: {{ $member->first_submitted_at->format('d M Y H:i') }}</div>
                                                    <div>Aktivitas terakhir: {{ $member->last_submitted_at?->format('d M Y H:i') ?? '-' }}</div>
                                                </div>
                                            @endforeach
                                            @foreach ($membersWithoutSubmission as $member)
                                                <div class="audit-member-timeline__item">
                                                    <strong>{{ $member->user?->name ?? 'Auditor' }}</strong>
                                                    @if ($member->isLate($audit->status))
                                                        <span class="late-badge">Terlambat</span>
                                                    @endif
                                                    <div>Deadline: {{ $member->deadline_at?->format('d M Y H:i') ?? 'Belum ditetapkan' }}</div>
                                                    <div>Belum ada aktivitas pengerjaan</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td>
                                <div class="progress audits-assignment-progress">
                                    <div class="progress__track">
                                        <div class="progress__bar" style="width:{{ $progress }}%"></div>
                                    </div>
                                    <span class="progress__pct">{{ $progress }}%</span>
                                </div>
                                <div class="cell-meta"><strong>{{ $verifiedItems }}</strong> / {{ $totalItems }} Indikator Selesai</div>
                            </td>
                            <td>
                                <div class="cell-actions audits-assignment-actions">
                                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', $audit) }}">Buka Checklist</a>
                                    @if (auth()->user()?->canManageAssignments($audit))
                                        <a class="btn btn--primary btn--sm" href="{{ route('audits.assignments.edit', $audit) }}">Atur Tim/Penugasan</a>
                                    @endif
                                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.report', $audit) }}">Laporan</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty">
                                    <strong>Belum ada audit ISPO</strong>
                                    <span>Audit ISPO yang tersedia untuk penugasan akan muncul di sini.</span>
                                    @if (auth()->user()?->isAdmin())
                                        <a class="btn btn--primary" href="{{ route('audits.create') }}">+ Buat Proyek Audit Baru</a>
                                    @else
                                        <a class="btn btn--primary" href="{{ route('companies.index') }}">Lihat perusahaan</a>
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
