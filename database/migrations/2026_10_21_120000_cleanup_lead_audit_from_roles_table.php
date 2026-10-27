<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            $auditorRoleId = DB::table('roles')->where('name', 'auditor')->value('id');

            if (Schema::hasTable('users')) {
                DB::table('users')
                    ->where('role', 'lead_audit')
                    ->orWhere('email', 'lead@ispo.com')
                    ->update([
                        'role' => 'auditor',
                        'role_id' => $auditorRoleId,
                    ]);
            }

            DB::table('roles')->where('name', 'lead_audit')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // lead_audit is deprecated as global role
    }
};
