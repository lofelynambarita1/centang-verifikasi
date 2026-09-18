<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Company;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companies = Company::query()->with(['audits' => fn ($query) => $query->latest()->with('items')])->latest()->get();

        $audits = Audit::query()->with(['company', 'items'])->latest()->get();
        $items = $audits->flatMap->items;
        $companyRows = $companies->map(function (Company $company): array {
            $audit = $company->audits->first();
            $totalItems = $audit?->items->count() ?? 0;
            $verifiedItems = $audit?->items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count() ?? 0;

            return [
                'company' => $company,
                'audit' => $audit,
                'total_items' => $totalItems,
                'verified_items' => $verifiedItems,
                'progress' => $totalItems ? round($verifiedItems / $totalItems * 100) : 0,
            ];
        });
        $totalItems = $items->count();
        $verifiedItems = $items->whereIn('status', ['compliant', 'non_compliant', 'not_applicable'])->count();

        return view('welcome', [
            'companies' => $companies,
            'companyRows' => $companyRows,
            'audits' => $audits,
            'stats' => [
                'companies' => $companies->count(),
                'average_progress' => $companies->count() ? round($companyRows->avg('progress')) : 0,
                'completed' => $audits->where('status', 'completed')->count(),
                'met' => $items->where('status', 'compliant')->count(),
                'not_met' => $items->where('status', 'non_compliant')->count(),
                'completed_items' => $verifiedItems,
                'total_items' => $totalItems,
            ],
        ]);
    }
}
