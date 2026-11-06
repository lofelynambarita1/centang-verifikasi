<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Company;
use App\Models\User;
use App\Support\AuditProgressSummary;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $viewer = request()->user();
        $canViewAllProgress = $viewer->isAdmin() || $viewer->isLeadAuditor();
        $search = trim((string) request()->query('search', ''));

        $companies = Company::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($companyQuery) use ($search): void {
                    $companyQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('industry', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->with(['audits' => fn ($query) => $query->latest()->with([
                'company:id,name',
                'items:id,audit_id,title,status',
                'leadMember.user:id,name,role,requested_role',
                'members.user:id,name,role,requested_role',
                'assignees:id,name',
            ])])
            ->latest()
            ->get();

        $audits = $companies->flatMap->audits->values();
        if (! $canViewAllProgress) {
            $audits = $audits
                ->filter(fn (Audit $audit): bool => $audit->assignees->contains(
                    fn (User $assignee): bool => $assignee->is($viewer),
                ))
                ->values();
        }

        $activeAudits = $audits->whereIn('status', ['pending', 'in_progress'])->values();
        $dashboardProgress = $audits->mapWithKeys(function (Audit $audit) use ($canViewAllProgress, $viewer): array {
            $assignedPrinciples = $audit->assignees
                ->filter(fn (User $assignee): bool => $assignee->is($viewer))
                ->map(fn (User $assignee): int => (int) $assignee->pivot->principle_number)
                ->filter(fn (int $number): bool => $number > 0)
                ->unique()
                ->values()
                ->all();
            $progressItems = $canViewAllProgress
                ? $audit->items
                : $audit->items
                    ->filter(fn (AuditItem $item): bool => in_array($item->principleNumber(), $assignedPrinciples, true))
                    ->values();
            $isIspoChecklist = $audit->items->contains(
                fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1,
            );
            $principleNumbers = $isIspoChecklist
                ? ($canViewAllProgress ? range(1, 7) : $assignedPrinciples)
                : [];

            return [$audit->id => [
                'items' => $progressItems,
                'summary' => AuditProgressSummary::forItems($progressItems),
                'principles' => AuditProgressSummary::byPrinciple($audit->items, $principleNumbers),
            ]];
        });
        $items = $dashboardProgress->flatMap(fn (array $progress): mixed => $progress['items']);
        $totalItems = $items->count();
        $verifiedItems = $items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();
        $metItems = $items->where('status', 'compliant')->count();
        $notMetItems = $items->where('status', 'non_compliant')->count();
        $notApplicableItems = $items->where('status', 'not_applicable')->count();
        $pendingItems = $items->where('status', 'pending')->count();

        return view('welcome', [
            'companies' => $companies,
            'activeAudits' => $activeAudits,
            'dashboardProgress' => $dashboardProgress,
            'canViewAllProgress' => $canViewAllProgress,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'companies' => $canViewAllProgress
                    ? $companies->count()
                    : $audits->pluck('company_id')->unique()->count(),
                'in_progress' => $audits->where('status', 'in_progress')->count(),
                'completed' => $audits->where('status', 'completed')->count(),
                'met' => $items->where('status', 'compliant')->count(),
                'not_met' => $items->where('status', 'non_compliant')->count(),
                'completed_items' => $verifiedItems,
                'total_items' => $totalItems,
                'completion_percentage' => $totalItems > 0 ? round($verifiedItems / $totalItems * 100) : 0,
                'pending_items' => $pendingItems,
                'not_applicable_items' => $notApplicableItems,
                'met_items' => $metItems,
                'not_met_items' => $notMetItems,
            ],
        ]);
    }
}
