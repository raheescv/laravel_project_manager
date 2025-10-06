<?php

use App\Http\Controllers\FlatTradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FlatTrade API Integration Routes
|--------------------------------------------------------------------------
|
| These routes handle FlatTrade account integration including OAuth redirect
| and webhook post back functionality for professional trading account management.
|
*/

// FlatTrade OAuth Redirect URL - handles authorization code callback
Route::name('flat_trade::')->prefix('flat_trade')->group(function (): void {
    Route::get('/oauth/redirect', [FlatTradeController::class, 'handleOAuthRedirect'])->name('oauth.redirect');

    // FlatTrade PostBack URL - handles webhook notifications and data updates
    Route::post('/webhook/post_back', [FlatTradeController::class, 'handlePostBack'])->name('webhook.post_back');

    // Additional FlatTrade management routes (protected)
    Route::middleware(['auth'])->group(function (): void {
        // FlatTrade account management dashboard
        Route::get('/dashboard', [FlatTradeController::class, 'dashboard'])->name('dashboard')->can('view');

        // Connect FlatTrade account
        Route::get('/connect', [FlatTradeController::class, 'connect'])->name('connect')->can('connect');

        // Disconnect FlatTrade account
        Route::post('/disconnect', [FlatTradeController::class, 'disconnect'])->name('disconnect')->can('disconnect');

        // View trading data
        Route::get('/trades', [FlatTradeController::class, 'trades'])->name('trades')->can('view');

        // View account status
        Route::get('/status', [FlatTradeController::class, 'status'])->name('status')->can('view');
    });
});
