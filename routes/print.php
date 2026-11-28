<?php

use App\Http\Controllers\PrintController;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('print::')->prefix('print')->controller(PrintController::class)->group(function (): void {
        Route::name('sale::')->prefix('sale')->group(function (): void {
            Route::get('invoice/{id}', 'saleInvoice')->name('invoice')->can('sale.receipts');
            Route::get('day-session-report/{id}', 'daySessionReport')->name('day-session-report')->can('day session.print');
            Route::get('day-session-report-combined/{id}', 'daySessionReportCombined')->name('day-session-report-combined')->can('day session.print');
            Route::get('day-session-report-pdf/{id}', 'daySessionReportPdf')->name('day-session-report-pdf')->can('day session.print');
            Route::get('customer-receipt', 'customerReceipt')->name('customer-receipt')->can('sale.receipts');
        });
        Route::name('rentout::')->prefix('rentout')->group(function (): void {
            // Rent-out prints are shared by both agreement types; PrintController checks the type's permission.
            Route::get('statement/{id}/{fromDate?}/{toDate?}', 'rentoutStatement')->name('statement');
            Route::get('utilities-statement/{id}/{fromDate?}/{toDate?}', 'rentoutUtilitiesStatement')->name('utilities-statement');
            Route::get('reservation-form/{id}', 'reservationForm')->name('reservation-form');
            Route::get('residential-lease/{id}/{type?}', 'residentialLease')->name('residential-lease');
            Route::get('broker-agreement/{id}', 'brokerAgreement')->whereNumber('id')->name('broker-agreement')->can('rent out lease.view');
            Route::get('payment-receipt/{id}', 'rentOutPaymentReceipt')->name('payment-receipt');
            Route::get('payment-voucher/{id}', 'rentOutPaymentVoucher')->name('payment-voucher');
            Route::get('checklist/{id}', 'rentOutChecklist')->name('checklist')->can('rent out checklist.print');
            Route::get('commission/{kind}/{id}', 'rentOutCommissionDocument')->whereIn('kind', ['invoice', 'voucher'])->whereNumber('id')->name('commission')->can('rent out commission.view');
        });
        Route::name('project_contract::')->prefix('project-contract')->group(function (): void {
            Route::get('statement/{id}/{fromDate?}/{toDate?}', 'projectContractStatement')->whereNumber('id')->name('statement')->can('project contract.view');
            Route::get('payment-receipt/{id}', 'projectContractPaymentReceipt')->whereNumber('id')->name('payment-receipt')->can('project contract.view');
            Route::get('payment-voucher/{id}', 'projectContractPaymentVoucher')->whereNumber('id')->name('payment-voucher')->can('project contract.view');
            Route::get('voucher/{id}', 'projectContractVoucher')->whereNumber('id')->name('voucher')->can('project contract.view');
            Route::get('term/{kind}/{id}', 'projectContractTermDocument')->whereIn('kind', ['invoice', 'receipt', 'voucher'])->whereNumber('id')->name('term')->can('project contract.view');
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
        Route::name('student::')->prefix('student')->middleware(EnsureModuleEnabled::class.':'.ModuleAccess::SCHOOL)->group(function (): void {
            Route::get('pre-orders/{date}', 'studentPreOrders')->where('date', '\d{4}-\d{2}-\d{2}')->name('pre-orders')->can('student menu.view');
        });
    });
});
