<?php

use App\Http\Controllers\TradingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::name('trading::')->prefix('trading')
        ->controller(TradingController::class)->group(function () {
            Route::get('', 'index')->name('index');
        });
});
Route::name('webhook::')->prefix('webhook')
    ->controller(TradingController::class)->group(function () {
        Route::get('fyers', 'fyersWebhook')->name('fyers');
    });
