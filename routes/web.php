<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditEvidenceController;
use App\Http\Controllers\AuditProjectAssignmentController;
use App\Http\Controllers\AuditProjectController;
use App\Http\Controllers\AuditProjectMemberController;
use App\Http\Controllers\AuditProjectStructureController;
use App\Http\Controllers\AuditTemplateController;
use App\Http\Controllers\AuditWorkspaceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemplateStructureController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    /** Legacy verification workflow (kept intact, now behind authentication). */
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('companies', CompanyController::class);
    Route::get('audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
    Route::put('audit-items/{auditItem}', [AuditController::class, 'updateItem'])->name('audit-items.update');
    Route::post('audit-items/{auditItem}/evidence', [AuditController::class, 'storeEvidence'])->name('audit-items.evidence');
    Route::post('audit-items/{auditItem}/hasil', [AuditController::class, 'storeHasil'])->name('audit-items.hasil.store');
    Route::put('audit-hasils/{auditHasil}', [AuditController::class, 'updateHasil'])->name('audit-hasils.update');
    Route::delete('audit-hasils/{auditHasil}', [AuditController::class, 'destroyHasil'])->name('audit-hasils.destroy');
    Route::post('audits/{audit}/complete', [AuditController::class, 'complete'])->name('audits.complete');
    Route::get('audits/{audit}/report', [AuditController::class, 'report'])->name('audits.report');
    Route::get('audits/{audit}/pdf', [AuditController::class, 'pdf'])->name('audits.pdf');
    Route::get('audits/{audit}/download', [AuditController::class, 'download'])->name('audits.download');

    /** Collaborative audit project foundation. */
    Route::get('audit-projects/create', [AuditProjectController::class, 'create'])
        ->middleware('can:create,App\Models\AuditProject')
        ->name('audit-projects.create');
    Route::post('audit-projects', [AuditProjectController::class, 'store'])
        ->middleware('can:create,App\Models\AuditProject')
        ->name('audit-projects.store');
    Route::get('audit-projects/{auditProject}/edit', [AuditProjectController::class, 'edit'])
        ->middleware('can:update,auditProject')
        ->name('audit-projects.edit');
    Route::put('audit-projects/{auditProject}', [AuditProjectController::class, 'update'])
        ->middleware('can:update,auditProject')
        ->name('audit-projects.update');

    Route::get('audit-projects/{auditProject}/team', [AuditProjectMemberController::class, 'index'])
        ->middleware('can:view,auditProject')
        ->name('audit-projects.team');
    Route::post('audit-projects/{auditProject}/members', [AuditProjectMemberController::class, 'store'])
        ->middleware('can:manageMembers,auditProject')
        ->name('audit-projects.members.store');
    Route::put('audit-projects/{auditProject}/lead-auditor', [AuditProjectMemberController::class, 'replaceLead'])
        ->middleware('can:manageMembers,auditProject')
        ->name('audit-projects.lead-auditor.replace');
    Route::delete('audit-projects/{auditProject}/members/{member}', [AuditProjectMemberController::class, 'destroy'])
        ->middleware('can:manageMembers,auditProject')
        ->name('audit-projects.members.destroy');

    Route::resource('audit-projects', AuditProjectController::class)->only(['index', 'show']);
    Route::get('audit-projects/{auditProject}/progress', [AuditProjectController::class, 'progress'])
        ->middleware('can:viewProgress,auditProject')
        ->name('audit-projects.progress');

    /** Master templates. */
    Route::get('templates', [AuditTemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create', [AuditTemplateController::class, 'create'])
        ->middleware('can:create,App\Models\AuditTemplate')
        ->name('templates.create');
    Route::post('templates', [AuditTemplateController::class, 'store'])
        ->middleware('can:create,App\Models\AuditTemplate')
        ->name('templates.store');
    Route::get('templates/{template}/edit', [AuditTemplateController::class, 'edit'])
        ->middleware('can:update,template')
        ->name('templates.edit');
    Route::put('templates/{template}', [AuditTemplateController::class, 'update'])
        ->middleware('can:update,template')
        ->name('templates.update');

    /** Master template structure (Principle → Section → Indicator → Parameter). */
    Route::prefix('templates/{template}/structure')->name('templates.structure')->group(function (): void {
        Route::get('/', [TemplateStructureController::class, 'index'])->name('.index');
        Route::get('{type}/create', [TemplateStructureController::class, 'create'])
            ->middleware('can:update,template')->name('.create');
        Route::post('{type}', [TemplateStructureController::class, 'store'])
            ->middleware('can:update,template')->name('.store');
        Route::get('{type}/{id}/edit', [TemplateStructureController::class, 'edit'])
            ->middleware('can:update,template')->name('.edit');
        Route::put('{type}/{id}', [TemplateStructureController::class, 'update'])
            ->middleware('can:update,template')->name('.update');
        Route::delete('{type}/{id}', [TemplateStructureController::class, 'destroy'])
            ->middleware('can:update,template')->name('.destroy');
        Route::put('{type}/{id}/move', [TemplateStructureController::class, 'move'])
            ->middleware('can:update,template')->name('.move');
    });

    /** Read-only project snapshot structure. */
    Route::get('audit-projects/{auditProject}/structure', [AuditProjectStructureController::class, 'show'])
        ->middleware('can:view,auditProject')
        ->name('audit-projects.structure');

    /** Indicator assignment (Pembagian Auditor). */
    Route::get('audit-projects/{auditProject}/assignments', [AuditProjectAssignmentController::class, 'index'])
        ->middleware('can:view,auditProject')
        ->name('audit-projects.assignments');
    Route::post('audit-projects/{auditProject}/assignments/indicator/{indicator}', [AuditProjectAssignmentController::class, 'store'])
        ->middleware('can:manageAssignments,auditProject')
        ->name('audit-projects.assignments.store');
    Route::post('audit-projects/{auditProject}/assignments/bulk', [AuditProjectAssignmentController::class, 'bulk'])
        ->middleware('can:manageAssignments,auditProject')
        ->name('audit-projects.assignments.bulk');
    Route::post('audit-projects/{auditProject}/assignments/auto', [AuditProjectAssignmentController::class, 'autoAssign'])
        ->middleware('can:manageAssignments,auditProject')
        ->name('audit-projects.assignments.auto');

    /** Auditor's own indicators. */
    Route::get('audit-projects/{auditProject}/my-work', [AuditProjectAssignmentController::class, 'myWork'])
        ->middleware('can:view,auditProject')
        ->name('audit-projects.my-work');
    Route::get('audit-projects/{auditProject}/indicators/{indicator}', [AuditProjectAssignmentController::class, 'showIndicator'])
        ->middleware('can:view,auditProject')
        ->name('audit-projects.indicators.show');

    /** Auditor workspace + Hasil Audit draft (Tahap 4A). */
    Route::get('audit-projects/{auditProject}/work/{indicator}', [AuditWorkspaceController::class, 'show'])
        ->middleware('can:view,indicator')
        ->scopeBindings()
        ->name('audit-projects.work.show');
    Route::put('audit-projects/{auditProject}/work/{indicator}/draft', [AuditWorkspaceController::class, 'saveDraft'])
        ->middleware('can:update,indicator')
        ->scopeBindings()
        ->name('audit-projects.work.save-draft');
    Route::put('audit-projects/{auditProject}/work/{indicator}/autosave', [AuditWorkspaceController::class, 'autosave'])
        ->middleware('can:update,indicator')
        ->scopeBindings()
        ->name('audit-projects.work.autosave');
    Route::post('audit-projects/{auditProject}/work/{indicator}/evidences', [AuditEvidenceController::class, 'store'])
        ->middleware('can:update,indicator')
        ->scopeBindings()
        ->name('audit-projects.evidences.store');
    Route::get('audit-projects/{auditProject}/work/{indicator}/evidences/{evidence}/download', [AuditEvidenceController::class, 'download'])
        ->middleware('can:view,evidence')
        ->scopeBindings()
        ->name('audit-projects.evidences.download');
    Route::patch('audit-projects/{auditProject}/work/{indicator}/evidences/order', [AuditEvidenceController::class, 'reorder'])
        ->middleware('can:update,indicator')
        ->scopeBindings()
        ->name('audit-projects.evidences.order');
    Route::patch('audit-projects/{auditProject}/work/{indicator}/evidences/{evidence}', [AuditEvidenceController::class, 'update'])
        ->middleware('can:update,evidence')
        ->scopeBindings()
        ->name('audit-projects.evidences.update');
});
