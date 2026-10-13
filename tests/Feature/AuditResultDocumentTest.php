<?php

namespace Tests\Feature;

use App\Enums\AuditAssessmentStatus;
use App\Enums\AuditEvidenceLifecycleStatus;
use App\Enums\AuditProjectStatus;
use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use App\Models\AuditTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AuditResultService;
use App\Services\AuditResultWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditResultDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_can_save_plain_paragraph_without_list(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'assessment_status' => AuditAssessmentStatus::Memenuhi->value,
                'result_content' => json_encode($this->document([
                    ['type' => 'paragraph', 'text' => 'Perusahaan telah memiliki dokumen legalitas.'],
                ])),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $result = $indicator->fresh()->result()->firstOrFail();

        $this->assertSame('Perusahaan telah memiliki dokumen legalitas.', $result->result_text);
        $this->assertSame('Perusahaan telah memiliki dokumen legalitas.', $result->result_content['ops'][0]['insert']);
    }

    public function test_numbered_nested_list_and_text_marks_are_saved_with_plain_text_fallback(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode($this->document([
                    ['type' => 'list_item', 'text' => 'Struktur organisasi tersedia.', 'list_type' => 'ordered', 'level' => 1, 'marks' => ['bold']],
                    ['type' => 'list_item', 'text' => 'Penanggung jawab ditetapkan.', 'list_type' => 'ordered', 'level' => 2, 'marks' => ['italic']],
                    ['type' => 'list_item', 'text' => 'Pembagian fungsi terdokumentasi.', 'list_type' => 'ordered', 'level' => 3, 'marks' => ['underline']],
                ])),
            ])
            ->assertRedirect();

        $result = $indicator->fresh()->result()->firstOrFail();

        $this->assertSame(implode(PHP_EOL, [
            '1. Struktur organisasi tersedia.',
            '   a. Penanggung jawab ditetapkan.',
            '      1) Pembagian fungsi terdokumentasi.',
        ]), $result->result_text);
        $this->assertTrue($result->result_content['ops'][0]['attributes']['bold']);
        $this->assertTrue($result->result_content['ops'][2]['attributes']['italic']);
        $this->assertTrue($result->result_content['ops'][4]['attributes']['underline']);
    }

    public function test_custom_font_fields_are_not_persisted(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode(['ops' => [
                    ['insert' => 'Format aplikasi tetap mengatur font.', 'attributes' => ['size' => '72px']],
                    ['insert' => "\n"],
                ]]),
            ])
            ->assertSessionHasErrors('result_content');

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode(['ops' => [
                    ['insert' => 'Format aplikasi tetap mengatur font.', 'attributes' => ['font' => 'serif']],
                    ['insert' => "\n"],
                ]]),
            ])
            ->assertSessionHasErrors('result_content');
    }

    public function test_bullet_list_and_justify_are_saved(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode($this->document([
                    ['type' => 'paragraph', 'text' => 'Paragraf rata kiri kanan.', 'align' => 'justify'],
                    ['type' => 'list_item', 'text' => 'Dokumen tersedia.', 'list_type' => 'bullet', 'level' => 1],
                    ['type' => 'list_item', 'text' => 'Dokumen diverifikasi.', 'list_type' => 'bullet', 'level' => 2],
                ])),
            ])
            ->assertRedirect();

        $result = $indicator->fresh()->result()->firstOrFail();

        $this->assertSame('justify', $result->result_content['ops'][1]['attributes']['align']);
        $this->assertSame('bullet', $result->result_content['ops'][3]['attributes']['list']);
        $this->assertSame(1, $result->result_content['ops'][5]['attributes']['indent']);
    }

    public function test_save_then_reload_keeps_delta_document(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        $document = $this->document([
            ['type' => 'paragraph', 'text' => 'Perusahaan telah memiliki dokumen legalitas.', 'marks' => ['bold']],
            ['type' => 'list_item', 'text' => 'Dokumen HGU tersedia.', 'list_type' => 'ordered', 'level' => 1],
            ['type' => 'list_item', 'text' => 'Dokumen masih berlaku.', 'list_type' => 'ordered', 'level' => 2],
        ]);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode($document),
            ])
            ->assertRedirect();

        $response = $this->actingAs($auditor)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk();

        $response->assertSee('Dokumen HGU tersedia');
        $this->assertSame($document, $indicator->fresh()->result->result_content);
    }

    public function test_autosave_lock_version_conflict_still_returns_409(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        app(AuditResultService::class)->saveDocumentDraft($project, $indicator, $auditor, $this->document([
            ['type' => 'paragraph', 'text' => 'Versi pertama.'],
        ]), 0);

        app(AuditResultService::class)->saveDocumentDraft($project, $indicator, $auditor, $this->document([
            ['type' => 'paragraph', 'text' => 'Versi kedua.'],
        ]), 1);

        $this->actingAs($auditor)
            ->putJson(route('audit-projects.work.autosave', [$project, $indicator]), [
                'lock_version' => 1,
                'result_content' => $this->document([
                    ['type' => 'paragraph', 'text' => 'Versi konflik.'],
                ]),
            ])
            ->assertStatus(409)
            ->assertJsonPath('conflict', true);
    }

    public function test_legacy_result_text_still_renders_when_document_content_is_empty(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        app(AuditResultService::class)->saveDraft($project, $indicator, $auditor, 'Teks lama tetap tampil.', 0);

        $this->actingAs($auditor)
            ->get(route('audit-projects.work.show', [$project, $indicator]))
            ->assertOk()
            ->assertSee('Teks lama tetap tampil.');
    }

    public function test_non_pic_cannot_edit_document(): void
    {
        [, , , $project, $indicator] = $this->assignedIndicator();
        $other = User::factory()->auditor()->create();

        $this->actingAs($other)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode($this->document([
                    ['type' => 'paragraph', 'text' => 'Tidak boleh.'],
                ])),
            ])
            ->assertForbidden();
    }

    public function test_submitted_result_is_readonly_for_pic(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $result = app(AuditResultService::class)->saveDocumentDraft($project, $indicator, $auditor, $this->document([
            ['type' => 'paragraph', 'text' => 'Siap dikirim.'],
        ]), 0, AuditAssessmentStatus::Memenuhi);

        app(AuditResultWorkflowService::class)->submit($project, $indicator, $auditor, $result->lock_version);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => $result->lock_version,
                'result_content' => json_encode($this->document([
                    ['type' => 'paragraph', 'text' => 'Tidak boleh diubah.'],
                ])),
            ])
            ->assertForbidden();
    }

    public function test_unsafe_html_script_and_remote_image_are_rejected(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode(['ops' => [
                    ['insert' => 'Konten tidak aman.', 'html' => '<script>alert(1)</script>'],
                    ['insert' => "\n"],
                ]]),
            ])
            ->assertSessionHasErrors('result_content');

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode([
                    'type' => 'audit_result_document',
                    'version' => 1,
                    'ops' => [
                        ['insert' => ['image' => 'https://example.test/image.png']],
                    ],
                ]),
            ])
            ->assertSessionHasErrors('result_content');
    }

    public function test_evidence_is_optional_and_existing_evidence_is_not_deleted_by_document_save(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        $evidence = AuditIndicatorEvidence::create([
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'original_name' => 'bukti.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 10,
            'sha256' => str_repeat('a', 64),
            'storage_disk' => 'audit_evidence',
            'storage_path' => 'project-'.$project->id.'/indicator-'.$indicator->id.'/bukti.png',
            'uploaded_by' => $auditor->id,
            'sort_order' => 10,
            'lifecycle_status' => AuditEvidenceLifecycleStatus::Active,
        ]);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode($this->document([
                    ['type' => 'paragraph', 'text' => 'Hasil audit tanpa gambar.'],
                ])),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_indicator_evidences', [
            'id' => $evidence->id,
            'audit_project_indicator_id' => $indicator->id,
        ]);
    }

    public function test_pic_can_upload_image_evidence_for_editor(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        Storage::fake('audit_evidence');

        $this->actingAs($auditor)
            ->postJson(route('audit-projects.evidences.editor-images.store', [$project, $indicator]), [
                'evidence' => UploadedFile::fake()->image('struktur.jpg'),
                'caption' => 'Struktur Organisasi Perusahaan',
            ])
            ->assertCreated()
            ->assertJsonPath('evidence.name', 'struktur.jpg')
            ->assertJsonPath('evidence.caption', 'Struktur Organisasi Perusahaan');

        $evidence = AuditIndicatorEvidence::firstOrFail();

        $this->assertSame('image/jpeg', $evidence->mime_type);
        $this->assertSame('Struktur Organisasi Perusahaan', $evidence->caption);
        Storage::disk('audit_evidence')->assertExists($evidence->storage_path);
    }

    public function test_pic_can_attach_existing_image_evidence_to_result_content(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        $evidence = $this->imageEvidence($project, $indicator, $auditor);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 0,
                'result_content' => json_encode(['ops' => [
                    ['insert' => "Perusahaan telah memiliki struktur organisasi.\n"],
                    ['insert' => ['auditEvidence' => ['evidence_id' => $evidence->id]]],
                    ['insert' => "\n"],
                    ['insert' => "Dokumen telah diverifikasi.\n"],
                ]]),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $result = $indicator->fresh()->result()->firstOrFail();

        $this->assertSame(['auditEvidence' => ['evidence_id' => $evidence->id]], $result->result_content['ops'][1]['insert']);
        $this->assertSame(implode(PHP_EOL, [
            'Perusahaan telah memiliki struktur organisasi.',
            'Dokumen telah diverifikasi.',
        ]), $result->result_text);
        $this->assertStringNotContainsString('project-', $result->result_text);
        $this->assertStringNotContainsString('http', $result->result_text);
    }

    public function test_evidence_from_other_project_or_indicator_cannot_be_embedded(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        [, , $otherAuditor, $otherProject, $otherIndicator] = $this->assignedIndicator();
        $foreignProjectEvidence = $this->imageEvidence($otherProject, $otherIndicator, $otherAuditor);
        $sameProjectOtherIndicator = $indicator->section->indicators()->create([
            'audit_project_id' => $project->id,
            'code' => '1.1.2',
            'title' => 'Indikator Lain',
            'sort_order' => 2,
        ]);
        $foreignIndicatorEvidence = $this->imageEvidence($project, $sameProjectOtherIndicator, $auditor);

        foreach ([$foreignProjectEvidence, $foreignIndicatorEvidence] as $evidence) {
            $this->actingAs($auditor)
                ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                    'lock_version' => 0,
                    'result_content' => json_encode(['ops' => [
                        ['insert' => "Teks valid.\n"],
                        ['insert' => ['auditEvidence' => ['evidence_id' => $evidence->id]]],
                    ]]),
                ])
                ->assertSessionHasErrors('result_content');
        }
    }

    public function test_non_pic_and_non_image_evidence_cannot_be_attached(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        $other = User::factory()->auditor()->create();
        $pdfEvidence = $this->pdfEvidence($project, $indicator, $auditor);

        $payload = [
            'lock_version' => 0,
            'result_content' => json_encode(['ops' => [
                ['insert' => "Teks valid.\n"],
                ['insert' => ['auditEvidence' => ['evidence_id' => $pdfEvidence->id]]],
            ]]),
        ];

        $this->actingAs($other)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), $payload)
            ->assertForbidden();

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), $payload)
            ->assertSessionHasErrors('result_content');
    }

    public function test_removed_evidence_reference_only_changes_document_not_evidence_record(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        $evidence = $this->imageEvidence($project, $indicator, $auditor);

        app(AuditResultService::class)->saveDocumentDraft($project, $indicator, $auditor, [
            'ops' => [
                ['insert' => "Sebelum gambar.\n"],
                ['insert' => ['auditEvidence' => ['evidence_id' => $evidence->id]]],
                ['insert' => "\n"],
            ],
        ], 0);

        $this->actingAs($auditor)
            ->put(route('audit-projects.work.save-draft', [$project, $indicator]), [
                'lock_version' => 1,
                'result_content' => json_encode(['ops' => [
                    ['insert' => "Sebelum gambar.\n"],
                ]]),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_indicator_evidences', [
            'id' => $evidence->id,
            'lifecycle_status' => AuditEvidenceLifecycleStatus::Active->value,
        ]);
        $this->assertSame("Sebelum gambar.\n", $indicator->fresh()->result->result_content['ops'][0]['insert']);
    }

    public function test_version_history_stores_document_with_evidence_reference(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        $evidence = $this->imageEvidence($project, $indicator, $auditor);

        app(AuditResultService::class)->saveDocumentDraft($project, $indicator, $auditor, [
            'ops' => [
                ['insert' => "Versi dengan gambar.\n"],
                ['insert' => ['auditEvidence' => ['evidence_id' => $evidence->id]]],
                ['insert' => "\n"],
            ],
        ], 0);

        $version = $indicator->fresh()->result->versions()->firstOrFail();

        $this->assertSame(['auditEvidence' => ['evidence_id' => $evidence->id]], $version->result_content['ops'][1]['insert']);
    }

    public function test_pdf_upload_for_editor_image_is_rejected_with_clear_message(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();
        Storage::fake('audit_evidence');

        $this->actingAs($auditor)
            ->postJson(route('audit-projects.evidences.editor-images.store', [$project, $indicator]), [
                'evidence' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evidence')
            ->assertSee('Untuk saat ini, hanya file gambar yang dapat disisipkan langsung ke Hasil Audit.');
    }

    public function test_version_history_stores_document_snapshot(): void
    {
        [, , $auditor, $project, $indicator] = $this->assignedIndicator();

        app(AuditResultService::class)->saveDocumentDraft($project, $indicator, $auditor, $this->document([
            ['type' => 'paragraph', 'text' => 'Versi dokumen.'],
        ]), 0);

        $version = $indicator->fresh()->result->versions()->firstOrFail();

        $this->assertSame('Versi dokumen.', $version->result_text);
        $this->assertArrayHasKey('ops', $version->result_content);
    }

    private function imageEvidence(AuditProject $project, AuditProjectIndicator $indicator, User $uploader): AuditIndicatorEvidence
    {
        return AuditIndicatorEvidence::create([
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'original_name' => 'struktur.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 10,
            'sha256' => str_repeat('b', 64),
            'storage_disk' => 'audit_evidence',
            'storage_path' => 'project-'.$project->id.'/indicator-'.$indicator->id.'/struktur.png',
            'uploaded_by' => $uploader->id,
            'caption' => 'Struktur Organisasi Perusahaan',
            'sort_order' => 10,
            'lifecycle_status' => AuditEvidenceLifecycleStatus::Active,
        ]);
    }

    private function pdfEvidence(AuditProject $project, AuditProjectIndicator $indicator, User $uploader): AuditIndicatorEvidence
    {
        return AuditIndicatorEvidence::create([
            'audit_project_id' => $project->id,
            'audit_project_indicator_id' => $indicator->id,
            'original_name' => 'dokumen.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('c', 64),
            'storage_disk' => 'audit_evidence',
            'storage_path' => 'project-'.$project->id.'/indicator-'.$indicator->id.'/dokumen.pdf',
            'uploaded_by' => $uploader->id,
            'sort_order' => 10,
            'lifecycle_status' => AuditEvidenceLifecycleStatus::Active,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $content
     * @return array<string, mixed>
     */
    private function document(array $content): array
    {
        $ops = [];

        foreach ($content as $block) {
            $attributes = [];
            foreach (($block['marks'] ?? []) as $mark) {
                $attributes[$mark] = true;
            }

            $textOp = ['insert' => $block['text']];
            if ($attributes !== []) {
                $textOp['attributes'] = $attributes;
            }
            $ops[] = $textOp;

            $lineAttributes = [];
            if (($block['type'] ?? 'paragraph') === 'list_item') {
                $lineAttributes['list'] = $block['list_type'] ?? 'ordered';
                if ((int) ($block['level'] ?? 1) > 1) {
                    $lineAttributes['indent'] = (int) $block['level'] - 1;
                }
            }
            if (($block['align'] ?? null) === 'justify') {
                $lineAttributes['align'] = 'justify';
            }

            $lineOp = ['insert' => "\n"];
            if ($lineAttributes !== []) {
                $lineOp['attributes'] = $lineAttributes;
            }
            $ops[] = $lineOp;
        }

        return ['ops' => $ops];
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
        $name = 'Audit Document '.fake()->unique()->numerify('####');

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
