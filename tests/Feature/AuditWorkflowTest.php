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
            ->assertSee('Parameter tujuh')
            ->assertSee('class="report-letterhead-image"', false)
            ->assertSee('data:image/jpeg;base64,', false)
            ->assertDontSee('ispo-report-ending')
            ->assertDontSee('Dokumen ini dibuat dari data verifikasi audit ISPO yang tersimpan dalam sistem.')
            ->assertDontSee('Status audit:');
    }

    public function test_audit_report_and_show_display_only_the_five_standard_identity_rows(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create([
            'title' => 'Judul audit tambahan',
            'scope' => 'Ruang lingkup tambahan',
            'auditor_name' => 'Auditor Utama',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-07',
        ]);
        $labels = [
            'Nama Perusahaan',
            'Alamat Perusahaan',
            'Luasan Lahan',
            'Auditor',
            'Tanggal Audit',
        ];

        $reportContent = $this->get(route('audits.report', $audit))
            ->assertOk()
            ->getContent();
        preg_match_all('/<div><span>(.*?)<\/span><b>:/s', $reportContent, $reportLabels);

        $this->assertSame($labels, array_map(
            static fn (string $label): string => trim(strip_tags($label)),
            $reportLabels[1],
        ));

        $showContent = $this->get(route('audits.show', $audit))
            ->assertOk()
            ->getContent();
        preg_match_all('/<div class="meta-row"><span>(.*?)<\/span>/s', $showContent, $showLabels);

        $this->assertSame($labels, array_map(
            static fn (string $label): string => trim(strip_tags($label)),
            $showLabels[1],
        ));
        $this->assertStringContainsString('01 Januari 2026 – 07 Januari 2026', $reportContent);
        $this->assertStringContainsString('01 Januari 2026 – 07 Januari 2026', $showContent);
    }

    public function test_pdf_facts_table_contains_only_the_five_standard_rows(): void
    {
        $company = Company::factory()->create([
            'name' => 'Perusahaan Contoh',
            'address' => 'Jl. Contoh No. 1',
        ]);
        $audit = $company->audits()->create([
            'title' => 'Audit pdf facts',
            'auditor_name' => 'Auditor Contoh',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-07',
        ]);

        $html = view('audits.pdf', [
            'audit' => $audit->load('company'),
            'items' => $audit->items,
            'isIspoChecklist' => false,
            'principles' => collect(),
        ])->render();

        $this->assertStringContainsString(
            "body { font-family: Arial, 'DejaVu Sans', sans-serif; font-size: 10pt; color: #000; line-height: 1.5; margin: 0; }",
            $html,
        );
        $this->assertStringContainsString(
            'body, body * { color: #000000 !important; border-color: #000000 !important; }',
            $html,
        );
        $this->assertStringContainsString(
            'h1.title { text-align: center; font-size: 13pt; font-weight: bold;',
            $html,
        );
        $this->assertStringContainsString(
            'h2.section { font-size: 12pt; font-weight: bold; text-transform: uppercase; color: #000000; margin: 18px 0 8px; }',
            $html,
        );
        $this->assertStringContainsString(
            '.section-summary-heading { font-size: 10.5pt; font-weight: bold; margin: 0 0 6px; }',
            $html,
        );
        $this->assertStringContainsString('p { margin: 0 0 8px; text-align: justify; }', $html);
        $this->assertStringContainsString(
            '<p class="section-summary-heading">RINGKASAN PENILAIAN</p>',
            $html,
        );
        $this->assertStringContainsString(
            '.auditee { text-align: center; font-size: 11pt; font-weight: bold;',
            $html,
        );
        $this->assertStringContainsString(
            'table.facts { width: 100%; border: none; border-collapse: collapse; margin-bottom: 18px; font-size: 10pt; font-weight: normal;',
            $html,
        );
        $this->assertStringContainsString('size: letter portrait;', $html);
        $this->assertStringContainsString('margin-top: 2.54cm;', $html);
        $this->assertStringContainsString('margin-bottom: 2.54cm;', $html);
        $this->assertStringContainsString('margin-left: 2.54cm;', $html);
        $this->assertStringContainsString('margin-right: 2.54cm;', $html);
        $this->assertStringContainsString(
            ".footer { position: fixed; bottom: -1.29cm; left: 0; right: 0; height: 1cm; font-family: Arial, 'DejaVu Sans', sans-serif; text-align: right; font-size: 8.5pt; color: #000000; }",
            $html,
        );
        $this->assertStringContainsString(
            '<div class="footer">FrO. 8603/GIS-1.1</div>',
            $html,
        );
        $this->assertStringContainsString(
            '.letterhead { width: 100%; margin-top: 0; margin-bottom: 20px; text-align: center; }',
            $html,
        );
        $this->assertStringContainsString('.letterhead td { vertical-align: top; }', $html);
        $this->assertStringContainsString('.results-letterhead td { vertical-align: top; }', $html);
        $this->assertStringContainsString(
            '.letterhead-image { display: block; width: 92%; max-width: 95%; height: auto; margin: 0 auto; }',
            $html,
        );
        $this->assertStringNotContainsString('max-height: 75px', $html);
        $this->assertStringNotContainsString('border-bottom: 3px solid #1b2450', $html);
        $this->assertStringNotContainsString('border-bottom: 2px solid #1b2450', $html);
        $this->assertStringContainsString(
            '.auditee { text-align: center; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0 0 16px; }',
            $html,
        );
        $this->assertStringContainsString('.facts-wrapper { padding-left: 24px; }', $html);
        $this->assertStringContainsString(
            'table.facts { width: 100%; border: none; border-collapse: collapse;',
            $html,
        );
        $this->assertStringContainsString(
            'table.facts td { padding: 2px 0; vertical-align: top; text-align: left; }',
            $html,
        );
        $this->assertStringContainsString('table.facts td.k { width: 160px; font-weight: normal; }', $html);
        $this->assertStringContainsString('table.facts td.s { width: 15px; text-align: left; }', $html);
        $this->assertStringContainsString('.results-page { page-break-before: always; }', $html);
        $this->assertStringContainsString(
            'h2.results-title { font-size: 11pt; font-weight: bold; text-transform: uppercase; text-align: left; margin: 0 0 8px; }',
            $html,
        );
        $this->assertStringContainsString(
            'table.assessment-table { width: 100%; border-collapse: collapse; border: 1px solid #000000; font-size: 9.5pt; color: #000000; }',
            $html,
        );
        $this->assertStringContainsString(
            'table.assessment-table tr { page-break-inside: avoid; }',
            $html,
        );

        preg_match('/<table class="facts">(.*?)<\/table>/s', $html, $factsTable);
        $this->assertArrayHasKey(1, $factsTable);

        preg_match_all('/<td class="k">(.*?)<\/td>/s', $factsTable[1], $labels);

        $this->assertSame([
            'Nama Perusahaan',
            'Alamat Perusahaan',
            'Luasan Lahan',
            'Auditor',
            'Tanggal Audit',
        ], array_map(static fn (string $label): string => trim(strip_tags($label)), $labels[1]));
        $this->assertStringContainsString('>(PERUSAHAAN CONTOH)</div>', $html);
        $this->assertStringContainsString('Jl. Contoh No. 1', $factsTable[1]);
        $this->assertStringContainsString('Auditor Contoh', $factsTable[1]);
        $this->assertStringContainsString('01 Januari 2026 – 07 Januari 2026', $factsTable[1]);
        $this->assertStringNotContainsString('class="signatures"', $html);
        $this->assertStringNotContainsString('(Nama Pimpinan Perusahaan)', $html);
        $this->assertStringNotContainsString('class="footer-note"', $html);
        $this->assertStringNotContainsString('Dokumen ini dihasilkan otomatis dari data verifikasi audit ISPO', $html);
    }

    public function test_pdf_renders_assessment_items_as_a_two_column_hierarchical_table(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit assessment table']);
        $item = $audit->items()->create([
            'title' => '1.1.1 Izin lokasi dan KKPR',
            'description' => "Memiliki izin lokasi.\nDokumen masih berlaku.",
            'sort_order' => 1,
            'status' => 'compliant',
        ]);
        $item->hasils()->create([
            'content' => 'Dokumen telah diverifikasi.',
            'evidence_path' => 'audits/evidence/izin.pdf',
            'sort_order' => 0,
        ]);

        $html = view('audits.pdf', [
            'audit' => $audit->load('company'),
            'items' => $audit->items,
            'isIspoChecklist' => true,
            'principles' => collect([1 => collect([$item])]),
        ])->render();

        $resultsPage = substr($html, strpos($html, '<div class="results-page">'));

        $this->assertStringContainsString('<img src="data:image/jpeg;base64,', $resultsPage);
        $this->assertStringContainsString('<h2 class="results-title">HASIL VERIFIKASI PENILAIAN</h2>', $resultsPage);
        $this->assertStringContainsString('<table class="assessment-table">', $resultsPage);
        $this->assertStringContainsString('<td><strong>1.</strong></td>', $resultsPage);
        $this->assertStringContainsString('<strong>KEPATUHAN TERHADAP PERATURAN PERUNDANGUNDANGAN</strong>', $resultsPage);
        $this->assertStringContainsString('<td><strong>1.1</strong></td>', $resultsPage);
        $this->assertStringContainsString('<strong>Izin Lokasi atau Kesesuaian Kegiatan Pemanfaatan Ruang</strong>', $resultsPage);
        $this->assertStringContainsString('<td>1.1.1</td>', $resultsPage);
        $this->assertStringContainsString('<td>Parameter</td>', $resultsPage);
        $this->assertStringContainsString('<li>Memiliki izin lokasi.</li>', $resultsPage);
        $this->assertStringContainsString('<li>Dokumen masih berlaku.</li>', $resultsPage);
        $this->assertStringContainsString('<strong><u>Hasil Audit:</u></strong>', $resultsPage);
        $this->assertStringContainsString('Dokumen telah diverifikasi.', $resultsPage);
        $this->assertStringContainsString('Bukti: izin.pdf', $resultsPage);
        $this->assertStringContainsString(
            '<strong><u>Status :</u></strong> <strong>Memenuhi</strong>',
            $resultsPage,
        );
    }

    public function test_admin_and_project_lead_can_update_the_audit_summary(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit summary']);
        $lead = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        $summary = "Ringkasan dari admin.\nBaris kedua.";

        $response = $this->from(route('audits.show', $audit))
            ->put(route('audits.summary.update', $audit), ['summary_notes' => $summary])
            ->assertRedirect(route('audits.show', $audit))
            ->assertSessionHas('success');

        $this->get($response->headers->get('Location'))->assertOk();
        $this->assertSame($summary, $audit->fresh()->summary_notes);

        $this->actingAs($lead)
            ->from(route('audits.show', $audit))
            ->put(route('audits.summary.update', $audit), ['summary_notes' => 'Ringkasan dari Lead Auditor.'])
            ->assertRedirect(route('audits.show', $audit));

        $this->assertSame('Ringkasan dari Lead Auditor.', $audit->fresh()->summary_notes);
    }

    public function test_only_admin_or_project_lead_can_view_and_update_the_audit_summary_form(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit summary authorization']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $outsider = User::factory()->create(['role' => 'auditor']);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $auditor->id,
            'project_role' => ProjectRole::Auditor->value,
        ]);

        $this->get(route('audits.show', $audit))
            ->assertSee('Ringkasan Penilaian Lead Auditor')
            ->assertSee('name="summary_notes"', false);

        foreach ([$auditor, $outsider] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)
                ->get(route('audits.show', $audit))
                ->assertDontSee('name="summary_notes"', false);

            $this->put(route('audits.summary.update', $audit), [
                'summary_notes' => 'Tidak boleh disimpan.',
            ])->assertForbidden();
        }

        $this->assertNull($audit->fresh()->summary_notes);
    }

    public function test_audit_reports_render_saved_summary_notes_as_escaped_multiline_text(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create([
            'title' => 'Audit PDF summary',
            'summary_notes' => "Ringkasan <script>alert('x')</script>\nBaris kedua.",
        ]);

        $html = view('audits.pdf', [
            'audit' => $audit->load('company'),
            'items' => $audit->items,
            'isIspoChecklist' => false,
            'principles' => collect(),
        ])->render();

        $this->assertStringContainsString('Ringkasan &lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', $html);
        $this->assertStringContainsString('<br />', $html);
        $this->assertStringNotContainsString('<script>alert(', $html);
        $this->assertStringNotContainsString('Berikut ini merupakan ringkasan dari hasil penilaian', $html);
        $this->assertStringNotContainsString('<table class="summary">', $html);
        $this->assertStringNotContainsString('Total indikator', $html);
        $this->assertStringContainsString('HASIL VERIFIKASI PENILAIAN', $html);

        $this->get(route('audits.report', $audit))
            ->assertOk()
            ->assertSee('Ringkasan &lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(', false)
            ->assertDontSee('Berikut ini merupakan ringkasan dari hasil penilaian')
            ->assertDontSee('Total indikator')
            ->assertDontSee('class="report-summary"', false)
            ->assertSee('HASIL VERIFIKASI PENILAIAN');

        $reportStyles = file_get_contents(resource_path('css/app.css'));

        $this->assertSame(
            1,
            preg_match(
                '/\.ispo-report-body, \.ispo-report-body \* \{\s*color: #000000 !important;\s*border-color: #000000 !important;\s*\}/',
                $reportStyles,
            ),
        );
    }

    public function test_audit_views_offer_preview_and_download_pdf_actions(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit report actions']);
        $audit->items()->create([
            'title' => '1.1.1 Izin lokasi dan KKPR',
            'sort_order' => 1,
            'status' => 'compliant',
        ]);
        $previewUrl = route('audits.pdf', $audit);
        $downloadUrl = route('audits.pdf', ['audit' => $audit, 'download' => 1]);

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('Preview PDF')
            ->assertSee('Unduh Laporan PDF')
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertSee($previewUrl, false)
            ->assertSee($downloadUrl, false);

        $this->get(route('audits.report', $audit))
            ->assertOk()
            ->assertSee('Preview PDF')
            ->assertSee('Unduh Laporan PDF')
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertSee($previewUrl, false)
            ->assertSee($downloadUrl, false);
    }

    public function test_audit_pdf_preview_returns_an_inline_pdf_file(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit pdf']);
        $audit->items()->createMany([
            ['title' => '1.1.1 Izin lokasi dan KKPR', 'sort_order' => 1, 'status' => 'compliant', 'note' => 'Dokumen lengkap.'],
            ['title' => '2.1.1 Rencana strategis perkebunan', 'sort_order' => 2, 'status' => 'non_compliant'],
        ]);

        $response = $this->get(route('audits.pdf', $audit));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('laporan-verifikasi-ispo-', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_audit_pdf_download_returns_an_attachment_when_requested(): void
    {
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit pdf download']);
        $audit->items()->create([
            'title' => '1.1.1 Izin lokasi dan KKPR',
            'sort_order' => 1,
            'status' => 'compliant',
        ]);

        $response = $this->get(route('audits.pdf', ['audit' => $audit, 'download' => 1]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('content-disposition'));
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
            ->assertSee('01 Januari 2026')
            ->assertSee('07 Januari 2026')
            ->assertSee('Lead Audit')
            ->assertSee('Auditor Prinsip Satu')
            ->assertSee('Bukti hasil verifikasi.')
            ->assertSee('Temuan utama audit')
            ->assertSee('Dokumen perlu diperbarui.')
            ->assertDontSee('PIC Perusahaan')
            ->assertDontSee('Ruang Lingkup')
            ->assertDontSee('Surveillance 1')
            ->assertDontSee('Standar ISPO - Kebun & PKS');
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
