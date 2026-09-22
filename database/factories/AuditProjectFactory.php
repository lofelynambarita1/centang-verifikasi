<?php

namespace Database\Factories;

use App\Enums\AuditProjectStatus;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditProject>
 */
class AuditProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_code' => 'PRJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'template_id' => AuditTemplate::factory(),
            'company_id' => Company::factory(),
            'name' => 'Audit '.fake()->company(),
            'audit_type' => fake()->randomElement(['Audit Lapangan', 'Audit Dokumen', 'Verifikasi']),
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => fake()->randomFloat(2, 10, 2000),
            'status' => AuditProjectStatus::Draft->value,
            'created_by' => User::factory()->admin(),
        ];
    }
}
