<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_login_page_does_not_contain_registration_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Belum punya akun?')
            ->assertDontSee('Daftar akun');
    }

    public function test_registration_routes_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_admin_can_approve_requested_role_for_preexisting_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Auditor Baru',
            'email' => 'auditor.baru@example.com',
            'password' => 'password123',
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

        $this->post(route('users.approve-role', $user))
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

    public function test_lead_can_assign_one_auditor_to_multiple_principles(): void
    {
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditorOne = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit assignment']);
        $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);

        $this->actingAs($lead)
            ->get(route('audits.assignments.index'))
            ->assertOk()
            ->assertSee(route('audits.assignments.edit', $audit), false);

        $this->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => $lead->id,
            'team_members_present' => '1',
            'team_members' => [$auditorOne->id],
            'assignments' => [
                ['principle' => 1, 'user_id' => $auditorOne->id],
                ['principle' => 2, 'user_id' => $auditorOne->id],
            ],
        ])->assertRedirect();

        $this->get(route('audits.assignments.edit', $audit))->assertOk();

        $this->assertDatabaseHas('audit_principle_user', ['audit_id' => $audit->id, 'user_id' => $auditorOne->id, 'principle_number' => 1]);
        $this->assertDatabaseHas('audit_principle_user', ['audit_id' => $audit->id, 'user_id' => $auditorOne->id, 'principle_number' => 2]);
        $this->assertSame(2, $audit->assignees()->count());
    }

    public function test_team_member_cannot_open_or_update_project_assignments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $member = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit team member']);
        $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $member->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $admin->id,
        ]);

        $this->actingAs($member)
            ->get(route('audits.assignments.index'))
            ->assertForbidden();

        $this->get(route('audits.assignments.edit', $audit))->assertForbidden();
        $this->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => $member->id,
            'team_members_present' => '1',
            'team_members' => [],
            'assignments' => [['principle' => 1, 'user_id' => '']],
        ])->assertForbidden();

        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $this->assertDatabaseMissing('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $member->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
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
        $lead = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit ISPO lead']);
        $audit->items()->create(['title' => '1.1.1 Legalitas usaha', 'sort_order' => 1]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $otherAudit = Company::factory()->create()->audits()->create(['title' => 'Audit proyek lain']);
        $otherAudit->items()->create(['title' => '1.1.1 Legalitas usaha', 'sort_order' => 1]);
        $otherLead = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $otherAudit->id,
            'user_id' => $otherLead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);

        $this->actingAs($lead)
            ->get(route('audits.assignments.index'))
            ->assertOk()
            ->assertSee('Penugasan auditor')
            ->assertSee('Audit ISPO lead')
            ->assertSee('Audit proyek lain')
            ->assertSee(route('audits.assignments.edit', $audit), false)
            ->assertDontSee(route('audits.assignments.edit', $otherAudit), false);
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
        $otherAuditor = User::factory()->create(['role' => 'auditor', 'name' => 'Auditor Prinsip Dua']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit permission']);
        $assignedItem = $audit->items()->create(['title' => '1.1.1 Prinsip satu', 'sort_order' => 1]);
        $unassignedItem = $audit->items()->create([
            'title' => '2.1.1 Prinsip dua',
            'sort_order' => 2,
            'note' => 'Catatan dari rekan tim.',
        ]);
        $hasil = $unassignedItem->hasils()->create(['content' => 'Hasil dari rekan tim.', 'sort_order' => 1]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);
        $audit->assignees()->attach($otherAuditor, ['principle_number' => 2]);

        $this->actingAs($auditor)->put(route('audit-items.update', $assignedItem), ['status' => 'compliant'])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_items', ['id' => $assignedItem->id, 'status' => 'compliant']);

        $this->get(route('audits.show', ['audit' => $audit, 'page' => 1]))
            ->assertOk()
            ->assertSee(route('audit-items.update', $assignedItem), false)
            ->assertDontSee('Mode Pratinjau');

        $this->get(route('audits.show', ['audit' => $audit, 'page' => 2]))
            ->assertOk()
            ->assertSee('Mode Pratinjau')
            ->assertSee('Ditugaskan kepada Auditor Prinsip Dua')
            ->assertSee('Catatan dari rekan tim.')
            ->assertSee('Hasil dari rekan tim.')
            ->assertDontSee(route('audit-items.update', $unassignedItem), false)
            ->assertDontSee(route('audit-items.hasil.store', $unassignedItem), false)
            ->assertDontSee(route('audit-hasils.update', $hasil), false)
            ->assertDontSee('name="evidence"', false);

        $this->actingAs($auditor)->put(route('audit-items.update', $unassignedItem), ['status' => 'compliant'])
            ->assertForbidden();
    }

    public function test_unassigned_auditor_cannot_submit_status_notes_or_evidence_for_another_principle(): void
    {
        Storage::fake('public');

        $member = User::factory()->create(['role' => 'auditor']);
        $assignedItemOwner = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit write authorization']);
        $item = $audit->items()->create([
            'title' => '3.1.1 Pengelolaan lingkungan',
            'sort_order' => 1,
            'status' => 'pending',
            'note' => 'Catatan awal',
        ]);
        $hasil = $item->hasils()->create([
            'content' => 'Hasil awal',
            'evidence_path' => 'audit-evidence/existing.pdf',
            'sort_order' => 1,
        ]);
        Storage::disk('public')->put($hasil->evidence_path, 'existing evidence');
        $audit->assignees()->attach($assignedItemOwner, ['principle_number' => 3]);

        $this->actingAs($member)
            ->put(route('audit-items.update', $item), ['status' => 'compliant', 'note' => 'Perubahan tidak sah'])
            ->assertForbidden();

        $this->post(route('audit-items.evidence', $item), [
            'evidence' => UploadedFile::fake()->create('unauthorized.pdf', 10, 'application/pdf'),
        ])->assertForbidden();

        $this->post(route('audit-items.hasil.store', $item), ['content' => 'Hasil tidak sah'])
            ->assertForbidden();

        $this->put(route('audit-hasils.update', $hasil), ['content' => 'Perubahan hasil tidak sah'])
            ->assertForbidden();

        $this->delete(route('audit-hasils.destroy', $hasil))->assertForbidden();

        $this->assertDatabaseHas('audit_items', [
            'id' => $item->id,
            'status' => 'pending',
            'note' => 'Catatan awal',
            'evidence_path' => null,
        ]);
        $this->assertDatabaseHas('audit_hasils', [
            'id' => $hasil->id,
            'content' => 'Hasil awal',
            'evidence_path' => 'audit-evidence/existing.pdf',
        ]);
        Storage::disk('public')->assertExists('audit-evidence/existing.pdf');
    }
}
