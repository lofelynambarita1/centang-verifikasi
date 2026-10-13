<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Enums\SystemRole;
use App\Http\Requests\StoreAuditProjectRequest;
use App\Http\Requests\UpdateAuditProjectAssessmentSummaryRequest;
use App\Http\Requests\UpdateAuditProjectRequest;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditProgressService;
use App\Services\AuditProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuditProjectController extends Controller
{
    public function __construct(
        private readonly AuditProjectService $projects,
        private readonly AuditProgressService $progress,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $filterProjects = $this->accessibleProjectsQuery($user)
            ->with('leadMember.user')
            ->get(['id', 'audit_type']);

        $statusGroups = [
            'not_started' => [
                'label' => 'Belum Dimulai',
                'values' => ['draft', 'team_setup', 'assignment'],
            ],
            'running' => [
                'label' => 'Berjalan',
                'values' => ['in_progress', 'review'],
            ],
            'completed' => [
                'label' => 'Selesai',
                'values' => ['final'],
            ],
            'archived' => [
                'label' => 'Diarsipkan',
                'values' => ['archived'],
            ],
        ];

        $selectedStatus = $request->string('status')->toString();
        $projects = $this->accessibleProjectsQuery($user)
            ->with(['company', 'template', 'leadMember.user', 'creator'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('project_code', 'like', '%'.$search.'%')
                        ->orWhereHas('company', fn (Builder $company): Builder => $company->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when(
                array_key_exists($selectedStatus, $statusGroups),
                fn (Builder $query): Builder => $query->whereIn('status', $statusGroups[$selectedStatus]['values']),
            )
            ->when($request->filled('audit_type'), fn (Builder $query): Builder => $query->where('audit_type', $request->string('audit_type')->toString()))
            ->when($request->filled('lead_auditor_id'), fn (Builder $query): Builder => $query->whereHas('leadMember', fn (Builder $lead): Builder => $lead->where('user_id', $request->integer('lead_auditor_id'))))
            ->latest()
            ->get();

        return view('audit-projects.index', [
            'projects' => $projects,
            'statuses' => $statusGroups,
            'auditTypes' => $filterProjects->pluck('audit_type')->filter()->unique()->sort()->values(),
            'leadAuditors' => $filterProjects->pluck('leadMember.user')->filter()->unique('id')->sortBy('name')->values(),
            'hasFilters' => $request->hasAny(['search', 'status', 'audit_type', 'lead_auditor_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', AuditProject::class);

        return view('audit-projects.create', $this->formPayload());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuditProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', AuditProject::class);

        $data = $request->validated();
        $duplicateExists = AuditProject::query()
            ->where('name', $data['name'])
            ->where('company_id', $data['company_id'])
            ->whereDate('audit_start_date', $data['audit_start_date'])
            ->whereDate('audit_end_date', $data['audit_end_date'])
            ->exists();

        if ($duplicateExists && ! $request->boolean('confirm_duplicate')) {
            return back()
                ->withInput()
                ->with('duplicate_warning', 'Project dengan nama, perusahaan, dan periode yang sama sudah ditemukan. Pastikan Anda tidak membuat project duplikat.');
        }

        $project = $this->projects->createProject($data, $request->user());

        return redirect()->route('audit-projects.show', $project)
            ->with('success', 'Project audit dan tim auditor berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AuditProject $auditProject): View
    {
        $this->authorize('view', $auditProject);

        $auditProject->load([
            'company', 'template', 'creator',
            'leadMember.user', 'members.user', 'activityLogs.user',
        ])->loadCount([
            'snapshotPrinciples', 'snapshotSections', 'snapshotIndicators', 'snapshotParameters', 'assignments',
        ]);

        $currentMember = $auditProject->members->firstWhere('user_id', auth()->id());

        return view('audit-projects.show', [
            'project' => $auditProject,
            'currentProjectRole' => $currentMember?->project_role === ProjectRole::LeadAuditor
                ? 'Lead Auditor'
                : ($currentMember ? 'Auditor' : null),
            'projectRoleLabel' => $currentMember?->project_role?->label(),
        ]);
    }

    public function progress(AuditProject $auditProject): View
    {
        $this->authorize('viewProgress', $auditProject);

        $auditProject->load('company', 'template', 'members.user', 'snapshotPrinciples');
        $currentMember = $auditProject->members->firstWhere('user_id', auth()->id());
        $reviewStatus = request()->string('review_status')->toString() ?: 'submitted';
        $reviewStatus = in_array($reviewStatus, ['submitted', 'revision_requested', 'approved', 'all'], true)
            ? $reviewStatus
            : 'submitted';
        $reviewAuditor = request()->integer('review_auditor') ?: null;
        $reviewQueue = $this->progress->reviewQueue($auditProject, $reviewStatus, $reviewAuditor);
        $reviewCounts = collect([
            'submitted' => $this->progress->reviewQueue($auditProject, 'submitted')->count(),
            'revision_requested' => $this->progress->reviewQueue($auditProject, 'revision_requested')->count(),
            'approved' => $this->progress->reviewQueue($auditProject, 'approved')->count(),
        ]);

        return view('audit-projects.progress', [
            'project' => $auditProject,
            'summary' => $this->progress->projectSummary($auditProject),
            'auditors' => $this->progress->auditorBreakdown($auditProject),
            'principles' => $this->progress->principleBreakdown($auditProject),
            'indicators' => $this->progress->indicatorDetails($auditProject),
            'reviewQueue' => $reviewQueue,
            'reviewStatus' => $reviewStatus,
            'reviewAuditor' => $reviewAuditor,
            'reviewCounts' => $reviewCounts,
            'projectRoleLabel' => $currentMember?->project_role?->label(),
        ]);
    }

    public function updateAssessmentSummary(
        UpdateAuditProjectAssessmentSummaryRequest $request,
        AuditProject $auditProject,
    ): RedirectResponse {
        $this->authorize('updateAssessmentSummary', $auditProject);

        $auditProject->update($request->validated());

        return redirect()->route('audit-projects.show', $auditProject)
            ->with('success', 'Ringkasan penilaian berhasil disimpan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AuditProject $auditProject): View
    {
        $this->authorize('update', $auditProject);

        return view('audit-projects.edit', [
            'project' => $auditProject,
            ...$this->formPayload(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuditProjectRequest $request, AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('update', $auditProject);

        $this->projects->updateProject($auditProject, $request->validated(), $request->user());

        return redirect()->route('audit-projects.show', $auditProject)
            ->with('success', 'Project audit diperbarui.');
    }

    public function start(AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('manageAssignments', $auditProject);

        $this->projects->startAudit($auditProject, request()->user());

        return redirect()->route('audit-projects.my-work', $auditProject)
            ->with('success', 'Audit berhasil dimulai. Auditor sudah dapat mengisi Hasil Audit.');
    }

    /**
     * Shared dropdown data for the create/edit forms.
     *
     * @return array{templates: mixed, companies: mixed, auditors: mixed}
     */
    private function formPayload(): array
    {
        return [
            'templates' => AuditTemplate::where('is_active', true)->orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'auditors' => User::where('system_role', SystemRole::Auditor->value)->orderBy('name')->get(),
        ];
    }

    private function accessibleProjectsQuery(User $user): Builder
    {
        return AuditProject::query()
            ->when(! $user->system_role->isAdminOrAbove(), function (Builder $query) use ($user): void {
                $query->whereHas('members', fn (Builder $members): Builder => $members->where('user_id', $user->id));
            });
    }
}
