<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_creation_creates_an_audit_checklist(): void
    {
        $response = $this->post(route('companies.store'), [
            'name' => 'PT Test Verifikasi',
            'industry' => 'Manufaktur',
            'due_date' => '2026-10-01',
        ]);

        $company = Company::query()->firstOrFail();
        $this->assertDatabaseHas('audits', ['company_id' => $company->id, 'status' => 'pending']);
        $this->assertDatabaseCount('audit_items', 4);
        $audit = $company->audits()->firstOrFail();
        $response->assertRedirect(route('audits.show', $audit));
        $this->get(route('audits.show', $audit))->assertOk()->assertSee('Prinsip verifikasi');
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
