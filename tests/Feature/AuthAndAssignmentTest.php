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

    public function test_public_registration_requests_a_role_and_waits_for_admin_approval(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Belum punya akun?')
            ->assertSee(route('register'));

        $this->post(route('register.store'), [
            'name' => 'Auditor Baru',
            'email' => 'auditor.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'name' => 'Auditor Baru',
            'email' => 'auditor.baru@example.com',
            'role' => 'auditor',
            'requested_role' => 'admin',
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'auditor.baru@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Menunggu persetujuan:')
            ->assertSee('<strong>Admin</strong>', false)
            ->assertSee('Setujui role');

        $this->post(route('users.approve-role', User::where('email', 'auditor.baru@example.com')->firstOrFail()))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'auditor.baru@example.com',
            'role' => 'admin',
            'requested_role' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => 'auditor.baru@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_registration_page_explains_the_available_role(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Ajukan akun')
            ->assertSee('Lead Auditor')
            ->assertSee('Admin')
            ->assertSee('Persetujuan Admin diperlukan.');
    }

    public function test_non_admin_cannot_approve_a_requested_role(): void
    {
        $user = User::factory()->create(['role' => 'auditor', 'requested_role' => 'lead_audit']);

        $this->actingAs(User::factory()->create(['role' => 'lead_audit']))
            ->post(route('users.approve-role', $user))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'auditor',
            'requested_role' => 'lead_audit',
        ]);
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
            ->assertSee(route('audits.assignments.edit', $audit), false);

        $this->get(route('audits.assignments.edit', $audit))
            ->assertOk()
            ->assertSee('Kepatuhan terhadap peraturan')
            ->assertSee('Simpan penugasan');

        $this->get(route('audits.show', $audit))
            ->assertDontSee('Simpan penugasan');

        $this->get(route('audits.assignments.edit', $otherAudit))
            ->assertNotFound();
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
            ->assertSee(route('audits.assignments.edit', $audit), false);
    }

    public function test_auditor_cannot_open_the_auditor_assignment_menu(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);

        $this->actingAs($auditor)
            ->get(route('dashboard'))
            ->assertDontSee('Penugasan auditor');

        $this->get(route('audits.assignments.index'))->assertForbidden();
    }

    public function test_auditor_cannot_open_audit_principle_assignments(): void
    {
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit ISPO demo']);
        $audit->items()->create(['title' => '1.1.1 Legalitas usaha', 'sort_order' => 1]);

        $this->actingAs(User::factory()->create(['role' => 'auditor']))
            ->get(route('audits.assignments.edit', $audit))
            ->assertForbidden();
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
