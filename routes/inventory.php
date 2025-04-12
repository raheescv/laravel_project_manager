<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('inventory::')->prefix('inventory')->controller(InventoryController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('inventory.view');
        Route::name('product::')->prefix('product')->group(function (): void {
            Route::get('view/{id}', 'view')->name('view')->can('inventory.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('transfer::')->prefix('transfer')->controller(InventoryTransferController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('inventory transfer.view');
            Route::get('create', 'page')->name('create')->can('inventory transfer.create');
            Route::get('edit/{id}', 'page')->name('edit')->can('inventory transfer.edit');
            Route::get('view/{id}', 'view')->name('view')->can('inventory transfer.view');
            Route::get('print/{id}', 'print')->name('print');
            Route::get('list', 'get')->name('list');
        });
    });
});
