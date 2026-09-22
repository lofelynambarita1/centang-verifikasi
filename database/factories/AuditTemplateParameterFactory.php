<?php

namespace Database\Factories;

use App\Enums\ParameterType;
use App\Models\AuditTemplateIndicator;
use App\Models\AuditTemplateParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditTemplateParameter>
 */
class AuditTemplateParameterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indicator_id' => AuditTemplateIndicator::factory(),
            'parameter_type' => fake()->randomElement(ParameterType::cases())->value,
            'number' => fake()->optional()->numerify('#'),
            'content' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 9),
        ];
    }
}
