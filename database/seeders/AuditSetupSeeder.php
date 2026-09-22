<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\AuditTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditSetupSeeder extends Seeder
{
    /**
     * Seed the admin, three dummy auditors, and the ISPO template.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator GIS',
                'password' => 'password',
                'system_role' => SystemRole::Admin->value,
            ],
        );

        $auditors = [
            ['name' => 'Andi Auditor', 'email' => 'auditor1@example.com'],
            ['name' => 'Budi Auditor', 'email' => 'auditor2@example.com'],
            ['name' => 'Citra Auditor', 'email' => 'auditor3@example.com'],
        ];

        foreach ($auditors as $auditor) {
            User::updateOrCreate(
                ['email' => $auditor['email']],
                [
                    'name' => $auditor['name'],
                    'password' => 'password',
                    'system_role' => SystemRole::Auditor->value,
                ],
            );
        }

        AuditTemplate::updateOrCreate(
            ['code' => 'FrO.8603/GIS-1.1'],
            [
                'scheme' => 'ISPO',
                'name' => 'Laporan Verifikasi Hasil ISPO',
                'version' => '1.1',
                'description' => 'Template verifikasi ISPO sesuai Permentan No. 33/2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia.',
                'is_active' => true,
                'created_by' => $admin->id,
            ],
        );
    }
}
