<?php

use App\Http\Controllers\LogController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('log::')->prefix('log')
        ->controller(LogController::class)->group(function (): void {
            Route::get('inventory', 'inventory')->name('inventory')->can('log.inventory');
        });

    Route::name('report::')->prefix('report')
        ->controller(ReportController::class)->group(function (): void {
            Route::get('sale_item', 'sale_item')->name('sale_item');
            Route::get('sale_return_item', 'sale_return_item')->name('sale_return_item');
            Route::get('purchase_item', 'purchase_item')->name('purchase_item');
            Route::get('day_book', 'day_book')->name('day_book');
        });
});
