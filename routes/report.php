<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::name('report::')->prefix('report')
        ->controller(ReportController::class)->group(function () {
            Route::get('sale_item', 'sale_item')->name('sale_item');
            Route::get('purchase_item', 'purchase_item')->name('purchase_item');
            Route::get('day_book', 'day_book')->name('day_book');
        });
});
