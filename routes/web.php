<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceController;
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

    Route::name('product::')->prefix('product')->controller(ProductController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('product.view');
        Route::get('create', 'page')->name('create')->can('product.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('product.view');
        Route::get('list', 'get')->name('list');
    });

    Route::name('service::')->prefix('service')->controller(ServiceController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('service.view');
        Route::get('create', 'page')->name('create')->can('service.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('service.edit');
        Route::get('list', 'get')->name('list');
    });

    Route::name('account::')->prefix('account')->controller(AccountController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('account.view');
        Route::get('list', 'get')->name('list');
        Route::name('customer::')->prefix('customer')->group(function (): void {
            Route::get('', 'customer')->name('index')->can('customer.view');
        });
        Route::name('vendor::')->prefix('vendor')->group(function (): void {
            Route::get('', 'vendor')->name('index')->can('vendor.view');
        });
    });

    Route::name('inventory::')->prefix('inventory')->controller(InventoryController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('inventory.view');
        Route::name('product::')->prefix('product')->group(function (): void {
            Route::get('view/{id}', 'view')->name('view')->can('inventory.view');
            Route::get('list', 'get')->name('list');
        });
    });
    Route::name('notification::')->prefix('notification')->controller(NotificationController::class)->group(function (): void {
        Route::get('', 'index')->name('index');
    });
    Route::name('audit::')->prefix('audit')->controller(AuditController::class)->group(function (): void {
        Route::get('{modal}/{id}', 'index')->name('index');
    });

    Route::name('sale::')->prefix('sale')->controller(SaleController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale.view');
        Route::get('create', 'page')->name('create')->can('sale.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('sale.edit');
        Route::get('receipts', 'receipts')->name('receipts')->can('sale.receipts');
    });

    Route::name('purchase::')->prefix('purchase')->controller(PurchaseController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('purchase.view');
        Route::get('create', 'page')->name('create')->can('purchase.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('purchase.edit');
        Route::get('view/{id}', 'get')->name('view')->can('purchase.view');
    });
});
