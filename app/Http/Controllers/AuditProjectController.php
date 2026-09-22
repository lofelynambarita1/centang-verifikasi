<?php

namespace App\Http\Controllers;

use App\Enums\SystemRole;
use App\Http\Requests\StoreAuditProjectRequest;
use App\Http\Requests\UpdateAuditProjectRequest;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuditProjectController extends Controller
{
    public function __construct(private readonly AuditProjectService $projects) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = auth()->user();

        $projects = AuditProject::query()
            ->with(['company', 'template', 'leadMember.user', 'creator'])
            ->when(! $user->system_role->isAdminOrAbove(), function ($query) use ($user): void {
                $query->whereHas('members', fn ($members) => $members->where('user_id', $user->id));
            })
            ->latest()
            ->get();

        return view('audit-projects.index', ['projects' => $projects]);
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

        $project = $this->projects->createProject($request->validated(), $request->user());

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

        return view('audit-projects.show', ['project' => $auditProject]);
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
}
