@extends('layouts.app')

@php
    $totalItems = $allItems->count();
    $verifiedItems = $allItems->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
    $progress = $totalItems ? round(($verifiedItems / $totalItems) * 100) : 0;
    $nonCompliant = $allItems->where('status', 'non_compliant')->count();
    $compliant = $allItems->where('status', 'compliant')->count();
    $na = $allItems->where('status', 'not_applicable')->count();
    $statusTabs = [
        ['label' => 'Semua', 'count' => $totalItems],
        ['label' => 'Memenuhi', 'count' => $compliant],
        ['label' => 'Tidak memenuhi', 'count' => $nonCompliant],
        ['label' => 'NA', 'count' => $na],
    ];
@endphp

@section('title', 'Verifikasi Audit | '.$audit->company->name)
@section('eyebrow', 'VERIFIKASI')
@section('heading', 'Verifikasi audit ISPO')

@section('actions')
    <div class="page-actions">
        <a class="btn btn--outline" href="{{ route('companies.index') }}">Perusahaan</a>
        <a class="btn btn--outline" href="{{ route('companies.edit', $audit->company) }}">Edit data</a>

        @if ($isAuditManager)
            <div class="menu-split" data-menu>
                <button class="btn btn--outline menu-split__trigger menu-split__trigger--plain" type="button" aria-expanded="false" aria-controls="audit-export-menu">
                    Cetak laporan
                </button>
                <div class="menu-split__panel" id="audit-export-menu" role="menu" aria-label="Export laporan">
                    <a class="menu-split__item" href="{{ route('audits.report', $audit) }}" role="menuitem">Cetak laporan</a>
                    <a class="menu-split__item" href="{{ route('audits.download', $audit) }}" role="menuitem">Download Word</a>
                    <a class="menu-split__item menu-split__item--primary" href="{{ route('audits.pdf', $audit) }}" role="menuitem">Download PDF</a>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrappers = document.querySelectorAll('[data-menu]');

            wrappers.forEach((wrapper) => {
                const trigger = wrapper.querySelector('.menu-split__trigger');
                const panel = wrapper.querySelector('.menu-split__panel');

                if (!trigger || !panel) {
                    return;
                }

                const closeMenu = () => {
                    wrapper.classList.remove('is-open');
                    trigger.setAttribute('aria-expanded', 'false');
                    panel.setAttribute('hidden', 'hidden');
                };

                const openMenu = () => {
                    wrapper.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                    panel.removeAttribute('hidden');
                };

                trigger.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const isOpen = wrapper.classList.contains('is-open');
                    wrappers.forEach((menu) => {
                        const menuTrigger = menu.querySelector('.menu-split__trigger');
                        const menuPanel = menu.querySelector('.menu-split__panel');
                        if (menu !== wrapper && menuTrigger && menuPanel) {
                            menu.classList.remove('is-open');
                            menuTrigger.setAttribute('aria-expanded', 'false');
                            menuPanel.setAttribute('hidden', 'hidden');
                        }
                    });

                    if (isOpen) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });

                document.addEventListener('click', function (event) {
                    if (!wrapper.contains(event.target)) {
                        closeMenu();
                    }
                });

                closeMenu();
            });
        });
    </script>
@endpush

@section('content')
    <section class="hero">
        <div class="hero__head">
            <div>
                <div class="hero__title">{{ $audit->company->name }}</div>
                <div class="hero__sub">{{ $audit->title ?: 'Audit Verifikasi ISPO' }}</div>
            </div>
        </div>
        <div class="hero__body">
            <div class="meta-row"><span>Nama perusahaan</span><b>{{ $audit->company->name }}</b></div>
            <div class="meta-row"><span>Alamat</span><b>{{ $audit->company->address ?: 'Belum diisi' }}</b></div>
            <div class="meta-row"><span>Bidang usaha</span><b>{{ $audit->company->industry ?: 'Belum diisi' }}</b></div>
            <div class="meta-row"><span>Auditor</span><b>{{ $audit->auditorNamesText() ?: 'Belum ditentukan' }}</b></div>
            <div class="meta-row"><span>Tanggal audit</span><b>{{ $audit->due_date?->translatedFormat('d F Y') ?: '-' }}</b></div>
            <div class="meta-row"><span>Status audit</span><b>{{ $audit->status === 'completed' ? 'Selesai' : ($audit->status === 'in_progress' ? 'Sedang diverifikasi' : 'Belum mulai') }}</b></div>
        </div>
    </section>

    <section class="audit-summary" aria-label="{{ $isAuditManager ? 'Progres verifikasi keseluruhan' : 'Progres prinsip yang ditugaskan' }}">
        <div class="summary-head">
            <strong>{{ $isAuditManager ? 'Progres verifikasi keseluruhan' : 'Progres prinsip yang ditugaskan' }}</strong>
            <div class="summary-metric">
                <span>{{ $verifiedItems }} / {{ $totalItems }} indikator terisi</span>
                <strong>{{ $progress }}%</strong>
            </div>
        </div>
        <div class="progress progress--lg">
            <div class="progress__track">
                <div class="progress__bar" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        <div class="summary-foot">
            <span>Memenuhi: {{ $compliant }}</span>
            <span> · </span>
            <span>Tidak memenuhi: {{ $nonCompliant }}</span>
            <span> · </span>
            <span>NA: {{ $na }}</span>
        </div>
    </section>

    <div class="tabs" role="navigation" aria-label="Pilih prinsip">
        @if ($isIspoChecklist)
            @foreach ($principles as $principleNumber => $principleItems)
                @php
                    $hasNonCompliantItems = $principleItems->contains(fn ($item): bool => $item->status === 'non_compliant');
                @endphp
                <a
                    class="tab {{ $currentPage === $principleNumber ? 'active' : '' }} {{ $hasNonCompliantItems ? 'tab--non-compliant' : '' }}"
                    href="{{ route('audits.show', ['audit' => $audit, 'page' => $principleNumber]) }}"
                    aria-label="Prinsip {{ $principleNumber }} · {{ $principleItems->count() }} indikator{{ $hasNonCompliantItems ? ', terdapat indikator tidak memenuhi' : '' }}"
                >
                    Prinsip {{ $principleNumber }} · {{ $principleItems->count() }}
                    @if ($hasNonCompliantItems)
                        <span class="tab__warning" aria-hidden="true">!</span>
                    @endif
                </a>
            @endforeach
        @else
            @foreach ($statusTabs as $index => $tab)
                <button type="button" class="tab {{ $index === 0 ? 'active' : '' }}">{{ $tab['label'] }} · {{ $tab['count'] }}</button>
            @endforeach
        @endif
    </div>

    <section class="checklist-block" aria-label="Prinsip verifikasi">
        @php
            $principleTitles = [
                1 => 'KEPATUHAN TERHADAP PERATURAN PERUNDANG-UNDANGAN',
                2 => 'PENERAPAN PRAKTEK PERKEBUNAN YANG BAIK',
                3 => 'PENGELOLAAN LINGKUNGAN HIDUP, SUMBER DAYA ALAM, DAN KEANEKARAGAMAN HAYATI',
                4 => 'TANGGUNG JAWAB KETENAGAKERJAAN',
                5 => 'TANGGUNG JAWAB SOSIAL DAN PEMBERDAYAAN EKONOMI MASYARAKAT',
                6 => 'PENERAPAN TRANSPARANSI',
                7 => 'PENINGKATAN USAHA SECARA BERKELANJUTAN',
            ];
            $activePrinciple = $isIspoChecklist ? $currentPage : 1;
            $principleNumbers = $reviewablePrinciples->values();
            $principlePosition = $principleNumbers->search($currentPage);
            $previousPrinciple = $principlePosition > 0 ? $principleNumbers->get($principlePosition - 1) : null;
            $nextPrinciple = $principleNumbers->get($principlePosition + 1);
            $hasPrinciplePagination = $isIspoChecklist && $principleNumbers->count() > 1;
            $hasLegacyPagination = ! $isIspoChecklist && $items->hasPages();
        @endphp
        <div class="principle-head">
            <span class="num">Prinsip {{ $activePrinciple }}</span>
            <h2>{{ $principleTitles[$activePrinciple] }}</h2>
        </div>

        @php
            $criteriaMap = config('ispo.criteria', []);
            $previousCriteria = null;
        @endphp

        @forelse ($items as $item)
            @php
                preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/s', $item->title, $indicatorParts);
                $indicatorCode = $indicatorParts[1] ?? $loop->iteration;
                $indicatorTitle = $indicatorParts[2] ?? $item->title;
                $criteriaCode = $isIspoChecklist ? \App\Support\IspoChecklist::criteriaCode($item->title) : null;
                $criteriaInfo = $criteriaCode ? ($criteriaMap[$criteriaCode] ?? null) : null;
                $statusLabel = $item->status === 'compliant' ? 'Memenuhi' : ($item->status === 'non_compliant' ? 'Tidak memenuhi' : ($item->status === 'not_applicable' ? 'NA' : 'Belum diperiksa'));
                $statusClass = $item->status === 'compliant' ? 'chip--done' : ($item->status === 'non_compliant' ? 'chip--red' : ($item->status === 'not_applicable' ? 'chip--todo' : 'chip--todo'));
            @endphp

            @if ($criteriaInfo && $criteriaCode !== $previousCriteria)
                <div class="criteria-head">
                    <span class="criteria-code">{{ $criteriaCode }}</span>
                    <span class="criteria-title">{{ $criteriaInfo['title'] }}</span>
                </div>
                @if (! empty($criteriaInfo['description']))
                    <p class="criteria-desc">{{ $criteriaInfo['description'] }}</p>
                @endif
                @php $previousCriteria = $criteriaCode; @endphp
            @endif

            <article class="checklist-item {{ $item->status === 'non_compliant' ? 'checklist-item--non-compliant' : '' }}" data-audit-item>
                <div class="checklist-top">
                    <div class="checklist-num">{{ $indicatorCode }}</div>
                    <div class="checklist-body">
                        <p class="checklist-gist">{{ $indicatorTitle }}</p>
                        <div class="parameter-box {{ $item->status === 'non_compliant' ? 'parameter-box--non-compliant' : '' }}">
                            <div class="parameter-label" data-audit-parameter-label>{{ $item->status === 'non_compliant' ? 'Parameter · Tidak memenuhi' : 'Parameter' }}</div>
                            <div class="parameter-body">{!! nl2br(e($item->description ?: 'Tersedia dokumen dan bukti pendukung sesuai persyaratan.')) !!}</div>
                        </div>

                        <div class="hasil-block {{ $item->status === 'non_compliant' ? 'hasil-block--non-compliant' : '' }}">
                            <div class="hasil-head">
                                <span class="field-label">Hasil Audit</span>
                                <span class="hasil-count">{{ $item->hasils->count() }} hasil</span>
                            </div>
                            <p class="hasil-alert" role="status" aria-live="polite" @if ($item->status !== 'non_compliant') hidden @endif>
                                Tidak memenuhi · Perlu tindak lanjut. Hasil audit di bawah dapat diedit.
                            </p>

                            <div class="hasil-list">
                                @forelse ($item->hasils as $hasil)
                                    <div class="hasil-card {{ $item->status === 'non_compliant' ? 'hasil-card--non-compliant' : '' }}">
                                        <form method="POST" action="{{ route('audit-hasils.update', $hasil) }}" enctype="multipart/form-data" class="hasil-form">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="page" value="{{ $currentPage }}">
                                            <label class="hasil-input-label" for="hasil-{{ $hasil->id }}">Catatan hasil audit</label>
                                            <textarea id="hasil-{{ $hasil->id }}" class="note-box {{ $item->status === 'non_compliant' ? 'note-box--non-compliant' : '' }}" name="content" rows="3" placeholder="Tulis hasil audit...">{{ old('content', $hasil->content) }}</textarea>
                                            <div class="hasil-form-footer">
                                                <div class="hasil-evidence-controls">
                                                    <div class="hasil-file-picker">
                                                        <label class="hasil-input-label" for="hasil-bukti-{{ $hasil->id }}">Ganti bukti</label>
                                                        <input id="hasil-bukti-{{ $hasil->id }}" class="hasil-file-input" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" aria-label="Ganti bukti audit">
                                                    </div>
                                                    @if ($hasil->evidence_path)
                                                        @php
                                                            $evidenceUrl = Storage::disk('public')->url($hasil->evidence_path);
                                                            $evidenceExt = strtolower(pathinfo($hasil->evidence_path, PATHINFO_EXTENSION));
                                                            $isImageEvidence = in_array($evidenceExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                                        @endphp

                                                        @if ($isImageEvidence)
                                                            <a class="evidence-preview-link" href="{{ $evidenceUrl }}" target="_blank" rel="noreferrer" aria-label="Buka pratinjau bukti audit">
                                                                <img class="evidence-thumbnail" src="{{ $evidenceUrl }}" alt="Bukti audit">
                                                            </a>
                                                        @endif

                                                        <a class="hasil-evidence-link" href="{{ $evidenceUrl }}" target="_blank" rel="noreferrer">Lihat bukti tersimpan</a>
                                                    @endif
                                                </div>
                                                <button class="save-btn" type="submit">Simpan perubahan</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('audit-hasils.destroy', $hasil) }}" class="hasil-delete-form" onsubmit="return confirm('Hapus hasil audit ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="page" value="{{ $currentPage }}">
                                            <button type="submit" class="hasil-delete">Hapus hasil audit</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="hasil-empty">Belum ada hasil audit. Tekan "Tambah hasil audit" untuk menambahkan.</p>
                                @endforelse
                            </div>

                            <details class="hasil-add">
                                <summary>Tambah hasil audit</summary>
                                <div class="add-form">
                                    <form method="POST" action="{{ route('audit-items.hasil.store', $item) }}" enctype="multipart/form-data" class="hasil-form">
                                        @csrf
                                        <input type="hidden" name="page" value="{{ $currentPage }}">
                                        <label class="hasil-input-label" for="hasil-baru-{{ $item->id }}">Catatan hasil audit baru</label>
                                        <textarea id="hasil-baru-{{ $item->id }}" class="note-box {{ $item->status === 'non_compliant' ? 'note-box--non-compliant' : '' }}" name="content" rows="3" placeholder="Tulis hasil audit atau temuan baru..."></textarea>
                                        <div class="hasil-form-footer">
                                            <div class="hasil-file-picker">
                                                <label class="hasil-input-label" for="hasil-bukti-baru-{{ $item->id }}">Bukti pendukung (opsional)</label>
                                                <input id="hasil-bukti-baru-{{ $item->id }}" class="hasil-file-input" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" aria-label="Bukti pendukung audit">
                                            </div>
                                            <button class="save-btn" type="submit">Tambah hasil audit</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('audit-items.update', $item) }}" class="item-status-form" data-audit-status-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="page" value="{{ $currentPage }}">
                    <div class="status-form {{ $item->status === 'non_compliant' ? 'status-form--non-compliant' : '' }}" data-audit-status-controls>
                        <label for="status-{{ $item->id }}">Status indikator:</label>
                        <span class="chip {{ $statusClass }}" data-audit-status-chip>{{ $statusLabel }}</span>
                        <select id="status-{{ $item->id }}" name="status" data-audit-status required>
                            @if ($item->status === 'pending')
                                <option value="" selected disabled hidden></option>
                            @endif
                            <option value="compliant" @selected($item->status === 'compliant')>Memenuhi</option>
                            <option value="non_compliant" @selected($item->status === 'non_compliant')>Tidak memenuhi</option>
                            <option value="not_applicable" @selected($item->status === 'not_applicable')>NA</option>
                        </select>
                        <button class="save-btn" type="submit">Simpan</button>
                    </div>
                </form>
            </article>
        @empty
            <div class="empty">Belum ada prinsip verifikasi untuk audit ini.</div>
        @endforelse

        @if ($hasPrinciplePagination || $hasLegacyPagination)
            <div class="pagination-row">
                @if (($isIspoChecklist && ! $previousPrinciple) || (! $isIspoChecklist && $currentPage <= 1))
                    <button type="button" class="btn btn--ghost btn--sm" disabled>Prinsip sebelumnya</button>
                @elseif ($isIspoChecklist)
                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', ['audit' => $audit, 'page' => $previousPrinciple]) }}">Prinsip sebelumnya</a>
                @else
                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', ['audit' => $audit, 'page' => $currentPage - 1]) }}">Prinsip sebelumnya</a>
                @endif

                @if ($isIspoChecklist)
                    <span>Prinsip {{ $currentPage }} · {{ $principlePosition + 1 }} dari {{ $principleNumbers->count() }}</span>
                @else
                    <span>Halaman {{ $currentPage }} dari {{ $items->lastPage() }} prinsip</span>
                @endif

                @if ($isIspoChecklist && $nextPrinciple)
                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', ['audit' => $audit, 'page' => $nextPrinciple]) }}">Prinsip berikutnya</a>
                @elseif (! $isIspoChecklist && $currentPage < $items->lastPage())
                    <a class="btn btn--ghost btn--sm" href="{{ route('audits.show', ['audit' => $audit, 'page' => $currentPage + 1]) }}">Prinsip berikutnya</a>
                @else
                    <button type="button" class="btn btn--ghost btn--sm" disabled>Prinsip berikutnya</button>
                @endif
            </div>
        @endif

        @if ($isAuditManager)
            <div class="audit-completion">
                <div>
                    <strong>{{ $verifiedItems === $totalItems && $totalItems > 0 ? 'Semua indikator sudah diisi' : 'Audit belum selesai' }}</strong>
                    <span>{{ $verifiedItems }} dari {{ $totalItems }} indikator sudah memiliki status.</span>
                    <span class="draft-note">Status audit: <b>{{ $audit->status === 'completed' ? 'Selesai' : 'Draft' }}</b> · data tersimpan otomatis setiap kali Anda menekan Simpan.</span>
                </div>
                <div class="completion-actions">
                    @if ($verifiedItems === $totalItems && $totalItems > 0)
                        <a class="btn btn--primary" href="{{ route('audits.pdf', $audit) }}">Download 1 file lengkap (PDF)</a>
                    @else
                        <span class="draft-note">Lengkapi semua indikator untuk mengunduh seluruh prinsip dalam 1 file.</span>
                    @endif
                    <form method="POST" action="{{ route('audits.complete', $audit) }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit" @disabled($verifiedItems !== $totalItems || $totalItems === 0)>
                            {{ $audit->status === 'completed' ? 'Audit telah selesai' : 'Tandai audit selesai' }}
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </section>
@endsection