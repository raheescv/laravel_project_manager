<?php

use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('purchase::')->prefix('purchase')->controller(PurchaseController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('purchase.view');
        Route::get('create', 'page')->name('create')->can('purchase.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('purchase.edit');
        Route::get('view/{id}', 'view')->name('view')->can('purchase.view');
        Route::get('payments', 'payments')->name('payments')->can('purchase.payments');
        Route::get('invoices', 'get')->name('invoice-list');
    });

    Route::name('purchase_return::')->prefix('purchase_return')->controller(PurchaseReturnController::class)->group(function (): void {
        Route::get('', 'index')->name('index'); // ->can('purchase return.view');
        Route::get('create', 'page')->name('create'); // ->can('purchase return.create');
        Route::get('edit/{id}', 'page')->name('edit'); // ->can('purchase return.edit');
        Route::get('view/{id}', 'view')->name('view'); // ->can('purchase return.view');
        Route::get('payments', 'payments')->name('payments'); // ->can('purchase return.payments');
    });
});
