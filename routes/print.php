<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::name('print::')->prefix('print')->controller(PrintController::class)->group(function () {
        Route::name('sale::')->prefix('sale')->group(function () {
            Route::get('invoice/{id}', 'saleInvoice')->name('invoice');
        });
    });
});
