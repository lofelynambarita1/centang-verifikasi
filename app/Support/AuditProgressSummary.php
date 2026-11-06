<?php

namespace App\Support;

use App\Models\AuditItem;
use Illuminate\Support\Collection;

class AuditProgressSummary
{
    /**
     * @param  Collection<int, AuditItem>  $items
     * @return array{total: int, verified: int, percentage: int, compliant: int, non_compliant: int, not_applicable: int, pending: int}
     */
    public static function forItems(Collection $items): array
    {
        $compliant = $items->where('status', 'compliant')->count();
        $nonCompliant = $items->where('status', 'non_compliant')->count();
        $notApplicable = $items->where('status', 'not_applicable')->count();
        $pending = $items->where('status', 'pending')->count();
        $total = $items->count();
        $verified = $compliant + $nonCompliant + $notApplicable;

        return [
            'total' => $total,
            'verified' => $verified,
            'percentage' => $total > 0 ? (int) round($verified / $total * 100) : 0,
            'compliant' => $compliant,
            'non_compliant' => $nonCompliant,
            'not_applicable' => $notApplicable,
            'pending' => $pending,
        ];
    }

    /**
     * @param  Collection<int, AuditItem>  $items
     * @param  array<int, int>  $principleNumbers
     * @return array<int, array{principle: int, total: int, verified: int, percentage: int}>
     */
    public static function byPrinciple(Collection $items, array $principleNumbers): array
    {
        $progress = [];

        foreach (array_unique($principleNumbers) as $principleNumber) {
            $principleItems = $items->filter(
                fn (AuditItem $item): bool => $item->principleNumber() === $principleNumber,
            );
            $summary = self::forItems($principleItems);

            $progress[$principleNumber] = [
                'principle' => $principleNumber,
                'total' => $summary['total'],
                'verified' => $summary['verified'],
                'percentage' => $summary['percentage'],
            ];
        }

        return $progress;
    }
}
