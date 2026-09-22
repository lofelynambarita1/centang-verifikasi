<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_indicator_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audit_project_id')->constrained('audit_projects')->cascadeOnDelete();
            $table->foreignId('audit_project_indicator_id')->constrained('audit_project_indicators')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('audit_project_id');
            $table->index('audit_project_indicator_id');
            $table->index('uploaded_by');
            $table->index('sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_indicator_evidences');
    }
};
