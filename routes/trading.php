<?php

use App\Http\Controllers\TradingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('trading::')->prefix('trading')
        ->controller(TradingController::class)->group(function (): void {
            Route::get('', 'index')->name('index');
        });
});
Route::name('webhook::')->prefix('webhook')
    ->controller(TradingController::class)->group(function (): void {
        Route::get('fyers', 'fyersWebhook')->name('fyers');
    });
