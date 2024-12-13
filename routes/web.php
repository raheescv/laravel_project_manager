<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::name('product_type::')
        ->prefix('product_type')
        ->controller(ProductTypeController::class)
        ->group(function () {
            Route::get('', 'index')->name('index');
        });
});

require __DIR__.'/auth.php';