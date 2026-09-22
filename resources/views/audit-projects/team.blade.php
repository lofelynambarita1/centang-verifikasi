@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];
    $initials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };
@endphp

@section('title', 'Kelola Tim | '.$project->name)
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

    <section class="panel panel--pad">
        <div class="panel__head">
            <div>
                <h2>Anggota tim</h2>
                <p>{{ $members->count() }} anggota</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Peran</th>
                        <th>Status keanggotaan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>
                            <div class="cell-company">
                                <div class="avatar {{ $avatarColors[$loop->index % 5] }}">{{ $initials($member->user?->name ?? '?') }}</div>
                                <div>
                                    <strong>{{ $member->user?->name }}</strong>
                                    <span>{{ $member->user?->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($member->isLead())
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
                                    @if (! $member->isLead())
                                        <form method="POST" action="{{ route('audit-projects.members.destroy', [$project, $member]) }}" onsubmit="return confirm('Hapus {{ $member->user?->name }} dari project ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
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

    @can('manage-audit-projects')
        <section class="panel panel--pad form-card" style="max-width: none;">
            <div class="panel__head">
                <div>
                    <h2>Tambah anggota auditor</h2>
                    <p>Lead Auditor ditambahkan/diganti lewat form pergantian Lead Auditor.</p>
                </div>
            </div>
            <form class="form" method="POST" action="{{ route('audit-projects.members.store', $project) }}">
                @csrf
                <div class="form-grid">
                    <div class="field">
                        <label for="user_id">Pilih auditor</label>
                        <select id="user_id" name="user_id" required>
                            <option value="">— Pilih auditor —</option>
                            @foreach ($availableAuditors as $auditor)
                                <option value="{{ $auditor->id }}">{{ $auditor->name }} &lt;{{ $auditor->email }}&gt;</option>
                            @endforeach
                        </select>
                        @if ($availableAuditors->isEmpty())
                            <span class="cell-meta">Semua auditor sudah terdaftar di project ini.</span>
                        @endif
                    </div>
                    <div class="field" style="justify-content: flex-end; display: flex; align-items: end;">
                        <button class="btn btn--primary" type="submit" @disabled($availableAuditors->isEmpty())>+ Tambah auditor</button>
                    </div>
                </div>
            </form>

            <form class="form" method="POST" action="{{ route('audit-projects.lead-auditor.replace', $project) }}" style="margin-top: 6px;">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="field">
                        <label for="new_lead_auditor_id">Ganti Lead Auditor</label>
                        <select id="new_lead_auditor_id" name="new_lead_auditor_id" required>
                            <option value="">— Pilih lead baru —</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}" @selected($project->leadMember?->user_id === $auditor->id)>
                                    {{ $auditor->name }} &lt;{{ $auditor->email }}&gt;
                                    @if ($project->leadMember?->user_id === $auditor->id)
                                        (lead saat ini)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <span class="cell-meta">Lead lama diubah menjadi auditor biasa — tidak dihapus dari project.</span>
                    </div>
                    <div class="field" style="justify-content: flex-end; display: flex; align-items: end;">
                        <button class="btn btn--ghost" type="submit">Ganti Lead Auditor</button>
                    </div>
                </div>
            </form>
        </section>
    @endcan
@endsection