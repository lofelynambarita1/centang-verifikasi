<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_hasils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('content')->nullable();
            $table->string('evidence_path')->nullable();
            $table->timestamps();
            $table->index(['audit_item_id', 'sort_order']);
        });

        DB::table('audit_items')
            ->where(function ($query): void {
                $query->whereNotNull('note')->where('note', '!=', '')
                    ->orWhereNotNull('evidence_path');
            })
            ->orderBy('id')
            ->get(['id', 'note', 'evidence_path'])
            ->each(function (object $item): void {
                DB::table('audit_hasils')->insert([
                    'audit_item_id' => $item->id,
                    'sort_order' => 0,
                    'content' => $item->note,
                    'evidence_path' => $item->evidence_path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_hasils');
    }
};
