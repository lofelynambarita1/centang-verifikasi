<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrate a development database that already ran the initial audit_projects
     * migration to the revised audit-field structure. Fresh installs already carry
     * the final structure from the create migration, so every step is guarded.
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_projects')) {
            return;
        }

        Schema::table('audit_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_projects', 'project_code')) {
                $table->string('project_code')->unique()->after('id');
            }

            if (! Schema::hasColumn('audit_projects', 'audit_type')) {
                $table->string('audit_type')->nullable();
            }

            if (! Schema::hasColumn('audit_projects', 'audit_start_date')) {
                $table->date('audit_start_date')->nullable();
            }

            if (! Schema::hasColumn('audit_projects', 'audit_end_date')) {
                $table->date('audit_end_date')->nullable();
            }

            if (! Schema::hasColumn('audit_projects', 'land_area')) {
                $table->decimal('land_area', 12, 2)->nullable();
            }

            if (Schema::hasColumn('audit_projects', 'audit_date')) {
                $table->dropColumn('audit_date');
            }
        });

        Schema::table('audit_projects', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('audit_projects') || ! Schema::hasColumn('audit_projects', 'audit_date')) {
            return;
        }

        Schema::table('audit_projects', function (Blueprint $table) {
            $table->dropColumn(['project_code', 'audit_type', 'audit_start_date', 'audit_end_date', 'land_area']);
            $table->date('audit_date');
        });

        Schema::table('audit_projects', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
    }
};
