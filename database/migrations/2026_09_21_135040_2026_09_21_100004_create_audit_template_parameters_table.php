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
        Schema::create('audit_template_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('audit_template_indicators')->cascadeOnDelete();
            $table->string('parameter_type');
            $table->string('number')->nullable();
            $table->text('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_template_parameters');
    }
};
