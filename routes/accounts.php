<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('account::')->prefix('account')->controller(AccountController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('account.view');
        Route::get('list', 'get')->name('list');
        Route::get('view/{id}', 'view')->name('view')->can('expense.view');
       
        Route::name('customer::')->prefix('customer')->group(function (): void {
            Route::get('', 'customer')->name('index')->can('customer.view');
            Route::get('view/{id}', 'customer')->name('view')->can('customer.view');
        });

        // API route for customer details
        Route::get('customer/{id}/details', 'getCustomerDetails')->name('customer.details')->can('customer.view');

       Route::get('customer-by-sale/{sale_id}', 'getCustomerBySaleId')->name('customerd.customerBySale')->can('customer.view');


        
        Route::name('vendor::')->prefix('vendor')->group(function (): void {
            Route::get('', 'vendor')->name('index')->can('vendor.view');
        });
        Route::name('expense::')->prefix('expense')->controller(ExpenseController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('expense.view');
        });
        Route::name('income::')->prefix('income')->controller(IncomeController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('income.view');
        });
        Route::name('notes::')->prefix('notes')->controller(AccountController::class)->group(function (): void {
            Route::get('/{id?}', 'notes')->name('index')->can('account note.view');
        });
    });
});
