<?php

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::name('settings::')->prefix('settings')
    ->controller(SettingsController::class)->group(function () {
        Route::get('', 'index')->name('index');
    });
