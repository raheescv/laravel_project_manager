<?php

use App\Http\Controllers\StudentController;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureModuleEnabled::class.':'.ModuleAccess::SCHOOL])->group(function (): void {
    Route::name('student::')->prefix('student')->controller(StudentController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('student.view');
        Route::get('create', 'page')->name('create')->can('student.create');
        Route::get('import', 'import')->name('import')->can('student.import');
        Route::get('edit/{id}', 'page')->name('edit')->can('student.edit');
        Route::get('view/{id}', 'view')->name('view')->can('student.view');

        // Reports (Students menu). Module-gated by the group above.
        Route::name('report::')->prefix('report')->group(function (): void {
            Route::get('wallet', 'walletReport')->name('wallet')->can('report.student wallet');
            Route::get('recharges', 'rechargeReport')->name('recharges')->can('report.student recharge');
        });
        Route::get('canteen-menu', 'canteenMenu')->name('canteen-menu')->can('student menu.view');
    });
});
