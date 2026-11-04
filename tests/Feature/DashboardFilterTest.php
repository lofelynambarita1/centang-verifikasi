<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_search_filters_active_projects_and_removes_duplicate_sections(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $matchingCompany = Company::factory()->create(['name' => 'Alpha Timber']);
        $matchingCompany->audits()->create(['title' => 'Audit Alpha', 'status' => 'in_progress']);
        $matchingCompany->audits()->create(['title' => 'Audit Selesai', 'status' => 'completed']);

        $otherCompany = Company::factory()->create(['name' => 'Beta Retail']);
        $otherCompany->audits()->create(['title' => 'Audit Beta', 'status' => 'in_progress']);

        $response = $this->actingAs($user)->get(route('dashboard', ['search' => 'alpha']));

        $response->assertOk();
        $response->assertSee('Proyek audit aktif');
        $response->assertSee('Audit Alpha');
        $response->assertSee('Alpha Timber');
        $response->assertDontSee('Beta Retail');
        $response->assertDontSee('Audit Selesai');
        $response->assertSee('Audit berjalan');
        $response->assertSee('Temuan / ketidaksesuaian');
        $response->assertSee('Buka');
        $response->assertSee('dashboard-projects', false);
        $response->assertSee('dashboard-projects__table', false);
        $response->assertDontSee('Status Seluruh Indikator');
        $response->assertDontSee('donut__ring', false);
        $response->assertDontSee('Progres Verifikasi Perusahaan');
        $response->assertDontSee('Audit terkini');
        $response->assertDontSee('dashboard-subnav');
    }

    public function test_dashboard_shows_active_audit_project_indicator_progress(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor', 'name' => 'Lead Dashboard']);
        $company = Company::factory()->create(['name' => 'PT Progres Sawit']);
        $audit = $company->audits()->create([
            'title' => 'Audit Berjalan',
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
            'added_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Proyek audit aktif')
            ->assertSee('PT Progres Sawit')
            ->assertSee('Audit Berjalan')
            ->assertSee('Lead Dashboard')
            ->assertSee('<strong>1</strong> / 2 indikator selesai', false)
            ->assertSee('dashboard-projects__dates', false)
            ->assertSee('dashboard-projects__table', false)
            ->assertSee('s/d', false)
            ->assertSee('50%');
    }

    public function test_dashboard_shows_overall_indicator_status_in_a_donut_chart(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();
        $audit = $company->audits()->create(['title' => 'Audit Status', 'status' => 'in_progress']);
        $statuses = array_merge(
            array_fill(0, 4, 'compliant'),
            ['non_compliant', 'not_applicable'],
            array_fill(0, 401, 'pending'),
        );
        $audit->items()->createMany(
            collect($statuses)
                ->map(fn (string $status, int $index): array => [
                    'title' => 'Indikator '.$index,
                    'sort_order' => $index,
                    'status' => $status,
                ])
                ->all(),
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Status Seluruh Indikator')
            ->assertSee('dashboard-overall__chart', false)
            ->assertSee('<strong>1%</strong>', false)
            ->assertSee('Diagram lingkaran status indikator: Memenuhi 4, Belum memenuhi 1, Tidak berlaku 1, Menunggu penilaian 401')
            ->assertSee('6 dari 407 indikator telah dinilai');
    }

    public function test_dashboard_shows_zero_percent_when_there_are_no_indicators(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();
        $company->audits()->create(['title' => 'Audit Kosong', 'status' => 'in_progress']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Status Seluruh Indikator')
            ->assertSee('0%')
            ->assertSee('0 dari 0 indikator telah dinilai');
    }

    public function test_dashboard_lead_can_view_all_company_and_principle_progress(): void
    {
        $lead = User::factory()->create(['role' => 'auditor']);
        $leadCompany = Company::factory()->create(['name' => 'PT Perusahaan Lead']);
        $leadAudit = $leadCompany->audits()->create(['title' => 'Audit Lead', 'status' => 'in_progress']);
        $leadAudit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1, 'status' => 'compliant']);
        AuditProjectMember::create([
            'audit_id' => $leadAudit->id,
            'user_id' => $lead->id,
            'project_role' => ProjectRole::LeadAudit->value,
            'added_by' => User::factory()->create(['role' => 'admin'])->id,
        ]);

        $otherCompany = Company::factory()->create(['name' => 'PT Perusahaan Lain']);
        $otherAudit = $otherCompany->audits()->create(['title' => 'Audit Perusahaan Lain', 'status' => 'in_progress']);
        $otherAudit->items()->create(['title' => '2.1.1 Lingkungan', 'sort_order' => 1, 'status' => 'pending']);

        $this->actingAs($lead)
            ->get(route('dashboard'))
            ->assertSee('Status Seluruh Indikator')
            ->assertSee('PT Perusahaan Lead')
            ->assertSee('PT Perusahaan Lain')
            ->assertSee('data-principle="1"', false)
            ->assertSee('data-principle="2"', false);
    }

    public function test_dashboard_auditor_only_sees_assigned_principle_progress(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create(['name' => 'PT Auditor Ditugaskan']);
        $audit = $company->audits()->create(['title' => 'Audit Auditor', 'status' => 'in_progress']);
        $audit->items()->createMany([
            ['title' => '1.1.1 Legalitas', 'sort_order' => 1, 'status' => 'compliant'],
            ['title' => '1.1.2 Perizinan', 'sort_order' => 2, 'status' => 'pending'],
            ['title' => '2.1.1 Lingkungan', 'sort_order' => 3, 'status' => 'compliant'],
        ]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);

        $this->actingAs($auditor)
            ->get(route('dashboard'))
            ->assertDontSee('Status Seluruh Indikator')
            ->assertSee('PT Auditor Ditugaskan')
            ->assertSee('<strong>1</strong> / 2 indikator selesai', false)
            ->assertSee('data-principle="1"', false)
            ->assertDontSee('data-principle="2"', false);
    }
}
