<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTeamAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_assignment_edit_page_excludes_admin_and_only_shows_auditors_in_team_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Utama']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin ISPO Rahasia']);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Budi Lead']);
        $auditor1 = User::factory()->create(['role' => 'auditor', 'name' => 'Ani Auditor']);
        $auditor2 = User::factory()->create(['role' => 'auditor', 'name' => 'Citra Auditor']);

        $company = Company::factory()->create(['name' => 'PT Kebun Sawit']);
        $audit = $company->audits()->create(['title' => 'Audit Sertifikasi ISPO']);
        $audit->items()->create(['title' => '1.1.1 Legalitas lahan', 'sort_order' => 1]);

        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('audits.assignments.edit', $audit));

        $response->assertOk()
            ->assertSee('Lead Auditor Proyek')
            ->assertSee('Tim Auditor Proyek')
            ->assertSee('Budi Lead')
            ->assertSee('Ani Auditor')
            ->assertSee('Citra Auditor')
            // Admin lain sama sekali tidak boleh ada di halaman form penugasan
            ->assertDontSee('Admin ISPO Rahasia')
            ->assertDontSee('name="team_members[]" value="'.$admin->id.'"', false)
            ->assertDontSee('name="team_members[]" value="'.$otherAdmin->id.'"', false)
            ->assertDontSee('value="'.$admin->id.'" data-name="'.$admin->name.'"', false);
    }

    public function test_principles_checklist_only_displays_project_team_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin ISPO']);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Lead Budi']);
        $teamMember = User::factory()->create(['role' => 'auditor', 'name' => 'Anggota Tim Ani']);
        $outsideAuditor = User::factory()->create(['role' => 'auditor', 'name' => 'Auditor Luar Doni']);

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Seleksi Tim']);
        $audit->items()->create(['title' => '1.1.1 Bukti legal', 'sort_order' => 1]);

        // Daftarkan Lead Budi dan Anggota Tim Ani ke AuditProjectMember
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $teamMember->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('audits.assignments.edit', $audit));

        $response->assertOk();

        // Di bagian prinsip 1 sampai 7:
        // Controller mengirimkan $teamAuditors yang hanya berisi Lead Budi & Anggota Tim Ani
        /** @var Collection $teamAuditors */
        $teamAuditors = $response->viewData('teamAuditors');
        $this->assertCount(2, $teamAuditors);
        $this->assertTrue($teamAuditors->contains('id', $lead->id));
        $this->assertTrue($teamAuditors->contains('id', $teamMember->id));
        $this->assertFalse($teamAuditors->contains('id', $outsideAuditor->id));
        $this->assertFalse($teamAuditors->contains('id', $admin->id));

        // Dropdown prinsip hanya memuat anggota tim terpilih.
        preg_match('/<table class="table assignment-matrix">.*?<\/table>/s', $response->getContent(), $matches);
        $matrix = $matches[0] ?? '';

        $this->assertNotSame('', $matrix);
        $this->assertStringContainsString('name="assignments[1][user_id]"', $matrix);
        $this->assertStringContainsString('-- Belum Ditugaskan --', $matrix);
        $this->assertStringContainsString('<option value="'.$lead->id.'"', $matrix);
        $this->assertStringContainsString('<option value="'.$teamMember->id.'"', $matrix);
        $this->assertStringNotContainsString('<option value="'.$outsideAuditor->id.'"', $matrix);
        $this->assertStringNotContainsString('<option value="'.$admin->id.'"', $matrix);
        $this->assertStringNotContainsString('type="radio"', $matrix);
    }

    public function test_admin_can_select_project_team_and_assign_principles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Lead Herman']);
        $auditor1 = User::factory()->create(['role' => 'auditor', 'name' => 'Auditor Maya']);
        $auditor2 = User::factory()->create(['role' => 'auditor', 'name' => 'Auditor Rendi']);

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Proyek ISPO']);
        $audit->items()->create(['title' => '1.1.1 Dokumen', 'sort_order' => 1]);

        $payload = [
            'lead_auditor_id' => $lead->id,
            'team_members_present' => '1',
            'team_members' => [$auditor1->id, $auditor2->id],
            'assignments' => [
                ['principle' => 1, 'user_id' => $auditor1->id],
                ['principle' => 2, 'user_id' => $auditor2->id],
                ['principle' => 3, 'user_id' => $lead->id],
            ],
        ];

        $this->actingAs($admin)
            ->put(route('audits.assignments.update', $audit), $payload)
            ->assertRedirect(route('audits.assignments.index'))
            ->assertSessionHas('success', 'Penugasan tim audit berhasil diperbarui.');

        // Pastikan hanya user yang dipilih masuk ke AuditProjectMember
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $auditor1->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $auditor2->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);
        $this->assertSame(3, $audit->members()->count());

        // Pastikan penugasan prinsip tersimpan
        $this->assertDatabaseHas('audit_principle_user', [
            'audit_id' => $audit->id,
            'user_id' => $auditor1->id,
            'principle_number' => 1,
        ]);
        $this->assertDatabaseHas('audit_principle_user', [
            'audit_id' => $audit->id,
            'user_id' => $auditor2->id,
            'principle_number' => 2,
        ]);
        $this->assertDatabaseHas('audit_principle_user', [
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'principle_number' => 3,
        ]);
    }

    public function test_deselecting_an_auditor_from_project_team_removes_them_from_members_and_principle_assignments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $auditorKeep = User::factory()->create(['role' => 'auditor']);
        $auditorRemove = User::factory()->create(['role' => 'auditor']);

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Lepas Tim']);
        $audit->items()->create(['title' => '1.1.1 Dokumen', 'sort_order' => 1]);

        // Awalnya auditorKeep dan auditorRemove ada di tim
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $auditorKeep->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $admin->id,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $auditorRemove->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $admin->id,
        ]);

        $audit->assignees()->attach($auditorKeep->id, ['principle_number' => 1]);
        $audit->assignees()->attach($auditorRemove->id, ['principle_number' => 2]);

        // Sekarang update tim dengan HANYA auditorKeep (auditorRemove tidak dipilih)
        $this->actingAs($admin)->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => $lead->id,
            'team_members_present' => '1',
            'team_members' => [$auditorKeep->id],
            'assignments' => [
                ['principle' => 1, 'user_id' => $auditorKeep->id],
            ],
        ])->assertRedirect();

        // auditorRemove harus terhapus dari AuditProjectMember
        $this->assertDatabaseMissing('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $auditorRemove->id,
        ]);

        // auditorRemove juga harus terlepas dari penugasan prinsip
        $this->assertDatabaseMissing('audit_principle_user', [
            'audit_id' => $audit->id,
            'user_id' => $auditorRemove->id,
        ]);

        // auditorKeep tetap terdaftar
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $auditorKeep->id,
        ]);
        $this->assertDatabaseHas('audit_principle_user', [
            'audit_id' => $audit->id,
            'user_id' => $auditorKeep->id,
            'principle_number' => 1,
        ]);
    }

    public function test_admin_user_cannot_be_selected_into_team_or_principles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anotherAdmin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Validasi Admin']);
        $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);

        // Coba assign admin ke lead_auditor_id
        $this->actingAs($admin)->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => $anotherAdmin->id,
            'team_members_present' => '1',
            'team_members' => [],
            'assignments' => [
                ['principle' => 1, 'user_id' => ''],
            ],
        ])->assertSessionHasErrors('lead_auditor_id');

        // Coba assign admin ke team_members
        $this->actingAs($admin)->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => '',
            'team_members_present' => '1',
            'team_members' => [$anotherAdmin->id],
            'assignments' => [
                ['principle' => 1, 'user_id' => ''],
            ],
        ])->assertSessionHasErrors('team_members.0');

        // Coba assign admin ke assignments users
        $this->actingAs($admin)->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => '',
            'team_members_present' => '1',
            'team_members' => [],
            'assignments' => [
                ['principle' => 1, 'user_id' => $anotherAdmin->id],
            ],
        ])->assertSessionHasErrors('assignments.0.user_id');
    }

    public function test_duplicate_principles_with_different_assignees_are_rejected(): void
    {
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditorOne = User::factory()->create(['role' => 'auditor']);
        $auditorTwo = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit single assignee']);

        $this->actingAs($lead)
            ->put(route('audits.assignments.update', $audit), [
                'lead_auditor_id' => $lead->id,
                'team_members_present' => '1',
                'team_members' => [$auditorOne->id, $auditorTwo->id],
                'assignments' => [
                    ['principle' => 1, 'user_id' => $auditorOne->id],
                    ['principle' => 1, 'user_id' => $auditorTwo->id],
                ],
            ])
            ->assertSessionHasErrors('assignments');

        $this->assertSame(0, $audit->assignees()->count());
    }

    public function test_auditor_outside_the_selected_project_team_cannot_be_assigned_to_a_principle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $teamMember = User::factory()->create(['role' => 'auditor']);
        $outsideAuditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit tim terpilih']);

        $this->actingAs($admin)
            ->put(route('audits.assignments.update', $audit), [
                'lead_auditor_id' => $lead->id,
                'team_members_present' => '1',
                'team_members' => [$teamMember->id],
                'assignments' => [
                    ['principle' => 1, 'user_id' => $outsideAuditor->id],
                ],
            ])
            ->assertSessionHasErrors('assignments.0.user_id');

        $this->assertSame(0, $audit->assignees()->count());
    }
}
