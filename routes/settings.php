<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Settings\BranchController;
use App\Http\Controllers\Settings\CategoryController;
use App\Http\Controllers\Settings\CountryController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\UnitController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {

    Route::name('settings::')->prefix('settings')->controller(SettingsController::class)->group(function (): void {
        Route::get('', 'index')->name('index');
        Route::name('category::')->prefix('category')->controller(CategoryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('category.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('unit::')->prefix('unit')->controller(UnitController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('unit.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('branch::')->prefix('branch')->controller(BranchController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('branch.view');
            Route::get('list', 'get')->name('list');
            Route::get('assigned-list', 'fetch')->name('assigned-list');
        });
        Route::name('department::')->prefix('department')->controller(DepartmentController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('department.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('roles::')->prefix('roles')->controller(RoleController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('role.view');
            Route::get('{id}/permissions', 'permissions')->name('permission')->can('role.permissions');
        });
        Route::name('country::')->prefix('country')->controller(CountryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('service.view');
            Route::get('list', 'get')->name('list');
        });
    });
    Route::name('product::')->prefix('product')->controller(ProductController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('product.view');
        Route::get('create', 'page')->name('create')->can('product.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('product.view');
        Route::get('list', 'get')->name('list');
    });
    Route::name('service::')->prefix('service')->controller(ServiceController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('service.view');
        Route::get('create', 'page')->name('create')->can('service.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('service.edit');
        Route::get('list', 'get')->name('list');
    });
});
