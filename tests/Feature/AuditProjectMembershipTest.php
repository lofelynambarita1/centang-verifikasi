<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditProjectMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(User $admin): AuditProject
    {
        $project = $admin->createdAuditProjects()->create([
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project tim',
            'project_code' => 'PRJ-TIM',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
        ]);

        return $project;
    }

    public function test_admin_can_add_an_auditor_as_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $project->members()->create(['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('audit-projects.members.store', $project), ['user_id' => $auditor->id])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_project_members', [
            'audit_project_id' => $project->id,
            'user_id' => $auditor->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'user_id' => $admin->id,
            'activity' => 'member.added',
        ]);
    }

    public function test_a_user_cannot_be_registered_twice_on_the_same_project(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $project->members()->create(['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('audit-projects.members.store', $project), ['user_id' => $lead->id])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(1, $project->members()->count());
    }

    public function test_admin_can_remove_a_regular_auditor(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $project->members()->create(['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);
        $member = $project->members()->create(['user_id' => $auditor->id, 'project_role' => 'auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->delete(route('audit-projects.members.destroy', [$project, $member]))
            ->assertRedirect();

        $this->assertDatabaseMissing('audit_project_members', ['id' => $member->id]);
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'user_id' => $admin->id,
            'activity' => 'member.removed',
        ]);
    }

    public function test_lead_auditor_cannot_be_removed_directly(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $member = $project->members()->create(['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->delete(route('audit-projects.members.destroy', [$project, $member]))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('audit_project_members', ['id' => $member->id, 'project_role' => 'lead_auditor']);
    }

    public function test_replacing_lead_demotes_the_old_lead_to_regular_auditor(): void
    {
        $admin = User::factory()->admin()->create();
        $oldLead = User::factory()->auditor()->create();
        $newLead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $project->members()->create(['user_id' => $oldLead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);
        $project->members()->create(['user_id' => $auditor->id, 'project_role' => 'auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->put(route('audit-projects.lead-auditor.replace', $project), ['new_lead_auditor_id' => $newLead->id])
            ->assertRedirect();

        $remaining = $project->members()->get();

        $this->assertSame(3, $remaining->count(), 'old lead must stay in the project as a member');

        $this->assertDatabaseHas('audit_project_members', [
            'audit_project_id' => $project->id,
            'user_id' => $oldLead->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);
        $this->assertDatabaseHas('audit_project_members', [
            'audit_project_id' => $project->id,
            'user_id' => $newLead->id,
            'project_role' => ProjectRole::LeadAuditor->value,
        ]);

        $this->assertSame(1, $project->members()->where('project_role', ProjectRole::LeadAuditor->value)->count());
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'user_id' => $admin->id,
            'activity' => 'lead_auditor.replaced',
        ]);
    }

    public function test_replacing_lead_with_an_existing_regular_auditor_promotes_them(): void
    {
        $admin = User::factory()->admin()->create();
        $oldLead = User::factory()->auditor()->create();
        $newLead = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $project->members()->create(['user_id' => $oldLead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);
        $project->members()->create(['user_id' => $newLead->id, 'project_role' => 'auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->put(route('audit-projects.lead-auditor.replace', $project), ['new_lead_auditor_id' => $newLead->id])
            ->assertRedirect();

        $this->assertSame(2, $project->members()->count());
        $this->assertSame(1, $project->members()->where('project_role', ProjectRole::LeadAuditor->value)->count());
        $this->assertDatabaseHas('audit_project_members', [
            'audit_project_id' => $project->id,
            'user_id' => $oldLead->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);
    }

    public function test_replacing_lead_with_the_current_lead_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();

        $project = $this->makeProject($admin);
        $project->members()->create(['user_id' => $lead->id, 'project_role' => 'lead_auditor', 'added_by' => $admin->id]);

        $this->actingAs($admin)
            ->put(route('audit-projects.lead-auditor.replace', $project), ['new_lead_auditor_id' => $lead->id])
            ->assertSessionHasErrors('new_lead_auditor_id');
    }

    public function test_updating_a_project_logs_the_change(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin);
        $newTemplate = AuditTemplate::factory()->create();
        $newCompany = Company::factory()->create();

        $this->actingAs($admin)
            ->put(route('audit-projects.update', $project), [
                'template_id' => $newTemplate->id,
                'company_id' => $newCompany->id,
                'name' => 'Nama project baru',
                'audit_type' => 'Audit Dokumen',
                'audit_start_date' => now()->addMonths(2)->toDateString(),
                'audit_end_date' => now()->addMonths(2)->addDays(5)->toDateString(),
                'status' => 'draft',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_projects', ['id' => $project->id, 'name' => 'Nama project baru']);
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'user_id' => $admin->id,
            'activity' => 'project.updated',
        ]);
    }
}
