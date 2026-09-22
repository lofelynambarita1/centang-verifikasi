<?php

namespace Tests\Feature;

use App\Enums\AuditProjectStatus;
use App\Enums\ProjectRole;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Tests\TestCase;

class AuditProjectCreationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $lead, array|Enumerable $auditors, ?Company $company = null, ?AuditTemplate $template = null): array
    {
        $auditorIds = $auditors instanceof Enumerable
            ? $auditors->map(fn (User $user): int => $user->id)->all()
            : array_map(fn (User $user): int => $user->id, $auditors);

        return [
            'template_id' => ($template ?? AuditTemplate::factory()->create())->id,
            'company_id' => ($company ?? Company::factory()->create())->id,
            'name' => 'Audit ISPO PT Sawit Makmur',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => 1250.50,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => $auditorIds,
        ];
    }

    public function test_admin_can_create_a_project_with_lead_and_auditors_in_one_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditors = User::factory()->auditor()->count(3)->create();

        $response = $this->actingAs($admin)->post(route('audit-projects.store'), $this->payload($lead, $auditors));

        $project = AuditProject::where('name', 'Audit ISPO PT Sawit Makmur')->firstOrFail();

        $response->assertRedirect(route('audit-projects.show', $project));

        $this->assertDatabaseHas('audit_project_members', [
            'audit_project_id' => $project->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAuditor->value,
        ]);

        foreach ($auditors as $auditor) {
            $this->assertDatabaseHas('audit_project_members', [
                'audit_project_id' => $project->id,
                'user_id' => $auditor->id,
                'project_role' => ProjectRole::Auditor->value,
            ]);
        }

        $this->assertSame(4, $project->members()->count());
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'user_id' => $admin->id,
            'activity' => 'project.created',
        ]);
    }

    public function test_new_project_defaults_to_draft_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();

        $this->actingAs($admin)->post(route('audit-projects.store'), $this->payload($lead, []));

        $project = AuditProject::where('name', 'Audit ISPO PT Sawit Makmur')->firstOrFail();

        $this->assertSame(AuditProjectStatus::Draft->value, $project->status->value);
        $this->assertTrue($project->audit_start_date->lte($project->audit_end_date));
    }

    public function test_each_new_project_gets_a_unique_project_code(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();

        $this->actingAs($admin)->post(route('audit-projects.store'), $this->payload($lead, []));
        $this->actingAs($admin)->post(route('audit-projects.store'), $this->payload($lead, []));

        $codes = AuditProject::pluck('project_code');

        $this->assertSame(2, $codes->count());
        $this->assertSame(2, $codes->unique()->count());
        $codes->each(function (string $code): void {
            $this->assertMatchesRegularExpression('/^PRJ-\d{8}-[A-Z0-9]{4}$/', $code);
        });
    }

    public function test_project_code_is_unique_at_database_level(): void
    {
        $admin = User::factory()->admin()->create();
        $project = AuditProject::factory()->create();

        $this->expectException(QueryException::class);

        AuditProject::create([
            'project_code' => $project->project_code,
            'template_id' => AuditTemplate::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Project kode duplikat',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'created_by' => $admin->id,
        ]);
    }

    public function test_audit_end_date_cannot_be_before_start_date(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();

        $payload = $this->payload($lead, []);
        $payload['audit_start_date'] = now()->addMonth()->toDateString();
        $payload['audit_end_date'] = now()->addMonth()->subDays(1)->toDateString();

        $this->actingAs($admin)
            ->post(route('audit-projects.store'), $payload)
            ->assertSessionHasErrors('audit_end_date');

        $this->assertDatabaseMissing('audit_projects', ['name' => $payload['name']]);
    }

    public function test_lead_auditor_cannot_also_be_listed_as_a_regular_auditor(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditors = User::factory()->auditor()->count(2)->create();

        $payload = $this->payload($lead, $auditors);
        $payload['auditor_ids'][] = $lead->id;

        $this->actingAs($admin)
            ->post(route('audit-projects.store'), $payload)
            ->assertSessionHasErrors('auditor_ids');

        $this->assertDatabaseMissing('audit_projects', ['name' => $payload['name']]);
    }

    public function test_admin_cannot_be_assigned_as_lead_auditor(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload($admin, []);
        $payload['lead_auditor_id'] = $admin->id;

        $this->actingAs($admin)
            ->post(route('audit-projects.store'), $payload)
            ->assertSessionHasErrors('lead_auditor_id');

        $this->assertDatabaseMissing('audit_projects', ['name' => $payload['name']]);
    }

    public function test_create_form_renders_the_new_fields_for_an_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('audit-projects.create'))
            ->assertOk()
            ->assertSee('Jenis audit')
            ->assertSee('Luas lahan')
            ->assertSee('Tanggal mulai audit')
            ->assertSee('Tanggal selesai audit')
            ->assertSee('Lead Auditor');
    }

    public function test_project_creation_requires_the_expected_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('audit-projects.store'), [])
            ->assertSessionHasErrors([
                'template_id', 'company_id', 'name', 'audit_type',
                'audit_start_date', 'audit_end_date', 'lead_auditor_id',
            ]);
    }

    public function test_project_detail_page_shows_team_and_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditors = User::factory()->auditor()->count(2)->create();

        $this->actingAs($admin)->post(route('audit-projects.store'), $this->payload($lead, $auditors));

        $project = AuditProject::where('name', 'Audit ISPO PT Sawit Makmur')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('audit-projects.show', $project))
            ->assertOk()
            ->assertSee($project->name)
            ->assertSee($project->project_code)
            ->assertSee($project->audit_type)
            ->assertSee($lead->name)
            ->assertSee('Project audit "'.$project->name.'" dibuat.');
    }
}
