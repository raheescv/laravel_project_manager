<?php

use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('sale::')->prefix('sale')->controller(SaleController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale.view');
        Route::get('create', 'page')->name('create')->can('sale.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('sale.edit');
        Route::get('view/{id}', 'view')->name('view')->can('sale.view');
        Route::get('receipts', 'receipts')->name('receipts')->can('sale.receipts');
    });
});
