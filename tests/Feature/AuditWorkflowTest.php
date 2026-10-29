<?php

namespace Tests\Feature;

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

    public function test_a_company_creation_copies_the_iso_checklist_into_the_new_audit(): void
    {
        $canonical = IspoChecklist::items();

        $response = $this->post(route('companies.store'), [
            'name' => 'PT Test Verifikasi',
            'industry' => 'Manufaktur',
            'address' => 'Jl. Contoh No. 1',
            'land_area' => '1.250 ha',
            'contact_name' => 'Dina PIC',
            'contact_email' => 'dina@example.com',
            'auditor_name' => 'Auditor Satu, Auditor Dua',
            'due_date' => '2026-10-01',
            'report_summary_paragraph_one' => 'Ringkasan kustom paragraf pertama.',
            'report_summary_paragraph_two' => 'Ringkasan kustom paragraf kedua.',
        ]);

        $company = Company::where('name', 'PT Test Verifikasi')->firstOrFail();
        $audit = $company->audits()->firstOrFail();

        $response->assertRedirect(route('audits.show', $audit));
        $this->assertDatabaseHas('audits', ['company_id' => $company->id, 'status' => 'pending']);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'industry' => 'Manufaktur',
            'address' => 'Jl. Contoh No. 1',
            'land_area' => '1.250 ha',
            'contact_name' => 'Dina PIC',
            'contact_email' => 'dina@example.com',
        ]);
        $this->assertDatabaseHas('audits', [
            'id' => $audit->id,
            'auditor_name' => 'Auditor Satu, Auditor Dua',
            'due_date' => '2026-10-01 00:00:00',
            'report_summary_paragraph_one' => 'Ringkasan kustom paragraf pertama.',
            'report_summary_paragraph_two' => 'Ringkasan kustom paragraf kedua.',
        ]);
        $this->assertSame(count($canonical), $audit->items()->count());
        $this->assertSame(
            $canonical[0]['title'],
            $audit->items()->orderBy('sort_order')->value('title'),
        );
        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('Prinsip verifikasi')
            ->assertSee('Prinsip 2');
    }

    public function test_company_edit_updates_company_details_and_latest_audit_information(): void
    {
        $company = Company::factory()->create();
        $olderAudit = $company->audits()->create([
            'title' => 'Audit lama',
            'auditor_name' => 'Auditor Lama',
            'due_date' => '2026-09-01',
        ]);
        $latestAudit = $company->audits()->create([
            'title' => 'Audit terbaru',
            'auditor_name' => 'Auditor Sebelumnya',
            'due_date' => '2026-10-01',
            'report_summary_paragraph_one' => 'Isi paragraf lama satu.',
            'report_summary_paragraph_two' => 'Isi paragraf lama dua.',
        ]);

        $this->get(route('companies.edit', $company))
            ->assertSee('>Auditor Sebelumnya</textarea>', false)
            ->assertSee('value="2026-10-01"', false)
            ->assertSee('>Isi paragraf lama satu.</textarea>', false)
            ->assertSee('>Isi paragraf lama dua.</textarea>', false);

        $response = $this->put(route('companies.update', $company), [
            'name' => 'PT Perusahaan Lengkap',
            'industry' => 'Perkebunan',
            'address' => 'Alamat terbaru',
            'land_area' => '2.500 ha',
            'contact_name' => 'PIC Terbaru',
            'contact_email' => 'pic@example.com',
            'auditor_name' => "Auditor Satu\nAuditor Dua",
            'due_date' => '2026-11-15',
            'report_summary_paragraph_one' => 'Penilaian khusus untuk PT Perusahaan Lengkap.',
            'report_summary_paragraph_two' => "Ringkasan hasil\nberlanjut di baris kedua.",
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'PT Perusahaan Lengkap',
            'industry' => 'Perkebunan',
            'address' => 'Alamat terbaru',
            'land_area' => '2.500 ha',
            'contact_name' => 'PIC Terbaru',
            'contact_email' => 'pic@example.com',
        ]);
        $this->assertDatabaseHas('audits', [
            'id' => $latestAudit->id,
            'auditor_name' => "Auditor Satu\nAuditor Dua",
            'due_date' => '2026-11-15 00:00:00',
            'report_summary_paragraph_one' => 'Penilaian khusus untuk PT Perusahaan Lengkap.',
            'report_summary_paragraph_two' => "Ringkasan hasil\nberlanjut di baris kedua.",
        ]);
        $this->assertDatabaseHas('audits', [
            'id' => $olderAudit->id,
            'auditor_name' => 'Auditor Lama',
            'due_date' => '2026-09-01 00:00:00',
        ]);

        $this->get(route('audits.report', $latestAudit))
            ->assertSee('2.500 ha')
            ->assertSee('Auditor Satu')
            ->assertSee('Auditor Dua')
            ->assertSee('Penilaian khusus untuk PT Perusahaan Lengkap.')
            ->assertSee('Ringkasan hasil')
            ->assertSee('berlanjut di baris kedua.');
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

    public function test_audit_status_selector_offers_only_three_review_statuses(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit status']);
        $item = $audit->items()->create(['title' => 'Indikator status', 'sort_order' => 1]);

        $this->get(route('audits.show', $audit))
            ->assertSee('id="status-'.$item->id.'" name="status" data-audit-status required', false)
            ->assertDontSee('<option value="pending"', false)
            ->assertSee('<option value="compliant"', false)
            ->assertSee('<option value="non_compliant"', false)
            ->assertSee('<option value="not_applicable"', false)
            ->assertSee('<option value="" selected disabled hidden></option>', false);
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
    }

    public function test_audit_pdf_uses_the_original_letterhead_and_auditee_format(): void
    {
        $company = Company::factory()->create(['name' => 'PT Template Verifikasi']);
        $audit = $company->audits()->create(['title' => 'Audit template']);
        $audit->items()->create([
            'title' => '1.1.1 Izin lokasi',
            'description' => 'Dokumen izin lokasi tersedia.',
            'sort_order' => 1,
        ]);
        $audit->load(['company', 'items.hasils', 'findings']);
        $items = $audit->items;

        $html = view('audits.pdf', [
            'audit' => $audit,
            'items' => $items,
            'isIspoChecklist' => false,
            'principles' => collect(),
        ])->render();

        $this->assertStringContainsString('class="gis-letterhead" src="data:image/jpeg;base64,', $html);
        $this->assertStringContainsString('FrO. 8603/GIS-1.1', $html);
        $this->assertStringContainsString('(PT TEMPLATE VERIFIKASI)', $html);
        $this->assertStringNotContainsString('(AUDITEE / PT TEMPLATE VERIFIKASI)', $html);
        $this->assertStringContainsString('top: 0;', $html);
        $this->assertStringContainsString('width: 92%;', $html);
        $this->assertStringContainsString('<col style="width: 68pt">', $html);
        $this->assertStringContainsString('text-align: left;', $html);
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
        $item = $audit->items()->create(['title' => '1.1.1 Izin lokasi', 'sort_order' => 1]);
        $hasil = $item->hasils()->create(['content' => 'Awal', 'sort_order' => 0]);

        $this->put(route('audit-hasils.update', $hasil), ['content' => 'Diperbarui'])
            ->assertStatus(302);
        $this->assertDatabaseHas('audit_hasils', ['id' => $hasil->id, 'content' => 'Diperbarui']);

        $this->delete(route('audit-hasils.destroy', $hasil))->assertStatus(302);
        $this->assertDatabaseMissing('audit_hasils', ['id' => $hasil->id]);
        $this->assertSame('in_progress', $audit->fresh()->status);
    }

    public function test_non_compliant_audit_results_are_highlighted_and_can_be_edited(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit temuan']);
        $item = $audit->items()->create([
            'title' => '1.1.1 Izin lokasi',
            'description' => 'Dokumen izin wajib tersedia.',
            'sort_order' => 1,
            'status' => 'non_compliant',
        ]);
        $hasil = $item->hasils()->create([
            'content' => 'Dokumen izin belum tersedia.',
            'evidence_path' => 'audit-evidence/izin.jpg',
            'sort_order' => 0,
        ]);

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('checklist-item--non-compliant', false)
            ->assertSee('parameter-box--non-compliant', false)
            ->assertSee('hasil-block--non-compliant', false)
            ->assertSee('Tidak memenuhi · Perlu tindak lanjut.', false)
            ->assertSee('note-box--non-compliant', false)
            ->assertSee('Catatan hasil audit')
            ->assertSee('Ganti bukti')
            ->assertSee('type="file" name="evidence"', false)
            ->assertSee('Lihat bukti tersimpan')
            ->assertSee('Simpan perubahan')
            ->assertSee('Hapus hasil audit')
            ->assertSee('Dokumen izin belum tersedia.');

        $this->put(route('audit-hasils.update', $hasil), ['content' => 'Izin lokasi sedang diperbaiki.'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_hasils', [
            'id' => $hasil->id,
            'content' => 'Izin lokasi sedang diperbaiki.',
        ]);
    }

    public function test_non_compliant_status_highlights_results_and_compliant_status_clears_it(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit perubahan status']);
        $item = $audit->items()->create([
            'title' => '1.1.1 Izin lokasi',
            'sort_order' => 1,
            'status' => 'compliant',
        ]);
        $item->hasils()->create(['content' => 'Catatan dapat diperbarui.', 'sort_order' => 0]);

        $this->put(route('audit-items.update', $item), ['status' => 'non_compliant'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_items', ['id' => $item->id, 'status' => 'non_compliant']);
        $this->get(route('audits.show', $audit))
            ->assertSee('checklist-item--non-compliant', false)
            ->assertSee('class="tab active tab--non-compliant"', false)
            ->assertSee('terdapat indikator tidak memenuhi')
            ->assertSee('Tidak memenuhi · Perlu tindak lanjut.')
            ->assertSee('status-form--non-compliant', false);

        $this->put(route('audit-items.update', $item), ['status' => 'compliant'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_items', ['id' => $item->id, 'status' => 'compliant']);
        $this->get(route('audits.show', $audit))
            ->assertDontSee('checklist-item--non-compliant', false)
            ->assertDontSee('tab--non-compliant', false)
            ->assertDontSee('status-form--non-compliant', false);
    }

    public function test_non_compliant_indicator_marks_only_its_principle_tab(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit tab prinsip']);
        $audit->items()->create([
            'title' => '1.1.1 Izin lokasi',
            'sort_order' => 1,
            'status' => 'compliant',
        ]);
        $audit->items()->create([
            'title' => '2.1.1 Rencana strategis',
            'sort_order' => 2,
            'status' => 'non_compliant',
        ]);

        $response = $this->get(route('audits.show', $audit));

        $response
            ->assertOk()
            ->assertSee('aria-label="Prinsip 2 · 1 indikator, terdapat indikator tidak memenuhi"', false)
            ->assertSee('tab--non-compliant', false);
        $this->assertSame(1, substr_count((string) $response->getContent(), 'tab--non-compliant'));
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
}
