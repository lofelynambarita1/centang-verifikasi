<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('companies.index', [
            'companies' => Company::query()
                ->with('latestAudit')
                ->withCount('audits')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        Company::create($request->validated());

        return redirect()->route('companies.index')->with('success', 'Data perusahaan berhasil disimpan.');
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
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        StoreCompanyRequest $request,
        Company $company,
    ): RedirectResponse {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $company->update($request->validated());

        return redirect()->route('companies.index')->with('success', 'Data perusahaan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Perusahaan dihapus.');
    }
}
