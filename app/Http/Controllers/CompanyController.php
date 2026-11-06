<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\CertificationProcess;
use App\Models\Company;
use App\Notifications\AuditSystemNotification;
use App\Support\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    public function store(StoreCompanyRequest $request, NotificationDispatcher $notifications): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

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
        abort_unless(auth()->user()?->isAdmin(), 403);

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
        abort_unless(auth()->user()?->isAdmin(), 403);
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
        abort_unless(auth()->user()?->isAdmin(), 403);
        $companyName = $company->name;
        $certificationDocumentPaths = $company->certificationProcesses()
            ->get(['summary_document_path', 'audit_correction_document_path', 'certificate_document_path'])
            ->flatMap(fn (CertificationProcess $process) => [
                $process->summary_document_path,
                $process->audit_correction_document_path,
                $process->certificate_document_path,
            ])
            ->filter()
            ->all();
        $company->delete();

        foreach ($certificationDocumentPaths as $path) {
            if (! Storage::disk('local')->delete($path)) {
                Log::warning('Dokumen sertifikasi gagal dihapus setelah perusahaan dihapus.', [
                    'company_id' => $company->id,
                    'path' => $path,
                ]);
            }
        }

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
