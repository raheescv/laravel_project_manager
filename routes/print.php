<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('print::')->prefix('print')->controller(PrintController::class)->group(function (): void {
        Route::name('sale::')->prefix('sale')->group(function (): void {
            Route::get('invoice/{id}', 'saleInvoice')->name('invoice');
            Route::get('day-session-report/{id}', 'daySessionReport')->name('day-session-report');
        });
    });
});
