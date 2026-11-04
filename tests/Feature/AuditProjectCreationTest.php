<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Audit;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\User;
use App\Support\IspoChecklist;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuditProjectCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_create_audit_project_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $auditor = User::factory()->create(['role' => 'auditor', 'name' => 'Budi Auditor']);
        $company = Company::factory()->create(['name' => 'PT Perkebunan Sawit']);

        $this->actingAs($admin)
            ->get(route('audits.create'))
            ->assertOk()
            ->assertSee('Buat Proyek Audit Baru')
            ->assertSee($company->name)
            ->assertSee($auditor->name)
            ->assertSee('name="company_id"', false)
            ->assertSee('name="start_date"', false)
            ->assertSee('name="end_date"', false)
            ->assertSee('name="scope_category"', false)
            ->assertSee('name="scope_detail"', false)
            ->assertSee('Hulu')
            ->assertSee('Hilir')
            ->assertSee('Bioenergi')
            ->assertSee('Kebun Sawit')
            ->assertSee('PKS / Pabrik')
            ->assertSee('Integrasi (Kebun & PKS)', false)
            ->assertSee('name="lead_auditor_id"', false);
    }

    public function test_non_admin_cannot_access_the_create_audit_project_form(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);

        $this->actingAs($auditor)
            ->get(route('audits.create'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_the_create_audit_project_form(): void
    {
        $this->get(route('audits.create'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_sees_create_button_on_assignments_page_while_auditor_does_not(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Demo']);
        $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->get(route('audits.assignments.index'))
            ->assertOk()
            ->assertSee('Buat Proyek Audit Baru')
            ->assertSee(route('audits.create'), false);

        // Auditor biasa dilarang membuka index penugasan
        $this->actingAs($auditor)
            ->get(route('audits.assignments.index'))
            ->assertForbidden();
    }

    public function test_assignments_index_shows_project_team_dates_progress_and_report_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Dina Lead']);
        $auditor = User::factory()->create(['role' => 'auditor', 'name' => 'Rudi Auditor']);
        $company = Company::factory()->create(['name' => 'PT Audit Sawit']);
        $audit = $company->audits()->create([
            'title' => 'Surveillance 1',
            'status' => 'in_progress',
            'start_date' => '2025-03-01',
            'end_date' => '2025-03-05',
        ]);
        $audit->items()->createMany([
            ['title' => '1.1.1 Legalitas', 'sort_order' => 1, 'status' => 'compliant'],
            ['title' => '1.1.2 Perizinan', 'sort_order' => 2, 'status' => 'pending'],
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);
        AuditProjectMember::create([
            'audit_id' => $audit->id,
            'user_id' => $auditor->id,
            'project_role' => ProjectRole::Auditor->value,
            'added_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('audits.assignments.index'))
            ->assertOk()
            ->assertSee('PT Audit Sawit')
            ->assertSee('Surveillance 1')
            ->assertSee('Dina Lead')
            ->assertSee('Rudi Auditor')
            ->assertSee('Detail Tim & Deadline')
            ->assertSee('<details class="audit-team-details">', false)
            ->assertSee('2025')
            ->assertSee('<strong>1</strong> / 2 Indikator Selesai', false)
            ->assertSee('50%')
            ->assertSee('Buka Checklist')
            ->assertSee('Atur Tim/Penugasan')
            ->assertSee('Laporan')
            ->assertSee(route('audits.show', $audit), false)
            ->assertSee(route('audits.assignments.edit', $audit), false)
            ->assertSee(route('audits.report', $audit), false);
    }

    #[DataProvider('validScopeSelections')]
    public function test_admin_can_create_audit_project_with_a_valid_scope(
        string $scopeCategory,
        ?string $scopeDetail,
        string $expectedScope,
    ): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Lead Auditor Utama']);
        $company = Company::factory()->create(['name' => 'PT Sawit Makmur Jaya']);

        $startDate = now()->addDays(2)->format('Y-m-d');
        $endDate = now()->addDays(9)->format('Y-m-d');

        $payload = [
            'company_id' => $company->id,
            'title' => 'Surveillance 1',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'scope_category' => $scopeCategory,
            'lead_auditor_id' => $lead->id,
        ];
        if ($scopeDetail !== null) {
            $payload['scope_detail'] = $scopeDetail;
        }

        $response = $this->actingAs($admin)->post(route('audits.store'), $payload);

        $audit = Audit::where('company_id', $company->id)->latest()->firstOrFail();

        // Redirect langsung ke halaman pembagian prinsip
        $response->assertRedirect(route('audits.assignments.edit', $audit));
        $response->assertSessionHas('success');

        // Pastikan atribut audit tersimpan
        $this->assertSame('Surveillance 1', $audit->title);
        $this->assertSame('pending', $audit->status);
        $this->assertSame($startDate, $audit->start_date->format('Y-m-d'));
        $this->assertSame($endDate, $audit->end_date->format('Y-m-d'));
        $this->assertSame($endDate, $audit->due_date->format('Y-m-d'));
        $this->assertSame($expectedScope, $audit->scope);
        $this->assertSame($lead->name, $audit->auditor_name);

        // Checklist ISPO otomatis dibuat untuk seluruh indikator
        $canonicalCount = count(IspoChecklist::items());
        $this->assertGreaterThan(0, $canonicalCount);
        $this->assertSame($canonicalCount, $audit->items()->count());
        $this->assertTrue($audit->items->every(fn ($item) => $item->status === 'pending'));

        // Lead Auditor otomatis terdaftar pada AuditProjectMember dengan project_role = lead_audit
        $this->assertDatabaseHas('audit_project_members', [
            'audit_id' => $audit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => $admin->id,
        ]);

        // Helper isLeadAuditor mengembalikan true
        $this->assertTrue($lead->isLeadAuditor($audit));
        $this->assertTrue($audit->userIsLead($lead));
    }

    /**
     * @return array<string, array{string, ?string, string}>
     */
    public static function validScopeSelections(): array
    {
        return [
            'Hulu kebun' => ['hulu', 'hulu_kebun', 'hulu_kebun'],
            'Hulu PKS' => ['hulu', 'hulu_pks', 'hulu_pks'],
            'Hulu integrasi' => ['hulu', 'hulu_integrasi', 'hulu_integrasi'],
            'Hilir' => ['hilir', null, 'hilir'],
            'Bioenergi' => ['bioenergi', null, 'bioenergi'],
        ];
    }

    public function test_admin_can_create_an_audit_with_default_title_when_title_is_blank(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create(['name' => 'PT Sawit Nusantara']);

        $response = $this->actingAs($admin)->post(route('audits.store'), [
            'company_id' => $company->id,
            'title' => '',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'scope_category' => 'bioenergi',
            'lead_auditor_id' => $lead->id,
        ]);

        $audit = Audit::where('company_id', $company->id)->firstOrFail();

        $response->assertRedirect(route('audits.assignments.edit', $audit));
        $this->assertSame('Audit Verifikasi ISPO PT Sawit Nusantara', $audit->title);
        $this->assertSame('bioenergi', $audit->scope);
    }

    public function test_non_admin_cannot_create_an_audit_project(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create();

        $this->actingAs($auditor)->post(route('audits.store'), [
            'company_id' => $company->id,
            'title' => 'Audit Ilegal',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'scope_category' => 'hulu',
            'scope_detail' => 'hulu_kebun',
            'lead_auditor_id' => $lead->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('audits', ['title' => 'Audit Ilegal']);
    }

    public function test_audit_project_creation_validates_required_fields_and_date_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        // Kosong
        $this->actingAs($admin)->post(route('audits.store'), [])
            ->assertSessionHasErrors(['company_id', 'start_date', 'end_date', 'scope_category', 'lead_auditor_id'])
            ->assertSessionHasErrors([
                'scope_category' => 'Ruang lingkup verifikasi wajib dipilih.',
            ]);

        // Tanggal selesai sebelum tanggal mulai
        $lead = User::factory()->create(['role' => 'auditor']);
        $this->actingAs($admin)->post(route('audits.store'), [
            'company_id' => $company->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'lead_auditor_id' => $lead->id,
        ])->assertSessionHasErrors('end_date');

        // Lead auditor harus ber-role auditor, bukan admin
        $anotherAdmin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('audits.store'), [
            'company_id' => $company->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'lead_auditor_id' => $anotherAdmin->id,
        ])->assertSessionHasErrors('lead_auditor_id');
    }

    public function test_hulu_scope_requires_a_valid_subcategory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create();
        $payload = [
            'company_id' => $company->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'scope_category' => 'hulu',
            'lead_auditor_id' => $lead->id,
        ];

        $this->actingAs($admin)
            ->post(route('audits.store'), $payload)
            ->assertSessionHasErrors([
                'scope_detail' => 'Sub-kategori Hulu wajib dipilih.',
            ]);

        $this->post(route('audits.store'), $payload + ['scope_detail' => 'hilir'])
            ->assertSessionHasErrors([
                'scope_detail' => 'Sub-kategori Hulu yang dipilih tidak valid.',
            ]);

        $this->assertDatabaseCount('audits', 0);
    }

    public function test_scope_detail_is_rejected_for_non_hulu_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->post(route('audits.store'), [
                'company_id' => $company->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(5)->format('Y-m-d'),
                'scope_category' => 'hilir',
                'scope_detail' => 'hulu_kebun',
                'lead_auditor_id' => $lead->id,
            ])
            ->assertSessionHasErrors([
                'scope_detail' => 'Sub-kategori hanya dapat dipilih untuk ruang lingkup Hulu.',
            ]);

        $this->assertDatabaseCount('audits', 0);
    }

    public function test_scope_category_must_be_one_of_the_available_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->post(route('audits.store'), [
                'company_id' => $company->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(5)->format('Y-m-d'),
                'scope_category' => 'unknown',
                'lead_auditor_id' => $lead->id,
            ])
            ->assertSessionHasErrors([
                'scope_category' => 'Ruang lingkup verifikasi yang dipilih tidak valid.',
            ]);

        $this->assertDatabaseCount('audits', 0);
    }
}
