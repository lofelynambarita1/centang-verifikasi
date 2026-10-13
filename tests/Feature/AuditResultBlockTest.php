<?php

namespace Tests\Feature;

use App\Enums\AuditAssessmentStatus;
use App\Enums\AuditProjectStatus;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AuditResultService;
use App\Services\AuditResultWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditResultBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_can_save_nested_structured_blocks_without_evidence(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
                'result_blocks' => [
                    ['type' => 'list_item', 'level' => 1, 'text' => 'Pelaku usaha memiliki dokumen utama.'],
                    ['type' => 'list_item', 'level' => 2, 'text' => 'Dokumen pendukung tersedia.'],
                    ['type' => 'list_item', 'level' => 3, 'text' => 'Bukti pertama telah diverifikasi.'],
                ],
            ])
            ->assertRedirect();

        $result = $indicator->fresh()->result()->with('blocks')->firstOrFail();

        $this->assertSame(3, $result->blocks->count());
        $this->assertSame([1, 2, 3], $result->blocks->pluck('level')->all());
        $this->assertSame(
            implode(PHP_EOL, [
                '1. Pelaku usaha memiliki dokumen utama.',
                '   a. Dokumen pendukung tersedia.',
                '      1) Bukti pertama telah diverifikasi.',
            ]),
            $result->result_text,
        );
        $this->assertSame(AuditAssessmentStatus::Memenuhi, $result->assessment_status);
    }

    public function test_reorder_regenerates_numbering_from_current_order(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $service = app(AuditResultService::class);
        $service->saveStructuredDraft($project, $indicator, $auditor, [
            ['level' => 1, 'text' => 'Poin pertama.'],
            ['level' => 1, 'text' => 'Poin kedua.'],
        ], 0);

        $result = $service->saveStructuredDraft($project, $indicator, $auditor, [
            ['level' => 1, 'text' => 'Poin kedua.'],
            ['level' => 1, 'text' => 'Poin pertama.'],
        ], 1);

        $this->assertSame(implode(PHP_EOL, ['1. Poin kedua.', '2. Poin pertama.']), $result->result_text);
        $this->assertSame(2, $result->lock_version);
    }

    public function test_non_pic_cannot_edit_structured_blocks(): void
    {
        [, , , $project, $indicator] = $this->assignedIndicator();
        $other = User::factory()->auditor()->create();

        $this->actingAs($other)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_blocks' => [
                    ['type' => 'list_item', 'level' => 1, 'text' => 'Tidak boleh disimpan.'],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_indicator_results', [
            'audit_project_indicator_id' => $indicator->id,
        ]);
    }

    public function test_submitted_result_blocks_are_readonly(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $result = app(AuditResultService::class)->saveStructuredDraft($project, $indicator, $auditor, [
            ['level' => 1, 'text' => 'Siap dikirim.'],
        ], 0, AuditAssessmentStatus::Memenuhi);

        app(AuditResultWorkflowService::class)->submit($project, $indicator, $auditor, $result->lock_version);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => $result->lock_version,
                'result_blocks' => [
                    ['type' => 'list_item', 'level' => 1, 'text' => 'Tidak boleh diubah.'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_legacy_result_text_still_renders_when_blocks_do_not_exist(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Teks lama tetap tampil.', 0);

        $this->actingAs($auditor)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Teks lama tetap tampil.');
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: AuditProject, 4: AuditProjectIndicator}
     */
    private function assignedIndicator(): array
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.1')->firstOrFail();

        app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);
        $project->update(['status' => AuditProjectStatus::InProgress->value]);

        return [$admin, $lead, $auditor, $project->fresh(), $indicator->fresh()];
    }

    private function createProject(User $admin, User $lead, array $auditors = []): AuditProject
    {
        $template = $this->makeTemplate();
        $name = 'Audit Blocks '.fake()->unique()->numerify('####');

        $this->actingAs($admin)->post(route('audit-projects.store'), [
            'template_id' => $template->id,
            'company_id' => Company::factory()->create()->id,
            'name' => $name,
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => 100,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => array_map(fn (User $user): int => $user->id, $auditors),
        ]);

        $project = AuditProject::where('name', $name)->firstOrFail();
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        return $project;
    }

    private function makeTemplate(): AuditTemplate
    {
        $template = AuditTemplate::factory()->create();
        $principle = $template->principles()->create(['code' => '1', 'title' => 'Prinsip Satu', 'sort_order' => 1]);
        $section = $principle->sections()->create(['code' => '1.1', 'title' => 'Kriteria Satu', 'sort_order' => 1]);
        $section->indicators()->create(['code' => '1.1.1', 'title' => 'Indikator Satu', 'sort_order' => 1]);

        return $template;
    }
}
