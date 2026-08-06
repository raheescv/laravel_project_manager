<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Theme settings route - public but with throttling to prevent abuse.
// IdentifyTenant resolves the tenant from the subdomain / X-Tenant-Subdomain
// header so the tenant-keyed cache serves THIS tenant's settings; without a
// resolved tenant the response is null rather than another tenant's theme.
Route::get('/theme-settings', function () {
    return response()->json([
        'success' => true,
        'settings' => tenant_cache('theme_settings'),
    ]);
})->middleware([IdentifyTenant::class, 'throttle:60,1']); // Allow 60 requests per minute

// Protected routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
