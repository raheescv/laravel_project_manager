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
        Route::get('/dashboard', [FlatTradeController::class, 'dashboard'])->name('dashboard')->can('flat_trade.view');

        // Connect FlatTrade account
        Route::get('/connect', [FlatTradeController::class, 'connect'])->name('connect')->can('flat_trade.connect');

        // Disconnect FlatTrade account
        Route::post('/disconnect', [FlatTradeController::class, 'disconnect'])->name('disconnect')->can('flat_trade.disconnect');

        // View trading data
        Route::get('/trades', [FlatTradeController::class, 'trades'])->name('trades')->can('flat_trade.view');

        // View account status
        Route::get('/status', [FlatTradeController::class, 'status'])->name('status')->can('flat_trade.view');

        // Trading operations
        Route::post('/buy', [FlatTradeController::class, 'placeBuyOrder'])->name('buy')->can('flat_trade.trade');
        Route::post('/sell', [FlatTradeController::class, 'placeSellOrder'])->name('sell')->can('flat_trade.trade');
        Route::post('/bracket-order', [FlatTradeController::class, 'placeBracketOrder'])->name('bracket_order')->can('flat_trade.trade');
        Route::post('/cancel-order', [FlatTradeController::class, 'cancelOrder'])->name('cancel_order')->can('flat_trade.trade');
        Route::post('/trade-cycle', [FlatTradeController::class, 'executeTradeCycle'])->name('trade_cycle')->can('flat_trade.trade');

        // Market data and account info
        Route::get('/market-data', [FlatTradeController::class, 'getMarketData'])->name('market_data')->can('flat_trade.view');
        Route::get('/balance', [FlatTradeController::class, 'getBalance'])->name('balance')->can('flat_trade.view');
        Route::get('/holdings', [FlatTradeController::class, 'getHoldings'])->name('holdings')->can('flat_trade.view');
    });
});


