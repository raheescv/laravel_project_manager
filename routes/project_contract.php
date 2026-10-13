<?php

use App\Http\Controllers\ProjectContract\ContractInsightController;
use App\Http\Controllers\ProjectContract\ContractPaymentController;
use App\Http\Controllers\ProjectContract\ContractRecordController;
use App\Http\Controllers\ProjectContract\ContractSecurityController;
use App\Http\Controllers\ProjectContract\ProjectContractController;
use App\Http\Controllers\VendorController;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

// Agreement Module: project contracts with vendors. One Vue app serves every
// screen; the JSON endpoints live under api/.
Route::middleware(['auth', EnsureModuleEnabled::class.':'.ModuleAccess::AGREEMENT])->group(function (): void {
    Route::name('project-contract::')->prefix('project-contract')->group(function (): void {
        Route::controller(ProjectContractController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('project contract.view');
            Route::get('create', 'create')->name('create')->can('project contract.create');
            Route::get('edit/{id}', 'edit')->whereNumber('id')->name('edit')->can('project contract.edit');
            Route::get('view/{id}', 'view')->whereNumber('id')->name('view')->can('project contract.view');
            Route::get('reports', fn () => redirect()->route('project-contract::report::pending'))->name('reports')->can('project contract.report');
            Route::get('reports/pending', 'pendingReport')->name('report::pending')->can('project contract.report');
            Route::get('reports/paid', 'paidReport')->name('report::paid')->can('project contract.report');
        });

        Route::name('vendor::')->prefix('vendors')->controller(VendorController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('vendor.view');
        });

        Route::name('api::')->prefix('api')->group(function (): void {
            Route::controller(ProjectContractController::class)->group(function (): void {
                Route::get('options', 'options')->name('options')->can('project contract.view');
                Route::get('contracts', 'list')->name('list')->can('project contract.view');
                Route::post('contracts', 'store')->name('store')->can('project contract.create');
                Route::get('contracts/{id}', 'show')->whereNumber('id')->name('show')->can('project contract.view');
                Route::put('contracts/{id}', 'update')->whereNumber('id')->name('update')->can('project contract.edit');
                Route::delete('contracts/{id}', 'destroy')->whereNumber('id')->name('destroy')->can('project contract.delete');
                Route::post('contracts/{id}/submit', 'submit')->whereNumber('id')->name('submit')->can('project contract.edit');
                Route::post('contracts/{id}/status', 'status')->whereNumber('id')->name('status')->can('project contract.edit');
                Route::post('approvals/decide', 'decide')->name('decide')->can('project contract.approve');
            });

            Route::controller(ContractPaymentController::class)->group(function (): void {
                Route::post('contracts/{id}/terms', 'storeTerm')->whereNumber('id')->name('term.store')->can('project contract.term');
                Route::put('contracts/{id}/terms/{termId}', 'updateTerm')->whereNumber(['id', 'termId'])->name('term.update')->can('project contract.term');
                Route::delete('contracts/{id}/terms/{termId}', 'destroyTerm')->whereNumber(['id', 'termId'])->name('term.destroy')->can('project contract.term');
                Route::post('contracts/{id}/payments', 'storePayment')->whereNumber('id')->name('payment.store')->can('project contract.payment');
                Route::post('contracts/{id}/payments/{paymentId}/reverse', 'reversePayment')->whereNumber(['id', 'paymentId'])->name('payment.reverse')->can('project contract.payment reverse');
            });

            Route::controller(ContractSecurityController::class)->group(function (): void {
                Route::post('contracts/{id}/securities', 'store')->whereNumber('id')->name('security.store')->can('project contract.security');
                Route::delete('contracts/{id}/securities/{securityId}', 'destroy')->whereNumber(['id', 'securityId'])->name('security.destroy')->can('project contract.security');
                Route::post('contracts/{id}/securities/{securityId}/settle', 'settle')->whereNumber(['id', 'securityId'])->name('security.settle')->can('project contract.security');
            });

            Route::controller(ContractRecordController::class)->group(function (): void {
                Route::post('contracts/{id}/extensions', 'extend')->whereNumber('id')->name('extension.store')->can('project contract.extend');
                Route::post('contracts/{id}/documents', 'storeDocuments')->whereNumber('id')->name('document.store')->can('project contract.document');
                Route::delete('contracts/{id}/documents/{documentId}', 'destroyDocument')->whereNumber(['id', 'documentId'])->name('document.destroy')->can('project contract.document');
                Route::post('contracts/{id}/notes', 'storeNote')->whereNumber('id')->name('note.store')->can('project contract.note');
                Route::put('contracts/{id}/notes/{noteId}', 'updateNote')->whereNumber(['id', 'noteId'])->name('note.update')->can('project contract.note');
                Route::delete('contracts/{id}/notes/{noteId}', 'destroyNote')->whereNumber(['id', 'noteId'])->name('note.destroy')->can('project contract.note');
            });

            Route::controller(ContractInsightController::class)->group(function (): void {
                Route::get('reports/pending', 'pending')->name('report.pending')->can('project contract.report');
                Route::get('reports/paid', 'paid')->name('report.paid')->can('project contract.report');
                Route::get('reports/{report}/export', 'export')->whereIn('report', ['pending', 'paid'])->name('report.export')->can('project contract.report');
            });

            Route::name('vendor::')->prefix('vendors')->controller(VendorController::class)->group(function (): void {
                Route::get('', 'list')->name('list')->can('vendor.view');
                Route::get('export', 'export')->name('export')->can('vendor.export');
                Route::post('delete', 'destroyMany')->name('destroy-many')->can('vendor.delete');
                Route::get('search', 'search')->name('search')->can('project contract.view');
                Route::get('{id}', 'show')->whereNumber('id')->name('show')->can('vendor.view');
                Route::post('', 'store')->name('store')->can('vendor.create');
                Route::put('{id}', 'update')->whereNumber('id')->name('update')->can('vendor.edit');
                Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy')->can('vendor.delete');
            });
        });
    });
});
