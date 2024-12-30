<?php

use App\Http\Controllers\Settings\CategoryController;
use App\Http\Controllers\Settings\UnitController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::name('settings::')->prefix('settings')
    ->controller(SettingsController::class)->group(function () {
        Route::get('', 'index')->name('index');
        Route::name('category::')->prefix('category')->controller(CategoryController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('list', 'get')->name('list');
        });
        Route::name('unit::')->prefix('unit')->controller(UnitController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('list', 'get')->name('list');
        });
    });
