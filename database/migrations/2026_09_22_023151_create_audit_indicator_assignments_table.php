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
        Schema::create('audit_indicator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_indicator_id')->constrained('audit_project_indicators')->cascadeOnDelete();
            $table->foreignId('pic_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['audit_project_id', 'audit_project_indicator_id'], 'uk_assignment_project_indicator');
            $table->index('audit_project_id');
            $table->index('pic_user_id');
            $table->index('audit_project_indicator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_indicator_assignments');
    }
};
