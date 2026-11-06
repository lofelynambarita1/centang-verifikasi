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
        Schema::create('certification_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('summary_document_path')->nullable();
            $table->string('audit_correction_document_path')->nullable();
            $table->longText('audit_report')->nullable();
            $table->longText('review_panel')->nullable();
            $table->string('certificate_document_path')->nullable();
            $table->uuid('certificate_token')->nullable()->unique();
            $table->unique(['company_id', 'category']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certification_processes');
    }
};
