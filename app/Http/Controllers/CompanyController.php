<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Notifications\AuditSystemNotification;
use App\Support\NotificationDispatcher;
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
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request, NotificationDispatcher $notifications): RedirectResponse
    {
        $company = Company::create($request->validated());
        $notifications->broadcast(new AuditSystemNotification(
            'company_created',
            'Perusahaan baru ditambahkan',
            "{$request->user()->name} menambahkan perusahaan {$company->name}.",
            route('companies.show', $company, false),
            sendMail: false,
        ));

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
        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        StoreCompanyRequest $request,
        Company $company,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        $company->update($request->validated());
        $notifications->broadcast(new AuditSystemNotification(
            'company_updated',
            'Data perusahaan diperbarui',
            "{$request->user()->name} memperbarui data {$company->name}.",
            route('companies.show', $company, false),
            sendMail: false,
        ));

        return redirect()->route('companies.index')->with('success', 'Data perusahaan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company, NotificationDispatcher $notifications): RedirectResponse
    {
        $companyName = $company->name;
        $company->delete();
        $notifications->broadcast(new AuditSystemNotification(
            'company_deleted',
            'Data perusahaan dihapus',
            auth()->user()->name." menghapus data perusahaan {$companyName}.",
            route('companies.index', [], false),
            sendMail: false,
        ));

        return redirect()->route('companies.index')->with('success', 'Perusahaan dihapus.');
    }
}
