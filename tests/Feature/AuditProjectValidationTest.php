<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\IsSystemAuditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuditProjectValidationTest extends TestCase
{
    use RefreshDatabase;

    private function passesSystemAuditorRule(int $userId): bool
    {
        return Validator::make(
            ['user_id' => $userId],
            ['user_id' => [new IsSystemAuditor]],
        )->passes();
    }

    public function test_a_valid_auditor_passes_the_system_auditor_rule(): void
    {
        $auditor = User::factory()->auditor()->create();

        $this->assertTrue($this->passesSystemAuditorRule($auditor->id));
    }

    public function test_an_admin_fails_the_system_auditor_rule(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($this->passesSystemAuditorRule($admin->id));
    }

    public function test_a_super_admin_fails_the_system_auditor_rule(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse($this->passesSystemAuditorRule($superAdmin->id));
    }
}
