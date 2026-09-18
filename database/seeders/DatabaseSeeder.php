<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $company = Company::create([
            'name' => 'PT Arunika Logistik',
            'industry' => 'Logistik',
            'address' => 'Surabaya',
            'contact_name' => 'Dina Saputra',
            'contact_email' => 'dina@example.com',
        ]);

        $audit = $company->audits()->create([
            'title' => 'Audit kepatuhan operasional',
            'status' => 'in_progress',
            'auditor_name' => 'Arif Rahman',
            'due_date' => now()->addDays(7),
        ]);

        foreach ([
            ['title' => 'Legalitas dan dokumen perusahaan', 'description' => 'Pastikan dokumen legalitas masih berlaku dan sesuai.', 'status' => 'compliant'],
            ['title' => 'Struktur organisasi dan tanggung jawab', 'description' => 'Verifikasi pembagian peran dan otorisasi kerja.', 'status' => 'compliant'],
            ['title' => 'Prosedur operasional standar', 'description' => 'Periksa ketersediaan SOP dan bukti penerapannya.', 'status' => 'pending'],
            ['title' => 'Keselamatan dan kesehatan kerja', 'description' => 'Periksa kelengkapan prosedur K3 dan pelatihan.', 'status' => 'pending'],
        ] as $sortOrder => $item) {
            $audit->items()->create($item + ['sort_order' => $sortOrder]);
        }
    }
}
