<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ColorController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DaySessionController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SaleReturnController;
use App\Http\Controllers\Api\V1\SaleSettingController;
use App\Http\Controllers\Api\V1\SizeController;
use App\Http\Controllers\Api\V1\StockCheckController;
use App\Http\Controllers\Api\V1\StorefrontController;
use App\Http\Middleware\EnsureMobilePermission;
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

    // Public catalog API — intentionally open (no login), but a tenant MUST resolve.
    // ":required" makes IdentifyTenant abort when no tenant is identified, so these
    // endpoints can never fall through to the un-scoped "all tenants" query.
    Route::middleware(IdentifyTenant::class.':required')->group(function () {

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

        // Storefront branding (accent color the showcase website applies at boot)
        Route::get('settings/branding', [StorefrontController::class, 'branding'])->name('api.v1.settings.branding');
    });

    // Mobile routes (PIN-authenticated staff / POS app)
    Route::middleware(IdentifyTenant::class)->group(function () {

        // Auth routes (public) — one endpoint handles PIN and username/password.
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('api.v1.login');

        // Authenticated routes (admin + employee)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
            Route::post('change-pin', [AuthController::class, 'changePin'])->name('api.v1.change-pin');
            Route::post('change-password', [AuthController::class, 'changePassword'])->name('api.v1.change-password');

            // Profile — the signed-in user edits their own name/email/mobile and avatar.
            // No extra permission gate: anyone authenticated may manage their own profile.
            Route::match(['put', 'patch', 'post'], 'profile', [ProfileController::class, 'update'])->name('api.v1.profile.update');
            Route::post('profile/photo', [ProfileController::class, 'updatePhoto'])->name('api.v1.profile.photo');

            // Bill routes
            Route::prefix('sale')->group(function () {
                Route::get('/', [SaleController::class, 'index'])->name('api.v1.sale.index');
                Route::post('/', [SaleController::class, 'store'])->name('api.v1.sale.store');
                Route::get('/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->name('api.v1.sale.show');
                Route::get('/{sale}/receipt', [SaleController::class, 'receipt'])->whereNumber('sale')->name('api.v1.sale.receipt');
                Route::match(['post', 'put', 'patch'], '/{sale}', [SaleController::class, 'update'])->whereNumber('sale')->name('api.v1.sale.update');
                // Delete is the one permission-gated sale action (matching the web
                // `sale.delete` guard); the create/view/edit endpoints stay open.
                Route::delete('/{sale}', [SaleController::class, 'destroy'])->whereNumber('sale')
                    ->middleware(EnsureMobilePermission::class.':sale.delete')
                    ->name('api.v1.sale.destroy');
            });

            // Sale return routes — a return is always raised against a paid sale.
            // Access is permission-driven (Spatie) to match the web module:
            // viewing, creating and editing returns are gated separately.
            Route::prefix('sale-return')->group(function () {
                Route::get('/', [SaleReturnController::class, 'index'])
                    ->middleware(EnsureMobilePermission::class.':sales return.view')
                    ->name('api.v1.sale-return.index');
                Route::post('/', [SaleReturnController::class, 'store'])
                    ->middleware(EnsureMobilePermission::class.':sales return.create')
                    ->name('api.v1.sale-return.store');
                // Shared by the create pick-flow and the edit re-fetch, so allow either.
                Route::get('/from-sale/{sale}', [SaleReturnController::class, 'fromSale'])->whereNumber('sale')
                    ->middleware(EnsureMobilePermission::class.':sales return.create,sales return.edit')
                    ->name('api.v1.sale-return.from-sale');
                Route::get('/{saleReturn}', [SaleReturnController::class, 'show'])->whereNumber('saleReturn')
                    ->middleware(EnsureMobilePermission::class.':sales return.view')
                    ->name('api.v1.sale-return.show');
                Route::match(['put', 'patch'], '/{saleReturn}', [SaleReturnController::class, 'update'])->whereNumber('saleReturn')
                    ->middleware(EnsureMobilePermission::class.':sales return.edit')
                    ->name('api.v1.sale-return.update');
            });

            // Customer routes
            Route::prefix('customers')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('api.v1.customers.index');
            });

            // Employees (stylists) — for assigning a stylist to a sale / line.
            Route::get('employees', [EmployeeController::class, 'index'])->name('api.v1.employees.index');

            // Payment methods (for the custom-payment selector)
            Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('api.v1.payment-methods.index');

            // Currencies (multi-currency list + base, cached by the app for offline use)
            Route::get('settings/currencies', [CurrencyController::class, 'index'])->name('api.v1.settings.currencies');

            // Sale settings (default quantity, cached by the app for offline use)
            Route::get('settings/sale', [SaleSettingController::class, 'index'])->name('api.v1.settings.sale');

            // Company logo bytes for the receipt header (cached by the app,
            // re-fetched when print.logo_version changes)
            Route::get('settings/logo', [SaleSettingController::class, 'logo'])->name('api.v1.settings.logo');

            // Stock Check (physical inventory count) — permission-driven, matching
            // the web `inventory.stock check` gate. A count snapshots branch stock
            // on create, is counted against (scan +1 or typed qty), and marking
            // items completed reconciles real inventory.
            Route::prefix('stock-check')
                ->middleware(EnsureMobilePermission::class.':inventory.stock check')
                ->group(function () {
                    Route::get('/', [StockCheckController::class, 'index'])->name('api.v1.stock-check.index');
                    Route::post('/', [StockCheckController::class, 'store'])->name('api.v1.stock-check.store');
                    Route::get('/{id}', [StockCheckController::class, 'show'])->whereNumber('id')->name('api.v1.stock-check.show');
                    Route::get('/{id}/items', [StockCheckController::class, 'items'])->whereNumber('id')->name('api.v1.stock-check.items');
                    Route::post('/{id}/scan', [StockCheckController::class, 'scan'])->whereNumber('id')->name('api.v1.stock-check.scan');
                    Route::match(['put', 'patch'], '/{id}', [StockCheckController::class, 'update'])->whereNumber('id')->name('api.v1.stock-check.update');
                    Route::delete('/{id}', [StockCheckController::class, 'destroy'])->whereNumber('id')->name('api.v1.stock-check.destroy');
                });

            // Admin routes — access is permission-driven (Spatie), not the is_admin
            // flag, so any staff role granted the permission can reach them.
            Route::prefix('admin')->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])
                    ->middleware(EnsureMobilePermission::class.':report.sales overview')
                    ->name('api.v1.admin.dashboard');
                Route::get('/reports', [ReportController::class, 'index'])
                    ->middleware(EnsureMobilePermission::class.':report.sale item')
                    ->name('api.v1.admin.reports');
                Route::get('/day-status', [DaySessionController::class, 'status'])
                    ->middleware(EnsureMobilePermission::class.':day session.create')
                    ->name('api.v1.admin.day-status.check');
                Route::post('/day-status', [DaySessionController::class, 'toggle'])
                    ->middleware(EnsureMobilePermission::class.':day session.create')
                    ->name('api.v1.admin.day-status');
            });
        });
    });
});
