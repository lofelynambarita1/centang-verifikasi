<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
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
                'auditor_name' => 'Arif Rahman',
                'due_date' => $request->date('due_date'),
            ]);

            foreach (self::defaultChecklist() as $position => $item) {
                $audit->items()->create($item + ['sort_order' => $position]);
            }

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

    private static function defaultChecklist(): array
    {
        return [
            ['title' => 'Legalitas dan dokumen perusahaan', 'description' => 'Pastikan dokumen legalitas masih berlaku dan sesuai.'],
            ['title' => 'Struktur organisasi dan tanggung jawab', 'description' => 'Verifikasi pembagian peran dan otorisasi kerja.'],
            ['title' => 'Prosedur operasional standar', 'description' => 'Periksa ketersediaan SOP dan bukti penerapannya.'],
            ['title' => 'Keselamatan dan kesehatan kerja', 'description' => 'Periksa kelengkapan prosedur K3 dan pelatihan.'],
        ];
    }
}
