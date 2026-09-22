<?php

namespace Tests\Feature;

use App\Enums\AuditAssessmentStatus;
use App\Enums\AuditProgressState;
use App\Enums\AuditProjectStatus;
use App\Models\ActivityLog;
use App\Models\AuditIndicatorResult;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AuditProgressService;
use App\Services\AuditResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuditResultTest extends TestCase
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
        $i1->parameters()->create(['parameter_type' => 'parameter', 'content' => 'Parameter 1', 'sort_order' => 1]);
        $i1->parameters()->create(['parameter_type' => 'verifier', 'content' => 'Verifier 1', 'sort_order' => 2]);
        $s1->indicators()->create(['code' => '1.1.3', 'title' => 'Indikator 2', 'sort_order' => 2]);
        $s1b->indicators()->create(['code' => '1.1.1.1', 'title' => 'Indikator 3', 'sort_order' => 1]);

        $p2 = $template->principles()->create(['code' => '2', 'title' => 'Prinsip Dua', 'sort_order' => 10]);
        $s2 = $p2->sections()->create(['code' => '2.1', 'title' => 'Kriteria 2', 'sort_order' => 1]);
        $s2->indicators()->create(['code' => '2.1.1', 'title' => 'Indikator 4', 'sort_order' => 1]);
        $s2->indicators()->create(['code' => '2.1.2', 'title' => 'Indikator 5', 'sort_order' => 2]);

        return $template;
    }

    /**
     * Create a project snapshot at "assignment" status. Tests assign PICs and
     * then advance it to in_progress before saving results.
     */
    private function createProject(User $admin, User $lead, array $auditors = []): AuditProject
    {
        $template = $this->makeTemplate();
        $name = 'Audit Hasil '.fake()->unique()->numerify('####');

        $this->actingAs($admin)->post(route('audit-projects.store'), [
            'template_id' => $template->id,
            'company_id' => Company::factory()->create()->id,
            'name' => $name,
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => 100,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => array_map(fn (User $u): int => $u->id, $auditors),
        ]);

        $project = AuditProject::where('name', $name)->firstOrFail();
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        return $project;
    }

    private function assignAsPic(AuditProject $project, AuditProjectIndicator $indicator, User $pic, User $actor): void
    {
        app(AssignmentService::class)->assign($project, $indicator, $pic, $actor);
        $this->inProgress($project);
    }

    private function inProgress(AuditProject $project): AuditProject
    {
        $project->update(['status' => AuditProjectStatus::InProgress->value]);

        return $project;
    }

    public function test_pic_can_open_their_own_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Indikator 1')
            ->assertSee('Parameter 1')
            ->assertSee('Simpan Draft')
            ->assertSee('Mode edit');
    }

    public function test_pic_can_save_a_draft_while_project_is_in_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Temuan audit awal.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $result = AuditIndicatorResult::where('audit_project_indicator_id', $indicator->id)->firstOrFail();

        $this->assertSame('Temuan audit awal.', $result->result_text);
        $this->assertSame(1, $result->lock_version);
        $this->assertSame($auditor->id, $result->created_by);
    }

    public function test_draft_is_stored_on_the_correct_indicator(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $first = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $second = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();
        app(AssignmentService::class)->assign($project, $first, $auditor, $admin);
        app(AssignmentService::class)->assign($project, $second, $auditor, $admin);
        $this->inProgress($project);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $first]), [
                'result_text' => 'Hanya untuk indikator pertama.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_indicator_results', [
            'audit_project_indicator_id' => $first->id,
            'result_text' => 'Hanya untuk indikator pertama.',
        ]);
        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $second->id]);
        $this->assertSame(1, $project->results()->count());
    }

    public function test_updating_a_draft_updates_the_same_record_and_creates_a_second_version(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Versi pertama.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Versi kedua.',
                'lock_version' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(1, AuditIndicatorResult::where('audit_project_indicator_id', $indicator->id)->count(), 'must not duplicate');
        $this->assertSame('Versi kedua.', $indicator->fresh()->result->result_text);
        $this->assertSame(2, $indicator->fresh()->result->lock_version);

        $versions = $project->resultVersions()->where('audit_project_indicator_id', $indicator->id)->orderBy('version_number')->get();
        $this->assertSame(2, $versions->count());
        $this->assertSame(1, (int) $versions[0]->version_number);
        $this->assertSame('Versi pertama.', $versions[0]->result_text);
        $this->assertSame(2, (int) $versions[1]->version_number);
        $this->assertSame('Versi kedua.', $versions[1]->result_text);
    }

    public function test_identical_content_does_not_create_an_extra_version(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Konten sama.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => ' Konten sama. ',
                'lock_version' => 1,
            ])
            ->assertRedirect();

        $result = $indicator->fresh()->result;

        $this->assertSame(1, $result->lock_version, 'lock_version must not change for identical content');
        $this->assertSame(1, $project->resultVersions()->count(), 'identical content must not add a version');
    }

    public function test_empty_result_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => '',
                'lock_version' => 0,
            ])
            ->assertSessionHasErrors('result_text');

        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicator->id]);

        $this->expectException(ValidationException::class);
        app(AuditResultService::class)->saveDraft($this->inProgress($project), $indicator, $auditor, '   ', 0);
    }

    public function test_auditor_who_is_not_the_pic_cannot_update(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditorA, $admin);

        $this->actingAs($auditorB)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Tidak boleh.',
                'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
                'lock_version' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicator->id]);
    }

    public function test_lead_can_view_but_not_edit_an_auditors_indicator(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($lead)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Read only');

        $this->actingAs($lead)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Lead tidak boleh edit.',
                'lock_version' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicator->id]);
    }

    public function test_lead_who_is_the_pic_can_edit_their_own_indicator(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $lead, $admin);

        $this->actingAs($lead)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Hasil dari Lead sebagai PIC.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $this->assertSame('Hasil dari Lead sebagai PIC.', $indicator->fresh()->result->result_text);
    }

    public function test_admin_can_view_but_not_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($admin)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Read only');

        $this->actingAs($admin)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Admin tidak boleh edit.',
                'lock_version' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicator->id]);
    }

    public function test_outside_user_cannot_open_the_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $outsider = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($outsider)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertForbidden();
    }

    public function test_result_cannot_be_saved_outside_in_progress_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();

        foreach ([
            AuditProjectStatus::Assignment,
            AuditProjectStatus::Review,
            AuditProjectStatus::Final,
            AuditProjectStatus::Archived,
        ] as $status) {
            $project = $this->createProject($admin, $lead, [$auditor]);
            $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
            app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);
            $project->update(['status' => $status->value]);

            $this->actingAs($auditor)
                ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                    'result_text' => 'Tidak boleh di status '.$status->value,
                    'lock_version' => 0,
                ])
                ->assertForbidden();

            $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicator->id]);
        }
    }

    public function test_service_rejects_saving_outside_in_progress_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);

        try {
            app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Harus ditolak.', 0);
            $this->fail('Saving outside in_progress must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicator->id]);
    }

    public function test_indicator_from_another_project_cannot_be_used(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $projectA = $this->createProject($admin, $lead, [$auditor]);
        $projectB = $this->createProject($admin, $lead, [$auditor]);
        $indicatorOfB = $projectB->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($projectA, $projectA->snapshotIndicators()->where('code', '1.1.2')->firstOrFail(), $auditor, $admin);
        app(AssignmentService::class)->assign($projectB, $indicatorOfB, $auditor, $admin);
        $this->inProgress($projectB);

        $this->actingAs($auditor)
            ->get(route('audit-projects.work.show', [$projectA, $indicatorOfB]))
            ->assertNotFound();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$projectA, $indicatorOfB]), [
                'result_text' => 'Tidak boleh.',
                'lock_version' => 0,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('audit_indicator_results', ['audit_project_indicator_id' => $indicatorOfB->id]);
    }

    public function test_optimistic_locking_rejects_stale_lock_version(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Versi satu.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Versi dua.',
                'lock_version' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Versi konflik.',
                'lock_version' => 1,
            ])
            ->assertSessionHasErrors('lock_version');
    }

    public function test_failed_optimistic_locking_does_not_touch_result_or_history(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $service = app(AuditResultService::class);
        $service->saveDraft($project, $indicator, $auditor, 'Versi satu.', 0);
        $service->saveDraft($project, $indicator, $auditor, 'Versi dua.', 1);

        $beforeConflict = $indicator->fresh()->result;
        $versionCountBeforeConflict = $project->resultVersions()
            ->where('audit_project_indicator_id', $indicator->id)
            ->count();
        $activityCountBeforeConflict = ActivityLog::where('audit_project_id', $project->id)->count();

        try {
            $service->saveDraft($project, $indicator, $auditor, 'Versi konflik.', 1);
            $this->fail('Stale lock_version must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('lock_version', $e->errors());
        }

        $result = $indicator->fresh()->result;

        $this->assertSame('Versi dua.', $result->result_text);
        $this->assertSame(2, $result->lock_version);
        $this->assertSame($beforeConflict->updated_by, $result->updated_by);
        $this->assertEquals($beforeConflict->updated_at, $result->updated_at);
        $this->assertSame($versionCountBeforeConflict, $project->resultVersions()
            ->where('audit_project_indicator_id', $indicator->id)
            ->count(), 'no version may be created by a conflicted save');
        $this->assertSame($activityCountBeforeConflict, ActivityLog::where('audit_project_id', $project->id)->count());
    }

    public function test_activity_log_is_written_on_create_and_update_without_content(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Isi rahasia audit.',
                'lock_version' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'activity' => 'audit_result.created',
        ]);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Isi rahasia audit diperbarui.',
                'lock_version' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'activity' => 'audit_result.updated',
        ]);

        foreach (ActivityLog::where('audit_project_id', $project->id)->where('activity', 'like', 'audit_result.%')->get() as $log) {
            $this->assertStringNotContainsString('Isi rahasia', $log->description);
            $this->assertStringNotContainsString('Isi rahasia', (string) json_encode($log->meta));
        }
    }

    public function test_my_work_lists_only_own_assigned_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $service = app(AssignmentService::class);
        $mine = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $theirs = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();
        $service->assign($project, $mine, $auditorA, $admin);
        $service->assign($project, $theirs, $auditorB, $admin);

        $this->actingAs($auditorA)
            ->get(route('audit-projects.my-work', $project))
            ->assertOk()
            ->assertSee('Indikator 1')
            ->assertDontSee('Indikator 4');
    }

    public function test_my_work_summary_counts_draft_and_not_started(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);

        $service = app(AssignmentService::class);
        $first = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $second = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();
        $service->assign($project, $first, $auditor, $admin);
        $service->assign($project, $second, $auditor, $admin);
        $this->inProgress($project);

        $this->actingAs($auditor)
            ->get(route('audit-projects.my-work', $project))
            ->assertOk()
            ->assertSee('2 total')
            ->assertSee('2 belum dikerjakan')
            ->assertSee('0 draft');

        app(AuditResultService::class)->saveDraft($project, $first, $auditor, 'Sekaligus dikerjakan.', 0);

        $this->actingAs($auditor)
            ->get(route('audit-projects.my-work', $project))
            ->assertOk()
            ->assertSee('2 total')
            ->assertSee('1 belum dikerjakan')
            ->assertSee('1 draft')
            ->assertSee('DRAFT');
    }

    public function test_workspace_previous_next_navigation_only_moves_through_own_pic_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $service = app(AssignmentService::class);
        $i1 = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $i2 = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();
        $i3 = $project->snapshotIndicators()->where('code', '1.1.3')->firstOrFail();
        $theirs = $project->snapshotIndicators()->where('code', '2.1.2')->firstOrFail();

        $i1->update(['sort_order' => 10]);
        $i2->update(['sort_order' => 20]);
        $i3->update(['sort_order' => 30]);

        $service->assign($project, $i1, $auditorA, $admin);
        $service->assign($project, $i2, $auditorA, $admin);
        $service->assign($project, $i3, $auditorA, $admin);
        $service->assign($project, $theirs, $auditorB, $admin);

        $this->actingAs($auditorA)
            ->get(route('audit-projects.work.show', [$project, $i1]))
            ->assertOk()
            ->assertDontSee('Sebelumnya:', 'first own indicator has no previous hop')
            ->assertSee(route('audit-projects.work.show', [$project, $i2]));

        $this->actingAs($auditorA)
            ->get(route('audit-projects.work.show', [$project, $i2]))
            ->assertOk()
            ->assertSee(route('audit-projects.work.show', [$project, $i1]))
            ->assertSee(route('audit-projects.work.show', [$project, $i3]));

        $this->actingAs($auditorA)
            ->get(route('audit-projects.work.show', [$project, $i3]))
            ->assertOk()
            ->assertSee(route('audit-projects.work.show', [$project, $i2]))
            ->assertDontSee('Berikutnya:', 'last own indicator has no next hop')
            ->assertDontSee(route('audit-projects.work.show', [$project, $theirs]), 'another auditor\'s indicator must never appear');

        $this->actingAs($auditorA)
            ->get(route('audit-projects.work.show', [$project, $theirs]))
            ->assertForbidden();
    }

    public function test_create_with_nonzero_lock_version_is_rejected_without_creating_result(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Tidak boleh dibuat.',
                'lock_version' => 1,
            ])
            ->assertSessionHasErrors('lock_version');

        $this->assertDatabaseMissing('audit_indicator_results', [
            'audit_project_indicator_id' => $indicator->id,
        ]);
    }

    public function test_create_without_lock_version_is_rejected_by_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Versi tanpa lock.',
            ])
            ->assertSessionHasErrors('lock_version');
    }

    public function test_identical_content_with_current_lock_version_is_a_no_op(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);
        $service = app(AuditResultService::class);

        $result = $service->saveDraft($project, $indicator, $auditor, 'Konten sama.', 0);
        $before = $result->fresh();
        $versionCount = $result->versions()->count();
        $logCount = ActivityLog::where('audit_project_id', $project->id)->count();

        $service->saveDraft($project, $indicator, $auditor, 'Konten sama.', 1);
        $after = $result->fresh();

        $this->assertSame($before->result_text, $after->result_text);
        $this->assertSame($before->lock_version, $after->lock_version);
        $this->assertSame($before->updated_by, $after->updated_by);
        $this->assertEquals($before->updated_at, $after->updated_at);
        $this->assertSame($versionCount, $after->versions()->count());
        $this->assertSame($logCount, ActivityLog::where('audit_project_id', $project->id)->count());
    }

    public function test_identical_content_with_stale_lock_version_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);
        $service = app(AuditResultService::class);

        $service->saveDraft($project, $indicator, $auditor, 'Konten sama.', 0);

        $this->expectException(ValidationException::class);
        $service->saveDraft($project, $indicator, $auditor, 'Konten sama.', 0);
    }

    public function test_new_result_has_null_assessment_status_by_default_and_in_version_one(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();

        $result = app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Tanpa status.', 0);

        $this->assertNull($result->assessment_status);
        $this->assertNull($result->versions()->firstOrFail()->assessment_status);
    }

    public function test_each_valid_assessment_status_can_be_saved(): void
    {
        foreach (AuditAssessmentStatus::cases() as $status) {
            [$project, $indicator, $auditor] = $this->assessmentContext();

            $result = app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Status '.$status->value, 0, $status);

            $this->assertSame($status, $result->assessment_status);
            $this->assertSame($status, $result->versions()->firstOrFail()->assessment_status);
        }
    }

    public function test_invalid_assessment_status_is_rejected(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'result_text' => 'Status invalid.',
                'assessment_status' => 'invalid',
                'lock_version' => 0,
            ])
            ->assertSessionHasErrors('assessment_status');
    }

    public function test_status_only_change_creates_version_and_increments_lock_version(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $service = app(AuditResultService::class);
        $service->saveDraft($project, $indicator, $auditor, 'Isi tetap.', 0, AuditAssessmentStatus::Memenuhi);

        $result = $service->saveDraft($project, $indicator, $auditor, 'Isi tetap.', 1, AuditAssessmentStatus::TidakMemenuhi);

        $this->assertSame(2, $result->lock_version);
        $this->assertSame(AuditAssessmentStatus::TidakMemenuhi, $result->assessment_status);
        $this->assertSame(2, $result->versions()->count());
        $this->assertSame(AuditAssessmentStatus::TidakMemenuhi, $result->versions()->latest('version_number')->firstOrFail()->assessment_status);
    }

    public function test_identical_text_and_status_is_a_no_op(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $service = app(AuditResultService::class);
        $result = $service->saveDraft($project, $indicator, $auditor, 'Tetap sama.', 0, AuditAssessmentStatus::Na);
        $before = $result->fresh();
        $versionCount = $result->versions()->count();
        $activityCount = ActivityLog::where('audit_project_id', $project->id)->count();

        $service->saveDraft($project, $indicator, $auditor, 'Tetap sama.', 1, AuditAssessmentStatus::Na);
        $after = $result->fresh();

        $this->assertSame($before->lock_version, $after->lock_version);
        $this->assertEquals($before->updated_at, $after->updated_at);
        $this->assertSame($before->updated_by, $after->updated_by);
        $this->assertSame($versionCount, $after->versions()->count());
        $this->assertSame($activityCount, ActivityLog::where('audit_project_id', $project->id)->count());
    }

    public function test_stale_status_change_is_rejected_without_changing_status_or_version(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $service = app(AuditResultService::class);
        $service->saveDraft($project, $indicator, $auditor, 'Status lama.', 0, AuditAssessmentStatus::Memenuhi);
        $service->saveDraft($project, $indicator, $auditor, 'Status baru.', 1, AuditAssessmentStatus::TidakMemenuhi);
        $before = $indicator->fresh()->result;
        $versionCount = $before->versions()->count();

        try {
            $service->saveDraft($project, $indicator, $auditor, 'Status stale.', 1, AuditAssessmentStatus::Na);
            $this->fail('Stale status save must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('lock_version', $e->errors());
        }

        $after = $indicator->fresh()->result;
        $this->assertSame(AuditAssessmentStatus::TidakMemenuhi, $after->assessment_status);
        $this->assertSame($versionCount, $after->versions()->count());
    }

    public function test_pic_can_autosave_and_receives_current_lock_version(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();

        $response = $this->actingAs($auditor)->putJson(
            route('audit-projects.work.autosave', [$project, $indicator]),
            [
                'result_text' => 'Autosave pertama.',
                'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
                'lock_version' => 0,
            ],
        );

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'lock_version' => 1,
            ])
            ->assertJsonStructure(['ok', 'lock_version', 'saved_at']);
        $this->assertDatabaseHas('audit_indicator_results', [
            'audit_project_indicator_id' => $indicator->id,
            'result_text' => 'Autosave pertama.',
            'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
        ]);
    }

    public function test_identical_autosave_is_a_no_op(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $service = app(AuditResultService::class);
        $service->saveDraft($project, $indicator, $auditor, 'Autosave sama.', 0, AuditAssessmentStatus::Na);
        $versions = $project->resultVersions()->count();
        $logs = ActivityLog::where('audit_project_id', $project->id)->count();

        $this->actingAs($auditor)->putJson(
            route('audit-projects.work.autosave', [$project, $indicator]),
            [
                'result_text' => 'Autosave sama.',
                'assessment_status' => AuditAssessmentStatus::Na->value,
                'lock_version' => 1,
            ],
        )->assertOk()->assertJson(['ok' => true, 'noop' => true, 'lock_version' => 1]);

        $this->assertSame($versions, $project->resultVersions()->count());
        $this->assertSame($logs, ActivityLog::where('audit_project_id', $project->id)->count());
    }

    public function test_stale_autosave_returns_conflict_without_mutating_data(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $service = app(AuditResultService::class);
        $service->saveDraft($project, $indicator, $auditor, 'Autosave awal.', 0, AuditAssessmentStatus::Memenuhi);
        $service->saveDraft($project, $indicator, $auditor, 'Autosave terbaru.', 1, AuditAssessmentStatus::TidakMemenuhi);
        $before = $indicator->fresh()->result;
        $versions = $before->versions()->count();
        $logs = ActivityLog::where('audit_project_id', $project->id)->count();

        $this->actingAs($auditor)->putJson(
            route('audit-projects.work.autosave', [$project, $indicator]),
            [
                'result_text' => 'Autosave stale.',
                'assessment_status' => AuditAssessmentStatus::Na->value,
                'lock_version' => 1,
            ],
        )->assertStatus(409)
            ->assertJson([
                'conflict' => true,
                'message' => 'Hasil Audit telah berubah sejak halaman ini dibuka. Muat ulang halaman sebelum menyimpan kembali.',
            ]);

        $after = $indicator->fresh()->result;
        $this->assertSame('Autosave terbaru.', $after->result_text);
        $this->assertSame(AuditAssessmentStatus::TidakMemenuhi, $after->assessment_status);
        $this->assertSame($versions, $after->versions()->count());
        $this->assertSame($logs, ActivityLog::where('audit_project_id', $project->id)->count());
    }

    public function test_autosave_validation_and_authorization_failures_use_expected_statuses(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $lead = $project->leadMember->user;
        $admin = User::factory()->admin()->create();
        $payload = [
            'result_text' => 'Autosave invalid.',
            'assessment_status' => 'invalid',
            'lock_version' => 0,
        ];

        $this->actingAs($auditor)->putJson(route('audit-projects.work.autosave', [$project, $indicator]), $payload)
            ->assertStatus(422)->assertJsonValidationErrors('assessment_status');

        $this->actingAs($auditor)->putJson(route('audit-projects.work.autosave', [$project, $indicator]), [
            'result_text' => 'Autosave tanpa lock.',
            'assessment_status' => null,
        ])->assertStatus(422)->assertJsonValidationErrors('lock_version');

        $this->actingAs($lead)->putJson(route('audit-projects.work.autosave', [$project, $indicator]), $payload)
            ->assertForbidden();
        $this->actingAs($admin)->putJson(route('audit-projects.work.autosave', [$project, $indicator]), $payload)
            ->assertForbidden();
    }

    public function test_autosave_rejects_non_pic_and_projects_not_in_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $other = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor, $other]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);

        $payload = [
            'result_text' => 'Tidak boleh.',
            'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
            'lock_version' => 0,
        ];
        $this->actingAs($other)->putJson(route('audit-projects.work.autosave', [$project, $indicator]), $payload)
            ->assertForbidden();

        $project->update(['status' => AuditProjectStatus::Assignment->value]);
        $this->actingAs($auditor)->putJson(route('audit-projects.work.autosave', [$project, $indicator]), $payload)
            ->assertForbidden();
    }

    public function test_cross_project_autosave_is_not_found(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $projectA = $this->createProject($admin, $lead, [$auditor]);
        $projectB = $this->createProject($admin, $lead, [$auditor]);
        $indicatorB = $projectB->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();

        $this->actingAs($auditor)->putJson(
            route('audit-projects.work.autosave', [$projectA, $indicatorB]),
            [
                'result_text' => 'Tidak boleh.',
                'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
                'lock_version' => 0,
            ],
        )->assertNotFound();
    }

    public function test_editable_workspace_exposes_autosave_url_and_current_lock_version(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();

        $this->actingAs($auditor)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('data-autosave-url', false)
            ->assertSee('data-lock-version', false)
            ->assertSee(route('audit-projects.work.autosave', [$project, $indicator]), false);
    }

    public function test_lead_and_admin_can_only_view_the_assessment_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);
        app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Hasil berstatus.', 0, AuditAssessmentStatus::Memenuhi);

        $this->actingAs($lead)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Memenuhi')
            ->assertSee('Read only');

        $this->actingAs($admin)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Memenuhi')
            ->assertSee('Read only');
    }

    public function test_progress_service_classifies_and_summarizes_snapshot_indicators(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $service = app(AuditProgressService::class);
        $this->assertSame(AuditProgressState::NotStarted, $service->stateFor($indicator));

        app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Isi', 0);
        $indicator->refresh()->load('result');
        $this->assertSame(AuditProgressState::StatusPending, $service->stateFor($indicator));

        app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Isi', 1, AuditAssessmentStatus::Memenuhi);
        $indicator->refresh()->load('result');
        $this->assertSame(AuditProgressState::DraftComplete, $service->stateFor($indicator));

        $summary = $service->projectSummary($project);
        $this->assertSame(5, $summary['total']);
        $this->assertSame(1, $summary['draft_complete']);
        $this->assertSame(20.0, $summary['completeness_percentage']);
    }

    public function test_progress_summary_returns_zero_percentages_for_project_without_indicators(): void
    {
        $project = AuditProject::factory()->create();

        $summary = app(AuditProgressService::class)->projectSummary($project);

        $this->assertSame(0, $summary['total']);
        $this->assertSame(0, $summary['not_started']);
        $this->assertSame(0, $summary['status_pending']);
        $this->assertSame(0, $summary['draft_complete']);
        $this->assertSame(0.0, $summary['filling_percentage']);
        $this->assertSame(0.0, $summary['completeness_percentage']);
    }

    public function test_auditor_breakdown_uses_assignments_and_includes_lead_and_unassigned_members(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $unassigned = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor, $unassigned]);
        $service = app(AssignmentService::class);
        $leadIndicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $auditorIndicator = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();
        $service->assign($project, $leadIndicator, $lead, $admin);
        $service->assign($project, $auditorIndicator, $auditor, $admin);

        $rows = app(AuditProgressService::class)->auditorBreakdown($project)->keyBy(fn (array $row): int => $row['user']->id);

        $this->assertSame(1, $rows[$lead->id]['assigned']);
        $this->assertSame(1, $rows[$auditor->id]['assigned']);
        $this->assertSame(0, $rows[$unassigned->id]['assigned']);
        $this->assertSame(1, $rows[$lead->id]['not_started']);
        $this->assertSame(0, $rows[$auditor->id]['draft_complete']);
    }

    public function test_principle_breakdown_counts_nested_section_indicator_under_parent_principle(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $nested = $project->snapshotIndicators()->where('code', '1.1.1.1')->firstOrFail();
        $project->assignments()->create([
            'audit_project_indicator_id' => $nested->id,
            'pic_user_id' => $auditor->id,
            'assigned_by' => $project->created_by,
            'assigned_at' => now(),
        ]);
        app(AuditResultService::class)->saveDraft($project, $nested, $auditor, 'Nested result.', 0, AuditAssessmentStatus::Memenuhi);

        $rows = app(AuditProgressService::class)->principleBreakdown($project)->keyBy(fn (array $row): string => $row['principle']->code);

        $this->assertSame(3, $rows['1']['total']);
        $this->assertSame(1, $rows['1']['draft_complete']);
        $this->assertSame(2, $rows['1']['not_started']);
        $this->assertSame(2, $rows['2']['total']);
        $this->assertSame(0, $rows['2']['draft_complete']);
    }

    public function test_lead_and_admin_can_view_read_only_progress_monitoring(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Isi', 0, AuditAssessmentStatus::Memenuhi);

        $admin = User::where('id', $project->created_by)->firstOrFail();
        $lead = $project->leadMember->user;

        $this->actingAs($lead)
            ->get(route('audit-projects.progress', $project))
            ->assertOk()
            ->assertSee('Monitoring Progress')
            ->assertSee('Draft Lengkap');

        $this->actingAs($admin)
            ->get(route('audit-projects.progress', $project))
            ->assertOk();

        $this->actingAs($auditor)
            ->get(route('audit-projects.progress', $project))
            ->assertForbidden();
    }

    public function test_outsider_cannot_view_progress_monitoring(): void
    {
        [$project] = $this->assessmentContext();
        $outsider = User::factory()->auditor()->create();

        $this->actingAs($outsider)
            ->get(route('audit-projects.progress', $project))
            ->assertForbidden();
    }

    public function test_lead_and_admin_open_monitoring_indicators_in_existing_read_only_workspace(): void
    {
        [$project, $indicator, $auditor] = $this->assessmentContext();
        $admin = User::where('id', $project->created_by)->firstOrFail();
        $lead = $project->leadMember->user;

        $this->actingAs($lead)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Read only');

        $this->actingAs($admin)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Read only');
    }

    public function test_my_work_summary_only_counts_current_users_assignments(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $otherAuditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor, $otherAuditor]);
        $service = app(AssignmentService::class);
        $mine = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $other = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();
        $service->assign($project, $mine, $auditor, $admin);
        $service->assign($project, $other, $otherAuditor, $admin);
        $this->inProgress($project);
        app(AuditResultService::class)->saveDraft($project, $mine, $auditor, 'Hasil saya.', 0, AuditAssessmentStatus::Memenuhi);

        $this->actingAs($auditor)
            ->get(route('audit-projects.my-work', $project))
            ->assertOk()
            ->assertSee('1 total')
            ->assertSee('0 belum dikerjakan')
            ->assertSee('1 draft lengkap')
            ->assertDontSee('Indikator 4');
    }

    private function assessmentContext(): array
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $this->assignAsPic($project, $indicator, $auditor, $admin);

        return [$project, $indicator, $auditor];
    }
}
