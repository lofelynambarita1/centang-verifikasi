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
        if (Schema::hasTable('audits')) {
            Schema::table('audits', function (Blueprint $table): void {
                if (! Schema::hasColumn('audits', 'start_date')) {
                    $table->date('start_date')->nullable()->after('due_date');
                }
                if (! Schema::hasColumn('audits', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (! Schema::hasColumn('audits', 'scope')) {
                    $table->string('scope')->default('Standar ISPO')->after('end_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audits')) {
            Schema::table('audits', function (Blueprint $table): void {
                $columns = [];
                if (Schema::hasColumn('audits', 'start_date')) {
                    $columns[] = 'start_date';
                }
                if (Schema::hasColumn('audits', 'end_date')) {
                    $columns[] = 'end_date';
                }
                if (Schema::hasColumn('audits', 'scope')) {
                    $columns[] = 'scope';
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
