<?php

namespace Database\Factories;

use App\Models\AuditProject;
use App\Models\AuditProjectPrinciple;
use App\Models\AuditTemplatePrinciple;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditProjectPrinciple>
 */
class AuditProjectPrincipleFactory extends Factory
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
            'source_template_principle_id' => AuditTemplatePrinciple::factory(),
            'code' => (string) fake()->unique()->numberBetween(1, 9),
            'title' => 'Prinsip '.fake()->word(),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
