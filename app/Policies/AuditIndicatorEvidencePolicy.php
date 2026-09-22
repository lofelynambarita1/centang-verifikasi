<?php

namespace App\Policies;

use App\Enums\AuditEvidenceLifecycleStatus;
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
        return $evidence->lifecycle_status === AuditEvidenceLifecycleStatus::Active
            && $user->can('update', $evidence->indicator);
    }
}
