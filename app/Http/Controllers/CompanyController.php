<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Support\IspoChecklist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('companies.index', ['companies' => Company::with('audits.items')->latest()->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = DB::transaction(function () use ($request): Company {
            $company = Company::create($request->safe()->except('due_date'));
            $audit = $company->audits()->create([
                'title' => 'Audit kepatuhan operasional',
                'status' => 'pending',
                'auditor_name' => $request->input('auditor_name'),
                'due_date' => $request->date('due_date'),
            ]);
            $audit->items()->createMany(self::checklistItems());

            return $company;
        });

        $audit = $company->audits()->latest()->firstOrFail();

        return redirect()->route('audits.show', $audit)->with('success', 'Perusahaan dan prinsip audit berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): View
    {
        return view('companies.show', ['company' => $company->load(['audits.items'])]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company): View
    {
        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->safe()->except('due_date'));

        return redirect()->route('companies.show', $company)->with('success', 'Data perusahaan diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Perusahaan dihapus.');
    }

    /**
     * Canonical ISPO checklist items (same 7 principles for every company),
     * ready to be inserted into a new audit.
     *
     * @return array<int, array{title: string, description: ?string, sort_order: int, status: string}>
     */
    private static function checklistItems(): array
    {
        $items = [];

        foreach (IspoChecklist::items() as $position => $item) {
            $items[] = $item + ['sort_order' => $position, 'status' => 'pending'];
        }

        return $items;
    }
}
