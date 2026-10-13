<?php

namespace Database\Factories;

use App\Models\AuditIndicatorResult;
use App\Models\AuditIndicatorResultVersion;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditIndicatorResultVersion>
 */
class AuditIndicatorResultVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_indicator_result_id' => AuditIndicatorResult::factory(),
            'audit_project_id' => AuditProject::factory(),
            'audit_project_indicator_id' => AuditProjectIndicator::factory(),
            'version_number' => 1,
            'result_text' => $this->faker->paragraph(),
            'result_content' => null,
            'changed_by' => User::factory()->auditor(),
            'created_at' => now(),
        ];
    }
}
