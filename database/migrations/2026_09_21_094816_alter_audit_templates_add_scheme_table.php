<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the scheme column to audit_templates. Guarded so it is a no-op for
     * fresh installs that already include the column.
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_templates') || Schema::hasColumn('audit_templates', 'scheme')) {
            return;
        }

        Schema::table('audit_templates', function (Blueprint $table) {
            $table->string('scheme')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('audit_templates') || ! Schema::hasColumn('audit_templates', 'scheme')) {
            return;
        }

        Schema::table('audit_templates', function (Blueprint $table) {
            $table->dropColumn('scheme');
        });
    }
};
