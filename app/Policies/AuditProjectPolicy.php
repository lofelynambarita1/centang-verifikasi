<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\AuditProject;
use App\Models\User;

class AuditProjectPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AuditProject $project): bool
    {
        if ($user->system_role->isAdminOrAbove()) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    public function viewProgress(User $user, AuditProject $project): bool
    {
        return $user->system_role->isAdminOrAbove() || $project->userIsLead($user);
    }

    public function updateAssessmentSummary(User $user, AuditProject $project): bool
    {
        return $project->userIsLead($user);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->system_role->isAdminOrAbove();
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, AuditProject $project): bool
    {
        return $user->system_role->isAdminOrAbove();
    }

    /**
     * Determine whether the user can manage project membership.
     */
    public function manageMembers(User $user, AuditProject $project): bool
    {
        return $user->system_role->isAdminOrAbove();
    }

    /**
     * Determine whether the user can manage indicator assignments. Ordinary
     * auditors (even project members) can only read assignments, while admins,
     * super admins and the project's Lead Auditor may change them.
     */
    public function manageAssignments(User $user, AuditProject $project): bool
    {
        return $user->system_role->isAdminOrAbove()
            || $project->members()
                ->where('user_id', $user->id)
                ->where('project_role', ProjectRole::LeadAuditor->value)
                ->exists();
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, AuditProject $project): bool
    {
        return $user->system_role->isAdminOrAbove();
    }
}
