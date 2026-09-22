<?php

namespace Database\Factories;

use App\Models\AuditTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditTemplate>
 */
class AuditTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->bothify('ISO-####/GIS-#.#')),
            'scheme' => 'ISPO',
            'name' => fake()->randomElement(['Laporan Verifikasi Hasil ISPO', 'Verifikasi RSPO', 'Checklist Operasional']),
            'version' => '1.0',
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => User::factory()->admin(),
        ];
    }
}
