<?php

namespace Database\Factories;

use App\Models\AuditTemplate;
use App\Models\AuditTemplatePrinciple;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditTemplatePrinciple>
 */
class AuditTemplatePrincipleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_template_id' => AuditTemplate::factory(),
            'code' => (string) fake()->unique()->numberBetween(1, 9),
            'title' => 'Prinsip '.fake()->word(),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
