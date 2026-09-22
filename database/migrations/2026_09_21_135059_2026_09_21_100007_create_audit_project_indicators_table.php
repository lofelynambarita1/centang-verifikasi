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
        Schema::create('audit_project_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_section_id')->constrained('audit_project_sections')->cascadeOnDelete();
            $table->foreignId('source_template_indicator_id')->nullable()->constrained('audit_template_indicators')->nullOnDelete();
            $table->string('code');
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('indicator_type')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_project_indicators');
    }
};
