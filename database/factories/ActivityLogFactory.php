<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\AuditProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'user_id' => User::factory(),
            'activity' => 'project.created',
            'description' => 'Project dibuat.',
            'meta' => null,
        ];
    }
}
