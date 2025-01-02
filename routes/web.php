<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::name('users::')->prefix('users')
        ->controller(UserController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('view/{id}', 'get')->name('view');
        });

    Route::name('product::')->prefix('product')
        ->controller(ProductController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('create', 'page')->name('create');
            Route::get('edit/{id}', 'page')->name('edit');
        });
    Route::name('notification::')->prefix('notification')
        ->controller(NotificationController::class)->group(function () {
            Route::get('', 'index')->name('index');
        });
    Route::name('audit::')->prefix('audit')
        ->controller(AuditController::class)->group(function () {
            Route::get('{modal}/{id}', 'index')->name('index');
        });
});
