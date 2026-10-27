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
        $user = User::factory()->create();

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
        $user = User::factory()->create();
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
}
