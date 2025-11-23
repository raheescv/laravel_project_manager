<?php

use App\Http\Controllers\ApiLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('api_log::')->prefix('api_log')->controller(ApiLogController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('api_log.view');
        Route::get('moq-settings', 'moqSettings')->name('moq-settings')->can('api_log.moq settings');
    });
});
