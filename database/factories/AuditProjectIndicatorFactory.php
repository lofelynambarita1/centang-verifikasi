<?php

namespace Database\Factories;

use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditProjectSection;
use App\Models\AuditTemplateIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditProjectIndicator>
 */
class AuditProjectIndicatorFactory extends Factory
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
            'audit_project_section_id' => AuditProjectSection::factory(),
            'source_template_indicator_id' => AuditTemplateIndicator::factory(),
            'code' => fake()->unique()->numerify('#.#.#'),
            'title' => 'Indikator '.fake()->words(3, true),
            'description' => fake()->sentence(),
            'indicator_type' => fake()->randomElement(['Tunggal', 'Ganda']),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
