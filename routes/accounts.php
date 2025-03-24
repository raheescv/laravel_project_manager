<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('account::')->prefix('account')->controller(AccountController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('account.view');
        Route::get('list', 'get')->name('list');
        Route::name('customer::')->prefix('customer')->group(function (): void {
            Route::get('', 'customer')->name('index')->can('customer.view');
        });
        Route::name('vendor::')->prefix('vendor')->group(function (): void {
            Route::get('', 'vendor')->name('index')->can('vendor.view');
        });
    });
});
