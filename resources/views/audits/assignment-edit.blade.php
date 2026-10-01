@extends('layouts.app')

@section('title', 'Penanggung jawab prinsip | '.$audit->company->name)
@section('eyebrow', 'PENGELOLAAN AUDIT')
@section('heading', 'Penanggung jawab prinsip')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('audits.assignments.index') }}">Kembali ke daftar audit</a>
@endsection

@section('content')
    <p class="page-intro"><strong>{{ $audit->company->name }}</strong> · {{ $audit->title ?: 'Audit Verifikasi ISPO' }}</p>

    @php
        $principleTitles = [
            1 => 'Kepatuhan terhadap peraturan',
            2 => 'Praktik perkebunan yang baik',
            3 => 'Lingkungan hidup dan keanekaragaman hayati',
            4 => 'Tanggung jawab ketenagakerjaan',
            5 => 'Tanggung jawab sosial masyarakat',
            6 => 'Transparansi',
            7 => 'Peningkatan usaha berkelanjutan',
        ];
    @endphp

    <section class="panel panel--pad assignment-panel">
        <div class="panel__head">
            <div>
                <h2>Pilih auditor untuk setiap prinsip</h2>
                <p>Centang satu atau lebih nama. Perubahan berlaku setelah disimpan.</p>
            </div>
            <span class="role-badge role-badge--lead_audit">Admin / Lead audit</span>
        </div>

        <form method="POST" action="{{ route('audits.assignments.update', $audit) }}" class="assignment-form">
            @csrf
            @method('PUT')
            @foreach ($principleTitles as $number => $title)
                @php $assignedUserIds = $audit->assignees->where('pivot.principle_number', $number)->pluck('id')->all(); @endphp
                <fieldset class="assignment-row">
                    <legend><strong>Prinsip {{ $number }}</strong><span>{{ $title }}</span></legend>
                    <div class="assignment-users">
                        @forelse ($users as $user)
                            <label class="check-control">
                                <input type="checkbox" name="assignments[{{ $number }}][users][]" value="{{ $user->id }}" @checked(in_array($user->id, $assignedUserIds, true))>
                                <span>{{ $user->name }}</span>
                                <small>{{ str_replace('_', ' ', ucfirst($user->role)) }}</small>
                            </label>
                        @empty
                            <span class="cell-meta">Belum ada lead audit atau auditor.</span>
                        @endforelse
                    </div>
                    <input type="hidden" name="assignments[{{ $number }}][principle]" value="{{ $number }}">
                </fieldset>
            @endforeach
            <button class="btn btn--primary" type="submit">Simpan penugasan</button>
        </form>
    </section>
@endsection