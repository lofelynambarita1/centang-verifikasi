<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_indicator_evidences', function (Blueprint $table): void {
            $table->string('lifecycle_status')->default('active')->index();
            $table->foreignId('replaces_evidence_id')
                ->nullable()
                ->constrained('audit_indicator_evidences')
                ->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('removal_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('audit_indicator_evidences', function (Blueprint $table): void {
            $table->dropForeign(['replaces_evidence_id']);
            $table->dropForeign(['removed_by']);
            $table->dropColumn([
                'lifecycle_status',
                'replaces_evidence_id',
                'superseded_at',
                'removed_at',
                'removed_by',
                'removal_reason',
            ]);
        });
    }
};
