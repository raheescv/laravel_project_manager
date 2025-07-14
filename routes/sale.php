<?php

use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Middleware\RequireOpenDaySession;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {

    Route::name('sale::')->prefix('sale')->controller(SaleController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale.view');

        // Routes that require an open day session
        Route::middleware([RequireOpenDaySession::class])->group(function (): void {
            // Route::get('create', [App\Http\Controllers\Sale\PageController::class, 'index'])->name('create');
            Route::get('create', 'page')->name('create')->can('sale.create');
            Route::get('pos', 'posPage')->name('pos')->can('sale.create');
            Route::get('pos/{id}', 'posPage')->name('pos.edit')->can('sale.edit');
            Route::get('edit/{id}', 'page')->name('edit')->can('sale.edit');
        });

        Route::get('view/{id}', 'view')->name('view')->can('sale.view');
        Route::get('invoices', 'get')->name('invoice-list');
        Route::get('receipts', 'receipts')->name('receipts')->can('sale.receipts');

        Route::get('day-management', 'dayManagement')->name('day-management')->can('sale.view');
        Route::get('day-session/{id}', 'daySession')->name('day-session')->can('sale.view');
        Route::get('day-sessions-report', 'daySessionsReport')->name('day-sessions-report')->can('sale.view');
    });
    Route::name('sale_return::')->prefix('sale_return')->controller(SaleReturnController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sales return.view');
        Route::get('create', 'page')->name('create')->can('sales return.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('sales return.edit');
        Route::get('view/{id}', 'view')->name('view')->can('sales return.view');
        Route::get('payments', 'payments')->name('payments')->can('sales return.payments');
    });
});
