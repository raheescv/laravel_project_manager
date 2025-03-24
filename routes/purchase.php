<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('purchase::')->prefix('purchase')->controller(PurchaseController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('purchase.view');
        Route::get('create', 'page')->name('create')->can('purchase.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('purchase.edit');
        Route::get('view/{id}', 'get')->name('view')->can('purchase.view');
    });
});
