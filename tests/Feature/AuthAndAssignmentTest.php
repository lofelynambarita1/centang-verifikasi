<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_reach_the_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'password', 'role' => 'admin']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_lead_can_assign_multiple_auditors_to_one_principle(): void
    {
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditorOne = User::factory()->create(['role' => 'auditor']);
        $auditorTwo = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit assignment']);

        $this->actingAs($lead)->put(route('audits.assignments.update', $audit), [
            'assignments' => [
                ['principle' => 1, 'users' => [$auditorOne->id, $auditorTwo->id]],
                ['principle' => 2, 'users' => [$auditorOne->id]],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_principle_user', ['audit_id' => $audit->id, 'user_id' => $auditorOne->id, 'principle_number' => 1]);
        $this->assertDatabaseHas('audit_principle_user', ['audit_id' => $audit->id, 'user_id' => $auditorTwo->id, 'principle_number' => 1]);
        $this->assertSame(3, $audit->assignees()->count());
    }

    public function test_admin_can_open_the_auditor_assignment_menu(): void
    {
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit ISPO demo']);
        $audit->items()->create(['title' => '1.1.1 Legalitas usaha', 'sort_order' => 1]);
        $otherAudit = Company::factory()->create()->audits()->create(['title' => 'Audit checklist lama']);
        $otherAudit->items()->create(['title' => 'Legalitas perusahaan', 'sort_order' => 1]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('audits.assignments.index'))
            ->assertOk()
            ->assertSee('Penugasan auditor')
            ->assertSee('Audit ISPO demo')
            ->assertDontSee('Audit checklist lama')
            ->assertSee(route('audits.show', $audit).'#assignment-panel', false);
    }

    public function test_lead_auditor_can_open_the_auditor_assignment_menu(): void
    {
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit ISPO lead']);
        $audit->items()->create(['title' => '1.1.1 Legalitas usaha', 'sort_order' => 1]);

        $this->actingAs(User::factory()->create(['role' => 'lead_audit']))
            ->get(route('audits.assignments.index'))
            ->assertOk()
            ->assertSee('Penugasan auditor')
            ->assertSee('Audit ISPO lead')
            ->assertSee(route('audits.show', $audit).'#assignment-panel', false);
    }

    public function test_auditor_cannot_open_the_auditor_assignment_menu(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);

        $this->actingAs($auditor)
            ->get(route('dashboard'))
            ->assertDontSee('Penugasan auditor');

        $this->get(route('audits.assignments.index'))->assertForbidden();
    }

    public function test_auditor_can_only_update_an_assigned_principle(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit permission']);
        $assignedItem = $audit->items()->create(['title' => '1.1.1 Prinsip satu', 'sort_order' => 1]);
        $unassignedItem = $audit->items()->create(['title' => '2.1.1 Prinsip dua', 'sort_order' => 2]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);

        $this->actingAs($auditor)->put(route('audit-items.update', $assignedItem), ['status' => 'compliant'])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_items', ['id' => $assignedItem->id, 'status' => 'compliant']);

        $this->actingAs($auditor)->put(route('audit-items.update', $unassignedItem), ['status' => 'compliant'])
            ->assertForbidden();
    }
}
