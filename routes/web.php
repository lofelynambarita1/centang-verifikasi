<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::resource('companies', CompanyController::class);
Route::get('audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
Route::put('audit-items/{auditItem}', [AuditController::class, 'updateItem'])->name('audit-items.update');
Route::post('audit-items/{auditItem}/evidence', [AuditController::class, 'storeEvidence'])->name('audit-items.evidence');
Route::post('audits/{audit}/complete', [AuditController::class, 'complete'])->name('audits.complete');
Route::get('audits/{audit}/report', [AuditController::class, 'report'])->name('audits.report');
Route::get('audits/{audit}/download', [AuditController::class, 'download'])->name('audits.download');
