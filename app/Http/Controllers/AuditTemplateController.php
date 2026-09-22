<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditTemplateRequest;
use App\Http\Requests\UpdateAuditTemplateRequest;
use App\Models\AuditTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuditTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', AuditTemplate::class);

        return view('templates.index', ['templates' => AuditTemplate::withCount('projects')->with('creator')->latest()->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', AuditTemplate::class);

        return view('templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuditTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', AuditTemplate::class);

        AuditTemplate::create($request->validated() + ['created_by' => $request->user()->id]);

        return redirect()->route('templates.index')->with('success', 'Template audit berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AuditTemplate $template): View
    {
        $this->authorize('update', $template);

        return view('templates.edit', ['template' => $template]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuditTemplateRequest $request, AuditTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->update($request->validated());

        return redirect()->route('templates.index')->with('success', 'Template audit diperbarui.');
    }
}
