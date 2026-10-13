<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_indicator_results', function (Blueprint $table) {
            $table->json('result_content')->nullable()->after('result_text');
        });

        Schema::table('audit_indicator_result_versions', function (Blueprint $table) {
            $table->json('result_content')->nullable()->after('result_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_indicator_result_versions', function (Blueprint $table) {
            $table->dropColumn('result_content');
        });

        Schema::table('audit_indicator_results', function (Blueprint $table) {
            $table->dropColumn('result_content');
        });
    }
};
