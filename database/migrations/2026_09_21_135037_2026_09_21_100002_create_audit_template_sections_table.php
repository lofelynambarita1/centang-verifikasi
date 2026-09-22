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
        Schema::create('audit_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principle_id')->constrained('audit_template_principles')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('audit_template_sections')->nullOnDelete();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['principle_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_template_sections');
    }
};
