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
            ->assertSee('Nama PIC')
            ->assertSee('Email PIC')
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
            'contact_name' => 'Siti Aminah',
            'contact_email' => 'siti@ptpn.co.id',
        ]);

        $response = $this->actingAs($admin)->get(route('companies.edit', $company));

        $response->assertOk()
            ->assertSee('Edit data perusahaan')
            ->assertSee('PT Perkebunan Nusantara')
            ->assertSee('Kelapa Sawit')
            ->assertSee('Jl. Sawit Raya No. 1')
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
            'contact_name' => 'Ahmad Fauzi',
            'contact_email' => 'fauzi@sawitsejahtera.com',
        ]);

        $response->assertRedirect(route('companies.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Sawit Sejahtera Bersama',
            'industry' => 'Perkebunan Kelapa Sawit',
            'address' => 'Jl. Kebun Indah KM 5',
            'contact_name' => 'Ahmad Fauzi',
            'contact_email' => 'fauzi@sawitsejahtera.com',
        ]);

        $company = Company::where('name', 'PT Sawit Sejahtera Bersama')->firstOrFail();
        // Memastikan tidak ada audit otomatis yang dibuat
        $this->assertSame(0, $company->audits()->count());
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
            'contact_name' => 'Rina Wijaya',
            'contact_email' => 'rina@barusukses.com',
        ]);
    }

    public function test_company_index_shows_master_data_and_audit_history_count_without_audit_progress_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create([
            'name' => 'PT Sawit Sejahtera',
            'industry' => 'Perkebunan Kelapa Sawit',
            'address' => 'Jl. Kebun Raya',
            'contact_name' => 'Dewi Lestari',
            'contact_email' => 'dewi@sawit.co.id',
        ]);
        $company->audits()->createMany([
            ['title' => 'Surveillance 1'],
            ['title' => 'Audit Resertifikasi'],
        ]);

        $this->actingAs($admin)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertSee('Nama perusahaan')
            ->assertSee('Bidang usaha')
            ->assertSee('Kontak PIC')
            ->assertSee('Total riwayat audit')
            ->assertSee('PT Sawit Sejahtera')
            ->assertSee('Jl. Kebun Raya')
            ->assertSee('Perkebunan Kelapa Sawit')
            ->assertSee('Dewi Lestari')
            ->assertSee('dewi@sawit.co.id')
            ->assertSee('Detail / Riwayat')
            ->assertSee('+ Tambah perusahaan')
            ->assertSee('Edit')
            ->assertSee('Hapus')
            ->assertSee('2')
            ->assertDontSee('Auditor / Tanggal')
            ->assertDontSee('Progres verifikasi')
            ->assertDontSee('Surveillance 1');
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
