<?php

namespace Database\Factories;

use App\Models\AuditTemplatePrinciple;
use App\Models\AuditTemplateSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditTemplateSection>
 */
class AuditTemplateSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'principle_id' => AuditTemplatePrinciple::factory(),
            'parent_id' => null,
            'code' => fake()->unique()->numerify('#.#'),
            'title' => 'Kriteria '.fake()->word(),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
