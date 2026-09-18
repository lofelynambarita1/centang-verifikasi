<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Perusahaan | Verifikasi Audit ISPO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="ispo-shell">
    <aside class="ispo-sidebar">
        <a class="ispo-brand" href="{{ route('dashboard') }}">
            <strong>Verifikasi Audit ISPO</strong>
            <span>Permentan No. 33/2025</span>
            <small>Versi offline · data tersimpan di<br>browser ini saja</small>
        </a>
        <nav class="ispo-nav" aria-label="Navigasi utama">
            <a class="ispo-nav-link" href="{{ route('dashboard') }}"><b>▪</b> Dashboard</a>
            <a class="ispo-nav-link ispo-nav-active" href="{{ route('companies.index') }}"><b>▪</b> Daftar Perusahaan</a>
        </nav>
    </aside>

    <main class="ispo-main">
        <header class="ispo-header">
            <div>
                <p class="ispo-kicker">WORKSPACE / PERUSAHAAN</p>
                <h1>Daftar perusahaan</h1>
                <p>Kelola perusahaan (auditee) yang akan diverifikasi</p>
            </div>
            <a class="ispo-primary" href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
        </header>

        @if (session('success'))
            <div class="ispo-alert">{{ session('success') }}</div>
        @endif

        <section class="ispo-company-panel ispo-list-panel">
            <div class="ispo-table-wrap">
                <table class="ispo-table ispo-company-list">
                    <thead>
                        <tr><th>NAMA PERUSAHAAN</th><th>AUDITOR / TANGGAL</th><th>PROGRES VERIFIKASI</th><th>AKSI</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($companies as $company)
                        @php
                            $audit = $company->audits->sortByDesc('created_at')->first();
                            $totalItems = $audit?->items->count() ?? 0;
                            $verifiedItems = $audit?->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count() ?? 0;
                            $progress = $totalItems ? round($verifiedItems / $totalItems * 100) : 0;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ strtolower($company->name) }}</strong>
                                <span>{{ $company->address ?: 'Alamat belum diisi' }}</span>
                            </td>
                            <td>
                                <strong>{{ $audit?->auditor_name ?: 'Belum ditentukan' }}</strong>
                                <span>{{ $audit?->due_date?->format('d F Y') ?: '-' }}</span>
                            </td>
                            <td>
                                <div class="ispo-progress"><i style="width:{{ $progress }}%"></i></div>
                                <span class="ispo-progress-label">{{ $progress }}%</span>
                                <small>{{ $verifiedItems }}/{{ $totalItems }} indikator</small>
                            </td>
                            <td class="ispo-actions">
                                <a class="ispo-action-open" href="{{ $audit ? route('audits.show', $audit) : route('companies.show', $company) }}">Buka</a>
                                <a class="ispo-action-edit" href="{{ route('companies.edit', $company) }}">Edit</a>
                                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Hapus perusahaan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ispo-action-delete" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="ispo-empty">Belum ada perusahaan. Silakan tambahkan perusahaan terlebih dahulu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
