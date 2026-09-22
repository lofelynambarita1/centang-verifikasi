<?php

namespace Database\Factories;

use App\Models\AuditProject;
use App\Models\AuditProjectPrinciple;
use App\Models\AuditProjectSection;
use App\Models\AuditTemplateSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditProjectSection>
 */
class AuditProjectSectionFactory extends Factory
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
            'audit_project_principle_id' => AuditProjectPrinciple::factory(),
            'parent_id' => null,
            'source_template_section_id' => AuditTemplateSection::factory(),
            'code' => fake()->unique()->numerify('#.#'),
            'title' => 'Kriteria '.fake()->word(),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
