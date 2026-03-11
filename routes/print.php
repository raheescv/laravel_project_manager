<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('print::')->prefix('print')->controller(PrintController::class)->group(function (): void {
        Route::name('sale::')->prefix('sale')->group(function (): void {
            Route::get('invoice/{id}', 'saleInvoice')->name('invoice');
            Route::get('day-session-report/{id}', 'daySessionReport')->name('day-session-report')->can('day session.print');
            Route::get('day-session-report-pdf/{id}', 'daySessionReportPdf')->name('day-session-report-pdf')->can('day session.print');
            Route::get('customer-receipt', 'customerReceipt')->name('customer-receipt');
        });
        Route::name('rentout::')->prefix('rentout')->group(function (): void {
            Route::get('statement/{id}', 'rentoutStatement')->name('statement');
            Route::get('utilities-statement/{id}', 'rentoutUtilitiesStatement')->name('utilities-statement');
        });
        Route::name('sale_return::')->prefix('sale-return')->group(function (): void {
            Route::get('payment-receipt', 'saleReturnPaymentReceipt')->name('payment-receipt');
        });
        Route::name('tailoring::')->prefix('tailoring')->group(function (): void {
            Route::get('customer-receipt', 'tailoringCustomerReceipt')->name('customer-receipt');
        });
    });
});
