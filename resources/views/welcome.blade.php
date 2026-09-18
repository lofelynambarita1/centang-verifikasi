<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Audit ISPO</title>
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
            <a class="ispo-nav-link ispo-nav-active" href="{{ route('dashboard') }}"><b>▪</b> Dashboard</a>
            <a class="ispo-nav-link" href="{{ route('companies.index') }}"><b>▪</b> Daftar Perusahaan</a>
        </nav>
    </aside>

    <main class="ispo-main">
        <header class="ispo-header">
            <div>
                <p class="ispo-kicker">VERIFIKASI AUDIT ISPO</p>
                <h1>Dashboard</h1>
                <p>Ringkasan status verifikasi audit ISPO seluruh perusahaan</p>
            </div>
            <a class="ispo-primary" href="{{ route('companies.index') }}">Kelola perusahaan</a>
        </header>

        <section class="ispo-stats" aria-label="Ringkasan audit">
            <article><strong>{{ $stats['companies'] }}</strong><span>Total perusahaan</span></article>
            <article><strong>{{ $stats['average_progress'] }}%</strong><span>Rata-rata progres<br>verifikasi</span></article>
            <article><strong>{{ $stats['completed'] }}</strong><span>Audit selesai 100%</span></article>
            <article><strong>{{ $stats['met'] }}</strong><span>Total indikator<br>memenuhi</span></article>
            <article><strong>{{ $stats['not_met'] }}</strong><span>Total indikator tidak<br>memenuhi</span></article>
        </section>

        <section class="ispo-company-panel">
            <div class="ispo-panel-heading">
                <h2>Perusahaan terbaru</h2>
                <a href="{{ route('companies.create') }}">+ Tambah perusahaan</a>
            </div>
            <div class="ispo-table-wrap">
                <table class="ispo-table">
                    <thead><tr><th>PERUSAHAAN</th><th>PROGRES</th><th>RINCIAN</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($companyRows as $row)
                        <tr>
                            <td><strong>{{ strtolower($row['company']->name) }}</strong><span>{{ $row['company']->industry ?: 'Bidang usaha belum diisi' }}</span></td>
                            <td><div class="ispo-progress"><i style="width:{{ $row['progress'] }}%"></i></div><span>{{ $row['progress'] }}%</span></td>
                            <td>{{ $row['verified_items'] }} / {{ $row['total_items'] }} indikator</td>
                            <td><a class="ispo-detail" href="{{ $row['audit'] ? route('audits.show', $row['audit']) : route('companies.show', $row['company']) }}">Rincian →</a></td>
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
