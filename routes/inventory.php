<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('inventory::')->prefix('inventory')->controller(InventoryController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('inventory.view');
        Route::name('product::')->prefix('product')->group(function (): void {
            Route::get('view/{id}', 'view')->name('view')->can('inventory.view');
            Route::get('list', 'get')->name('list');
        });
    });
});
