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
        if (! Schema::hasTable('audit_project_members')) {
            Schema::create('audit_project_members', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
                $table->unsignedBigInteger('audit_project_id')->nullable()->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('project_role');
                $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['audit_id', 'user_id']);
                $table->index(['audit_id', 'project_role']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_project_members');
    }
};
