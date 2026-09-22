<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_indicator_results', function (Blueprint $table): void {
            $table->string('workflow_status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_indicator_results', function (Blueprint $table): void {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['workflow_status', 'submitted_at', 'submitted_by']);
        });
    }
};
