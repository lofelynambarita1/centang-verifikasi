<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\CertificationProcess;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificationProcessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_each_category_and_manage_company_process(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create(['name' => 'PT Sawit Sejahtera']);

        $this->actingAs($admin)
            ->get(route('certification-process.index', 'hulu'))
            ->assertOk()
            ->assertSee('Hulu')
            ->assertSee('PT Sawit Sejahtera')
            ->assertSee(route('certification-process.show', ['hulu', $company]), false)
            ->assertSee('Hilir')
            ->assertSee('Bio');
    }

    public function test_lead_auditor_only_sees_companies_with_an_assigned_lead_audit(): void
    {
        $lead = User::factory()->create(['role' => 'auditor']);
        $assignedCompany = Company::factory()->create(['name' => 'Perusahaan Ditugaskan']);
        $otherCompany = Company::factory()->create(['name' => 'Perusahaan Lain']);
        $audit = $assignedCompany->audits()->create(['title' => 'Audit Hulu']);
        $audit->members()->create([
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);

        $this->actingAs($lead)
            ->get(route('certification-process.index', 'hulu'))
            ->assertOk()
            ->assertSee('Perusahaan Ditugaskan')
            ->assertDontSee('Perusahaan Lain');
    }

    public function test_lead_auditor_cannot_open_an_unassigned_company_process(): void
    {
        $lead = User::factory()->create(['role' => 'auditor']);
        $assignedCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $audit = $assignedCompany->audits()->create(['title' => 'Audit Hulu']);
        $audit->members()->create([
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);

        $this->actingAs($lead)
            ->get(route('certification-process.show', ['hulu', $otherCompany]))
            ->assertNotFound();

        $this->put(route('certification-process.update', ['hulu', $otherCompany]), [
            'audit_report' => 'Tidak berwenang',
        ])->assertForbidden();
    }

    public function test_admin_can_save_process_documents_and_get_a_public_certificate_qr(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create(['name' => 'PT Sawit Sejahtera']);

        $response = $this->actingAs($admin)->put(
            route('certification-process.update', ['hulu', $company]),
            [
                'summary_document' => UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\nLaporan ringkas"),
                'audit_report' => 'Hasil audit telah ditinjau.',
                'review_panel' => 'Panel menyetujui hasil review.',
                'certificate_document' => UploadedFile::fake()->createWithContent('sertifikat.pdf', "%PDF-1.4\nSertifikat"),
            ],
        );

        $response->assertRedirect(route('certification-process.show', ['hulu', $company]));
        $process = CertificationProcess::query()->firstOrFail();
        $this->assertSame('Hasil audit telah ditinjau.', $process->audit_report);
        $this->assertSame('Panel menyetujui hasil review.', $process->review_panel);
        $this->assertNotNull($process->certificate_token);
        Storage::disk('local')->assertExists($process->summary_document_path);
        Storage::disk('local')->assertExists($process->certificate_document_path);

        $this->get(route('certification-process.show', ['hulu', $company]))
            ->assertOk()
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee('Unduh QR code');

        auth()->logout();

        $this->get(route('certificates.show', $process->certificate_token))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="certificate.pdf"');
    }

    public function test_invalid_document_upload_is_rejected_without_creating_a_process(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->from(route('certification-process.show', ['hulu', $company]))
            ->put(route('certification-process.update', ['hulu', $company]), [
                'summary_document' => UploadedFile::fake()->createWithContent('not-a-document.exe', 'invalid'),
            ])
            ->assertSessionHasErrors('summary_document');

        $this->assertDatabaseCount('certification_processes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_public_certificate_route_returns_not_found_for_unknown_token(): void
    {
        $this->get(route('certificates.show', 'unknown-token'))
            ->assertNotFound();
    }
}
