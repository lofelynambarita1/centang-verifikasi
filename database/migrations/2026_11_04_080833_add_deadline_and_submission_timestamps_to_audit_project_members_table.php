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
        Schema::table('audit_project_members', function (Blueprint $table) {
            $table->timestamp('deadline_at')->nullable()->after('added_by');
            $table->timestamp('first_submitted_at')->nullable()->after('deadline_at');
            $table->timestamp('last_submitted_at')->nullable()->after('first_submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_project_members', function (Blueprint $table) {
            $table->dropColumn(['deadline_at', 'first_submitted_at', 'last_submitted_at']);
        });
    }
};
