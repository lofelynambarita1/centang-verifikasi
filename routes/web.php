<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'create'])->name('login');
Route::post('login', [AuthController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('companies', CompanyController::class);
    Route::get('audits/assignments', [AuditController::class, 'assignmentsIndex'])
        ->name('audits.assignments.index');
    Route::get('audits/{audit}/assignments', [AuditController::class, 'assignmentsEdit'])
        ->name('audits.assignments.edit');

    Route::middleware('role:admin')->group(function (): void {
        Route::get('audits/create', [AuditController::class, 'create'])->name('audits.create');
        Route::post('audits', [AuditController::class, 'store'])->name('audits.store');
    });
    Route::middleware('role:admin,auditor')->group(function (): void {
        Route::get('audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
        Route::put('audit-items/{auditItem}', [AuditController::class, 'updateItem'])->name('audit-items.update');
        Route::post('audit-items/{auditItem}/evidence', [AuditController::class, 'storeEvidence'])->name('audit-items.evidence');
        Route::post('audit-items/{auditItem}/hasil', [AuditController::class, 'storeHasil'])->name('audit-items.hasil.store');
        Route::put('audit-hasils/{auditHasil}', [AuditController::class, 'updateHasil'])->name('audit-hasils.update');
        Route::delete('audit-hasils/{auditHasil}', [AuditController::class, 'destroyHasil'])->name('audit-hasils.destroy');
        Route::get('audits/{audit}/report', [AuditController::class, 'report'])->name('audits.report');
        Route::get('audits/{audit}/pdf', [AuditController::class, 'pdf'])->name('audits.pdf');
        Route::get('audits/{audit}/download', [AuditController::class, 'download'])->name('audits.download');
    });

    Route::post('audits/{audit}/complete', [AuditController::class, 'complete'])
        ->name('audits.complete');

    Route::put('audits/{audit}/assignments', [AuditController::class, 'assignPrinciples'])
        ->name('audits.assignments.update');
    Route::post('users/{user}/approve-role', [UserController::class, 'approveRoleRequest'])
        ->middleware('role:admin')
        ->name('users.approve-role');
    Route::resource('users', UserController::class)->except('show')->middleware('role:admin');
});
