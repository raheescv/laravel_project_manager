<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\Settings\CategoryController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\UnitController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::name('settings::')->prefix('settings')->controller(SettingsController::class)->group(function () {
        Route::get('', 'index')->name('index');
        Route::name('category::')->prefix('category')->controller(CategoryController::class)->group(function () {
            Route::get('', 'index')->name('index')->can('category.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('unit::')->prefix('unit')->controller(UnitController::class)->group(function () {
            Route::get('', 'index')->name('index')->can('unit.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('department::')->prefix('department')->controller(DepartmentController::class)->group(function () {
            Route::get('', 'index')->name('index')->can('department.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('roles::')->prefix('roles')->controller(RoleController::class)->group(function () {
            Route::get('', 'index')->name('index')->can('role.view');
            Route::get('{id}/permissions', 'permissions')->name('permission')->can('role.permissions');
        });
    });
});
