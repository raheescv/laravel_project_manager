<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::name('users::')->prefix('users')->controller(UserController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('user.view');
        Route::get('view/{id}', 'view')->name('view')->can('user.edit');
        Route::get('list', 'get')->name('list');
        Route::name('employee::')->prefix('employee')->group(function (): void {
            Route::get('', 'employee')->name('index')->can('employee.view');
            Route::get('view/{id}', 'view')->name('view')->can('employee.edit');
        });
    });

    Route::name('notification::')->prefix('notification')->controller(NotificationController::class)->group(function (): void {
        Route::get('', 'index')->name('index');
    });
    Route::name('audit::')->prefix('audit')->controller(AuditController::class)->group(function (): void {
        Route::get('{modal}/{id}', 'index')->name('index');
    });
    Route::name('backup::')->prefix('backup')->controller(BackupController::class)->group(function (): void {
        Route::get('/', 'index')->name('index')->can('backup.view');
        Route::get('download/{file}', 'get')->name('download')->can('backup.download');
        Route::get('create', 'store')->name('create')->can('backup.create');
    });
});
