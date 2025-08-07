<?php

use App\Http\Controllers\Api\POSController;
use App\Http\Controllers\ComboOfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\Settings\CustomerController;
use App\Http\Middleware\RequireOpenDaySession;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {

    Route::name('sale::')->prefix('sale')->controller(SaleController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale.view');

        // Routes that require an open day session
        Route::middleware([RequireOpenDaySession::class])->group(function (): void {
            // Route::get('create', [App\Http\Controllers\Sale\PageController::class, 'index'])->name('create');
            Route::get('create', 'page')->name('create')->can('sale.create');
            Route::get('pos/{id?}', 'posPage')->name('pos')->can('sale.create');
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

    Route::prefix('products')->name('api.products.')->group(function () {
        Route::get('/', [POSController::class, 'getProducts'])->name('index');
        Route::get('search', [ProductController::class, 'index'])->name('search');
        Route::get('by-barcode', [POSController::class, 'getProductByBarcode'])->name('by-barcode');
    });

    // Customer Management
    Route::prefix('customers')->name('api.customers.')->group(function () {
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::put('/{id}', [CustomerController::class, 'store'])->name('edit');
        Route::get('check-mobile', [CustomerController::class, 'get'])->name('get');
    });

    Route::prefix('pos')->name('api.pos.')->group(function () {
        Route::post('add-item', [POSController::class, 'addItem'])->name('add-item');
        Route::post('update-item', [POSController::class, 'updateItem'])->name('update-item');
        Route::post('remove-item', [POSController::class, 'removeItem'])->name('remove-item');
        Route::post('submit', [POSController::class, 'submitSale'])->name('submit');
        Route::get('drafts', [POSController::class, 'getDraftSales'])->name('drafts');
    });

    // Combo Offer API Routes
    Route::prefix('combo_offer')->name('api.combo_offer.')->group(function () {
        Route::get('list', [ComboOfferController::class, 'get'])->name('list');
    });
});
