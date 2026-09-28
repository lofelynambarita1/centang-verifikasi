<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_can_filter_companies_by_search_and_status(): void
    {
        $user = User::factory()->create();

        $matchingCompany = Company::factory()->create(['name' => 'Alpha Timber']);
        $matchingCompany->audits()->create(['title' => 'Audit Alpha', 'status' => 'completed']);

        $otherCompany = Company::factory()->create(['name' => 'Beta Retail']);
        $otherCompany->audits()->create(['title' => 'Audit Beta', 'status' => 'in_progress']);

        $response = $this->actingAs($user)->get(route('dashboard', ['search' => 'alpha', 'status' => 'completed']));

        $response->assertOk();
        $response->assertSee('Alpha Timber');
        $response->assertDontSee('Beta Retail');
    }
}
