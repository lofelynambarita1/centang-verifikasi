<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['label' => 'Admin', 'description' => 'Administrator sistem dengan akses penuh']
        );

        $auditorRole = Role::firstOrCreate(
            ['name' => 'auditor'],
            ['label' => 'Auditor', 'description' => 'Auditor verifikasi dokumen dan lapangan']
        );

        // Bersihkan sisa pengguna yang masih memegang role lead_audit menjadi auditor
        User::where('role', 'lead_audit')->update([
            'role' => 'auditor',
            'role_id' => $auditorRole->id,
        ]);

        // Akun default: Admin
        User::updateOrCreate(
            ['email' => 'admin@ispo.com'],
            [
                'name' => 'Admin ISPO',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]
        );

        // Akun default: lead@ispo.com (diubah/ditetapkan memiliki role global auditor)
        User::updateOrCreate(
            ['email' => 'lead@ispo.com'],
            [
                'name' => 'Lead Auditor ISPO',
                'password' => Hash::make('password'),
                'role' => 'auditor',
                'role_id' => $auditorRole->id,
                'email_verified_at' => now(),
            ]
        );

        // Akun default: Auditor 1 (Budi)
        User::updateOrCreate(
            ['email' => 'budi@ispo.com'],
            [
                'name' => 'Budi Auditor',
                'password' => Hash::make('password'),
                'role' => 'auditor',
                'role_id' => $auditorRole->id,
                'email_verified_at' => now(),
            ]
        );

        // Akun default: Auditor 2 (Ani)
        User::updateOrCreate(
            ['email' => 'ani@ispo.com'],
            [
                'name' => 'Ani Auditor',
                'password' => Hash::make('password'),
                'role' => 'auditor',
                'role_id' => $auditorRole->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
