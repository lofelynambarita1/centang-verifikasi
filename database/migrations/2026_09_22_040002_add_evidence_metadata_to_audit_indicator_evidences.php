<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_indicator_evidences', function (Blueprint $table): void {
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_report_included')->default(false);
        });

        DB::table('audit_indicator_evidences')
            ->select('audit_project_indicator_id')
            ->distinct()
            ->orderBy('audit_project_indicator_id')
            ->pluck('audit_project_indicator_id')
            ->each(function (int $indicatorId): void {
                DB::table('audit_indicator_evidences')
                    ->where('audit_project_indicator_id', $indicatorId)
                    ->orderBy('id')
                    ->get(['id'])
                    ->each(function (object $evidence, int $index): void {
                        DB::table('audit_indicator_evidences')
                            ->where('id', $evidence->id)
                            ->update(['sort_order' => ($index + 1) * 10]);
                    });
            });
    }

    public function down(): void
    {
        Schema::table('audit_indicator_evidences', function (Blueprint $table): void {
            $table->dropColumn(['caption', 'sort_order', 'is_report_included']);
        });
    }
};
