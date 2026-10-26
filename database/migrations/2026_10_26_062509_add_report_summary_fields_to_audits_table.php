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
        Schema::table('audits', function (Blueprint $table) {
            $table->text('report_summary_paragraph_one')->nullable()->after('due_date');
            $table->text('report_summary_paragraph_two')->nullable()->after('report_summary_paragraph_one');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['report_summary_paragraph_one', 'report_summary_paragraph_two']);
        });
    }
};
