<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Audit;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DefaultUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAndDefaultUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_creates_only_two_active_system_roles_and_no_klien_or_lead_role(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin', 'label' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'auditor', 'label' => 'Auditor']);
        $this->assertDatabaseMissing('roles', ['name' => 'lead_audit']);
        $this->assertDatabaseMissing('roles', ['name' => 'klien']);
        $this->assertDatabaseMissing('roles', ['name' => 'client']);
        $this->assertSame(2, Role::count());
    }

    public function test_default_user_seeder_creates_expected_default_accounts(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(DefaultUserSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'klien@kebun.com']);
        $this->assertDatabaseMissing('roles', ['name' => 'lead_audit']);

        // Akun lead@ispo.com memiliki role global auditor (bukan role global lead_audit)
        $lead = User::where('email', 'lead@ispo.com')->firstOrFail();
        $this->assertNotNull($lead->role_id);
        $this->assertSame('auditor', $lead->role);
        $this->assertSame('auditor', $lead->roleRelation->name);
        $this->assertTrue($lead->isAuditor());
        $this->assertFalse($lead->isAdmin());
        $this->assertTrue(Hash::check('password', $lead->password));

        $admin = User::where('email', 'admin@ispo.com')->firstOrFail();
        $this->assertNotNull($admin->role_id);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('admin', $admin->roleRelation->name);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isLeadAuditor());
        $this->assertFalse($admin->isAuditor());
        $this->assertTrue(Hash::check('password', $admin->password));

        $budi = User::where('email', 'budi@ispo.com')->firstOrFail();
        $this->assertNotNull($budi->role_id);
        $this->assertSame('auditor', $budi->role);
        $this->assertSame('auditor', $budi->roleRelation->name);
        $this->assertTrue($budi->isAuditor());
        $this->assertFalse($budi->isLeadAuditor());
        $this->assertFalse($budi->isAdmin());
        $this->assertTrue(Hash::check('password', $budi->password));

        $ani = User::where('email', 'ani@ispo.com')->firstOrFail();
        $this->assertNotNull($ani->role_id);
        $this->assertSame('auditor', $ani->role);
        $this->assertSame('auditor', $ani->roleRelation->name);
        $this->assertTrue($ani->isAuditor());
        $this->assertFalse($ani->isLeadAuditor());
        $this->assertFalse($ani->isAdmin());
        $this->assertTrue(Hash::check('password', $ani->password));
    }

    public function test_user_management_form_only_allows_admin_and_auditor_roles(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        // Form tambah user hanya menampilkan Admin dan Auditor, tidak ada Lead Audit
        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('<option value="admin"', false)
            ->assertSee('<option value="auditor"', false)
            ->assertDontSee('<option value="lead_audit"', false);

        // Menolak pembuatan user dengan role lead_audit
        $this->post(route('users.store'), [
            'name' => 'Invalid Lead',
            'email' => 'invalid.lead@ispo.com',
            'role' => 'lead_audit',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role');

        // Menerima pembuatan user dengan role auditor
        $this->post(route('users.store'), [
            'name' => 'Valid Auditor',
            'email' => 'valid.auditor@ispo.com',
            'role' => 'auditor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'valid.auditor@ispo.com',
            'role' => 'auditor',
        ]);
    }

    public function test_admin_assigns_lead_auditor_via_penugasan_auditor_menu(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $leadAuditorUser = User::factory()->create(['role' => 'auditor', 'name' => 'Calon Lead']);
        $teamAuditor = User::factory()->create(['role' => 'auditor', 'name' => 'Anggota Tim']);

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit ISPO Tim']);
        $item1 = $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);
        $item2 = $audit->items()->create(['title' => '2.1.1 Praktik', 'sort_order' => 2]);

        // Halaman edit penugasan menampilkan pilihan Lead Auditor
        $this->actingAs($admin)
            ->get(route('audits.assignments.edit', $audit))
            ->assertOk()
            ->assertSee('Lead Auditor Proyek')
            ->assertSee('name="lead_auditor_id"', false)
            ->assertSee($leadAuditorUser->name);

        // Admin menugaskan lead_auditor_id dan pembagian prinsip
        $this->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => $leadAuditorUser->id,
            'team_members_present' => '1',
            'team_members' => [$teamAuditor->id],
            'assignments' => [
                ['principle' => 1, 'user_id' => $teamAuditor->id],
                ['principle' => 2, 'user_id' => $leadAuditorUser->id],
            ],
        ])->assertRedirect();

        // Terdaftar pada audit_project_members sebagai lead_audit
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $leadAuditorUser->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $teamAuditor->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);

        // Helper isLeadAuditor pada audit terkait mengembalikan true untuk calon lead, false untuk anggota lain
        $this->assertTrue($leadAuditorUser->isLeadAuditor($audit));
        $this->assertFalse($teamAuditor->isLeadAuditor($audit));

        // Lead Auditor berhak menyelesaikan/finalisasi audit setelah checklist terisi
        $item1->update(['status' => 'compliant']);
        $item2->update(['status' => 'compliant']);

        // Auditor biasa dilarang finalisasi
        $this->actingAs($teamAuditor)
            ->post(route('audits.complete', $audit))
            ->assertForbidden();

        // Lead Auditor berhak finalisasi
        $this->actingAs($leadAuditorUser)
            ->post(route('audits.complete', $audit))
            ->assertRedirect();
        $this->assertSame('completed', $audit->fresh()->status);

        // Lead Auditor berhak mencetak / mengunduh PDF laporan
        $this->actingAs($leadAuditorUser)
            ->get(route('audits.pdf', $audit))
            ->assertOk();
    }

    public function test_unauthenticated_users_cannot_access_verification_routes(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Checklist']);

        $this->get(route('audits.show', $audit))->assertRedirect(route('login'));
        $this->post(route('audits.complete', $audit))->assertRedirect(route('login'));
    }

    public function test_admin_can_update_checklist_verification(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Checklist']);
        $item = $audit->items()->create(['title' => '1.1.1 Legalitas Usaha', 'sort_order' => 1]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('Belum diperiksa')
            ->assertSee('<option value="compliant"', false)
            ->assertSee('<option value="non_compliant"', false)
            ->assertSee('<option value="not_applicable"', false)
            ->assertDontSee('<option value="pending"', false);

        $this->put(route('audit-items.update', $item), ['status' => 'compliant'])
            ->assertRedirect(route('audits.show', ['audit' => $audit, 'page' => 1]));

        $this->assertDatabaseHas('audit_items', [
            'id' => $item->id,
            'status' => 'compliant',
        ]);
    }

    public function test_pending_cannot_be_submitted_as_an_indicator_status(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit status indikator']);
        $lead = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $item = $audit->items()->create(['title' => '1.1.1 Legalitas Usaha', 'sort_order' => 1]);

        $this->actingAs($lead)
            ->from(route('audits.show', $audit))
            ->put(route('audit-items.update', $item), ['status' => 'compliant'])
            ->assertRedirect(route('audits.show', ['audit' => $audit, 'page' => 1]));

        $this->put(route('audit-items.update', $item), ['status' => 'pending'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('audit_items', [
            'id' => $item->id,
            'status' => 'compliant',
        ]);
    }

    public function test_verification_and_completion_authorization_based_on_audit_project_member(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Project Member Test']);
        $item1 = $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);
        $item2 = $audit->items()->create(['title' => '2.1.1 Lingkungan', 'sort_order' => 2]);

        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $memberAuditor = User::factory()->create(['role' => 'auditor']);
        $outsider = User::factory()->create(['role' => 'auditor']);

        // Admin menugaskan lead_audit dan auditor via AuditProjectMember
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $memberAuditor->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $admin->id,
        ]);

        // Tugaskan memberAuditor ke prinsip 1
        $audit->assignees()->attach($memberAuditor, ['principle_number' => 1]);

        // 1. Helper isLeadAuditor
        $this->assertTrue($lead->isLeadAuditor($audit));
        $this->assertFalse($memberAuditor->isLeadAuditor($audit));
        $this->assertFalse($outsider->isLeadAuditor($audit));
        $this->assertFalse($admin->isLeadAuditor($audit));

        // 2. Checklist verification rights (canUpdateChecklist / updateItem)
        // Admin dapat mengubah status checklist.
        $this->actingAs($admin)
            ->put(route('audit-items.update', $item1), ['status' => 'compliant'])
            ->assertRedirect();

        // Lead auditor proyek berhak mengisi checklist
        $this->actingAs($lead)
            ->put(route('audit-items.update', $item1), ['status' => 'compliant'])
            ->assertRedirect();
        $this->assertSame('compliant', $item1->fresh()->status);

        // Auditor anggota proyek hanya berhak mengisi prinsip yang ditugaskan (prinsip 1)
        $this->actingAs($memberAuditor)
            ->put(route('audit-items.update', $item1), ['status' => 'compliant'])
            ->assertRedirect();

        // Auditor anggota dilarang mengisi prinsip yang tidak ditugaskan (prinsip 2)
        $this->actingAs($memberAuditor)
            ->put(route('audit-items.update', $item2), ['status' => 'compliant'])
            ->assertForbidden();

        // Auditor luar (bukan anggota) dilarang mengisi checklist
        $this->actingAs($outsider)
            ->get(route('audits.show', $audit))
            ->assertOk()
            ->assertDontSee('name="status"', false);

        $this->actingAs($outsider)
            ->put(route('audit-items.update', $item1), ['status' => 'compliant'])
            ->assertForbidden();

        // 3. Finalization rights (complete)
        // Tandai semua item selesai agar audit valid untuk difinalisasi
        $item2->update(['status' => 'compliant']);

        // Auditor biasa dilarang finalisasi
        $this->actingAs($memberAuditor)
            ->post(route('audits.complete', $audit))
            ->assertForbidden();

        // Auditor luar dilarang finalisasi
        $this->actingAs($outsider)
            ->post(route('audits.complete', $audit))
            ->assertForbidden();

        // Lead Auditor pada proyek ini berhak menyelesaikan/finalisasi audit
        $this->actingAs($lead)
            ->post(route('audits.complete', $audit))
            ->assertRedirect();
        $this->assertSame('completed', $audit->fresh()->status);
    }
}
