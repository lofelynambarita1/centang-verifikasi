<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_create_company_form_with_only_master_data_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('companies.create'));

        $response->assertOk()
            ->assertSee('Tambah perusahaan')
            ->assertSee('Nama perusahaan')
            ->assertSee('Bidang usaha')
            ->assertSee('Alamat')
            ->assertSee('Klien sejak (tahun)')
            ->assertSee('Nama PIC')
            ->assertSee('Email PIC')
            ->assertSee('Luasan lahan (Ha)')
            ->assertSee('Simpan Perusahaan')
            ->assertDontSee('Nama auditor')
            ->assertDontSee('Deadline audit')
            ->assertDontSee('Simpan dan buat checklist')
            ->assertDontSee('name="auditor_name"', false)
            ->assertDontSee('name="due_date"', false);
    }

    public function test_admin_can_view_edit_company_form_with_only_master_data_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create([
            'name' => 'PT Perkebunan Nusantara',
            'industry' => 'Kelapa Sawit',
            'address' => 'Jl. Sawit Raya No. 1',
            'client_year' => 2020,
            'contact_name' => 'Siti Aminah',
            'contact_email' => 'siti@ptpn.co.id',
        ]);

        $response = $this->actingAs($admin)->get(route('companies.edit', $company));

        $response->assertOk()
            ->assertSee('Edit data perusahaan')
            ->assertSee('PT Perkebunan Nusantara')
            ->assertSee('Kelapa Sawit')
            ->assertSee('Jl. Sawit Raya No. 1')
            ->assertSee('name="client_year"', false)
            ->assertSee('value="2020"', false)
            ->assertSee('Siti Aminah')
            ->assertSee('siti@ptpn.co.id')
            ->assertSee('Simpan Perusahaan')
            ->assertDontSee('Nama auditor')
            ->assertDontSee('Deadline audit')
            ->assertDontSee('name="auditor_name"', false)
            ->assertDontSee('name="due_date"', false);
    }

    public function test_admin_can_create_company_without_creating_audit_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('companies.store'), [
            'name' => 'PT Sawit Sejahtera Bersama',
            'industry' => 'Perkebunan Kelapa Sawit',
            'address' => 'Jl. Kebun Indah KM 5',
            'client_year' => 2021,
            'land_area' => '1250.50',
            'contact_name' => 'Ahmad Fauzi',
            'contact_email' => 'fauzi@sawitsejahtera.com',
        ]);

        $response->assertRedirect(route('companies.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Sawit Sejahtera Bersama',
            'industry' => 'Perkebunan Kelapa Sawit',
            'address' => 'Jl. Kebun Indah KM 5',
            'client_year' => 2021,
            'land_area' => '1250.50',
            'contact_name' => 'Ahmad Fauzi',
            'contact_email' => 'fauzi@sawitsejahtera.com',
        ]);

        $company = Company::where('name', 'PT Sawit Sejahtera Bersama')->firstOrFail();
        // Memastikan tidak ada audit otomatis yang dibuat
        $this->assertSame(0, $company->audits()->count());
    }

    public function test_company_year_must_be_a_valid_past_or_current_calendar_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('companies.store'), [
                'name' => 'PT Tahun Tidak Valid',
                'client_year' => now()->year + 1,
            ])
            ->assertSessionHasErrors('client_year');

        $this->assertDatabaseMissing('companies', ['name' => 'PT Tahun Tidak Valid']);
    }

    public function test_admin_can_update_company_and_redirects_to_companies_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create([
            'name' => 'PT Lama Mandiri',
            'industry' => 'Lama',
        ]);

        $response = $this->actingAs($admin)->put(route('companies.update', $company), [
            'name' => 'PT Baru Sukses Makmur',
            'industry' => 'Agribisnis Sawit',
            'address' => 'Jl. Poros Baru No. 8',
            'client_year' => 2024,
            'land_area' => '250.75',
            'contact_name' => 'Rina Wijaya',
            'contact_email' => 'rina@barusukses.com',
        ]);

        $response->assertRedirect(route('companies.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'PT Baru Sukses Makmur',
            'industry' => 'Agribisnis Sawit',
            'address' => 'Jl. Poros Baru No. 8',
            'client_year' => 2024,
            'land_area' => '250.75',
            'contact_name' => 'Rina Wijaya',
            'contact_email' => 'rina@barusukses.com',
        ]);
    }

    public function test_company_index_shows_company_details_and_latest_audit_status_without_industry_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create([
            'name' => 'PT Sawit Sejahtera',
            'industry' => 'Perkebunan Kelapa Sawit',
            'address' => 'Jl. Kebun Raya',
            'client_year' => 2024,
            'contact_name' => 'Dewi Lestari',
            'contact_email' => 'dewi@sawit.co.id',
            'created_at' => now()->setDate(2024, 6, 15),
        ]);
        $company->audits()->createMany([
            ['title' => 'Surveillance 1', 'status' => 'completed', 'start_date' => '2024-06-15'],
            ['title' => 'Audit Resertifikasi', 'status' => 'in_progress', 'start_date' => '2026-03-20'],
        ]);
        $companyWithoutAudit = Company::factory()->create([
            'name' => 'PT Belum Audit',
            'created_at' => now()->setDate(2023, 5, 10),
        ]);
        $completedCompany = Company::factory()->create(['name' => 'PT Audit Selesai']);
        $completedCompany->audits()->create(['title' => 'Audit Selesai', 'status' => 'completed']);
        $pendingCompany = Company::factory()->create(['name' => 'PT Audit Belum Mulai']);
        $pendingCompany->audits()->create(['title' => 'Audit Pending', 'status' => 'pending']);

        $this->actingAs($admin)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertSee('Nama perusahaan')
            ->assertDontSee('Bidang usaha')
            ->assertSee('Klien Sejak (Tahun)')
            ->assertSee('Status Audit Terkini')
            ->assertSee('Kontak PIC')
            ->assertSee('Total riwayat audit')
            ->assertSee('PT Sawit Sejahtera')
            ->assertSee('Jl. Kebun Raya')
            ->assertSee('class="company-client-year">2024</td>', false)
            ->assertSee('Audit Resertifikasi - 2026')
            ->assertSee('Status: Sedang berlangsung')
            ->assertDontSee('Surveillance 1 - 2024')
            ->assertSee($companyWithoutAudit->name)
            ->assertSee('Belum ada audit')
            ->assertSee('class="company-client-year">-</td>', false)
            ->assertSee($completedCompany->name)
            ->assertSee('Selesai')
            ->assertSee($pendingCompany->name)
            ->assertSee('Belum mulai')
            ->assertDontSee('Perkebunan Kelapa Sawit')
            ->assertSee('Dewi Lestari')
            ->assertSee('dewi@sawit.co.id')
            ->assertSee('Detail / Riwayat')
            ->assertSee('+ Tambah perusahaan')
            ->assertSee('Edit')
            ->assertSee('Hapus')
            ->assertSee('aria-label="Aksi untuk PT Sawit Sejahtera"', false)
            ->assertSee('role="menu"', false)
            ->assertSee(route('companies.show', $company), false)
            ->assertSee(route('companies.edit', $company), false)
            ->assertSee(route('companies.destroy', $company), false)
            ->assertSee('2')
            ->assertDontSee('Auditor / Tanggal')
            ->assertDontSee('Progres verifikasi')
            ->assertDontSee('Perkebunan Kelapa Sawit');
    }

    public function test_auditors_and_lead_auditors_can_view_companies_but_not_management_controls(): void
    {
        $users = [];

        foreach (['auditor', 'lead_audit'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $users[] = $user;

            $this->actingAs($user)
                ->get(route('companies.index'))
                ->assertOk()
                ->assertDontSee('+ Tambah perusahaan');
        }

        $company = Company::factory()->create(['name' => 'PT Sawit Sejahtera']);

        foreach ($users as $user) {
            $this->actingAs($user)
                ->get(route('companies.index'))
                ->assertOk()
                ->assertSee('Detail / Riwayat')
                ->assertDontSee('+ Tambah perusahaan')
                ->assertDontSee('Edit')
                ->assertDontSee('Hapus');

            $this->actingAs($user)
                ->get(route('companies.show', $company))
                ->assertOk();
        }
    }

    public function test_auditors_and_lead_auditors_cannot_manage_company_master_data(): void
    {
        $company = Company::factory()->create();
        $originalName = $company->name;

        foreach (['auditor', 'lead_audit'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('companies.create'))->assertForbidden();
            $this->actingAs($user)->post(route('companies.store'), ['name' => 'PT Tidak Diizinkan'])->assertForbidden();
            $this->actingAs($user)->get(route('companies.edit', $company))->assertForbidden();
            $this->actingAs($user)->put(route('companies.update', $company), ['name' => 'PT Tidak Diizinkan'])->assertForbidden();
            $this->actingAs($user)->delete(route('companies.destroy', $company))->assertForbidden();
        }

        $this->assertDatabaseMissing('companies', ['name' => 'PT Tidak Diizinkan']);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => $originalName]);
        $this->assertModelExists($company);
    }
}
