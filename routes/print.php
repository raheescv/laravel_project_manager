<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('print::')->prefix('print')->controller(PrintController::class)->group(function (): void {
        Route::name('sale::')->prefix('sale')->group(function (): void {
            Route::get('invoice/{id}', 'saleInvoice')->name('invoice')->can('sale.receipts');
            Route::get('day-session-report/{id}', 'daySessionReport')->name('day-session-report')->can('day session.print');
            Route::get('day-session-report-pdf/{id}', 'daySessionReportPdf')->name('day-session-report-pdf')->can('day session.print');
            Route::get('customer-receipt', 'customerReceipt')->name('customer-receipt')->can('sale.receipts');
        });
        Route::name('rentout::')->prefix('rentout')->group(function (): void {
            Route::get('statement/{id}/{fromDate?}/{toDate?}', 'rentoutStatement')->name('statement')->can('rent out.print');
            Route::get('utilities-statement/{id}/{fromDate?}/{toDate?}', 'rentoutUtilitiesStatement')->name('utilities-statement')->can('rent out.print');
            Route::get('reservation-form/{id}', 'reservationForm')->name('reservation-form')->can('rent out lease booking.reservation form');
            Route::get('residential-lease/{id}/{type?}', 'residentialLease')->name('residential-lease')->can('rent out lease booking.residential lease');
            Route::get('payment-receipt/{id}', 'rentOutPaymentReceipt')->name('payment-receipt');
            Route::get('payment-voucher/{id}', 'rentOutPaymentVoucher')->name('payment-voucher');
            Route::get('checklist/{id}', 'rentOutChecklist')->name('checklist')->can('rent out checklist.print');
        });
        Route::name('purchase_vendor::')->prefix('purchase-vendor')->group(function (): void {
            Route::get('statement/{id}/{fromDate?}/{toDate?}', 'purchaseVendorStatement')->name('statement')->can('vendor.print');
            Route::get('payment-voucher/{vendorId}/{journalId}', 'purchaseVendorPaymentVoucher')->name('payment-voucher')->can('vendor.print');
        });
        Route::name('sale_return::')->prefix('sale-return')->group(function (): void {
            Route::get('payment-receipt', 'saleReturnPaymentReceipt')->name('payment-receipt')->can('sales return.receipts');
        });
        Route::name('tailoring::')->prefix('tailoring')->group(function (): void {
            Route::get('customer-receipt', 'tailoringCustomerReceipt')->name('customer-receipt')->can('tailoring order.receipts');
        });
    });
});
