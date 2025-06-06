<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TelegramWebhookController;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Theme settings route - public but with throttling to prevent abuse
Route::get('/theme-settings', function () {
    $themeSettings = Cache::get('theme_settings') ?: Configuration::where('key', 'theme_settings')->value('value');

    return response()->json([
        'success' => true,
        'settings' => $themeSettings ? json_decode($themeSettings, true) : null,
    ]);
})->middleware('throttle:60,1'); // Allow 60 requests per minute

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/products', [ProductController::class, 'list']);
});
