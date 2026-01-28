<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GeneralVoucherController;
use App\Http\Controllers\IncomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('account::')->prefix('account')->controller(AccountController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('account.view');
        Route::get('list', 'get')->name('list');
        Route::get('view/{id}', 'view')->name('view')->can('expense.view');
        Route::name('customer::')->prefix('customer')->group(function (): void {
            Route::get('', 'customer')->name('index')->can('customer.view');
            Route::get('view/{id}', 'customer')->name('view')->can('customer.view');
            Route::get('statement/{id}', 'statement')->name('statement')->can('customer.view');
        });

        // API route for customer details
        Route::get('customer/{id}/details', 'getCustomerDetails')->name('customer.details')->can('customer.view');
        Route::get('journal-entries/{journalId}', 'getJournalEntries')->name('journal-entries')->can('account.view');

        Route::name('vendor::')->prefix('vendor')->group(function (): void {
            Route::get('', 'vendor')->name('index')->can('vendor.view');
        });
        Route::name('expense::')->prefix('expense')->controller(ExpenseController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('expense.view');
        });
        Route::name('income::')->prefix('income')->controller(IncomeController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('income.view');
        });
        Route::name('general-voucher::')->prefix('general-voucher')->controller(GeneralVoucherController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('general voucher.view');
            Route::get('print/{id}', 'print')->name('print')->can('general voucher.view');
            Route::get('{id}/data', 'getData')->name('data')->can('general voucher.view');
            Route::post('', 'store')->name('store')->can('general voucher.create');
            Route::put('{id}', 'update')->name('update')->can('general voucher.edit');
        });
        Route::name('notes::')->prefix('notes')->controller(AccountController::class)->group(function (): void {
            Route::get('/{id?}', 'notes')->name('index')->can('account note.view');
        });
        // Cheque Routes
        Route::name('cheque::')->prefix('cheque')->controller(ChequeController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('cheque.view');
            Route::get('print', 'print')->name('print')->can('cheque.print');
            Route::get('view', 'view')->name('view')->can('cheque.view');
            Route::get('configuration', 'configuration')->name('configuration')->can('configuration.cheque');
        });
        // Bank Reconciliation Report
        Route::name('bank-reconciliation::')->prefix('bank-reconciliation')->group(function (): void {
            Route::get('', 'bankReconciliation')->name('index')->can('account.view');
        });
    });
});
