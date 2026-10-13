<?php

namespace Tests\Feature;

use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_project_pages(): void
    {
        $this->get(route('audit-projects.index'))->assertRedirect(route('login'));
        $this->get(route('audit-projects.create'))->assertRedirect(route('login'));
        $this->get(route('templates.index'))->assertRedirect(route('login'));
    }

    public function test_auditor_can_open_a_project_where_they_are_registered(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();

        $project = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project terdaftar',
            'project_code' => 'PRJ-REGIST',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $project->members()->createMany([
            ['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id],
            ['user_id' => $auditor->id, 'project_role' => 'auditor', 'added_by' => $admin->id],
        ]);

        $this->actingAs($auditor)
            ->get(route('audit-projects.show', $project))
            ->assertOk();

        $this->actingAs($auditor)
            ->get(route('audit-projects.team', $project))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->get(route('audit-projects.my-work', $project))
            ->assertOk();

        $this->actingAs($auditor)
            ->get(route('audit-projects.assignments', $project))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->post(route('audit-projects.start', $project))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->get(route('audit-projects.show', $project))
            ->assertOk()
            ->assertSee('Peran Anda')
            ->assertSee('Auditor')
            ->assertSee('Pekerjaan Saya')
            ->assertDontSee('Pembagian Auditor')
            ->assertDontSee('Kelola Tim')
            ->assertDontSee('Edit Project');
    }

    public function test_lead_access_is_scoped_to_the_project_membership_role(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $regularAuditor = User::factory()->auditor()->create();

        $projectA = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project lead saya',
            'project_code' => 'PRJ-LEAD-A',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $projectA->members()->createMany([
            ['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id],
            ['user_id' => $regularAuditor->id, 'project_role' => 'auditor', 'added_by' => $admin->id],
        ]);

        $projectB = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project lead lain',
            'project_code' => 'PRJ-LEAD-B',
            'audit_type' => 'Audit Dokumen',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $projectB->members()->createMany([
            ['user_id' => $lead->id, 'project_role' => 'auditor', 'added_by' => $admin->id],
            ['user_id' => User::factory()->auditor()->create()->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id],
        ]);

        $this->actingAs($lead)
            ->get(route('audit-projects.assignments', $projectA))
            ->assertOk();

        $this->actingAs($lead)
            ->get(route('audit-projects.assignments', $projectB))
            ->assertForbidden();

        $this->actingAs($lead)
            ->post(route('audit-projects.start', $projectB))
            ->assertForbidden();

        $this->actingAs($lead)
            ->get(route('audit-projects.show', $projectA))
            ->assertOk()
            ->assertSee('Peran Anda')
            ->assertSee('Lead Auditor')
            ->assertSee('Pembagian Auditor')
            ->assertDontSee('Kelola Tim')
            ->assertDontSee('Edit Project');
    }

    public function test_auditor_cannot_open_a_project_where_they_are_not_registered(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->auditor()->create();

        $project = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project tertutup',
            'project_code' => 'PRJ-CLOSED',
            'audit_type' => 'Audit Dokumen',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $project->members()->create([
            'user_id' => User::factory()->auditor()->create()->id,
            'project_role' => 'lead_auditor',
            'added_by' => $admin->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('audit-projects.show', $project))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('audit-projects.team', $project))
            ->assertForbidden();
    }

    public function test_auditor_cannot_create_a_project(): void
    {
        $auditor = User::factory()->auditor()->create();

        $this->actingAs($auditor)
            ->get(route('audit-projects.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->post(route('audit-projects.store'), [])
            ->assertForbidden();
    }

    public function test_auditor_cannot_manage_members_of_a_registered_project(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $newcomer = User::factory()->auditor()->create();

        $project = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project anggota',
            'project_code' => 'PRJ-MEMBER',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $member = $project->members()->create([
            'user_id' => $lead->id,
            'project_role' => 'lead_auditor',
            'added_by' => $admin->id,
        ]);
        $project->members()->create([
            'user_id' => $auditor->id,
            'project_role' => 'auditor',
            'added_by' => $admin->id,
        ]);

        $this->actingAs($auditor)
            ->post(route('audit-projects.members.store', $project), ['user_id' => $newcomer->id])
            ->assertForbidden();

        $this->actingAs($auditor)
            ->delete(route('audit-projects.members.destroy', [$project, $member]))
            ->assertForbidden();
    }

    public function test_auditor_project_list_is_scoped_to_their_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $auditor = User::factory()->auditor()->create();
        $otherLead = User::factory()->auditor()->create();

        $mine = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project milik saya',
            'project_code' => 'PRJ-MINE',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $mine->members()->create(['user_id' => $auditor->id, 'project_role' => 'auditor', 'added_by' => $admin->id]);
        $mine->members()->create(['user_id' => $otherLead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);

        $other = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project orang lain',
            'project_code' => 'PRJ-OTHER',
            'audit_type' => 'Audit Dokumen',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $other->members()->create(['user_id' => $otherLead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);

        $this->actingAs($auditor)
            ->get(route('audit-projects.index'))
            ->assertOk()
            ->assertSee('Project milik saya')
            ->assertDontSee('Project orang lain');
    }

    public function test_admin_can_open_any_project(): void
    {
        $admin = User::factory()->admin()->create();

        $project = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project admin',
            'project_code' => 'PRJ-ADMIN',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('audit-projects.show', $project))
            ->assertOk();
    }
}
