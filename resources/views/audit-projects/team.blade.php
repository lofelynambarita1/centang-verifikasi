@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];

    // Helper closure untuk inisial nama yang aman
    $getInitials = function (?string $name): string {
        if (!$name) return '?';
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };

    $leadUserId = $project->leadMember?->user_id;
@endphp

@section('title', 'Kelola Tim | ' . $project->name)
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Kelola tim')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">← Detail project</a>
@endsection

@section('content')
    <p class="page-intro">
        Kelola anggota project <strong>{{ $project->name }}</strong>. Satu user hanya bisa terdaftar sekali, dan
        hanya ada satu Lead Auditor aktif. Mengganti Lead Auditor membuat lead lama tetap menjadi auditor biasa.
    </p>

    @if (in_array($project->status->value, ['draft', 'team_setup'], true) && $members->contains(fn ($member): bool => $member->isLead()) && $members->count() >= 2)
        <form method="POST" action="{{ route('audit-projects.team.complete', $project) }}" style="margin-bottom: 22px;">
            @csrf
            <button class="btn btn--primary" type="submit">Selesaikan pembentukan tim</button>
        </form>
    @endif

    {{-- Tabel Anggota Tim --}}
    <section class="panel panel--pad" aria-label="Daftar Anggota Tim">
        <div class="panel__head">
            <div>
                <h2>Anggota tim</h2>
                <p>{{ $members->count() }} anggota terdaftar</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Anggota</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Status keanggotaan</th>
                        <th scope="col"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        @php
                            $userName = $member->user?->name;
                            $userEmail = $member->user?->email;
                            $isLead = $member->isLead();
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-company">
                                    <div class="avatar {{ $avatarColors[$loop->index % count($avatarColors)] }}" aria-hidden="true">
                                        {{ $getInitials($userName) }}
                                    </div>
                                    <div>
                                        <strong>{{ $userName ?? 'Pengguna Tidak Ditemukan' }}</strong>
                                        <span>{{ $userEmail ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($isLead)
                                    <span class="chip chip--done">Lead Auditor</span>
                                @else
                                    <span class="chip chip--todo">Auditor</span>
                                @endif
                            </td>
                            <td>
                                <span class="chip chip--done">Aktif</span>
                            </td>
                            <td>
                                @can('manage-audit-projects')
                                    <div class="cell-actions" style="justify-content: flex-end;">
                                        @if (! $isLead)
                                            <form
                                                method="POST"
                                                action="{{ route('audit-projects.members.destroy', [$project, $member]) }}"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ addslashes($userName ?? 'anggota ini') }} dari project?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn--danger btn--sm" type="submit" aria-label="Hapus {{ $userName }} dari project">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="cell-meta">Ganti lewat form di bawah</span>
                                        @endif
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty">
                                    <strong>Belum ada anggota</strong>
                                    <span>Tambahkan anggota auditor atau Lead Auditor dari form di bawah.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Form Tambah & Ganti Tim --}}
    @can('manage-audit-projects')
        <section class="panel panel--pad form-card" style="max-width: none;" aria-label="Form Pengelolaan Tim">
            <div class="panel__head">
                <div>
                    <h2>Tambah anggota auditor</h2>
                    <p>Lead Auditor ditambahkan/diganti lewat form pergantian Lead Auditor di bawah.</p>
                </div>
            </div>

            {{-- Form Tambah Auditor Baru --}}
            <form class="form" method="POST" action="{{ route('audit-projects.members.store', $project) }}">
                @csrf
                <div class="form-grid">
                    <div class="field @error('user_id') field--error @enderror">
                        <label for="user_id">Pilih auditor</label>
                        <select id="user_id" name="user_id" required @disabled($availableAuditors->isEmpty())>
                            <option value="">— Pilih auditor —</option>
                            @foreach ($availableAuditors as $auditor)
                                <option value="{{ $auditor->id }}" @selected(old('user_id') == $auditor->id)>
                                    {{ $auditor->name }} ({{ $auditor->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                        @if ($availableAuditors->isEmpty())
                            <span class="cell-meta">Semua auditor yang tersedia sudah terdaftar di project ini.</span>
                        @endif
                    </div>

                    <div class="field" style="justify-content: flex-end; display: flex; align-items: flex-end;">
                        <button class="btn btn--primary" type="submit" @disabled($availableAuditors->isEmpty())>
                            + Tambah auditor
                        </button>
                    </div>
                </div>
            </form>

            <hr style="margin: 24px 0; border: 0; border-top: 1px solid var(--border-color, #e5e7eb);" />

            {{-- Form Ganti Lead Auditor --}}
            <form class="form" method="POST" action="{{ route('audit-projects.lead-auditor.replace', $project) }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="field @error('new_lead_auditor_id') field--error @enderror">
                        <label for="new_lead_auditor_id">Ganti Lead Auditor</label>
                        <select id="new_lead_auditor_id" name="new_lead_auditor_id" required>
                            <option value="">— Pilih lead baru —</option>
                            @foreach ($auditors as $auditor)
                                @php
                                    $isCurrentLead = ($leadUserId === $auditor->id);
                                    $isSelected = old('new_lead_auditor_id', $leadUserId) == $auditor->id;
                                @endphp
                                <option value="{{ $auditor->id }}" @selected($isSelected)>
                                    {{ $auditor->name }} ({{ $auditor->email }})
                                    @if ($isCurrentLead)
                                        (Lead saat ini)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('new_lead_auditor_id')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                        <span class="cell-meta">Lead lama akan diubah menjadi auditor biasa (tidak dihapus dari project).</span>
                    </div>

                    <div class="field" style="justify-content: flex-end; display: flex; align-items: flex-end;">
                        <button class="btn btn--ghost" type="submit">
                            Ganti Lead Auditor
                        </button>
                    </div>
                </div>
            </form>
        </section>
    @endcan
@endsection