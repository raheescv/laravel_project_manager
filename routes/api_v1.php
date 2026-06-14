<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ColorController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DaySessionController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SizeController;
use App\Http\Middleware\EnsureMobileAdmin;
use App\Http\Middleware\IdentifyTenant;
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
    // Mobile routes (PIN-authenticated staff / POS app)
    Route::middleware(IdentifyTenant::class)->group(function () {
        
        // Product routes
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('api.v1.products.index');
            Route::get('/single/', [ProductController::class, 'show'])->name('api.v1.products.show');
            Route::get('/{id}', [ProductController::class, 'get'])->name('api.v1.products.show-by-id');
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

        // Branch routes
        Route::prefix('branches')->group(function () {
            Route::get('/', [BranchController::class, 'index'])->name('api.v1.branches.index');
        });
        // Auth routes (public)
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('api.v1.login');

        // Authenticated routes (admin + employee)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
            Route::post('change-pin', [AuthController::class, 'changePin'])->name('api.v1.change-pin');

            // Bill routes
            Route::prefix('sale')->group(function () {
                Route::get('/', [SaleController::class, 'index'])->name('api.v1.sale.index');
                Route::post('/', [SaleController::class, 'store'])->name('api.v1.sale.store');
                Route::get('/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->name('api.v1.sale.show');
            });

            // Customer routes
            Route::prefix('customers')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('api.v1.customers.index');
            });

            // Admin routes
            Route::prefix('admin')->middleware(EnsureMobileAdmin::class)->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.v1.admin.dashboard');
                Route::get('/reports', [ReportController::class, 'index'])->name('api.v1.admin.reports');
                Route::post('/day-status', [DaySessionController::class, 'toggle'])->name('api.v1.admin.day-status');
            });
        });
    });
});
