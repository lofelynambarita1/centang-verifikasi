<?php

namespace Tests\Feature;

use App\Enums\AuditAssessmentStatus;
use App\Enums\AuditEvidenceLifecycleStatus;
use App\Enums\AuditProjectStatus;
use App\Enums\ProjectRole;
use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectMember;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AuditReportDataService;
use App\Services\AuditReportWordService;
use App\Services\AuditResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Tests\TestCase;

class AuditReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_detail_shows_report_preview_button(): void
    {
        [$project, , , $lead] = $this->context();

        $this->actingAs($lead)->get(route('audit-projects.show', $project))
            ->assertOk()
            ->assertSee('Preview Laporan')
            ->assertSee(route('audit-projects.report.preview', $project), false);
    }

    public function test_project_member_can_preview_ordered_report_data_and_preview_is_read_only(): void
    {
        [$project, $indicator, $pic, $lead] = $this->context();
        Storage::fake('audit_evidence');
        $result = app(AuditResultService::class)->saveDraft($project, $indicator, $pic, 'Temuan tersimpan.', 0, AuditAssessmentStatus::Memenuhi);
        $this->upload($project, $indicator, $pic, 'included.png', 'image/png');
        $included = AuditIndicatorEvidence::firstOrFail();
        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $included]), [
            'caption' => 'Foto lapangan',
            'is_report_included' => true,
        ])->assertRedirect();

        $before = [$result->fresh()->result_text, $result->fresh()->lock_version, $result->versions()->count(), $result->fresh()->workflow_status->value];
        $response = $this->actingAs($pic)->get(route('audit-projects.report.preview', $project));

        $response->assertOk()
            ->assertSee('← Kembali ke Project')
            ->assertSee('Download Word')
            ->assertSee('Download PDF')
            ->assertSee('>Print<', false)
            ->assertSee('onclick="window.print()"', false)
            ->assertSee(route('audit-projects.report.pdf', $project), false)
            ->assertSee(route('audit-projects.report.word', $project), false)
            ->assertSee('PT GLOBAL INSPEKSI SERTIFIKASI')
            ->assertSee('Nama Perusahaan')
            ->assertSee($project->company->name)
            ->assertSee('Prinsip Satu')
            ->assertSee('Kriteria 1')
            ->assertSee('Indikator 1')
            ->assertSee('Parameter 1')
            ->assertSee('Temuan tersimpan.')
            ->assertSee('Memenuhi')
            ->assertSee('Foto lapangan')
            ->assertSee('data:image/png;base64,');

        $after = [$result->fresh()->result_text, $result->fresh()->lock_version, $result->versions()->count(), $result->fresh()->workflow_status->value];
        $this->assertSame($before, $after);
        $this->assertSame(1, AuditIndicatorEvidence::count());
    }

    public function test_report_lists_all_project_auditors_in_lead_first_order(): void
    {
        [$project, , , $lead] = $this->context();
        $firstAuditor = User::factory()->auditor()->create(['name' => 'Auditor Pertama']);
        $secondAuditor = User::factory()->auditor()->create(['name' => 'Auditor Kedua']);
        AuditProjectMember::factory()->create([
            'audit_project_id' => $project->id,
            'user_id' => $firstAuditor->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $lead->id,
        ]);
        AuditProjectMember::factory()->create([
            'audit_project_id' => $project->id,
            'user_id' => $secondAuditor->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $lead->id,
        ]);

        $content = $this->actingAs($lead)
            ->get(route('audit-projects.report.preview', $project))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('1. '.$lead->name, $content);
        $this->assertStringContainsString('3. Auditor Pertama', $content);
        $this->assertStringContainsString('4. Auditor Kedua', $content);
        $this->assertLessThan(
            strpos($content, '4. Auditor Kedua'),
            strpos($content, '3. Auditor Pertama'),
        );
    }

    public function test_lead_auditor_can_save_assessment_summary_and_report_preserves_newlines(): void
    {
        [$project, , , $lead] = $this->context();
        $summary = "Paragraf pertama.\n\nParagraf kedua.";

        $this->actingAs($lead)
            ->patch(route('audit-projects.assessment-summary.update', $project), [
                'assessment_summary' => $summary,
            ])
            ->assertRedirect(route('audit-projects.show', $project))
            ->assertSessionHas('success', 'Ringkasan penilaian berhasil disimpan.');

        $this->assertSame($summary, $project->fresh()->assessment_summary);

        $this->actingAs($lead)
            ->get(route('audit-projects.show', $project))
            ->assertOk()
            ->assertSee($summary, false);

        $this->actingAs($lead)
            ->get(route('audit-projects.report.preview', $project))
            ->assertOk()
            ->assertSee('RINGKASAN PENILAIAN')
            ->assertSee($summary, false);

        $this->actingAs($lead)
            ->get(route('audit-projects.report.pdf', $project))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_non_lead_and_non_member_cannot_save_assessment_summary(): void
    {
        [$project, , $auditor] = $this->context();
        $outsider = User::factory()->auditor()->create();

        foreach ([$auditor, $outsider] as $user) {
            $this->actingAs($user)
                ->patch(route('audit-projects.assessment-summary.update', $project), [
                    'assessment_summary' => 'Tidak boleh disimpan.',
                ])
                ->assertForbidden();
        }

        $this->assertNull($project->fresh()->assessment_summary);
    }

    public function test_report_only_includes_active_report_selected_evidence_and_handles_incomplete_result(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        $included = $this->upload($project, $indicator, $pic, 'included.pdf', 'application/pdf');
        $excluded = $this->upload($project, $indicator, $pic, 'excluded.pdf', 'application/pdf');
        $removed = $this->upload($project, $indicator, $pic, 'removed.pdf', 'application/pdf');
        $superseded = $this->upload($project, $indicator, $pic, 'superseded.pdf', 'application/pdf');

        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $included]), ['caption' => null, 'is_report_included' => true]);
        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $excluded]), ['caption' => null, 'is_report_included' => false]);
        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $removed]), ['caption' => null, 'is_report_included' => true]);
        $removed->update(['lifecycle_status' => AuditEvidenceLifecycleStatus::Removed]);
        $superseded->update(['is_report_included' => true, 'lifecycle_status' => AuditEvidenceLifecycleStatus::Superseded]);

        $this->actingAs($pic)->get(route('audit-projects.report.preview', $project))
            ->assertOk()
            ->assertSee('included.pdf')
            ->assertDontSee('excluded.pdf')
            ->assertDontSee('removed.pdf')
            ->assertDontSee('superseded.pdf')
            ->assertSee('Hasil Audit')
            ->assertSee('class="result">-', false)
            ->assertSee('Belum Dinilai');
    }

    public function test_outsider_cannot_preview_report(): void
    {
        [$project, , , $lead] = $this->context();

        $this->actingAs(User::factory()->auditor()->create())
            ->get(route('audit-projects.report.preview', $project))
            ->assertForbidden();
    }

    public function test_authorized_member_can_download_read_only_pdf_with_selected_evidence(): void
    {
        [$project, $indicator, $pic] = $this->context();
        Storage::fake('audit_evidence');
        $result = app(AuditResultService::class)->saveDraft($project, $indicator, $pic, 'Hasil PDF.', 0, AuditAssessmentStatus::Memenuhi);
        $included = $this->upload($project, $indicator, $pic, 'included.png', 'image/png');
        $excluded = $this->upload($project, $indicator, $pic, 'excluded.pdf', 'application/pdf');

        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $included]), [
            'caption' => 'Caption PDF',
            'is_report_included' => true,
        ]);
        $this->actingAs($pic)->patch(route('audit-projects.evidences.update', [$project, $indicator, $excluded]), [
            'caption' => null,
            'is_report_included' => false,
        ]);

        $before = [
            $result->fresh()->result_text,
            $result->fresh()->lock_version,
            $result->versions()->count(),
            AuditIndicatorEvidence::count(),
            $project->fresh()->version,
        ];

        $response = $this->actingAs($pic)->get(route('audit-projects.report.pdf', $project));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('Laporan-Verifikasi-Hasil-ISPO-Perusahaan-Report.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $after = [
            $result->fresh()->result_text,
            $result->fresh()->lock_version,
            $result->versions()->count(),
            AuditIndicatorEvidence::count(),
            $project->fresh()->version,
        ];
        $this->assertSame($before, $after);
    }

    public function test_outsider_cannot_download_report_pdf(): void
    {
        [$project, , , $lead] = $this->context();

        $this->actingAs(User::factory()->auditor()->create())
            ->get(route('audit-projects.report.pdf', $project))
            ->assertForbidden();
    }

    public function test_authorized_member_can_download_report_word_with_identity_data(): void
    {
        [$project, , , $lead] = $this->context();

        $response = $this->actingAs($lead)->get(route('audit-projects.report.word', $project));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertDownload('Laporan-Verifikasi-ISPO-Perusahaan-Report.docx');
    }

    public function test_generated_word_copy_can_be_loaded_again_by_template_processor(): void
    {
        [$project] = $this->context();
        $data = app(AuditReportDataService::class)->assemble($project);
        $path = app(AuditReportWordService::class)->generate($data);

        try {
            $this->assertFileExists($path);
            $this->assertGreaterThan(0, filesize($path));
            $this->assertSame([], (new TemplateProcessor($path))->getVariables());
        } finally {
            File::delete($path);
        }
    }

    public function test_outsider_cannot_download_report_word(): void
    {
        [$project] = $this->context();

        $this->actingAs(User::factory()->auditor()->create())
            ->get(route('audit-projects.report.word', $project))
            ->assertForbidden();
    }

    public function test_missing_word_template_returns_a_clear_not_found_response(): void
    {
        [$project, , , $lead] = $this->context();
        $templatePath = storage_path('app/templates/ispo/laporan-verifikasi-ispo.docx');
        $backupPath = $templatePath.'.test-backup';
        File::move($templatePath, $backupPath);

        try {
            $this->actingAs($lead)->get(route('audit-projects.report.word', $project))
                ->assertNotFound();
        } finally {
            File::move($backupPath, $templatePath);
        }
    }

    private function context(): array
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->auditor()->create();
        $pic = User::factory()->auditor()->create();
        $template = AuditTemplate::factory()->create();
        $principle = $template->principles()->create(['code' => '1', 'title' => 'Prinsip Satu', 'sort_order' => 1]);
        $section = $principle->sections()->create(['code' => '1.1', 'title' => 'Kriteria 1', 'sort_order' => 1]);
        $templateIndicator = $section->indicators()->create(['code' => '1.1.1', 'title' => 'Indikator 1', 'sort_order' => 1]);
        $templateIndicator->parameters()->create(['parameter_type' => 'parameter', 'content' => 'Parameter 1', 'sort_order' => 1]);
        $templateIndicator->parameters()->create(['parameter_type' => 'verifier', 'content' => 'Verifier 1', 'sort_order' => 2]);

        $this->actingAs($admin)->post(route('audit-projects.store'), [
            'template_id' => $template->id,
            'company_id' => Company::factory()->create(['name' => 'Perusahaan Report'])->id,
            'name' => 'Project Report',
            'audit_type' => 'Audit Lapangan',
            'audit_start_date' => '2026-01-01',
            'audit_end_date' => '2026-01-07',
            'land_area' => 123.45,
            'lead_auditor_id' => $lead->id,
            'auditor_ids' => [$pic->id],
        ]);
        $project = AuditProject::where('name', 'Project Report')->firstOrFail();
        $project->update(['status' => AuditProjectStatus::Assignment->value]);
        $indicator = $project->snapshotIndicators()->firstOrFail();
        app(AssignmentService::class)->assign($project, $indicator, $pic, $admin);
        $project->update(['status' => AuditProjectStatus::InProgress->value]);

        return [$project, $indicator, $pic, $lead];
    }

    private function upload(AuditProject $project, $indicator, User $pic, string $name, string $mime): AuditIndicatorEvidence
    {
        $this->actingAs($pic)->post(route('audit-projects.evidences.store', [$project, $indicator]), [
            'evidence' => UploadedFile::fake()->create($name, 10, $mime),
        ])->assertRedirect();

        return AuditIndicatorEvidence::latest('id')->firstOrFail();
    }
}
