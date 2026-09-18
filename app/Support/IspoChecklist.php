<?php

namespace App\Support;

use App\Models\Audit;
use App\Models\AuditItem;

class IspoChecklist
{
    /**
     * Resolve the fullest ISPO checklist already stored in the app (the
     * reference audit), so every audit shares the same 7 principles.
     */
    public static function referenceAudit(): ?Audit
    {
        return Audit::query()
            ->with('items')
            ->get()
            ->filter(fn (Audit $audit): bool => $audit->items->contains(
                fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1
            ))
            ->sortByDesc(fn (Audit $audit): array => [$audit->items->count(), $audit->updated_at?->timestamp ?? 0])
            ->first();
    }

    /**
     * The canonical ISPO indicator items (title + description only).
     *
     * @return array<int, array{title: string, description: ?string}>
     */
    public static function items(): array
    {
        $reference = static::referenceAudit();

        if ($reference === null) {
            return static::fallbackItems();
        }

        return $reference->items
            ->sortBy('sort_order')
            ->map(fn (AuditItem $item): array => [
                'title' => $item->title,
                'description' => $item->description,
            ])
            ->values()
            ->all();
    }

    /**
     * Replace an audit's items with the canonical ISPO checklist and reset it
     * to an in-progress state so the auditor only needs to fill in statuses.
     */
    public static function replaceItems(Audit $audit): void
    {
        $items = static::items();

        if ($items === []) {
            return;
        }

        $audit->items()->delete();
        $audit->items()->createMany(collect($items)
            ->map(fn (array $item, int $position): array => $item + ['sort_order' => $position, 'status' => 'pending'])
            ->values()
            ->all());
        $audit->update(['status' => 'in_progress']);
    }

    /**
     * Generic fallback used only when no ISPO checklist has been imported yet.
     *
     * @return array<int, array{title: string, description: ?string}>
     */
    private static function fallbackItems(): array
    {
        return [
            ['title' => 'Legalitas dan dokumen perusahaan', 'description' => 'Pastikan dokumen legalitas masih berlaku dan sesuai.'],
            ['title' => 'Struktur organisasi dan tanggung jawab', 'description' => 'Verifikasi pembagian peran dan otorisasi kerja.'],
            ['title' => 'Prosedur operasional standar', 'description' => 'Periksa ketersediaan SOP dan bukti penerapannya.'],
            ['title' => 'Keselamatan dan kesehatan kerja', 'description' => 'Periksa kelengkapan prosedur K3 dan pelatihan.'],
        ];
    }
}
