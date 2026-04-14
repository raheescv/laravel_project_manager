<?php

use App\Http\Controllers\TaskManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('task-management::')->prefix('task-management')->controller(TaskManagementController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('task-management.view');
    });
});
