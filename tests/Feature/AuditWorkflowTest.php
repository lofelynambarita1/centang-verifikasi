<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\User;
use App\Support\IspoChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_company_creation_stores_company_master_data_and_redirects_to_companies_index(): void
    {
        $response = $this->post(route('companies.store'), [
            'name' => 'PT Test Verifikasi',
            'industry' => 'Manufaktur',
            'address' => 'Jl. Kebun Raya No. 10',
            'contact_name' => 'Budi Santoso',
            'contact_email' => 'budi@kebun.com',
        ]);

        $response->assertRedirect(route('companies.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Test Verifikasi',
            'industry' => 'Manufaktur',
            'address' => 'Jl. Kebun Raya No. 10',
            'contact_name' => 'Budi Santoso',
            'contact_email' => 'budi@kebun.com',
        ]);

        $company = Company::where('name', 'PT Test Verifikasi')->firstOrFail();
        $this->assertSame(0, $company->audits()->count());
    }

    public function test_sync_command_unifies_legacy_audits_with_the_reference_checklist(): void
    {
        $canonical = IspoChecklist::items();

        $legacyCompany = Company::factory()->create();
        $legacy = $legacyCompany->audits()->create(['title' => 'Audit lama', 'status' => 'completed']);
        $legacy->items()->create(['title' => 'Legalitas perusahaan', 'sort_order' => 0]);

        $this->artisan('audit:sync-checklist')->assertSuccessful();

        $this->assertSame('in_progress', $legacy->fresh()->status);
        $this->assertSame(count($canonical), $legacy->items()->count());
        $this->assertSame(
            $canonical[0]['title'],
            $legacy->items()->orderBy('sort_order')->value('title'),
        );
    }

    public function test_audit_cannot_be_completed_until_every_item_is_filled(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit test']);
        $audit->items()->create(['title' => 'Item wajib', 'sort_order' => 0]);

        $response = $this->post(route('audits.complete', $audit));

        $response->assertStatus(422);
        $this->assertDatabaseHas('audits', ['id' => $audit->id, 'status' => 'pending']);
    }

    public function test_audit_principles_are_paginated_after_ten_items(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit pagination']);

        foreach (range(1, 12) as $sortOrder) {
            $audit->items()->create([
                'title' => "Prinsip {$sortOrder}",
                'sort_order' => $sortOrder,
            ]);
        }

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('Halaman 1 dari 2');

        $this->get(route('audits.show', ['audit' => $audit, 'page' => 2]))
            ->assertOk()
            ->assertSee('Halaman 2 dari 2')
            ->assertSee('Prinsip 12');
    }

    public function test_audit_report_renders_all_checklist_items(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit report']);
        $audit->items()->createMany([
            ['title' => 'Indikator 1', 'description' => 'Parameter satu', 'sort_order' => 1],
            ['title' => 'Indikator 7', 'description' => 'Parameter tujuh', 'sort_order' => 7],
        ]);

        $this->get(route('audits.report', $audit))
            ->assertSee('LAPORAN VERIFIKASI HASIL ISPO')
            ->assertSee('Indikator 1')
            ->assertSee('Indikator 7')
            ->assertSee('Parameter tujuh');
    }

    public function test_audit_pdf_download_returns_a_pdf_file(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit pdf']);
        $audit->items()->createMany([
            ['title' => '1.1.1 Izin lokasi dan KKPR', 'sort_order' => 1, 'status' => 'compliant', 'note' => 'Dokumen lengkap.'],
            ['title' => '2.1.1 Rencana strategis perkebunan', 'sort_order' => 2, 'status' => 'non_compliant'],
        ]);

        $response = $this->get(route('audits.pdf', $audit));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('laporan-verifikasi-ispo-', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_audit_pdf_download_handles_the_full_ispo_checklist(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit checklist ISPO lengkap']);
        $items = collect(IspoChecklist::items())
            ->map(fn (array $item, int $sortOrder): array => $item + [
                'sort_order' => $sortOrder,
                'status' => 'pending',
            ])
            ->all();
        $audit->items()->createMany($items);

        $response = $this->get(route('audits.pdf', $audit));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('laporan-verifikasi-ispo-', (string) $response->headers->get('content-disposition'));
    }

    public function test_audit_pdf_ignores_overly_large_image_evidence_to_avoid_timeout(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit pdf large evidence']);
        $item = $audit->items()->create([
            'title' => '1.1.1 Izin lokasi dan KKPR',
            'sort_order' => 1,
            'status' => 'compliant',
            'note' => 'Dokumen lengkap.',
        ]);

        $file = UploadedFile::fake()->image('bukti-large.jpg', 2000, 2000);
        $path = $file->store('audit-evidence', 'public');
        $fullPath = Storage::disk('public')->path($path);
        file_put_contents($fullPath, str_repeat('A', 3 * 1024 * 1024));

        $item->hasils()->create([
            'content' => 'Bukti foto terlampir.',
            'evidence_path' => $path,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('audits.pdf', $audit));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringNotContainsString('data:image', (string) $response->getContent());
    }

    public function test_audit_download_returns_one_docx_file_for_all_principles(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit download']);
        $audit->items()->createMany([
            ['title' => 'Prinsip satu', 'sort_order' => 1, 'status' => 'compliant'],
            ['title' => 'Prinsip dua', 'sort_order' => 2, 'status' => 'compliant'],
        ]);

        $response = $this->get(route('audits.download', $audit));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $response->headers->get('content-type'),
        );
        $this->assertStringContainsString('laporan-audit-'.$audit->id.'.docx', (string) $response->headers->get('content-disposition'));
    }

    public function test_partial_principle_is_saved_automatically_as_a_draft(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit draft']);
        $lead = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $this->actingAs($lead);

        $first = $audit->items()->create(['title' => '1.1.1 Izin lokasi', 'sort_order' => 1]);
        $second = $audit->items()->create(['title' => '2.1.1 Rencana strategis', 'sort_order' => 2]);

        $this->put(route('audit-items.update', $first), ['status' => 'compliant'])->assertStatus(302);

        $this->assertSame('in_progress', $audit->fresh()->status);
        $this->assertDatabaseHas('audit_items', ['id' => $second->id, 'status' => 'pending']);
    }

    public function test_store_hasil_audit_adds_a_result_with_evidence_and_keeps_the_draft(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit hasil']);
        $lead = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $this->actingAs($lead);

        $item = $audit->items()->create(['title' => '1.1.1 Izin lokasi', 'sort_order' => 1]);

        $this->post(route('audit-items.hasil.store', $item), [
            'content' => 'Dokumen izin lengkap dan sesuai.',
            'evidence' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertStatus(302);

        $hasil = $item->hasils()->firstOrFail();

        $this->assertSame('Dokumen izin lengkap dan sesuai.', $hasil->content);
        Storage::disk('public')->assertExists($hasil->evidence_path);
        $this->assertSame(1, $item->hasils()->count());
        $this->assertSame('in_progress', $audit->fresh()->status);
    }

    public function test_update_and_delete_hasil_audit(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit hasil lanjutan']);
        $lead = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $this->actingAs($lead);

        $item = $audit->items()->create(['title' => '1.1.1 Izin lokasi', 'sort_order' => 1]);
        $hasil = $item->hasils()->create(['content' => 'Awal', 'sort_order' => 0]);

        $this->put(route('audit-hasils.update', $hasil), ['content' => 'Diperbarui'])
            ->assertStatus(302);
        $this->assertDatabaseHas('audit_hasils', ['id' => $hasil->id, 'content' => 'Diperbarui']);

        $this->delete(route('audit-hasils.destroy', $hasil))->assertStatus(302);
        $this->assertDatabaseMissing('audit_hasils', ['id' => $hasil->id]);
        $this->assertSame('in_progress', $audit->fresh()->status);
    }

    public function test_report_shows_every_hasil_audit_entry_and_evidence(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit laporan hasil']);
        $item = $audit->items()->create(['title' => '1.1.1 Izin lokasi', 'description' => 'Parameter satu', 'sort_order' => 1]);
        $item->hasils()->create([
            'content' => 'Hasil audit lapangan lengkap.',
            'evidence_path' => 'audit-evidence/bukti-1.jpg',
            'sort_order' => 0,
        ]);

        Storage::disk('public')->put('audit-evidence/bukti-1.jpg', 'fake-image-content');

        $this->get(route('audits.report', $audit))
            ->assertOk()
            ->assertSee('Hasil audit lapangan lengkap.')
            ->assertSee('src="'.Storage::disk('public')->url('audit-evidence/bukti-1.jpg').'"', false);
    }

    public function test_report_contains_audit_identity_team_assignments_and_findings(): void
    {
        $company = Company::factory()->create([
            'name' => 'PT Sawit Contoh',
            'industry' => 'Perkebunan Kelapa Sawit',
            'address' => 'Jalan Kebun No. 7',
            'contact_name' => 'PIC Perusahaan',
        ]);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Lead Audit']);
        $auditor = User::factory()->create(['role' => 'auditor', 'name' => 'Auditor Prinsip Satu']);
        $audit = $company->audits()->create([
            'title' => 'Surveillance 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-07',
            'scope' => 'Standar ISPO - Kebun & PKS',
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => auth()->id(),
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $auditor->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => auth()->id(),
        ]);
        $audit->assignees()->attach($auditor->id, ['principle_number' => 1]);
        $auditItem = $audit->items()->create([
            'title' => '1.1.1 Legalitas kebun',
            'sort_order' => 1,
            'status' => 'non_compliant',
            'note' => 'Dokumen perlu diperbarui.',
        ]);
        $auditItem->hasils()->create([
            'content' => 'Bukti hasil verifikasi.',
            'sort_order' => 0,
        ]);
        $audit->findings()->create([
            'note' => 'Temuan utama audit',
            'recommendation' => 'Lengkapi dokumen pendukung.',
        ]);

        $this->actingAs($auditor)
            ->get(route('audits.report', $audit))
            ->assertOk()
            ->assertSee('Unduh Laporan PDF')
            ->assertSee('PT Sawit Contoh')
            ->assertSee('Perkebunan Kelapa Sawit')
            ->assertSee('Jalan Kebun No. 7')
            ->assertSee('PIC Perusahaan')
            ->assertSee('Surveillance 1')
            ->assertSee('01 Januari 2026')
            ->assertSee('07 Januari 2026')
            ->assertSee('Standar ISPO - Kebun & PKS')
            ->assertSee('Lead Audit')
            ->assertSee('Auditor Prinsip Satu')
            ->assertSee('Bukti hasil verifikasi.')
            ->assertSee('Temuan utama audit')
            ->assertSee('Dokumen perlu diperbarui.');
    }

    public function test_unassigned_auditor_cannot_view_another_projects_report_or_pdf(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit privat']);
        $audit->items()->create([
            'title' => '1.1.1 Indikator',
            'sort_order' => 1,
            'status' => 'compliant',
        ]);
        $outsider = User::factory()->create(['role' => 'auditor']);

        $this->actingAs($outsider)
            ->get(route('audits.report', $audit))
            ->assertForbidden();

        $this->get(route('audits.pdf', $audit))
            ->assertForbidden();

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertDontSee(route('audits.pdf', $audit), false);
    }
}
