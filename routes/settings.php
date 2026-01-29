<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\ComboOfferController;
use App\Http\Controllers\PackageCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Settings\AccountCategoryController;
use App\Http\Controllers\Settings\BranchController;
use App\Http\Controllers\Settings\CategoryController;
use App\Http\Controllers\Settings\CountryController;
use App\Http\Controllers\Settings\CustomerTypeController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\DesignationController;
use App\Http\Controllers\Settings\TailoringCategoryController;
use App\Http\Controllers\Settings\UnitController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {

    Route::name('settings::')->prefix('settings')->controller(SettingsController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('configuration.settings');
        Route::name('category::')->prefix('category')->controller(CategoryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('category.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('account_category::')->prefix('account_category')->controller(AccountCategoryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('account category.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('unit::')->prefix('unit')->controller(UnitController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('unit.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('designation::')->prefix('designation')->controller(DesignationController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('designation.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('tailoring_category::')->prefix('tailoring-category')->controller(TailoringCategoryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('tailoring category.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('branch::')->prefix('branch')->controller(BranchController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('branch.view');
            Route::get('list', 'get')->name('list');
            Route::get('assigned-list', 'fetch')->name('assigned-list');
        });
        Route::name('department::')->prefix('department')->controller(DepartmentController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('department.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('roles::')->prefix('roles')->controller(RoleController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('role.view');
            Route::get('{id}/permissions', 'permissions')->name('permission')->can('role.permissions');
        });
        Route::name('country::')->prefix('country')->controller(CountryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('service.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('customer_type::')->prefix('customer_type')->controller(CustomerTypeController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('customer type.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('working_day::')->prefix('working-day')->controller(\App\Http\Controllers\Settings\WorkingDayController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('configuration.settings');
        });
        Route::name('brand::')->prefix('brand')->controller(BrandController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('brand.view');
            Route::get('list', 'get')->name('list');
        });
        Route::name('package_category::')->prefix('package-category')->controller(PackageCategoryController::class)->group(function (): void {
            Route::get('', 'index')->name('index')->can('package category.view');
            Route::get('list', 'get')->name('list');
        });
    });
    Route::name('product::')->prefix('product')->controller(ProductController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('product.view');
        Route::get('create', 'page')->name('create')->can('product.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('product.view');
        Route::get('import', 'import')->name('import')->can('product.create');
        Route::get('list', 'get')->name('list');
    });
    Route::name('service::')->prefix('service')->controller(ServiceController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('service.view');
        Route::get('create', 'page')->name('create')->can('service.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('service.edit');
        Route::get('import', 'import')->name('import')->can('service.create');
        Route::get('list', 'get')->name('list');
    });
    Route::name('combo_offer::')->prefix('combo_offer')->controller(ComboOfferController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('combo offer.view');
        Route::get('list', 'get')->name('list');
    });
});
