<?php

use App\Http\Controllers\Property\MaintenanceController;
use App\Http\Controllers\Property\PropertyBuildingController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\Property\PropertyGroupController;
use App\Http\Controllers\Property\PropertyLeadController;
use App\Http\Controllers\Property\PropertyTypeController;
use App\Http\Controllers\Property\RentOutController;
use App\Http\Controllers\Property\RentOutReportController;
use App\Http\Controllers\Property\RentOutTransactionController;
use App\Http\Controllers\Property\TenantDetailController;
use App\Http\Controllers\Property\UtilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('property::')->prefix('property')->group(function (): void {
        // Property Groups
        Route::name('group::')->prefix('group')->controller(PropertyGroupController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('property group.view');
            Route::get('list', 'get')->name('list');
        });

        // Property Buildings
        Route::name('building::')->prefix('building')->controller(PropertyBuildingController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('property building.view');
            Route::get('list', 'get')->name('list');
        });

        // Properties
        Route::name('property::')->prefix('properties')->controller(PropertyController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('property.view');
            Route::get('list', 'get')->name('list');
        });

        // Property Leads
        Route::name('lead::')->prefix('lead')->controller(PropertyLeadController::class)->group(function (): void {
            Route::get('', 'index')->name('list')->can('property lead.view');
            Route::get('list', 'index')->name('index')->can('property lead.view');
            Route::get('calendar', 'calendar')->name('calendar')->can('property lead.view');
            Route::get('calendar/data', 'calendarData')->name('calendar.data')->can('property lead.view');
            Route::get('create', 'create')->name('create')->can('property lead.create');
            Route::get('edit/{id}', 'edit')->name('edit')->can('property lead.view');
        });

        // Property Types (Settings)
        Route::name('type::')->prefix('type')->controller(PropertyTypeController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('property type.view');
            Route::get('list', 'get')->name('list');
        });

        // Utilities (Settings)
        Route::name('utility::')->prefix('utility')->controller(UtilityController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('utility.view');
            Route::get('list', 'get')->name('list');
        });

        // RentOut - Rent Module (agreement_type = rental)
        Route::name('rent::')->prefix('rent')->group(function (): void {
            Route::controller(RentOutController::class)->group(function (): void {
                Route::get('', 'index')->name('index')->can('rent out.view');
                Route::get('create/{id?}', 'page')->name('create')->can('rent out.create');
                Route::get('edit/{id}', 'page')->name('edit')->can('rent out.edit');
                Route::get('view/{id}', 'view')->name('view')->can('rent out.view');
                Route::get('booking', 'booking')->name('booking')->can('rent out.view');
                Route::get('booking/create/{id?}', 'bookingPage')->name('booking.create')->can('rent out.create');
                Route::get('booking/edit/{id}', 'bookingPage')->name('booking.edit')->can('rent out.edit');
                Route::get('booking/view/{id}', 'bookingView')->name('booking.view')->can('rent out.view');
            });

            Route::controller(RentOutTransactionController::class)->group(function (): void {
                Route::get('payments', 'payments')->name('payments')->defaults('agreement_type', 'rental')->can('rent out.payment');
                Route::get('utilities', 'utilities')->name('utilities')->can('rent out utility.view');
                Route::get('services', 'services')->name('services')->can('rent out service.view');
                Route::get('payment-due', 'paymentDue')->name('payment-due')->defaults('agreement_type', 'rental')->can('rent out.payment');
                Route::get('cheque-management', 'chequeManagement')->name('cheque-management')->defaults('agreement_type', 'rental')->can('rent out cheque.view');
                Route::get('payment-history', 'paymentHistory')->name('payment-history')->defaults('agreement_type', 'rental')->can('rent out.payment');
            });
        });

        // RentOut - Sale/Lease Module (agreement_type = lease)
        Route::name('sale::')->prefix('sale')->group(function (): void {
            Route::controller(RentOutController::class)->group(function (): void {
                Route::get('', 'index')->name('index')->can('rent out lease.view');
                Route::get('create/{id?}', 'page')->name('create')->can('rent out lease.create');
                Route::get('edit/{id}', 'page')->name('edit')->can('rent out lease.create');
                Route::get('view/{id}', 'view')->name('view')->can('rent out lease.view');
                Route::get('booking', 'booking')->name('booking')->can('rent out lease.view');
                Route::get('booking/create/{id?}', 'bookingPage')->name('booking.create')->can('rent out lease.create');
                Route::get('booking/edit/{id}', 'bookingPage')->name('booking.edit')->can('rent out lease.create');
                Route::get('booking/view/{id}', 'bookingView')->name('booking.view')->can('rent out lease.view');
            });

            Route::controller(RentOutTransactionController::class)->group(function (): void {
                Route::get('payments', 'payments')->name('payments')->defaults('agreement_type', 'lease')->can('rent out lease.payment');
                Route::get('cheque-management', 'chequeManagement')->name('cheque-management')->defaults('agreement_type', 'lease')->can('rent out lease.cheque management');
            });
        });

        // Reports
        Route::name('report::')->prefix('report')->controller(RentOutReportController::class)->group(function (): void {
            Route::get('customer-property', 'customerProperty')->name('customer-property')->can('rent out.view');
            Route::get('security', 'security')->name('security')->can('rent out security.view');
            Route::get('service-charge', 'serviceCharge')->name('service-charge')->can('rent out lease.view');
            Route::get('daybook', 'daybook')->name('daybook')->can('rent out.view');
        });

        // Tenant Details
        Route::name('tenant::')->prefix('tenant')->controller(TenantDetailController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('tenant detail.view');
            Route::get('list', 'get')->name('list');
        });

        // Maintenance
        Route::name('maintenance::')->prefix('maintenance')->controller(MaintenanceController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('maintenance.view');
            Route::get('create', 'create')->name('create')->can('maintenance.create');
            Route::get('edit/{id}', 'edit')->name('edit')->can('maintenance.edit');
            Route::get('assign/{id}', 'assign')->name('assign')->can('maintenance.assign');
            Route::get('complaint/{id}', 'complaint')->name('complaint')->can('maintenance.view');
        });
    });
});
