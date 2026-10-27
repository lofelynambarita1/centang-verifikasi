<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('audit_project_members')) {
            // 1. Pastikan kolom audit_id ada
            if (! Schema::hasColumn('audit_project_members', 'audit_id')) {
                Schema::table('audit_project_members', function (Blueprint $table): void {
                    $table->unsignedBigInteger('audit_id')->nullable()->after('id');
                });

                // Sinkronkan data dari audit_project_id atau project_id jika ada
                if (Schema::hasColumn('audit_project_members', 'audit_project_id')) {
                    DB::table('audit_project_members')
                        ->whereNull('audit_id')
                        ->update(['audit_id' => DB::raw('audit_project_id')]);
                } elseif (Schema::hasColumn('audit_project_members', 'project_id')) {
                    DB::table('audit_project_members')
                        ->whereNull('audit_id')
                        ->update(['audit_id' => DB::raw('project_id')]);
                }
            }

            // 2. Pastikan kolom audit_project_id juga ada untuk kompatibilitas backward
            if (! Schema::hasColumn('audit_project_members', 'audit_project_id')) {
                Schema::table('audit_project_members', function (Blueprint $table): void {
                    $table->unsignedBigInteger('audit_project_id')->nullable()->after('audit_id');
                });

                DB::table('audit_project_members')
                    ->whereNull('audit_project_id')
                    ->update(['audit_project_id' => DB::raw('audit_id')]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed
    }
};
