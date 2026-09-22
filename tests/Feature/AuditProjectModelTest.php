<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_project_relationships_resolve(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $template = AuditTemplate::factory()->create();
        $company = Company::factory()->create();

        $project = AuditProject::create([
            'template_id' => $template->id,
            'company_id' => $company->id,
            'name' => 'Project relasi',
            'project_code' => 'PRJ-RELASI',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'created_by' => $admin->id,
        ]);

        $leadMember = $project->members()->create(['user_id' => $lead->id, 'project_role' => ProjectRole::LeadAuditor->value, 'added_by' => $admin->id]);
        $project->members()->create(['user_id' => $auditor->id, 'project_role' => ProjectRole::Auditor->value, 'added_by' => $admin->id]);
        $project->activityLogs()->create(['user_id' => $admin->id, 'activity' => 'project.created', 'description' => 'Dibuat.']);

        $project->refresh();

        $this->assertInstanceOf(AuditTemplate::class, $project->template);
        $this->assertInstanceOf(Company::class, $project->company);
        $this->assertTrue($project->creator->is($admin));
        $this->assertSame(2, $project->members()->count());
        $this->assertTrue($project->leadMember->is($leadMember));
        $this->assertSame(2, $project->users()->count());
        $this->assertSame(1, $project->activityLogs()->count());
    }

    public function test_user_relationships_resolve(): void
    {
        $admin = User::factory()->admin()->create();
        $auditor = User::factory()->auditor()->create();
        $template = AuditTemplate::factory()->create();
        $company = Company::factory()->create();

        $project = $admin->createdAuditProjects()->create([
            'template_id' => $template->id,
            'company_id' => $company->id,
            'name' => 'Project user',
            'project_code' => 'PRJ-USER',
            'audit_type' => 'Audit Dokumen',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(3)->toDateString(),
        ]);
        $project->members()->create(['user_id' => $auditor->id, 'project_role' => ProjectRole::LeadAuditor->value, 'added_by' => $admin->id]);

        $this->assertSame(1, $admin->createdAuditProjects()->count());
        $this->assertSame(1, $auditor->auditProjectMemberships()->count());
        $this->assertTrue($auditor->auditProjects()->first()->is($project));
    }

    public function test_member_and_activity_log_relationships_resolve(): void
    {
        $admin = User::factory()->admin()->create();
        $auditor = User::factory()->auditor()->create();
        $template = AuditTemplate::factory()->create();
        $company = Company::factory()->create();

        $project = $admin->createdAuditProjects()->create([
            'template_id' => $template->id,
            'company_id' => $company->id,
            'name' => 'Project sub relasi',
            'project_code' => 'PRJ-SUB',
            'audit_type' => 'Verifikasi',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);
        $member = $project->members()->create(['user_id' => $auditor->id, 'project_role' => ProjectRole::Auditor->value, 'added_by' => $admin->id]);
        $log = $project->activityLogs()->create(['user_id' => $admin->id, 'activity' => 'member.added', 'description' => 'Anggota ditambahkan.']);

        $this->assertInstanceOf(AuditProject::class, $member->project);
        $this->assertTrue($member->user->is($auditor));
        $this->assertTrue($member->adder->is($admin));

        $this->assertInstanceOf(AuditProject::class, $log->project);
        $this->assertTrue($log->user->is($admin));
    }
}
