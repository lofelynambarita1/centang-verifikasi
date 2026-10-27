<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
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
            ])])
            ->latest()
            ->get();

        $audits = $companies->flatMap->audits->values();
        $activeAudits = $audits->whereIn('status', ['pending', 'in_progress'])->values();
        $items = $audits->flatMap->items;
        $totalItems = $items->count();
        $verifiedItems = $items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();

        return view('welcome', [
            'companies' => $companies,
            'activeAudits' => $activeAudits,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'companies' => $companies->count(),
                'in_progress' => $audits->where('status', 'in_progress')->count(),
                'completed' => $audits->where('status', 'completed')->count(),
                'met' => $items->where('status', 'compliant')->count(),
                'not_met' => $items->where('status', 'non_compliant')->count(),
                'completed_items' => $verifiedItems,
                'total_items' => $totalItems,
            ],
        ]);
    }
}
