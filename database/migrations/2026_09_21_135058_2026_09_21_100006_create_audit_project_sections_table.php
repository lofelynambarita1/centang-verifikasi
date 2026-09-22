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
        Schema::create('audit_project_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_principle_id')->constrained('audit_project_principles')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('audit_project_sections')->nullOnDelete();
            $table->foreignId('source_template_section_id')->nullable()->constrained('audit_template_sections')->nullOnDelete();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_project_sections');
    }
};
