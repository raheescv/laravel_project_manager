<?php

use App\Http\Controllers\AiImageController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('inventory::')->prefix('inventory')->group(function (): void {
        Route::get('', [InventoryController::class, 'index'])->name('index')->can('inventory.view');
        Route::name('product::')->prefix('product')->group(function (): void {
            Route::get('view/{id}', [InventoryController::class, 'view'])->name('view')->can('inventory.view');
            Route::get('list', [InventoryController::class, 'get'])->name('list');
        });
        Route::name('transfer::')->prefix('transfer')->controller(InventoryTransferController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('inventory transfer.view');
            Route::get('create', 'page')->name('create')->can('inventory transfer.create');
            Route::get('edit/{id}', 'page')->name('edit')->can('inventory transfer.edit');
            Route::get('view/{id}', 'view')->name('view')->can('inventory transfer.view');
            Route::get('print/{id}', 'print')->name('print');
            Route::get('list', 'get')->name('list');
        });
        // Barcode Routes
        Route::name('barcode::')->prefix('barcode')->group(function (): void {
            Route::get('print/{id?}', [BarcodeController::class, 'print'])->name('print');
            Route::get('view/{id?}', [BarcodeController::class, 'print'])->name('view');
            Route::get('configuration', [BarcodeController::class, 'configuration'])->name('configuration')->can('barcode.configuration');
        });
        // AI Image Generation routes
        Route::get('ai-image', [AiImageController::class, 'index'])->name('ai-image')->can('inventory.view');
        Route::post('ai-image/generate', [AiImageController::class, 'generate'])->name('ai-image.generate')->can('inventory.view');
    });
});
