<?php

namespace Tests\Feature;

use App\Enums\AuditProjectStatus;
use App\Models\AuditProject;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuditAssignmentTest extends TestCase
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
        $i2 = $s1->indicators()->create(['code' => '1.1.3', 'title' => 'Indikator 2', 'sort_order' => 2]);
        $i3 = $s1b->indicators()->create(['code' => '1.1.1.1', 'title' => 'Indikator Sub', 'sort_order' => 1]);

        $p2 = $template->principles()->create(['code' => '2', 'title' => 'Prinsip Dua', 'sort_order' => 10]);
        $s2 = $p2->sections()->create(['code' => '2.1', 'title' => 'Kriteria 2', 'sort_order' => 1]);
        $i4 = $s2->indicators()->create(['code' => '2.1.1', 'title' => 'Indikator 4', 'sort_order' => 1]);
        $i5 = $s2->indicators()->create(['code' => '2.1.2', 'title' => 'Indikator 5', 'sort_order' => 2]);

        return $template;
    }

    private function createProject(User $admin, User $lead, array $auditors = []): AuditProject
    {
        $template = $this->makeTemplate();

        $this->actingAs($admin)->post(route('audit-projects.store'), [
            'template_id' => $template->id,
            'company_id' => Company::factory()->create()->id,
            'name' => 'Audit Penugasan',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => now()->addMonth()->toDateString(),
            'audit_end_date' => now()->addMonth()->addDays(7)->toDateString(),
            'land_area' => 100,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => array_map(fn (User $u): int => $u->id, $auditors),
        ]);

        $project = AuditProject::where('name', 'Audit Penugasan')->firstOrFail();
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        return $project;
    }

    public function test_completed_team_setup_promotes_project_before_principle_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $project->update(['status' => AuditProjectStatus::Draft->value]);

        $this->actingAs($admin)
            ->post(route('audit-projects.team.complete', $project))
            ->assertRedirect()
            ->assertSessionHas('success', 'Tim auditor berhasil disimpan. Project siap untuk pembagian auditor.');

        $this->assertSame(AuditProjectStatus::Assignment, $project->refresh()->status);

        $this->actingAs($lead)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect()
            ->assertSessionHas('success', '2 Prinsip berhasil dibagi ke 2 auditor. 5 indikator telah memiliki PIC.');

        $this->assertSame(5, $project->assignments()->count());
    }

    public function test_single_indicator_assignment_creates_assignment_history_and_log(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.store', [$project, $indicator]), ['pic_user_id' => $auditor->id])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $auditor->id,
        ]);
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'from_user_id' => null,
            'to_user_id' => $auditor->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'activity' => 'assignment.created',
        ]);
    }

    public function test_assigning_a_principle_assigns_all_descendant_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $principle = $project->snapshotPrinciples()->where('code', '1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.bulk', $project), [
                'node_type' => 'principle',
                'node_id' => $principle->id,
                'pic_user_id' => $auditor->id,
            ])
            ->assertRedirect();

        $assigned = $project->snapshotIndicators()
            ->where('code', 'like', '1.%')
            ->whereHas('assignment', fn ($q) => $q->where('pic_user_id', $auditor->id))
            ->count();
        $this->assertSame(3, $assigned, 'Principle Satu owns 3 descendant indicators');
        $this->assertSame(0, $project->snapshotIndicators()->where('code', '2.1.1')->whereHas('assignment')->count());
    }

    public function test_assigning_a_nested_section_covers_deeper_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $section = $project->snapshotSections()->where('code', '1.1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.bulk', $project), [
                'node_type' => 'section',
                'node_id' => $section->id,
                'pic_user_id' => $auditor->id,
            ])
            ->assertRedirect();

        $this->assertSame(3, $project->snapshotIndicators()->whereHas('assignment', fn ($q) => $q->where('pic_user_id', $auditor->id))->count());
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_id' => $project->id,
            'changed_by' => $admin->id,
            'reason' => 'assigned',
        ]);
    }

    public function test_assignment_to_a_non_member_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $outsider = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.store', [$project, $indicator]), ['pic_user_id' => $outsider->id])
            ->assertSessionHasErrors('pic_user_id');

        $this->assertDatabaseMissing('audit_indicator_assignments', ['audit_project_indicator_id' => $indicator->id]);
    }

    public function test_regular_auditor_cannot_manage_assignments(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        $this->actingAs($auditor)
            ->post(route('audit-projects.assignments.store', [$project, $indicator]), ['pic_user_id' => $auditor->id])
            ->assertForbidden();
    }

    public function test_regular_auditors_cannot_open_assignment_page(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);

        $this->actingAs($auditor)
            ->get(route('audit-projects.assignments', $project))
            ->assertForbidden();
    }

    public function test_edit_project_does_not_show_the_status_dropdown(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($admin)
            ->get(route('audit-projects.edit', $project))
            ->assertOk()
            ->assertDontSee('name="status"', false)
            ->assertDontSee('>Status</label>', false);
    }

    public function test_admin_can_start_audit_after_all_indicators_are_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('audit-projects.start', $project))
            ->assertRedirect()
            ->assertSessionHas('success', 'Audit berhasil dimulai. Auditor sudah dapat mengisi Hasil Audit.');

        $this->assertSame(AuditProjectStatus::InProgress, $project->refresh()->status);

        $indicator = $project->snapshotIndicators()->firstOrFail();
        $this->actingAs($lead)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Simpan Draft');
    }

    public function test_admin_cannot_start_audit_with_unassigned_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.store', [$project, $project->snapshotIndicators()->firstOrFail()]), [
                'pic_user_id' => $lead->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('audit-projects.start', $project))
            ->assertSessionHasErrors('start_audit')
            ->assertSessionHasErrors([
                'start_audit' => 'Semua indikator harus memiliki auditor/PIC sebelum audit dimulai.',
            ]);

        $this->assertSame(AuditProjectStatus::Assignment, $project->refresh()->status);
    }

    public function test_lead_auditor_can_manage_assignments(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        $this->actingAs($lead)
            ->post(route('audit-projects.assignments.store', [$project, $indicator]), ['pic_user_id' => $auditor->id])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $auditor->id,
        ]);
    }

    public function test_lead_auditor_can_open_assignment_page_and_start_complete_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($lead)
            ->get(route('audit-projects.assignments', $project))
            ->assertOk()
            ->assertSee('Pembagian Auditor')
            ->assertSee('Bagi Prinsip Otomatis')
            ->assertSee('Lihat / Atur Detail')
            ->assertDontSee('name="pic_user_ids[]"', false)
            ->assertSee('data-assignment-drawer', false)
            ->assertSee('data-drawer-content', false)
            ->assertSee('data-drawer-content="', false)
            ->assertSee('data-detail-indicator', false);

        $this->actingAs($lead)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect();

        $this->actingAs($lead)
            ->post(route('audit-projects.start', $project))
            ->assertRedirectToRoute('audit-projects.my-work', $project)
            ->assertSessionHas('success', 'Audit berhasil dimulai. Auditor sudah dapat mengisi Hasil Audit.');

        $this->assertSame(AuditProjectStatus::InProgress, $project->refresh()->status);

        $this->actingAs($lead)
            ->get(route('audit-projects.assignments', $project))
            ->assertOk()
            ->assertSee('Lihat Detail')
            ->assertDontSee('Bagi Prinsip Otomatis')
            ->assertDontSee('Mulai Audit')
            ->assertDontSee('name="pic_user_id"', false);
    }

    public function test_assignment_page_explains_unassigned_indicators_before_starting(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($lead)
            ->get(route('audit-projects.assignments', $project))
            ->assertOk()
            ->assertSee('Audit belum dapat dimulai.')
            ->assertSee($project->snapshotPrinciples()->count().' Prinsip belum memiliki PIC')
            ->assertSee($project->snapshotIndicators()->count().' indikator belum memiliki auditor/PIC')
            ->assertDontSee('>Mulai Audit</button>', false);
    }

    public function test_lead_auditor_can_be_assigned_as_pic(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        app(AssignmentService::class)->assign($project, $indicator, $lead, $admin);

        $this->assertSame($lead->id, $indicator->assignment->pic_user_id);
    }

    public function test_reassigning_an_indicator_records_history(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        app(AssignmentService::class)->assign($project, $indicator, $auditorA, $admin);
        app(AssignmentService::class)->reassign($project, $indicator, $auditorB, $admin);

        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $auditorB->id,
        ]);
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_indicator_id' => $indicator->id,
            'from_user_id' => $auditorA->id,
            'to_user_id' => $auditorB->id,
            'changed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'activity' => 'assignment.reassigned',
        ]);
        $this->assertSame(2, $project->assignmentHistories()->count());
    }

    public function test_one_indicator_has_a_single_active_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        app(AssignmentService::class)->assign($project, $indicator, $auditorA, $admin);
        app(AssignmentService::class)->assign($project, $indicator, $auditorB, $admin);

        $this->assertSame(1, $project->assignments()->where('audit_project_indicator_id', $indicator->id)->count());
        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $auditorB->id,
        ]);
    }

    public function test_auto_assign_only_fills_wholly_unassigned_principles(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $manual = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        app(AssignmentService::class)->assign($project, $manual, $auditorA, $admin);

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect();

        $this->assertSame($auditorA->id, $manual->assignment->pic_user_id, 'manual assignment stays untouched');
        $this->assertSame(3, $project->assignments()->count(), 'the assigned principle is skipped and the other principle is filled');
        $this->assertDatabaseHas('activity_logs', [
            'audit_project_id' => $project->id,
            'activity' => 'assignment.auto_assigned',
        ]);
    }

    public function test_auto_assign_distributes_principles_in_round_robin_order(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $alreadyOwned = $project->snapshotIndicators()->where('code', '2.1.2')->firstOrFail();
        app(AssignmentService::class)->assign($project, $alreadyOwned, $auditorA, $admin);

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect();

        $principleOneIndicatorIds = $project->snapshotIndicators()
            ->whereIn('audit_project_section_id', $project->snapshotSections()->where('audit_project_principle_id', $project->snapshotPrinciples()->where('code', '1')->value('id'))->pluck('id'))
            ->pluck('id');

        $this->assertSame(4, $project->assignments()->count(), 'only the unassigned principle is filled');
        $this->assertSame(
            count($principleOneIndicatorIds),
            $project->assignments()->whereIn('audit_project_indicator_id', $principleOneIndicatorIds)
                ->where('pic_user_id', $lead->id)->count(),
        );
        $this->assertSame(
            0,
            $project->assignments()->whereIn('audit_project_indicator_id', $principleOneIndicatorIds)
                ->where('pic_user_id', $auditorB->id)->count(),
        );
        $this->assertSame($auditorA->id, $alreadyOwned->assignment->pic_user_id, 'existing assignment is preserved');
    }

    public function test_auto_assign_uses_all_project_members_without_auditor_input(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect()
            ->assertSessionHas('success', '2 Prinsip berhasil dibagi ke 1 auditor. 5 indikator telah memiliki PIC.');

        $this->assertSame(5, $project->assignments()->count());
    }

    public function test_auto_assign_uses_round_robin_for_one_selected_auditor(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($lead)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect()
            ->assertSessionHas('success', '2 Prinsip berhasil dibagi ke 1 auditor. 5 indikator telah memiliki PIC.');

        $this->assertSame(5, $project->assignments()->where('pic_user_id', $lead->id)->count());

        $this->actingAs($lead)
            ->get(route('audit-projects.assignments', $project))
            ->assertSee('2 / 2')
            ->assertSee('5 / 5 indikator memiliki PIC');
    }

    public function test_auto_assign_never_includes_a_user_who_is_not_a_project_member(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $outsider = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect();

        $this->assertSame(5, $project->assignments()->count());
        $this->assertDatabaseMissing('audit_indicator_assignments', ['pic_user_id' => $outsider->id]);
    }

    public function test_auto_assign_reports_when_every_indicator_already_has_a_pic(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        foreach ($project->snapshotIndicators()->get() as $indicator) {
            app(AssignmentService::class)->assign($project, $indicator, $lead, $admin);
        }

        $assignmentCount = $project->assignments()->count();

        $this->actingAs($admin)
            ->post(route('audit-projects.assignments.auto', $project))
            ->assertRedirect()
            ->assertSessionHas('success', 'Semua prinsip sudah memiliki pembagian atau pengecualian.');

        $this->assertSame($assignmentCount, $project->assignments()->count());
    }

    public function test_member_still_having_assignments_cannot_be_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);

        $indicator = $project->snapshotIndicators()->firstOrFail();
        app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);

        $member = $project->members()->where('user_id', $auditor->id)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('audit-projects.members.destroy', [$project, $member]))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('audit_project_members', ['id' => $member->id]);
        $this->assertSame(1, $project->assignments()->where('pic_user_id', $auditor->id)->count());
    }

    public function test_replacing_lead_does_not_change_old_lead_assignments(): void
    {
        $admin = User::factory()->admin()->create();
        $oldLead = User::factory()->auditor()->create();
        $newLead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $oldLead);

        $indicator = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        app(AssignmentService::class)->assign($project, $indicator, $oldLead, $admin);

        $this->actingAs($admin)
            ->put(route('audit-projects.lead-auditor.replace', $project), ['new_lead_auditor_id' => $newLead->id])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $oldLead->id,
        ]);
        $this->assertDatabaseHas('audit_project_members', [
            'audit_project_id' => $project->id,
            'user_id' => $oldLead->id,
            'project_role' => 'auditor',
        ]);
    }

    public function test_project_cannot_enter_in_progress_with_unassigned_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        $this->actingAs($admin)
            ->put(route('audit-projects.update', $project), $this->updatePayload($project, [
                'status' => AuditProjectStatus::InProgress->value,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertSame(AuditProjectStatus::Assignment->value, $project->fresh()->status->value);
    }

    public function test_project_can_enter_in_progress_when_everything_is_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);

        $service = app(AssignmentService::class);
        foreach ($project->snapshotIndicators()->get() as $indicator) {
            $service->assign($project, $indicator, $auditor, $admin);
        }

        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        $this->actingAs($admin)
            ->put(route('audit-projects.update', $project), $this->updatePayload($project, [
                'status' => AuditProjectStatus::InProgress->value,
            ]))
            ->assertRedirect();

        $this->assertSame(AuditProjectStatus::InProgress->value, $project->fresh()->status->value);
    }

    public function test_template_cannot_be_replaced_from_assignment_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        $otherTemplate = AuditTemplate::factory()->create();

        $this->actingAs($admin)
            ->put(route('audit-projects.update', $project), $this->updatePayload($project, [
                'template_id' => $otherTemplate->id,
            ]))
            ->assertSessionHasErrors('template_id');

        $this->assertNotSame($otherTemplate->id, $project->fresh()->template_id);
        $this->assertSame(5, $project->snapshotIndicators()->count(), 'snapshot must stay intact');
    }

    public function test_state_transition_backwards_is_blocked(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);
        $project->update(['status' => AuditProjectStatus::Assignment->value]);

        $this->actingAs($admin)
            ->put(route('audit-projects.update', $project), $this->updatePayload($project, [
                'status' => AuditProjectStatus::TeamSetup->value,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertSame(AuditProjectStatus::Assignment->value, $project->fresh()->status->value);
    }

    public function test_outsider_still_cannot_read_the_project(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $outsider = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead);

        $this->actingAs($outsider)
            ->get(route('audit-projects.assignments', $project))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('audit-projects.my-work', $project))
            ->assertForbidden();
    }

    public function test_my_work_page_only_lists_indicators_the_user_is_pic_of(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $mine = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        $other = $project->snapshotIndicators()->where('code', '2.1.1')->firstOrFail();

        $service = app(AssignmentService::class);
        $service->assign($project, $mine, $auditorA, $admin);
        $service->assign($project, $other, $auditorB, $admin);

        $this->actingAs($auditorA)
            ->get(route('audit-projects.my-work', $project))
            ->assertOk()
            ->assertSee('Indikator 1')
            ->assertDontSee('Indikator 4');
    }

    public function test_non_pic_auditor_cannot_open_an_indicator_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $mine = $project->snapshotIndicators()->where('code', '1.1.2')->firstOrFail();
        app(AssignmentService::class)->assign($project, $mine, $auditorA, $admin);

        $this->actingAs($auditorB)
            ->get(route('audit-projects.indicators.show', [$project, $mine]))
            ->assertForbidden();

        $this->actingAs($auditorA)
            ->get(route('audit-projects.indicators.show', [$project, $mine]))
            ->assertOk()
            ->assertSee('Parameter 1')
            ->assertSee('Verifier 1');
    }

    public function test_workload_count_per_member_is_reported(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);

        $service = app(AssignmentService::class);
        foreach ($project->snapshotIndicators()->take(2)->get() as $indicator) {
            $service->assign($project, $indicator, $auditorA, $admin);
        }
        $service->assign($project, $project->snapshotIndicators()->where('code', '2.1.2')->firstOrFail(), $auditorB, $admin);

        $workloads = $service->workloads($project)->keyBy('user_id');

        $this->assertSame(2, (int) $workloads[$auditorA->id]->assignments_count);
        $this->assertSame(1, (int) $workloads[$auditorB->id]->assignments_count);
        $this->assertSame(0, (int) $workloads[$lead->id]->assignments_count);
    }

    public function test_draft_project_cannot_assign(): void
    {
        $this->assertStatusBlocksAssignment(AuditProjectStatus::Draft);
    }

    public function test_team_setup_project_cannot_assign(): void
    {
        $this->assertStatusBlocksAssignment(AuditProjectStatus::TeamSetup);
    }

    public function test_in_progress_project_cannot_assign(): void
    {
        $this->assertStatusBlocksAssignment(AuditProjectStatus::InProgress);
    }

    public function test_review_final_and_archived_projects_cannot_assign(): void
    {
        foreach ([AuditProjectStatus::Review, AuditProjectStatus::Final, AuditProjectStatus::Archived] as $status) {
            $this->assertStatusBlocksAssignment($status);
        }
    }

    public function test_assignment_status_allows_assigning(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);

        $this->assertSame($auditor->id, $indicator->fresh()->assignment->pic_user_id);
    }

    public function test_bulk_and_auto_assignment_are_rejected_outside_assignment_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $project->update(['status' => AuditProjectStatus::InProgress->value]);
        $principle = $project->snapshotPrinciples()->firstOrFail();

        try {
            app(AssignmentService::class)->assignPrinciple($project, $principle->id, $auditor, $admin);
            $this->fail('Bulk Principle assignment must be rejected while the project is in progress.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        try {
            app(AssignmentService::class)->autoAssign($project, $admin);
            $this->fail('Auto assignment must be rejected while the project is in progress.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(0, $project->assignments()->count());
    }

    public function test_assignment_history_survives_referenced_user_deletion(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditorA = User::factory()->auditor()->create();
        $auditorB = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditorA, $auditorB]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        $service = app(AssignmentService::class);
        $service->assign($project, $indicator, $auditorB, $admin);
        $service->assign($project, $indicator, $auditorA, $admin);
        $service->assign($project, $indicator, $auditorB, $admin);

        $this->assertSame(3, $project->assignmentHistories()->count(), 'three assignments must be recorded');

        $auditorA->delete();

        $this->assertDatabaseMissing('users', ['id' => $auditorA->id]);
        $this->assertSame(3, $project->assignmentHistories()->count(), 'history rows must never be cascade-deleted');
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_indicator_id' => $indicator->id,
            'to_user_id' => $auditorB->id,
        ]);
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_indicator_id' => $indicator->id,
            'from_user_id' => null,
        ]);
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_indicator_id' => $indicator->id,
            'to_user_id' => null,
        ]);
        $this->assertDatabaseHas('audit_assignment_histories', [
            'audit_project_indicator_id' => $indicator->id,
            'changed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $auditorB->id,
        ]);
    }

    public function test_active_pic_user_cannot_be_physically_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);

        try {
            $auditor->delete();
            $this->fail('Physically deleting an active PIC must be blocked by the database.');
        } catch (QueryException $e) {
            $this->assertStringContainsStringIgnoringCase('foreign key', $e->getMessage());
        }

        $this->assertDatabaseHas('users', ['id' => $auditor->id]);
        $this->assertDatabaseHas('audit_indicator_assignments', [
            'audit_project_indicator_id' => $indicator->id,
            'pic_user_id' => $auditor->id,
        ]);
    }

    private function assertStatusBlocksAssignment(AuditProjectStatus $status): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $auditor = User::factory()->auditor()->create();
        $project = $this->createProject($admin, $lead, [$auditor]);
        $project->update(['status' => $status->value]);
        $indicator = $project->snapshotIndicators()->firstOrFail();

        try {
            app(AssignmentService::class)->assign($project, $indicator, $auditor, $admin);
            $this->fail('Assignment must be rejected when project status is "'.$status->value.'".');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertDatabaseMissing('audit_indicator_assignments', ['audit_project_indicator_id' => $indicator->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(AuditProject $project, array $overrides = []): array
    {
        return $overrides + [
            'template_id' => $project->template_id,
            'company_id' => $project->company_id,
            'name' => $project->name,
            'audit_type' => $project->audit_type,
            'audit_start_date' => $project->audit_start_date->toDateString(),
            'audit_end_date' => $project->audit_end_date->toDateString(),
            'status' => $project->status->value,
        ];
    }
}
