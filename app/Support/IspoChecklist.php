<?php

namespace App\Support;

use App\Models\Audit;

class IspoChecklist
{
    /**
     * Nama prinsip ISPO beserta nomornya.
     *
     * @return array<int, string>
     */
    public static function principles(): array
    {
        return config('ispo.principles', []);
    }

    /**
     * Judul dan deskripsi setiap kriteria ISPO (mis. 1.1, 3.12).
     *
     * @return array<string, array{title: string, description: string}>
     */
    public static function criteria(): array
    {
        return config('ispo.criteria', []);
    }

    /**
     * Indikator ISPO lengkap dengan prinsip, kriteria, dan daftar parameternya.
     *
     * @return array<int, array{code: string, principle: int, criteria: string, title: string, parameters: array<int, string>}>
     */
    public static function indicators(): array
    {
        return config('ispo.indicators', []);
    }

    /**
     * Kode kriteria (mis. "1.1") dari judul indikator (mis. "1.1.1 ...").
     */
    public static function criteriaCode(?string $title): ?string
    {
        if ($title !== null && preg_match('/^(\d+\.\d+)\.\d+/', $title, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Checklist ISPO siap disimpan sebagai item audit.
     *
     * @return array<int, array{title: string, description: ?string}>
     */
    public static function items(): array
    {
        return array_map(function (array $indicator): array {
            $description = implode("\n", $indicator['parameters']);

            return [
                'title' => $indicator['code'].' '.$indicator['title'],
                'description' => $description !== '' ? $description : null,
            ];
        }, static::indicators());
    }

    /**
     * Ganti seluruh item audit dengan checklist ISPO acuan dan atur ulang
     * statusnya supaya auditor cukup mengisi hasil verifikasi.
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
}
