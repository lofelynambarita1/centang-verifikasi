<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_projects', function (Blueprint $table): void {
            $table->text('assessment_summary')->nullable()->after('land_area');
        });
    }

    public function down(): void
    {
        Schema::table('audit_projects', function (Blueprint $table): void {
            $table->dropColumn('assessment_summary');
        });
    }
};
