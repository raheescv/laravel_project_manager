<?php

use App\Http\Controllers\Api\V1\ProductController;
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

    // Add more V1 API routes here as needed
    // Route::prefix('categories')->group(function () {
    //     Route::get('/', [CategoryController::class, 'index']);
    // });

    // Route::prefix('brands')->group(function () {
    //     Route::get('/', [BrandController::class, 'index']);
    // });
});
