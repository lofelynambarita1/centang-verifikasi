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
        Schema::create('audit_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_indicator_id')->constrained('audit_project_indicators')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at');

            $table->index('audit_project_id');
            $table->index('audit_project_indicator_id');
            $table->index('to_user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_assignment_histories');
    }
};
