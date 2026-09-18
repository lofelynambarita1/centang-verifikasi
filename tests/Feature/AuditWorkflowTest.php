<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_creation_copies_the_iso_checklist_into_the_new_audit(): void
    {
        $reference = Company::factory()->create()->audits()->create(['title' => 'Checklist acuan']);
        $reference->items()->createMany([
            ['title' => '1.1.1 Izin lokasi dan KKPR', 'description' => 'Periksa dokumen izin.', 'sort_order' => 0],
            ['title' => '2.1.1 Rencana strategis perkebunan', 'sort_order' => 1],
            ['title' => '3.1.1 Izin lingkungan', 'sort_order' => 2],
        ]);

        $response = $this->post(route('companies.store'), [
            'name' => 'PT Test Verifikasi',
            'industry' => 'Manufaktur',
            'due_date' => '2026-10-01',
        ]);

        $company = Company::where('name', 'PT Test Verifikasi')->firstOrFail();
        $audit = $company->audits()->firstOrFail();

        $response->assertRedirect(route('audits.show', $audit));
        $this->assertDatabaseHas('audits', ['company_id' => $company->id, 'status' => 'pending']);
        $this->assertSame(
            ['1.1.1 Izin lokasi dan KKPR', '2.1.1 Rencana strategis perkebunan', '3.1.1 Izin lingkungan'],
            $audit->items()->orderBy('sort_order')->pluck('title')->all(),
        );
        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('Prinsip verifikasi')
            ->assertSee('Prinsip 2');
    }

    public function test_sync_command_unifies_legacy_audits_with_the_reference_checklist(): void
    {
        $reference = Company::factory()->create()->audits()->create(['title' => 'Checklist acuan']);
        $reference->items()->createMany([
            ['title' => '1.1.1 Izin lokasi dan KKPR', 'sort_order' => 0],
            ['title' => '2.1.1 Rencana strategis perkebunan', 'sort_order' => 1],
            ['title' => '3.1.1 Izin lingkungan', 'sort_order' => 2],
        ]);

        $legacyCompany = Company::factory()->create();
        $legacy = $legacyCompany->audits()->create(['title' => 'Audit lama', 'status' => 'completed']);
        $legacy->items()->create(['title' => 'Legalitas perusahaan', 'sort_order' => 0]);

        $this->artisan('audit:sync-checklist')->assertSuccessful();

        $this->assertSame('in_progress', $legacy->fresh()->status);
        $this->assertSame(
            ['1.1.1 Izin lokasi dan KKPR', '2.1.1 Rencana strategis perkebunan', '3.1.1 Izin lingkungan'],
            $legacy->items()->orderBy('sort_order')->pluck('title')->all(),
        );
        $this->assertSame(3, $reference->items()->count());
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
}
