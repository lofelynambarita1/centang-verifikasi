<?php

namespace App\Policies;

use App\Models\AuditTemplate;
use App\Models\User;

class AuditTemplatePolicy
{
    /**
     * Determine whether the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->system_role->isAdminOrAbove();
    }

    /**
     * Determine whether the user can update the template.
     */
    public function update(User $user, AuditTemplate $template): bool
    {
        return $user->system_role->isAdminOrAbove();
    }
}
