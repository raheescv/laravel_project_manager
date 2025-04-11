<?php

use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('sale::')->prefix('sale')->controller(SaleController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale.view');
        Route::get('create', 'page')->name('create')->can('sale.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('sale.edit');
        Route::get('view/{id}', 'view')->name('view')->can('sale.view');
        Route::get('invoices', 'get')->name('invoice-list');
        Route::get('receipts', 'receipts')->name('receipts')->can('sale.receipts');
    });
    Route::name('sale_return::')->prefix('sale_return')->controller(SaleReturnController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale_return.view');
        Route::get('create', 'page')->name('create')->can('sale_return.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('sale_return.edit');
        Route::get('view/{id}', 'view')->name('view')->can('sale_return.view');
        Route::get('payments', 'payments')->name('payments')->can('sale_return.payments');
    });
});
