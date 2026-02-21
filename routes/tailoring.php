<?php

use App\Http\Controllers\Tailoring\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    // Tailoring Order API Routes (must come before web routes to avoid route conflicts)
    Route::prefix('tailoring/order')->name('api.tailoring.order.')->group(function (): void {
        Route::get('categories', [OrderController::class, 'getCategories'])->name('categories');
        Route::get('category-models/{categoryId}', [OrderController::class, 'getCategoryModels'])->name('category-models');
        Route::post('category-models', [OrderController::class, 'addCategoryModel'])->name('add-category-model');
        Route::get('category-model-types/{categoryId}', [OrderController::class, 'getCategoryModelTypes'])->name('category-model-types');
        Route::post('category-model-types', [OrderController::class, 'addCategoryModelType'])->name('add-category-model-type');
        Route::get('products', [OrderController::class, 'getProducts'])->name('products');
        Route::get('products/by-barcode', [OrderController::class, 'getProductByBarcode'])->name('products-by-barcode');
        Route::get('product-colors', [OrderController::class, 'getProductColors'])->name('product-colors');
        Route::get('measurement-options', [OrderController::class, 'getMeasurementOptionsApi'])->name('measurement-options');
        Route::post('measurement-options', [OrderController::class, 'addMeasurementOption'])->name('add-measurement-option');
        Route::get('old-measurements/{accountId}/{categoryId}', [OrderController::class, 'getOldMeasurements'])->name('old-measurements');
        Route::post('add-item', [OrderController::class, 'addItem'])->name('add-item');
        Route::put('update-item/{id}', [OrderController::class, 'updateItem'])->name('update-item');
        Route::delete('remove-item/{id}', [OrderController::class, 'removeItem'])->name('remove-item');
        Route::post('calculate-amount', [OrderController::class, 'calculateAmount'])->name('calculate-amount');
        Route::post('add-payment', [OrderController::class, 'addPayment'])->name('add-payment');
        Route::put('update-payment/{id}', [OrderController::class, 'updatePayment'])->name('update-payment');
        Route::delete('remove-payment/{id}', [OrderController::class, 'deletePayment'])->name('remove-payment');
        Route::get('{orderId}/item/{itemId}', [OrderController::class, 'getItem'])->name('item');
        Route::get('{id}/payments', [OrderController::class, 'getPayments'])->name('payments');
    });

    // Tailoring Order Search (must be before order routes to avoid conflict with {id})
    Route::name('tailoring::order-search::')->prefix('tailoring/order-search')->controller(OrderController::class)->group(function (): void {
        Route::get('', 'orderSearchPage')->name('index')->can('tailoring order.view');
    });

    // Tailoring Order Routes
    Route::name('tailoring::order::')->prefix('tailoring/order')->controller(OrderController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('tailoring order.view');
        Route::get('create', 'page')->name('create')->can('tailoring order.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('tailoring order.edit');
        Route::post('', 'store')->name('store')->can('tailoring order.create');
        Route::put('{id}', 'update')->name('update')->can('tailoring order.edit');
        Route::get('{id}', 'show')->name('show')->can('tailoring order.view');
        Route::get('print/cutting-slip/{id}/{category_id}/{model_id?}', 'printCuttingSlip')->name('print-cutting-slip')->can('tailoring order.view');
        Route::get('print/receipt/{id}', 'printOrderReceipt')->name('print-receipt')->can('tailoring order.view');
        Route::get('print/receipt-thermal/{id}', 'printOrderReceiptThermal')->name('print-receipt-thermal')->can('tailoring order.view');
        Route::delete('{id}', 'destroy')->name('destroy')->can('tailoring order.delete');
    });

    // Tailoring Receipts
    Route::name('tailoring::receipts::')->prefix('tailoring/receipts')->controller(OrderController::class)->group(function (): void {
        Route::get('', 'receiptsPage')->name('index')->can('tailoring order.view');
    });

    // Job Completion Routes
    Route::name('tailoring::job-completion::')->prefix('tailoring/job-completion')->controller(OrderController::class)->group(function (): void {
        Route::get('', 'jobCompletionPage')->name('index')->can('tailoring job completion.view');
        Route::get('create', 'jobCompletionPage')->name('create')->can('tailoring job completion.create');
    });

    // Job Completion API Routes
    Route::prefix('tailoring/job-completion')->name('api.tailoring.job-completion.')->group(function (): void {
        Route::get('order-by-number/{orderNo}', [OrderController::class, 'getOrderByOrderNumber'])->name('order-by-number');
        Route::post('search-orders', [OrderController::class, 'searchOrders'])->name('search-orders');
        Route::put('{id}/completion', [OrderController::class, 'updateCompletion'])->name('update-completion');
        Route::put('item/{itemId}/completion', [OrderController::class, 'updateItemCompletion'])->name('update-item-completion');
        Route::get('racks', [OrderController::class, 'getRacks'])->name('racks');
        Route::get('tailors', [OrderController::class, 'getTailors'])->name('tailors');
        Route::get('cutters', [OrderController::class, 'getCutters'])->name('cutters');
        Route::post('calculate-stock-balance', [OrderController::class, 'calculateStockBalance'])->name('calculate-stock-balance');
        Route::post('calculate-tailor-commission', [OrderController::class, 'calculateTailorCommission'])->name('calculate-tailor-commission');
        Route::get('products/{productId}/stock', [OrderController::class, 'getProductStock'])->name('product-stock');
    });
});
