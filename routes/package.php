<?php

use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('package::')->prefix('package')->controller(PackageController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('package.view');
        Route::get('create', 'create')->name('create')->can('package.create');
        Route::get('edit/{id}', 'edit')->name('edit')->can('package.edit');
    });
});
