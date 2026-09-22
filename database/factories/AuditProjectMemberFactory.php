<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\AuditProject;
use App\Models\AuditProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditProjectMember>
 */
class AuditProjectMemberFactory extends Factory
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
            'user_id' => User::factory()->auditor(),
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => User::factory()->admin(),
        ];
    }

    public function leadAuditor(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_role' => ProjectRole::LeadAuditor->value,
        ]);
    }
}
