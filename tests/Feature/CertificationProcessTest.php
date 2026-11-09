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
        $otherCompany = Company::factory()->create(['name' => 'PT Perusahaan Kedua']);

        $this->actingAs($admin)
            ->get(route('certification-process.index', 'hulu'))
            ->assertOk()
            ->assertSee('Hulu')
            ->assertSee('PT Sawit Sejahtera')
            ->assertSee('PT Perusahaan Kedua')
            ->assertSee(route('certification-process.show', ['hulu', $company]), false)
            ->assertSee('Hilir')
            ->assertSee('Bioenergi')
            ->assertDontSee('>Bio<', false);
    }

    public function test_lead_auditor_can_access_all_company_processes(): void
    {
        $lead = User::factory()->create(['role' => 'auditor']);
        $leadCompany = Company::factory()->create(['name' => 'Perusahaan Tim Lead']);
        $otherCompany = Company::factory()->create(['name' => 'Perusahaan Lain']);
        $audit = $leadCompany->audits()->create(['title' => 'Audit Hulu']);
        $audit->members()->create([
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
        ]);

        $this->actingAs($lead)
            ->get(route('certification-process.index', 'hulu'))
            ->assertOk()
            ->assertSee('Perusahaan Tim Lead')
            ->assertSee('Perusahaan Lain');

        $this->get(route('certification-process.show', ['hulu', $otherCompany]))
            ->assertOk()
            ->assertSee('Simpan proses sertifikasi');

        $this->put(route('certification-process.update', ['hulu', $otherCompany]), [
            'audit_report' => 'Lead Auditor dapat mengelola semua perusahaan.',
        ])->assertRedirect(route('certification-process.show', ['hulu', $otherCompany]));

        $this->assertDatabaseHas('certification_processes', [
            'company_id' => $otherCompany->id,
            'category' => 'hulu',
            'audit_report' => 'Lead Auditor dapat mengelola semua perusahaan.',
        ]);
    }

    public function test_auditor_sees_only_assigned_companies_and_principle_progress(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $assignedCompany = Company::factory()->create(['name' => 'Perusahaan Ditugaskan']);
        $otherCompany = Company::factory()->create(['name' => 'Perusahaan Lain']);
        $audit = $assignedCompany->audits()->create(['title' => 'Audit Hulu Rahasia']);
        $audit->items()->create(['title' => '1.1.1 Persyaratan', 'status' => 'compliant']);
        $audit->items()->create(['title' => '1.1.2 Implementasi', 'status' => 'pending']);
        $audit->items()->create([
            'title' => '2.1.1 Prinsip tidak ditugaskan',
            'status' => 'non_compliant',
            'note' => 'Catatan yang tidak boleh ditampilkan',
        ]);
        $audit->assignees()->attach($auditor->id, ['principle_number' => 1]);
        $assignedCompany->certificationProcesses()->create([
            'category' => 'hulu',
            'audit_report' => 'Isi laporan yang hanya untuk pengelola',
            'review_panel' => 'Isi review yang hanya untuk pengelola',
            'summary_document_path' => 'private/laporan.pdf',
            'certificate_document_path' => 'private/sertifikat.pdf',
        ]);

        $this->actingAs($auditor)
            ->get(route('certification-process.index', 'hulu'))
            ->assertOk()
            ->assertSee('Perusahaan Ditugaskan')
            ->assertDontSee('Perusahaan Lain')
            ->assertSee('Lihat progres')
            ->assertDontSee('bagian terisi');

        $this->get(route('certification-process.show', ['hulu', $assignedCompany]))
            ->assertOk()
            ->assertSee('Prinsip 1')
            ->assertSee('Audit Hulu Rahasia')
            ->assertSee('50%')
            ->assertSee('Memenuhi')
            ->assertSee('Belum dinilai')
            ->assertDontSee('Prinsip 2')
            ->assertDontSee('Catatan yang tidak boleh ditampilkan')
            ->assertDontSee('Isi laporan yang hanya untuk pengelola')
            ->assertDontSee('Isi review yang hanya untuk pengelola')
            ->assertDontSee('Lihat / unduh')
            ->assertDontSee('Simpan proses sertifikasi');
    }

    public function test_auditor_cannot_access_unassigned_company_or_manage_documents(): void
    {
        Storage::fake('local');
        $auditor = User::factory()->create(['role' => 'auditor']);
        $assignedCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $audit = $assignedCompany->audits()->create(['title' => 'Audit Hulu']);
        $audit->items()->create(['title' => '1.1.1 Persyaratan']);
        $audit->assignees()->attach($auditor->id, ['principle_number' => 1]);
        Storage::disk('local')->put('private/laporan.pdf', 'Isi laporan privat.');
        $assignedCompany->certificationProcesses()->create([
            'category' => 'hulu',
            'summary_document_path' => 'private/laporan.pdf',
        ]);

        $this->actingAs($auditor)
            ->get(route('certification-process.show', ['hulu', $otherCompany]))
            ->assertNotFound();

        $this->put(route('certification-process.update', ['hulu', $assignedCompany]), [
            'audit_report' => 'Auditor tidak berwenang mengelola dokumen.',
        ])->assertForbidden();

        $this->get(route('certification-process.documents.download', ['hulu', $assignedCompany, 'summary']))
            ->assertNotFound();
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
