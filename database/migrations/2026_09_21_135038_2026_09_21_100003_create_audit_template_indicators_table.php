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
        Schema::create('audit_template_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('audit_template_sections')->cascadeOnDelete();
            $table->string('code');
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('indicator_type')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_template_indicators');
    }
};
