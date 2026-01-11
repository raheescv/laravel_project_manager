<?php

use App\Http\Controllers\AiImageController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryOpeningBalanceController;
use App\Http\Controllers\InventoryTransferController;
use App\Http\Controllers\StockCheckController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('inventory::')->prefix('inventory')->group(function (): void {
        Route::get('', [InventoryController::class, 'index'])->name('index')->can('inventory.view');
        Route::get('search', [InventoryController::class, 'search'])->name('search')->can('inventory.product search');
        Route::get('opening-balance', [InventoryOpeningBalanceController::class, 'index'])->name('opening-balance')->can('inventory.opening balance');
        Route::post('opening-balance/save', [InventoryOpeningBalanceController::class, 'save'])->name('opening-balance.save')->can('inventory.opening balance');
        Route::name('product::')->prefix('product')->group(function (): void {
            Route::get('view/{id}', [InventoryController::class, 'view'])->name('view')->can('inventory.view');
            Route::get('list', [InventoryController::class, 'get'])->name('list');
            Route::get('getProduct', [InventoryController::class, 'getProduct'])->name('getProduct');
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
            Route::get('configuration', [BarcodeController::class, 'configuration'])->name('configuration')->can('configuration.barcode');
            Route::name('cart::')->prefix('cart')->group(function (): void {
                Route::get('', [BarcodeController::class, 'index'])->name('index')->can('inventory.barcode cart');
                Route::get('print', [BarcodeController::class, 'cartPrint'])->name('print');
            });
        });
        // AI Image Generation routes
        Route::get('ai-image', [AiImageController::class, 'index'])->name('ai-image')->can('inventory.view');
        Route::post('ai-image/generate', [AiImageController::class, 'generate'])->name('ai-image.generate')->can('inventory.view');
        // Stock Check routes
        Route::name('stock-check::')->prefix('stock-check')->group(function (): void {
            Route::get('', [StockCheckController::class, 'index'])->name('index');
            Route::get('list', [StockCheckController::class, 'get'])->name('list');
            Route::post('create', [StockCheckController::class, 'store'])->name('create');
            Route::get('{id}', [StockCheckController::class, 'show'])->name('show');
            Route::put('{id}', [StockCheckController::class, 'update'])->name('update');
            Route::put('{id}/metadata', [StockCheckController::class, 'updateMetadata'])->name('update-metadata');
            Route::delete('{id}', [StockCheckController::class, 'delete'])->name('delete');
            Route::post('{id}/scan-barcode', [StockCheckController::class, 'scanBarcode'])->name('scan-barcode');
            Route::get('{id}/items', [StockCheckController::class, 'getItems'])->name('items');
        });
    });
});
