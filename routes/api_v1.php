<?php

use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ColorController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SizeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for version 1 of your API.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {

    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('api.v1.products.index');
        Route::get('/{product}', [ProductController::class, 'show'])->name('api.v1.products.show');
    });

    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('api.v1.categories.index');
    });

    // Brand routes
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('api.v1.brands.index');
    });

    // Size routes
    Route::prefix('sizes')->group(function () {
        Route::get('/', [SizeController::class, 'index'])->name('api.v1.sizes.index');
    });

    // Color routes
    Route::prefix('colors')->group(function () {
        Route::get('/', [ColorController::class, 'index'])->name('api.v1.colors.index');
    });
});
