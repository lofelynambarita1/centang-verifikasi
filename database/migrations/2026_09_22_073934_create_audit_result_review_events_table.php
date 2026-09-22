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
        Schema::create('audit_result_review_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_indicator_id')->constrained('audit_project_indicators')->cascadeOnDelete();
            $table->foreignId('audit_indicator_result_id')->constrained('audit_indicator_results')->cascadeOnDelete();
            $table->string('action')->index();
            $table->text('note')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('content_lock_version');
            $table->foreignId('audit_indicator_result_version_id')->nullable()->constrained('audit_indicator_result_versions')->nullOnDelete();
            $table->timestamp('created_at');
            $table->index(['audit_project_indicator_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_result_review_events');
    }
};
