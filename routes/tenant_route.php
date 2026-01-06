<?php

use App\Http\Controllers\TenantController;
use App\Http\Middleware\RequireSuperAdmin;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth', RequireSuperAdmin::class])->group(function (): void {
    Route::name('tenants::')->prefix('tenants')->controller(TenantController::class)->group(function (): void {
        Route::get('', 'index')->name('index');
    });
});
