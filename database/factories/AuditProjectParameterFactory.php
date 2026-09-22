<?php

namespace Database\Factories;

use App\Enums\ParameterType;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditProjectParameter;
use App\Models\AuditTemplateParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditProjectParameter>
 */
class AuditProjectParameterFactory extends Factory
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
            'source_template_parameter_id' => AuditTemplateParameter::factory(),
            'parameter_type' => fake()->randomElement(ParameterType::cases())->value,
            'number' => fake()->optional()->numerify('#'),
            'content' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
