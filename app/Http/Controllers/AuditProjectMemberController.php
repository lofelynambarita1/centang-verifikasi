<?php

namespace App\Http\Controllers;

use App\Enums\SystemRole;
use App\Http\Requests\AddProjectMemberRequest;
use App\Http\Requests\ReplaceLeadAuditorRequest;
use App\Models\AuditProject;
use App\Models\AuditProjectMember;
use App\Models\User;
use App\Services\AuditProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuditProjectMemberController extends Controller
{
    public function __construct(private readonly AuditProjectService $projects) {}

    /**
     * Show the team management page for a project.
     */
    public function index(AuditProject $auditProject): View
    {
        $this->authorize('view', $auditProject);

        $members = $auditProject->members()
            ->with('user')
            ->orderBy('project_role', 'desc')
            ->orderBy('id')
            ->get();

        return view('audit-projects.team', [
            'project' => $auditProject->load('template', 'company'),
            'members' => $members,
            'availableAuditors' => User::where('system_role', SystemRole::Auditor->value)
                ->whereNotIn('id', $members->pluck('user_id'))
                ->orderBy('name')
                ->get(),
            'auditors' => User::where('system_role', SystemRole::Auditor->value)->orderBy('name')->get(),
        ]);
    }

    /**
     * Add an auditor as a project member.
     */
    public function store(AddProjectMemberRequest $request, AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('manageMembers', $auditProject);

        $member = User::findOrFail($request->integer('user_id'));
        $this->projects->addAuditor($auditProject, $member, $request->user());

        return back()->with('success', 'Anggota auditor ditambahkan ke project.');
    }

    /**
     * Replace the active Lead Auditor. The old lead stays as an ordinary auditor.
     */
    public function replaceLead(ReplaceLeadAuditorRequest $request, AuditProject $auditProject): RedirectResponse
    {
        $this->authorize('manageMembers', $auditProject);

        $newLead = User::findOrFail($request->integer('new_lead_auditor_id'));
        $this->projects->replaceLeadAuditor($auditProject, $newLead, $request->user());

        return back()->with('success', 'Lead Auditor diganti. Lead lama tetap menjadi auditor project ini.');
    }

    /**
     * Remove an auditor from the project.
     */
    public function destroy(AuditProject $auditProject, AuditProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $auditProject);

        if ($member->audit_project_id !== $auditProject->id) {
            abort(404);
        }

        $this->projects->removeMember($auditProject, $member, auth()->user());

        return back()->with('success', 'Anggota dihapus dari project.');
    }
}
