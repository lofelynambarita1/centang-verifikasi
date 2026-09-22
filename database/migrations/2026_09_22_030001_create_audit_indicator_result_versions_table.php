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
        Schema::create('audit_indicator_result_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_indicator_result_id')->constrained('audit_indicator_results')->cascadeOnDelete();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_indicator_id')->constrained('audit_project_indicators')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('result_text');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->unique(['audit_indicator_result_id', 'version_number'], 'uk_result_version_number');
            $table->index('audit_project_id');
            $table->index('audit_project_indicator_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_indicator_result_versions');
    }
};
