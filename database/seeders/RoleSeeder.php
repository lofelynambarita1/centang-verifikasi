<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role lead_audit dibersihkan dari database
        Role::where('name', 'lead_audit')->delete();

        $roles = [
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Administrator sistem dengan akses penuh',
            ],
            [
                'name' => 'auditor',
                'label' => 'Auditor',
                'description' => 'Auditor verifikasi dokumen dan lapangan',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'label' => $role['label'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
