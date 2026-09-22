<?php

namespace Database\Factories;

use App\Models\AuditTemplateIndicator;
use App\Models\AuditTemplateSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditTemplateIndicator>
 */
class AuditTemplateIndicatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => AuditTemplateSection::factory(),
            'code' => fake()->unique()->numerify('#.#.#'),
            'title' => 'Indikator '.fake()->words(3, true),
            'description' => fake()->sentence(),
            'indicator_type' => fake()->randomElement(['Tunggal', 'Ganda']),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
