<?php

use App\Http\Controllers\FlatTradeController;
use App\Http\Controllers\Nifty50TradingController;
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

        // Test API connection
        Route::get('/test-api', [FlatTradeController::class, 'testApi'])->name('test_api')->can('flat_trade.view');

        // Trading operations
        Route::post('/buy', [FlatTradeController::class, 'placeBuyOrder'])->name('buy')->can('flat_trade.trade');
        Route::post('/sell', [FlatTradeController::class, 'placeSellOrder'])->name('sell')->can('flat_trade.trade');
        Route::post('/bracket-order', [FlatTradeController::class, 'placeBracketOrder'])->name('bracket_order')->can('flat_trade.trade');
        Route::post('/cancel-order', [FlatTradeController::class, 'cancelOrder'])->name('cancel_order')->can('flat_trade.trade');
        Route::post('/trade-cycle', [FlatTradeController::class, 'executeTradeCycle'])->name('trade_cycle')->can('flat_trade.trade');

        // Market data and quotes
        Route::get('/market-data', [FlatTradeController::class, 'getMarketData'])->name('market_data')->can('flat_trade.view');
        Route::get('/search-scrip', [FlatTradeController::class, 'searchScrip'])->name('search_scrip')->can('flat_trade.view');
        Route::get('/market-info', [FlatTradeController::class, 'getMarketInfo'])->name('market_info')->can('flat_trade.view');
        Route::get('/time-series', [FlatTradeController::class, 'getTimePriceSeries'])->name('time_series')->can('flat_trade.view');
        Route::get('/eod-chart', [FlatTradeController::class, 'getEODChartData'])->name('eod_chart')->can('flat_trade.view');

        // Account and portfolio info
        Route::get('/balance', [FlatTradeController::class, 'getBalance'])->name('balance')->can('flat_trade.view');
        Route::get('/holdings', [FlatTradeController::class, 'getHoldings'])->name('holdings')->can('flat_trade.view');
        Route::get('/user-details', [FlatTradeController::class, 'getUserDetails'])->name('user_details')->can('flat_trade.view');

        // Orders and trades
        Route::get('/order-book', [FlatTradeController::class, 'getOrderBook'])->name('order_book')->can('flat_trade.view');
        Route::get('/trade-book', [FlatTradeController::class, 'getTradeBook'])->name('trade_book')->can('flat_trade.view');
        Route::get('/position-book', [FlatTradeController::class, 'getPositionBook'])->name('position_book')->can('flat_trade.view');

        // Alerts management
        Route::post('/set-alert', [FlatTradeController::class, 'setAlert'])->name('set_alert')->can('flat_trade.trade');
        Route::get('/pending-alerts', [FlatTradeController::class, 'getPendingAlerts'])->name('pending_alerts')->can('flat_trade.view');
        Route::post('/cancel-alert', [FlatTradeController::class, 'cancelAlert'])->name('cancel_alert')->can('flat_trade.trade');

        // Nifty 50 Real Trading Routes
        Route::prefix('nifty50')->name('nifty50.')->group(function (): void {
            Route::get('/', function () {
                return view('nifty50-trading');
            })->name('dashboard')->can('flat_trade.view');

            Route::get('/best-stocks', [Nifty50TradingController::class, 'getBestStocks'])->name('best_stocks')->can('flat_trade.view');
            Route::post('/execute-trading', [Nifty50TradingController::class, 'executeRealTrading'])->name('execute_trading')->can('flat_trade.trade');
            Route::get('/market-status', [Nifty50TradingController::class, 'getMarketStatus'])->name('market_status')->can('flat_trade.view');
            Route::get('/positions', [Nifty50TradingController::class, 'getUserPositions'])->name('positions')->can('flat_trade.view');
            Route::post('/execute-sell-orders', [Nifty50TradingController::class, 'executeSellOrders'])->name('execute_sell_orders')->can('flat_trade.trade');
        });
    });
});
