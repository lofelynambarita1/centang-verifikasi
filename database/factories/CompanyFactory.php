<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'industry' => fake()->randomElement(['Manufaktur', 'Logistik', 'Distribusi']),
            'address' => fake()->address(),
            'land_area' => fake()->randomElement(['125 ha', '250 ha', '1.250 ha']),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
        ];
    }
}
