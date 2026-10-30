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
        return view('companies.index', ['companies' => Company::withCount('audits')->latest()->get()]);
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
<<<<<<< Updated upstream
        Company::create($request->validated());
=======
        abort_unless(auth()->user()?->isAdmin(), 403);

        $company = Company::create($request->validated());
        $notifications->broadcast(new AuditSystemNotification(
            'company_created',
            'Perusahaan baru ditambahkan',
            "{$request->user()->name} menambahkan perusahaan {$company->name}.",
            route('companies.show', $company, false),
            sendMail: false,
        ));
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
    public function update(StoreCompanyRequest $request, Company $company): RedirectResponse
    {
=======
    public function update(
        StoreCompanyRequest $request,
        Company $company,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        abort_unless(auth()->user()?->isAdmin(), 403);

>>>>>>> Stashed changes
        $company->update($request->validated());

        return redirect()->route('companies.index')->with('success', 'Data perusahaan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): RedirectResponse
    {
<<<<<<< Updated upstream
=======
        abort_unless(auth()->user()?->isAdmin(), 403);

        $companyName = $company->name;
>>>>>>> Stashed changes
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Perusahaan dihapus.');
    }
}
