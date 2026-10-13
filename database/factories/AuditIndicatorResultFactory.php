<?php

namespace Database\Factories;

use App\Models\AuditIndicatorResult;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditIndicatorResult>
 */
class AuditIndicatorResultFactory extends Factory
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
            'result_text' => $this->faker->paragraph(),
            'result_content' => null,
            'created_by' => User::factory()->auditor(),
            'updated_by' => User::factory()->auditor(),
            'lock_version' => 1,
        ];
    }
}
