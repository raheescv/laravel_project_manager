<?php

use App\Http\Controllers\LogController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('log::')->prefix('log')
        ->controller(LogController::class)->group(function (): void {
            Route::get('inventory', 'inventory')->name('inventory')->can('log.inventory');
        });

    Route::name('report::')->prefix('report')
        ->controller(ReportController::class)->group(function (): void {
            Route::get('sale_item', 'sale_item')->name('sale_item');
            Route::get('sale_return_item', 'sale_return_item')->name('sale_return_item');
            Route::get('purchase_item', 'purchase_item')->name('purchase_item');
            Route::get('day_book', 'day_book')->name('day_book');
            Route::get('sale_summary', 'sale_summary')->name('sale_summary')->can('report.sale summary');
            Route::get('sales_overview', 'sales_overview')->name('sales_overview')->can('report.sales overview');
            Route::get('profit_loss', 'profit_loss')->name('profit_loss')->can('report.profit loss');
            Route::get('trial_balance', 'trial_balance')->name('trial_balance')->can('report.trial balance');
            Route::get('balance_sheet', 'balance_sheet')->name('balance_sheet')->can('report.balance sheet');
            Route::get('customer', 'customer')->name('customer')->can('report.customer');
            Route::get('employee', 'employee')->name('employee')->can('report.employee');
            Route::get('product', 'product')->name('product')->can('report.product');
            Route::get('ai_generated', 'ai_generated')->name('ollama');
        });
});
