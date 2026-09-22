<?php

namespace App\Policies;

use App\Enums\AuditProjectStatus;
use App\Enums\AuditResultWorkflowStatus;
use App\Models\AuditIndicatorResult;
use App\Models\AuditProjectIndicator;
use App\Models\User;

class AuditProjectIndicatorPolicy
{
    /**
     * Who may open the indicator workspace:
     *
     * - Super admins/admins (who already hold project access) and the project's
     *   Lead Auditor may open any indicator read-only.
     * - A regular auditor may only open an indicator when they are the ACTIVE
     *   PIC of that very indicator — being a project member alone is no longer
     *   enough collect any project member may open.
     *
     * @see https://...  optimistic lock hardening
     */
    public function view(User $user, AuditProjectIndicator $indicator): bool
    {
        if ($user->system_role->isAdminOrAbove() && $user->can('view', $indicator->project)) {
            return true;
        }

        if ($indicator->project->userIsLead($user)) {
            return true;
        }

        return $indicator->assignment?->pic_user_id === $user->id;
    }

    /**
     * Editing a Hasil Audit draft requires the project to be in-progress and
     * the user to be the ACTIVE PIC of this very indicator. There is no
     * special-case for leads or admins: unless they are legitimately the PIC,
     * they stay read-only.
     */
    public function update(User $user, AuditProjectIndicator $indicator): bool
    {
        if ($indicator->project->status !== AuditProjectStatus::InProgress) {
            return false;
        }

        if ($indicator->assignment?->pic_user_id !== $user->id) {
            return false;
        }

        $result = AuditIndicatorResult::query()
            ->where('audit_project_id', $indicator->audit_project_id)
            ->where('audit_project_indicator_id', $indicator->id)
            ->first();

        return $result === null || in_array(
            $result->workflow_status,
            [AuditResultWorkflowStatus::Draft, AuditResultWorkflowStatus::RevisionRequested],
            true,
        );
    }

    public function review(User $user, AuditProjectIndicator $indicator): bool
    {
        return $indicator->project->status === AuditProjectStatus::InProgress
            && $indicator->project->userIsLead($user);
    }
}
