<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageGenComfyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhysicalVisitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicScanController;
use App\Http\Controllers\UserAttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorAnalyticsController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/home', [PublicScanController::class, 'home'])->name('public_home');
Route::get('/scan', [PublicScanController::class, 'index'])->name('scan.index');
Route::post('/scan/search', [PublicScanController::class, 'search'])->name('scan.search');
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
            Route::get('attendance', [UserAttendanceController::class, 'index'])->name('attendance')->can('employee attendance.view');
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

    Route::name('appointment::')->prefix('appointment')->controller(AppointmentController::class)->group(function (): void {
        Route::get('', 'index')->name('list')->can('appointment.view');
        Route::get('employee-calendar', 'calendar')->name('index')->can('appointment.view');
    });

    Route::get('generate-image', [ImageGenComfyController::class, 'generate'])->name('generate-image');
    Route::get('family-tree', [FamilyTreeController::class, 'index'])->name('family-tree'); // ->can('family-tree.view');
    Route::get('visitor-analytics', [VisitorAnalyticsController::class, 'index'])->name('visitor-analytics')->can('visitor analytics.view');

    Route::get('health', [HealthController::class, 'index'])->name('health')->can('system health.view');
});

// Physical Visitor Management Routes
Route::middleware(['auth'])->group(function (): void {
    Route::get('/visitors', [PhysicalVisitorController::class, 'index'])->name('visitors.index');
    Route::get('/visitors/create', [PhysicalVisitorController::class, 'create'])->name('visitors.create');
    Route::post('/visitors', [PhysicalVisitorController::class, 'store'])->name('visitors.store');
    Route::get('/visitors/{visitor}', [PhysicalVisitorController::class, 'show'])->name('visitors.show');
    Route::post('/visitors/{visitor}/checkout', [PhysicalVisitorController::class, 'checkout'])->name('visitors.checkout');
    Route::get('/visitors/stats', [PhysicalVisitorController::class, 'stats'])->name('visitors.stats');
});
