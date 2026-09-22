<?php

namespace Tests\Feature;

use App\Enums\AuditProjectStatus;
use App\Models\ActivityLog;
use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AuditResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_can_upload_pdf_with_private_metadata_and_activity_log_without_result_changes(): void
    {
        [$project, $indicator, $pic] = $this->context();
        $result = app(AuditResultService::class)->saveDraft($project, $indicator, $pic, 'Draft', 0);
        $file = UploadedFile::fake()->createWithContent('audit report.pdf', 'private evidence content');
        Storage::fake('audit_evidence');

        $response = $this->actingAs($pic)->post(
            route('audit-projects.evidences.store', [$project, $indicator]),
            ['evidence' => $file],
        );

        $response->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();
        $disk = Storage::disk('audit_evidence');

        $this->assertSame('audit report.pdf', $evidence->original_name);
        $this->assertSame('application/pdf', $evidence->mime_type);
        $this->assertSame(strlen('private evidence content'), $evidence->size_bytes);
        $this->assertSame(hash('sha256', 'private evidence content'), $evidence->sha256);
        $this->assertStringNotContainsString($evidence->original_name, $evidence->storage_path);
        $this->assertStringStartsWith('project-'.$project->id.'/indicator-'.$indicator->id.'/', $evidence->storage_path);
        $disk->assertExists($evidence->storage_path);
        $this->assertSame($result->lock_version, $result->fresh()->lock_version);
        $this->assertSame(1, $result->versions()->count());
        $this->assertSame(1, ActivityLog::where('activity', 'audit_evidence.uploaded')->count());
    }

    public function test_pic_can_upload_image_and_download_it(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->image('photo.png'),
        ])->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();

        $this->actingAs($pic)
            ->get(route('audit-projects.evidences.download', [$project, $indicator, $evidence]))
            ->assertDownload('photo.png');
    }

    public function test_new_evidence_uses_incrementing_sort_order_and_false_report_flag(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');

        foreach (['first.pdf', 'second.pdf'] as $name) {
            $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
                'evidence' => UploadedFile::fake()->create($name, 10, 'application/pdf'),
            ])->assertRedirect();
        }

        $this->assertSame([10, 20], AuditIndicatorEvidence::orderBy('id')->pluck('sort_order')->all());
        $this->assertTrue(AuditIndicatorEvidence::query()->where('is_report_included', false)->count() === 2);
    }

    public function test_pic_can_update_metadata_and_identical_metadata_is_a_noop(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();
        $result = app(AuditResultService::class)->saveDraft($project, $indicator, $pic, 'Draft', 0);
        $logCount = ActivityLog::count();

        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $evidence]), [
            'caption' => 'Bukti lapangan',
            'is_report_included' => 1,
        ])->assertRedirect();
        $evidence->refresh();

        $this->assertSame('Bukti lapangan', $evidence->caption);
        $this->assertTrue($evidence->is_report_included);
        $this->assertSame('audit_evidence.metadata_updated', ActivityLog::latest('id')->value('activity'));
        $logCountAfterUpdate = ActivityLog::count();
        $immutable = $evidence->only(['sha256', 'storage_disk', 'storage_path', 'original_name', 'mime_type', 'size_bytes']);

        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $evidence]), [
            'caption' => 'Bukti lapangan',
            'is_report_included' => 1,
        ])->assertRedirect();

        $this->assertSame($logCountAfterUpdate, ActivityLog::count());
        $this->assertSame($immutable, $evidence->fresh()->only(array_keys($immutable)));
        $this->assertSame($result->lock_version, $result->fresh()->lock_version);
        $this->assertSame(1, $result->versions()->count());
    }

    public function test_caption_over_500_characters_is_rejected(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();

        $this->actingAs($pic)
            ->patch(route('audit-projects.evidences.update', [$project, $indicator, $evidence]), [
                'caption' => str_repeat('x', 501),
                'is_report_included' => 0,
            ])
            ->assertSessionHasErrors('caption');
    }

    public function test_pic_can_reorder_all_indicator_evidence_to_normalized_positions(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        foreach (['first.pdf', 'second.pdf', 'third.pdf'] as $name) {
            $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
                'evidence' => UploadedFile::fake()->create($name, 10, 'application/pdf'),
            ])->assertRedirect();
        }
        $ids = AuditIndicatorEvidence::orderBy('id')->pluck('id')->all();

        $this->actingAs($pic)->patch(route('audit-projects.evidences.order', [$project, $indicator]), [
            'evidence_ids' => [$ids[2], $ids[0], $ids[1]],
        ])->assertRedirect();

        $this->assertSame(
            [[$ids[2], 10], [$ids[0], 20], [$ids[1], 30]],
            AuditIndicatorEvidence::orderBy('sort_order')->get(['id', 'sort_order'])->map(fn (AuditIndicatorEvidence $evidence): array => [$evidence->id, $evidence->sort_order])->all(),
        );
        $this->assertSame('audit_evidence.reordered', ActivityLog::latest('id')->value('activity'));
    }

    public function test_reorder_rejects_evidence_from_another_indicator_with_not_found(): void
    {
        [$project, $indicator, $pic] = $this->context();
        $otherIndicator = $project->snapshotIndicators()->create([
            'audit_project_id' => $project->id,
            'audit_project_section_id' => $indicator->audit_project_section_id,
            'code' => '1.1.2',
            'title' => 'Other indicator',
            'sort_order' => 2,
        ]);
        $project->update(['status' => AuditProjectStatus::Assignment->value]);
        app(AssignmentService::class)->assign($project, $otherIndicator, $pic, $pic);
        $project->update(['status' => AuditProjectStatus::InProgress->value]);
        Storage::fake('audit_evidence');
        foreach ([$indicator, $otherIndicator] as $currentIndicator) {
            $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $currentIndicator]), [
                'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
            ])->assertRedirect();
        }
        $foreignEvidence = $otherIndicator->evidences()->firstOrFail();

        $this->actingAs($pic)
            ->patch(route('audit-projects.evidences.order', [$project, $indicator]), [
                'evidence_ids' => [$foreignEvidence->id],
            ])
            ->assertNotFound();
    }

    public function test_lead_and_admin_view_metadata_read_only_while_non_pic_cannot_mutate_it(): void
    {
        [$project, $indicator, $pic, $admin, $lead, $auditor] = $this->context(true);
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();
        $payload = ['caption' => 'Read only check', 'is_report_included' => 1];

        foreach ([$lead, $admin, $auditor] as $user) {
            $this->actingAs($user)
                ->patch(route('audit-projects.evidences.update', [$project, $indicator, $evidence]), $payload)
                ->assertForbidden();
        }

        $this->actingAs($lead)->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Tampilkan di Laporan: Tidak')
            ->assertDontSee('Simpan Metadata');
    }

    public function test_metadata_mutation_is_forbidden_when_project_is_not_in_progress(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        $this->actingAs($pic)
            ->patch(route('audit-projects.evidences.update', [$project, $indicator, $evidence]), [
                'caption' => 'Blocked',
                'is_report_included' => 1,
            ])
            ->assertForbidden();
    }

    public function test_invalid_and_oversized_evidence_are_rejected(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');

        $this->actingAs($pic)
            ->post(route('audit-projects.evidences.store', [$project, $indicator]), [
                'evidence' => UploadedFile::fake()->create('run.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('evidence');

        config(['filesystems.audit_evidence.max_mb' => 1]);
        $this->actingAs($pic)
            ->post(route('audit-projects.evidences.store', [$project, $indicator]), [
                'evidence' => UploadedFile::fake()->create('large.pdf', 2048, 'application/pdf'),
            ])
            ->assertSessionHasErrors('evidence');
        $this->assertDatabaseCount('audit_indicator_evidences', 0);
    }

    public function test_only_pic_can_upload_and_project_must_be_in_progress(): void
    {
        [$project, $indicator, $pic, $admin, $lead, $auditor] = $this->context(true);
        Storage::fake('audit_evidence');
        $payload = ['evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')];

        $this->actingAs($lead)->post(route('audit-projects.evidences.store', [$project, $indicator]), $payload)->assertForbidden();
        $this->actingAs($admin)->post(route('audit-projects.evidences.store', [$project, $indicator]), $payload)->assertForbidden();
        $this->actingAs($auditor)->post(route('audit-projects.evidences.store', [$project, $indicator]), $payload)->assertForbidden();

        $project->update(['status' => AuditProjectStatus::Assignment->value]);
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), $payload)->assertForbidden();
        $this->assertDatabaseCount('audit_indicator_evidences', 0);
    }

    public function test_evidence_download_is_authorized_and_missing_physical_file_returns_not_found(): void
    {
        [$project, $indicator, $pic, $admin, $lead, $auditor, $outsider] = $this->context(true);
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $evidence = AuditIndicatorEvidence::firstOrFail();

        $this->actingAs($lead)->get(route('audit-projects.evidences.download', [$project, $indicator, $evidence]))->assertDownload('report.pdf');
        $this->actingAs($admin)->get(route('audit-projects.evidences.download', [$project, $indicator, $evidence]))->assertDownload('report.pdf');
        $this->actingAs($auditor)->get(route('audit-projects.evidences.download', [$project, $indicator, $evidence]))->assertForbidden();
        $this->actingAs($outsider)->get(route('audit-projects.evidences.download', [$project, $indicator, $evidence]))->assertForbidden();

        Storage::disk('audit_evidence')->delete($evidence->storage_path);
        $this->actingAs($pic)->get(route('audit-projects.evidences.download', [$project, $indicator, $evidence]))->assertNotFound();
    }

    public function test_cross_project_indicator_and_evidence_bindings_return_not_found(): void
    {
        [$projectA, $indicatorA, $picA, $admin] = $this->context(true);
        [$projectB, $indicatorB, $picB] = $this->context();
        Storage::fake('audit_evidence');
        $this->actingAs($picB)->post(route('audit-projects.evidences.store', [$projectB, $indicatorB]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $evidenceB = AuditIndicatorEvidence::firstOrFail();

        $this->actingAs($admin)
            ->post(route('audit-projects.evidences.store', [$projectA, $indicatorB]), ['evidence' => UploadedFile::fake()->create('x.pdf', 1, 'application/pdf')])
            ->assertNotFound();
        $this->actingAs($admin)
            ->get(route('audit-projects.evidences.download', [$projectA, $indicatorA, $evidenceB]))
            ->assertNotFound();
    }

    public function test_workspace_shows_upload_only_to_pic_and_lists_evidence_for_read_only_users(): void
    {
        [$project, $indicator, $pic, $admin, $lead] = $this->context(true);
        Storage::fake('audit_evidence');
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($pic)->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertSee('Upload Evidence')
            ->assertSee('report.pdf');
        $this->actingAs($lead)->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertDontSee('Upload Evidence')
            ->assertSee('report.pdf');
        $this->actingAs($admin)->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertDontSee('Upload Evidence')
            ->assertSee('report.pdf');
    }

    /**
     * @return array{AuditProject, AuditProjectIndicator, User, User, User, User, User}
     */
    private function context(bool $withUsers = false): array
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $pic = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $outsider = User::factory()->auditor()->create();
        $template = AuditTemplate::factory()->create();
        $principle = $template->principles()->create(['code' => '1', 'title' => 'Principle', 'sort_order' => 1]);
        $section = $principle->sections()->create(['code' => '1.1', 'title' => 'Section', 'sort_order' => 1]);
        $section->indicators()->create(['code' => '1.1.1', 'title' => 'Indicator', 'sort_order' => 1]);

        $this->actingAs($admin)->post(route('audit-projects.store'), [
            'template_id' => $template->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Evidence Project '.fake()->unique()->numerify('####'),
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => 100,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => [$pic->id, $auditor->id],
        ]);
        $project = AuditProject::latest('id')->firstOrFail();
        $indicator = $project->snapshotIndicators()->firstOrFail();
        $project->update(['status' => AuditProjectStatus::Assignment->value]);
        app(AssignmentService::class)->assign($project, $indicator, $pic, $admin);
        $project->update(['status' => AuditProjectStatus::InProgress->value]);

        return $withUsers
            ? [$project, $indicator, $pic, $admin, $lead, $auditor, $outsider]
            : [$project, $indicator, $pic];
    }
}
