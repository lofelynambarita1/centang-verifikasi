<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_indicator_results', function (Blueprint $table): void {
            $table->string('assessment_status')->nullable()->after('result_text');
        });

        Schema::table('audit_indicator_result_versions', function (Blueprint $table): void {
            $table->string('assessment_status')->nullable()->after('result_text');
        });
    }

    public function down(): void
    {
        Schema::table('audit_indicator_result_versions', function (Blueprint $table): void {
            $table->dropColumn('assessment_status');
        });

        Schema::table('audit_indicator_results', function (Blueprint $table): void {
            $table->dropColumn('assessment_status');
        });
    }
};
