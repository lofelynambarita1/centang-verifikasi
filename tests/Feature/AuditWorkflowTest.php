<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Support\IspoChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_creation_copies_the_iso_checklist_into_the_new_audit(): void
    {
        $canonical = IspoChecklist::items();

        $response = $this->post(route('companies.store'), [
            'name' => 'PT Test Verifikasi',
            'industry' => 'Manufaktur',
            'due_date' => '2026-10-01',
        ]);

        $company = Company::where('name', 'PT Test Verifikasi')->firstOrFail();
        $audit = $company->audits()->firstOrFail();

        $response->assertRedirect(route('audits.show', $audit));
        $this->assertDatabaseHas('audits', ['company_id' => $company->id, 'status' => 'pending']);
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

    public function test_report_shows_every_hasil_audit_entry_and_evidence(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit laporan hasil']);
        $item = $audit->items()->create(['title' => '1.1.1 Izin lokasi', 'description' => 'Parameter satu', 'sort_order' => 1]);
        $item->hasils()->create([
            'content' => 'Hasil audit lapangan lengkap.',
            'evidence_path' => 'audit-evidence/bukti-1.jpg',
            'sort_order' => 0,
        ]);

        $this->get(route('audits.report', $audit))
            ->assertOk()
            ->assertSee('Hasil audit lapangan lengkap.')
            ->assertSee('bukti-1.jpg');
    }
}
