<?php

namespace App\Rules;

use App\Models\AuditProjectMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProjectMemberNotDuplicate implements ValidationRule
{
    public function __construct(private readonly int $projectId) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = AuditProjectMember::query()
            ->where('audit_project_id', $this->projectId)
            ->where('user_id', $value)
            ->exists();

        if ($exists) {
            $fail('User sudah terdaftar pada project ini.');
        }
    }
}
