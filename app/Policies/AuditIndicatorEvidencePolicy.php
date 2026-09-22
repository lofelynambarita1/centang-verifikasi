<?php

namespace App\Policies;

use App\Models\AuditIndicatorEvidence;
use App\Models\User;

class AuditIndicatorEvidencePolicy
{
    public function view(User $user, AuditIndicatorEvidence $evidence): bool
    {
        return $user->can('view', $evidence->indicator);
    }

    public function update(User $user, AuditIndicatorEvidence $evidence): bool
    {
        return $user->can('update', $evidence->indicator);
    }
}
