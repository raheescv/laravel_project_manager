<?php

use App\Http\Controllers\TenantController;
use App\Http\Middleware\RequireSuperAdmin;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth', RequireSuperAdmin::class])->group(function (): void {
    Route::name('tenants::')->prefix('tenants')->controller(TenantController::class)->group(function (): void {
        Route::get('', 'index')->name('index');
        Route::get('view/{id}', 'view')->name('view');
    });
});

// Redeemed on the TARGET tenant's host, where the super admin has no session
// yet: the single-use token is the authorisation (see TenantSwitchService).
Route::get('tenants/enter/{token}', [TenantController::class, 'enter'])
    ->middleware('throttle:10,1')
    ->name('tenants::enter');
