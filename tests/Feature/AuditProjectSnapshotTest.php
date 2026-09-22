<?php

namespace Tests\Feature;

use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\TemplateSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditProjectSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemplate(): AuditTemplate
    {
        $template = AuditTemplate::factory()->create();

        $p1 = $template->principles()->create(['code' => '1', 'title' => 'Prinsip Satu', 'sort_order' => 30]);
        $s1 = $p1->sections()->create(['code' => '1.1', 'title' => 'Kriteria 1', 'sort_order' => 2]);
        $s1b = $p1->sections()->create([
            'code' => '1.1.1', 'title' => 'Sub Kriteria', 'parent_id' => $s1->id, 'sort_order' => 1,
        ]);
        $i1 = $s1->indicators()->create(['code' => '1.1.2', 'title' => 'Indikator 1', 'sort_order' => 1]);
        $i1->parameters()->create([
            'parameter_type' => 'parameter', 'number' => '1', 'content' => 'Parameter 1', 'sort_order' => 2,
        ]);
        $i1->parameters()->create(['parameter_type' => 'verifier', 'content' => 'Verifier 1', 'sort_order' => 1]);
        $i2 = $s1b->indicators()->create(['code' => '1.1.1.1', 'title' => 'Indikator Sub', 'sort_order' => 1]);
        $i2->parameters()->create(['parameter_type' => 'parameter', 'content' => 'Parameter 2', 'sort_order' => 9]);

        $p2 = $template->principles()->create(['code' => '2', 'title' => 'Prinsip Dua', 'sort_order' => 10]);
        $s2 = $p2->sections()->create(['code' => '2.1', 'title' => 'Kriteria 2', 'sort_order' => 1]);
        $i3 = $s2->indicators()->create(['code' => '2.1.1', 'title' => 'Indikator 3', 'sort_order' => 3]);
        $i3->parameters()->create(['parameter_type' => 'verifier', 'content' => 'Verifier 2', 'sort_order' => 4]);

        return $template;
    }

    private int $createdProjects = 0;

    private function payload(User $lead, array $auditors, AuditTemplate $template, string $name = 'Audit ISPO PT Sawit Makmur'): array
    {
        return [
            'template_id' => $template->id,
            'company_id' => Company::factory()->create()->id,
            'name' => $name,
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => 1250.50,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => $auditors,
        ];
    }

    private function createProject(
        User $admin,
        User $lead,
        AuditTemplate $template,
        array $auditors = [],
    ): AuditProject {
        $this->createdProjects++;

        $name = $this->createdProjects > 1
            ? 'Audit ISPO PT Sawit Makmur #'.$this->createdProjects
            : 'Audit ISPO PT Sawit Makmur';

        $this->actingAs($admin)->post(route('audit-projects.store'), $this->payload($lead, $auditors, $template, $name));

        return AuditProject::where('name', $name)->firstOrFail();
    }

    public function test_creating_a_project_copies_the_template_structure_and_logs_it(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template);

        $this->assertSame(2, $project->snapshotPrinciples()->count());
        $this->assertSame(3, $project->snapshotSections()->count());
        $this->assertSame(3, $project->snapshotIndicators()->count());
        $this->assertSame(4, $project->snapshotParameters()->count());

        $snapshot = $project->snapshotPrinciples()
            ->where('code', '1')
            ->with('sections.indicators.parameters')
            ->firstOrFail();
        $this->assertSame('Prinsip Satu', $snapshot->title);
        $this->assertSame($template->principles()->where('code', '1')->first()->id, $snapshot->source_template_principle_id);

        $snap11 = $snapshot->sections()->firstWhere('code', '1.1');
        $this->assertNull($snap11->parent_id);
        $this->assertSame($snap11->id, $snapshot->sections()->firstWhere('code', '1.1.1')->parent_id);
        $this->assertSame('Indikator Sub', $snapshot->sections()->firstWhere('code', '1.1.1')->indicators()->first()->title);

        $parameters = $snapshot->sections()->firstWhere('code', '1.1.1')->indicators()->first()->parameters()->get();
        $this->assertSame(['Parameter 2'], $parameters->pluck('content')->all());

        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'user_id' => $admin->id,
            'activity' => 'template.snapshot_created',
        ]);
    }

    public function test_snapshot_preserves_sort_order_of_all_levels(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template);

        $principles = $project->snapshotPrinciples()->orderBy('sort_order')->pluck('code')->all();
        $this->assertSame(['2', '1'], $principles);

        $p1 = $project->snapshotPrinciples()->where('code', '1')->firstOrFail();
        $sections = $p1->sections()->orderBy('sort_order')->pluck('code')->all();
        $this->assertSame(['1.1.1', '1.1'], $sections);

        $s1 = $p1->sections()->where('code', '1.1')->firstOrFail();
        $indicators = $s1->indicators()->orderBy('sort_order')->pluck('code')->all();
        $this->assertSame(['1.1.2'], $indicators);

        $i1 = $s1->indicators()->where('code', '1.1.2')->firstOrFail();
        $parameters = $i1->parameters()->orderBy('sort_order')->pluck('content')->all();
        $this->assertSame(['Verifier 1', 'Parameter 1'], $parameters);
    }

    public function test_changing_the_master_does_not_change_an_existing_project(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template);
        $before = $this->snapshotContent($project);

        $template->principles()->where('code', '1')->update(['title' => 'Prinsip Satu (BERUBAH)']);
        $template->principles()->where('code', '2')->delete();
        $template->principles()->firstOrFail()->sections()->where('code', '1.1')->firstOrFail()->delete();

        $this->assertSame($before, $this->snapshotContent($project->refresh()));
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    private function snapshotContent(AuditProject $project): array
    {
        $except = [
            'id', 'audit_project_id', 'parent_id', 'audit_project_principle_id',
            'audit_project_section_id', 'audit_project_indicator_id',
            'source_template_principle_id', 'source_template_section_id',
            'source_template_indicator_id', 'source_template_parameter_id',
            'created_at', 'updated_at',
        ];

        return [
            array_map(fn (array $row): array => array_diff_key($row, array_flip($except)), $project->snapshotPrinciples()->get()->toArray()),
            array_map(fn (array $row): array => array_diff_key($row, array_flip($except)), $project->snapshotSections()->get()->toArray()),
            array_map(fn (array $row): array => array_diff_key($row, array_flip($except)), $project->snapshotIndicators()->get()->toArray()),
            array_map(fn (array $row): array => array_diff_key($row, array_flip($except)), $project->snapshotParameters()->get()->toArray()),
        ];
    }

    public function test_deleting_a_master_parameter_does_not_touch_the_project_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template);

        $parameter = $template->principles()->where('code', '1')->firstOrFail()
            ->sections()->where('code', '1.1')->firstOrFail()
            ->indicators()->where('code', '1.1.2')->firstOrFail()
            ->parameters()->where('parameter_type', 'parameter')->firstOrFail();
        $parameter->forceDelete();

        $snapshot = $project->snapshotPrinciples()->where('code', '1')->firstOrFail()
            ->sections()->where('code', '1.1')->firstOrFail()
            ->indicators()->where('code', '1.1.2')->firstOrFail()
            ->parameters()->where('content', 'Parameter 1')->firstOrFail();

        $this->assertSame('Parameter 1', $snapshot->content);
        $this->assertNull($snapshot->source_template_parameter_id);
    }

    public function test_new_projects_always_get_the_latest_template_structure(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $first = $this->createProject($admin, $lead, $template);

        $template->principles()->create(['code' => '3', 'title' => 'Prinsip Baru', 'sort_order' => 99]);

        $second = $this->createProject($admin, $lead, $template);

        $this->assertSame(2, $first->snapshotPrinciples()->count());
        $this->assertSame(3, $second->snapshotPrinciples()->count());
        $this->assertSame('Prinsip Baru', $second->snapshotPrinciples()->where('code', '3')->firstOrFail()->title);
    }

    public function test_updating_a_project_to_a_new_template_replaces_its_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();
        $replacement = AuditTemplate::factory()->create();

        $project = $this->createProject($admin, $lead, $template);
        $this->assertSame(2, $project->snapshotPrinciples()->count());

        $this->actingAs($admin)->put(route('audit-projects.update', $project), [
            'template_id' => $replacement->id,
            'company_id' => $project->company_id,
            'name' => $project->name,
            'audit_type' => $project->audit_type,
            'audit_start_date' => $project->audit_start_date->toDateString(),
            'audit_end_date' => $project->audit_end_date->toDateString(),
            'status' => $project->status->value,
        ])->assertRedirect(route('audit-projects.show', $project));

        $this->assertSame(0, $project->refresh()->snapshotPrinciples()->count());
    }

    public function test_snapshot_failure_rolls_back_the_whole_project_creation(): void
    {
        $this->mock(TemplateSnapshotService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->andThrow(new RuntimeException('Snapshot gagal.'));
        });

        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $this->actingAs($admin)
            ->post(route('audit-projects.store'), $this->payload($lead, [], $template))
            ->assertStatus(500);

        $this->assertDatabaseMissing('audit_projects', ['name' => 'Audit ISPO PT Sawit Makmur']);
        $this->assertSame(0, AuditProject::count());
        $this->assertSame(0, \DB::table('audit_project_members')->count());
        $this->assertSame(0, \DB::table('activity_logs')->count());
    }

    public function test_auditor_who_is_not_a_member_cannot_open_the_project_structure(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $outsider = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template);

        $this->actingAs($outsider)
            ->get(route('audit-projects.structure', $project))
            ->assertForbidden();
    }

    public function test_member_and_admin_can_read_the_project_structure_page(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template, [$auditor->id]);

        foreach ([$lead, $auditor, $admin] as $user) {
            $this->actingAs($user)
                ->get(route('audit-projects.structure', $project))
                ->assertOk()
                ->assertSee('Prinsip Satu')
                ->assertSee('Sub Kriteria')
                ->assertSee('Verifier 1');
        }
    }

    public function test_project_structure_page_is_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $template = $this->makeTemplate();

        $project = $this->createProject($admin, $lead, $template);

        $response = $this->actingAs($admin)->get(route('audit-projects.structure', $project));

        $response->assertOk()
            ->assertDontSee('Tambah')
            ->assertDontSee('Edit')
            ->assertDontSee('Hapus');
    }
}
