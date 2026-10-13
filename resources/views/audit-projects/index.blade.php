@extends('layouts.app')

@php
    $avatarColors = ['avatar--coral', 'avatar--navy', 'avatar--mint', 'avatar--amber', 'avatar--violet'];

    // Helper closure untuk inisial nama
    $getInitials = function (string $name): string {
        $words = array_values(array_filter(preg_split('/[\s]+/', trim($name))));
        $chars = array_map(fn ($word) => strtoupper(substr($word, 0, 1)), array_slice($words, 0, 2));

        return $chars ? implode('', $chars) : '?';
    };

    // Pemetaan status ke CSS class dan Label
    $statusChip = [
        'draft'       => ['class' => 'chip--project-start',     'label' => 'Belum Dimulai'],
        'team_setup'  => ['class' => 'chip--project-start',     'label' => 'Belum Dimulai'],
        'assignment'  => ['class' => 'chip--project-start',     'label' => 'Belum Dimulai'],
        'in_progress' => ['class' => 'chip--project-running',   'label' => 'Berjalan'],
        'review'      => ['class' => 'chip--project-running',   'label' => 'Berjalan'],
        'final'       => ['class' => 'chip--project-completed', 'label' => 'Selesai'],
        'archived'    => ['class' => 'chip--project-archived',  'label' => 'Diarsipkan'],
    ];
@endphp

@section('title', 'Project Audit | Verifikasi ISPO')
@section('eyebrow', 'PROJECT AUDIT')
@section('heading', 'Project Audit')

@section('actions')
    @can('manage-audit-projects')
        <a class="btn btn--primary" href="{{ route('audit-projects.create') }}">+ Buat Project</a>
    @endcan
@endsection

@section('content')
    <p class="page-intro">Kelola dan pantau seluruh project audit ISPO.</p>

    {{-- Filter & Search Form --}}
    <form class="project-filters" method="GET" action="{{ route('audit-projects.index') }}" id="filterForm">
        <div class="project-filters__search">
            <label for="search" class="sr-only">Cari project</label>
            <input 
                type="search" 
                id="search" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Cari project, kode, atau perusahaan..."
                autocomplete="off"
            >
        </div>

        <select name="status" aria-label="Filter status">
            <option value="">Semua Status</option>
            @foreach ($statuses as $value => $status)
                <option value="{{ $value }}" @selected(request('status') === (string) $value)>
                    {{ $status['label'] }}
                </option>
            @endforeach
        </select>

        <select name="audit_type" aria-label="Filter jenis audit">
            <option value="">Semua Jenis Audit</option>
            @foreach ($auditTypes as $auditType)
                <option value="{{ $auditType }}" @selected(request('audit_type') === $auditType)>
                    {{ $auditType }}
                </option>
            @endforeach
        </select>

        <select name="lead_auditor_id" aria-label="Filter Lead Auditor">
            <option value="">Semua Lead Auditor</option>
            @foreach ($leadAuditors as $leadAuditor)
                <option value="{{ $leadAuditor->id }}" @selected((string) request('lead_auditor_id') === (string) $leadAuditor->id)>
                    {{ $leadAuditor->name }}
                </option>
            @endforeach
        </select>

        @if ($hasFilters)
            <a class="project-filters__reset" href="{{ route('audit-projects.index') }}">Reset Filter</a>
        @endif
    </form>

    {{-- Daftar Project --}}
    <section class="project-list" id="projectList" aria-label="Daftar project audit">
        @forelse ($projects as $project)
            @php
                $statusKey     = $project->status->value ?? $project->status;
                $projectStatus = $statusChip[$statusKey] ?? ['class' => 'chip--project-start', 'label' => 'Belum Dimulai'];
                $avatarColor   = $avatarColors[$loop->index % count($avatarColors)];
            @endphp

            <article class="project-card">
                <div class="project-card__main">
                    <div class="project-card__heading">
                        <div class="project-card__identity">
                            <div class="avatar {{ $avatarColor }}" aria-hidden="true">
                                {{ $getInitials($project->name) }}
                            </div>
                            <div>
                                <h2>{{ $project->name }}</h2>
                                <p>{{ $project->company?->name ?? 'Perusahaan belum tersedia' }}</p>
                            </div>
                        </div>
                        <span class="chip {{ $projectStatus['class'] }}">{{ $projectStatus['label'] }}</span>
                    </div>

                    <div class="project-card__meta">
                        <div>
                            <span>Jenis audit</span>
                            <strong>{{ $project->audit_type ?: 'Belum diisi' }}</strong>
                        </div>
                        <div>
                            <span>Periode audit</span>
                            <strong>
                                {{ $project->audit_start_date?->format('d M Y') ?? '-' }} – 
                                {{ $project->audit_end_date?->format('d M Y') ?? '-' }}
                            </strong>
                        </div>
                        <div>
                            <span>Lead Auditor</span>
                            <strong>{{ $project->leadMember?->user?->name ?? 'Belum ditentukan' }}</strong>
                        </div>
                    </div>

                    <div class="project-card__template">
                        <span>Acuan ISPO</span>
                        <strong>{{ $project->template?->name ?? 'Belum tersedia' }}</strong>
                    </div>
                </div>

                <div class="project-card__action">
                    <a class="btn btn--ghost" href="{{ route('audit-projects.show', $project) }}">Buka Project</a>
                </div>
            </article>
        @empty
            <div class="project-empty">
                @if ($hasFilters)
                    <strong>Tidak ada project yang sesuai dengan pencarian atau filter.</strong>
                    <span>Coba ubah kata kunci pencarian atau hapus beberapa filter.</span>
                @else
                    <strong>Belum ada project audit.</strong>
                    <span>Buat project pertama untuk memulai proses verifikasi ISPO.</span>
                    @can('manage-audit-projects')
                        <a class="btn btn--primary" href="{{ route('audit-projects.create') }}" style="margin-top: 1rem;">
                            + Buat Project
                        </a>
                    @endcan
                @endif
            </div>
        @endforelse
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm  = document.getElementById('filterForm');
        const projectList = document.getElementById('projectList');
        if (!filterForm) return;

        const searchInput = filterForm.querySelector('input[name="search"]');
        const selects     = filterForm.querySelectorAll('select');
        let debounceTimer;

        // Visual loading feedback saat mereload hasil filter
        const showLoadingState = () => {
            if (projectList) {
                projectList.style.opacity = '0.4';
                projectList.style.pointerEvents = 'none';
                projectList.style.transition = 'opacity 0.15s ease-in-out';
            }
        };

        // Auto submit ketika dropdown select berubah
        selects.forEach(select => {
            select.addEventListener('change', () => {
                showLoadingState();
                filterForm.submit();
            });
        });

        // Search input debounce (400ms)
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    showLoadingState();
                    filterForm.submit();
                }, 400);
            });

            // Prevent form re-submission ganda saat menekan tombol Enter
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    showLoadingState();
                    filterForm.submit();
                }
            });

            // Menjaga posisi fokus kursor search setelah reload halaman
            if (sessionStorage.getItem('audit_search_focused') === 'true') {
                const len = searchInput.value.length;
                searchInput.focus();
                searchInput.setSelectionRange(len, len);
                sessionStorage.removeItem('audit_search_focused');
            }

            searchInput.addEventListener('focus', () => {
                sessionStorage.setItem('audit_search_focused', 'true');
            });
            searchInput.addEventListener('blur', () => {
                sessionStorage.removeItem('audit_search_focused');
            });
        }
    });
</script>
@endpush