<?php

namespace Database\Factories;

use App\Models\AuditAssignmentHistory;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditAssignmentHistory>
 */
class AuditAssignmentHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_project_id' => AuditProject::factory(),
            'audit_project_indicator_id' => AuditProjectIndicator::factory(),
            'from_user_id' => null,
            'to_user_id' => User::factory()->auditor(),
            'changed_by' => User::factory()->admin(),
            'reason' => null,
            'created_at' => now(),
        ];
    }
}
