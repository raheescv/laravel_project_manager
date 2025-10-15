<?php

namespace App\Http\Controllers;

use App\Services\FlatTradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FlatTradeController extends Controller
{
    /**
     * Handle FlatTrade OAuth redirect callback
     *
     * This endpoint receives the request_code from FlatTrade after user authentication.
     * The request_code is a one-time code that needs to be exchanged for a token.
     */
    public function handleOAuthRedirect(Request $request): RedirectResponse|JsonResponse
    {
        try {
            // Validate required parameters from FlatTrade
            $code = $request->query('code');
            $client = $request->query('client');

            if (! $code) {
                Log::warning('FlatTrade OAuth redirect missing authorization code', [
                    'code' => $code,
                    'client' => $client,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return redirect()->route('flat_trade::dashboard')
                    ->with('error', 'Invalid authorization response from FlatTrade.');
            }

            // Clear the state from session after validation
            session()->forget('flat_trade_oauth_state');

            // Log successful OAuth callback
            Log::info('FlatTrade OAuth redirect received', [
                'authorization_code' => $code,
                'client' => $client,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            // Use FlatTradeService to authenticate with authorization code
            $flatTradeService = new FlatTradeService();

            if ($flatTradeService->authenticate($code)) {
                return redirect()->route('flat_trade::dashboard')
                    ->with('success', 'FlatTrade account connected successfully!');
            } else {
                return redirect()->route('flat_trade::dashboard')
                    ->with('error', 'Failed to authenticate with FlatTrade. Please try again.');
            }

        } catch (\Exception $e) {
            Log::error('FlatTrade OAuth redirect error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return redirect()->route('flat_trade::dashboard')
                ->with('error', 'Failed to process FlatTrade authorization. Please try again.');
        }
    }

    /**
     * Handle FlatTrade webhook post back notifications
     *
     * This endpoint receives real-time updates from FlatTrade about account changes,
     * trade executions, balance updates, and other important events.
     */
    public function handlePostBack(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature if FlatTrade provides one
            $signature = $request->header('X-FlatTrade-Signature');
            $payload = $request->getContent();

            // TODO: Implement signature verification
            // if (!$this->verifyWebhookSignature($signature, $payload)) {
            //     return response()->json(['error' => 'Invalid signature'], 401);
            // }

            $data = $request->json()->all();

            // // Log webhook receipt
            // Log::info('FlatTrade webhook post back received', [
            //     'event_type' => $data['event_type'] ?? 'unknown',
            //     'timestamp' => $data['timestamp'] ?? now(),
            //     'payload_size' => strlen($payload),
            //     'ip' => $request->ip(),
            // ]);

            // Process different types of webhook events
            $eventType = $data['event_type'] ?? 'unknown';

            switch ($eventType) {
                case 'trade_executed':
                    $this->handleTradeExecuted($data);
                    break;

                case 'account_updated':
                    $this->handleAccountUpdated($data);
                    break;

                case 'balance_changed':
                    $this->handleBalanceChanged($data);
                    break;

                case 'order_status_changed':
                    $this->handleOrderStatusChanged($data);
                    break;

                default:
                    // Log::info('FlatTrade webhook event type not handled', [
                    //     'event_type' => $eventType,
                    //     'data' => $data,
                    // ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Webhook processed']);

        } catch (\Exception $e) {
            Log::error('FlatTrade webhook post back error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Test API connection
     */
    public function testApi(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $result = $flatTradeService->getUserDetails();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display FlatTrade dashboard
     */
    public function dashboard(): View
    {
        $flatTradeService = new FlatTradeService();
        $accountConnected = false;
        $accountBalance = 0;
        $accountStatus = 'disconnected';
        $recentTrades = [];
        $userProfile = null;
        $holdings = [];
        $limits = [];

        try {
            // Check if user has a valid access token
            $accessToken = config('services.flat_trade.j_key');
            if ($accessToken) {
                // Try to get user details to verify connection
                $userProfile = $flatTradeService->getUserDetails();
                $accountConnected = true;
                $accountStatus = 'connected';

                // Get holdings
                $holdings = $flatTradeService->getHoldings();

                // Get limits
                $limits = $flatTradeService->getLimits();

                // Get trade book for recent trades
                $tradeBook = $flatTradeService->getTradeBook();
                $recentTrades = $tradeBook['data'] ?? [];
                // Extract balance from limits if available
                if (isset($limits['cash'])) {
                    $accountBalance = $limits['cash'] ?? 0;
                }
            }
        } catch (\Exception $e) {
            Log::info('FlatTrade dashboard - account not connected or token expired', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
        }

        $data = [
            'account_connected' => $accountConnected,
            'recent_trades' => $recentTrades,
            'account_balance' => $accountBalance,
            'account_status' => $accountStatus,
            'user_profile' => $userProfile,
            'holdings' => $holdings,
            'limits' => $limits,
        ];

        return view('flat-trade.dashboard', $data);
    }

    /**
     * Initiate FlatTrade account connection
     */
    public function connect(): RedirectResponse
    {
        // Generate state parameter for security
        $state = bin2hex(random_bytes(16));

        // Store state in session for validation
        session(['flat_trade_oauth_state' => $state]);

        // Build FlatTrade OAuth authorization URL as per the provided format
        $authUrl = 'https://auth.flattrade.in/?app_key='.config('services.flat_trade.api_key');

        Log::info('FlatTrade OAuth authorization initiated', [
            'user_id' => Auth::id(),
            'state' => $state,
            'auth_url' => $authUrl,
        ]);

        return redirect($authUrl);
    }

    /**
     * Disconnect FlatTrade account
     */
    public function disconnect(): RedirectResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $flatTradeService->disconnect();

            return redirect()->route('flat_trade::dashboard')
                ->with('success', 'FlatTrade account disconnected successfully.');
        } catch (\Exception $e) {
            Log::error('FlatTrade disconnect error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('flat_trade::dashboard')
                ->with('error', 'Failed to disconnect FlatTrade account.');
        }
    }

    /**
     * View trading data
     */
    public function trades(): View
    {
        // TODO: Fetch user's trade history from FlatTrade API

        return view('flat-trade.trades', [
            'trades' => [],
        ]);
    }

    /**
     * View account status
     */
    public function status(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();

            // Try to get user profile to check connection status
            $profile = $flatTradeService->getUserProfile();
            $balance = $flatTradeService->getAccountBalance();

            return response()->json([
                'connected' => true,
                'last_sync' => now(),
                'account_status' => 'connected',
                'profile' => $profile,
                'balance' => $balance,
            ]);
        } catch (\Exception $e) {
            Log::error('FlatTrade status check failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'connected' => false,
                'last_sync' => null,
                'account_status' => 'disconnected',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Place a buy order
     */
    public function placeBuyOrder(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
            'order_type' => 'required|in:MARKET,LIMIT,SL-LMT,SL-MKT',
            'price' => 'nullable|numeric|min:0.01',
            'trigger_price' => 'nullable|numeric|min:0.01',
            'exchange' => 'nullable|string|in:NSE,BSE',
            'product' => 'nullable|string|in:C,H,B',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $exchange = $request->exchange ?? 'NSE';
            $symbol = $request->symbol;
            $quantity = $request->quantity;
            $product = $request->product ?? 'C';

            switch ($request->order_type) {
                case 'MARKET':
                    $result = $flatTradeService->placeMarketOrder(
                        $exchange, $symbol, $quantity, 'B', $product
                    );
                    break;

                case 'LIMIT':
                    if (! $request->price) {
                        throw new \Exception('Price is required for limit orders');
                    }
                    $result = $flatTradeService->placeLimitOrder(
                        $exchange, $symbol, $quantity, $request->price, 'B', $product
                    );
                    break;

                case 'SL-LMT':
                    if (! $request->price || ! $request->trigger_price) {
                        throw new \Exception('Price and trigger price are required for stop loss limit orders');
                    }
                    $result = $flatTradeService->placeStopLossLimitOrder(
                        $exchange, $symbol, $quantity, $request->price, $request->trigger_price, 'B', $product
                    );
                    break;

                case 'SL-MKT':
                    if (! $request->trigger_price) {
                        throw new \Exception('Trigger price is required for stop loss market orders');
                    }
                    $result = $flatTradeService->placeStopLossMarketOrder(
                        $exchange, $symbol, $quantity, $request->trigger_price, 'B', $product
                    );
                    break;

                default:
                    throw new \Exception('Invalid order type');
            }

            Log::info('Buy order placed successfully', [
                'user_id' => Auth::id(),
                'order_data' => $request->all(),
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Buy order placed successfully',
                'order' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Buy order failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Place a sell order
     */
    public function placeSellOrder(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
            'order_type' => 'required|in:MARKET,LIMIT,SL-LMT,SL-MKT',
            'price' => 'nullable|numeric|min:0.01',
            'trigger_price' => 'nullable|numeric|min:0.01',
            'exchange' => 'nullable|string|in:NSE,BSE',
            'product' => 'nullable|string|in:C,H,B',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $exchange = $request->exchange ?? 'NSE';
            $symbol = $request->symbol;
            $quantity = $request->quantity;
            $product = $request->product ?? 'C';

            switch ($request->order_type) {
                case 'MARKET':
                    $result = $flatTradeService->placeMarketOrder(
                        $exchange, $symbol, $quantity, 'S', $product
                    );
                    break;

                case 'LIMIT':
                    if (! $request->price) {
                        throw new \Exception('Price is required for limit orders');
                    }
                    $result = $flatTradeService->placeLimitOrder(
                        $exchange, $symbol, $quantity, $request->price, 'S', $product
                    );
                    break;

                case 'SL-LMT':
                    if (! $request->price || ! $request->trigger_price) {
                        throw new \Exception('Price and trigger price are required for stop loss limit orders');
                    }
                    $result = $flatTradeService->placeStopLossLimitOrder(
                        $exchange, $symbol, $quantity, $request->price, $request->trigger_price, 'S', $product
                    );
                    break;

                case 'SL-MKT':
                    if (! $request->trigger_price) {
                        throw new \Exception('Trigger price is required for stop loss market orders');
                    }
                    $result = $flatTradeService->placeStopLossMarketOrder(
                        $exchange, $symbol, $quantity, $request->trigger_price, 'S', $product
                    );
                    break;

                default:
                    throw new \Exception('Invalid order type');
            }

            Log::info('Sell order placed successfully', [
                'user_id' => Auth::id(),
                'order_data' => $request->all(),
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sell order placed successfully',
                'order' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Sell order failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Place a bracket order
     */
    public function placeBracketOrder(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
            'entry_price' => 'required|numeric|min:0.01',
            'book_loss_price' => 'required|numeric|min:0.01',
            'book_profit_price' => 'required|numeric|min:0.01',
            'trail_price' => 'required|numeric|min:0.01',
            'order_type' => 'required|in:LIMIT,MARKET,SL-LMT',
            'trigger_price' => 'nullable|numeric|min:0.01',
            'exchange' => 'nullable|string|in:NSE,BSE',
            'product' => 'nullable|string|in:B',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $exchange = $request->exchange ?? 'NSE';
            $symbol = $request->symbol;
            $quantity = $request->quantity;
            $product = $request->product ?? 'B';

            switch ($request->order_type) {
                case 'LIMIT':
                    $result = $flatTradeService->placeBracketOrderLimit(
                        $exchange, $symbol, $quantity, $request->entry_price,
                        $request->book_loss_price, $request->book_profit_price,
                        $request->trail_price, 'B', $product
                    );
                    break;

                case 'MARKET':
                    $result = $flatTradeService->placeBracketOrderMarket(
                        $exchange, $symbol, $quantity, $request->book_loss_price,
                        $request->book_profit_price, $request->trail_price, 'B', $product
                    );
                    break;

                case 'SL-LMT':
                    if (! $request->trigger_price) {
                        throw new \Exception('Trigger price is required for stop loss limit bracket orders');
                    }
                    $result = $flatTradeService->placeBracketOrderStopLossLimit(
                        $exchange, $symbol, $quantity, $request->entry_price,
                        $request->trigger_price, $request->book_loss_price,
                        $request->book_profit_price, $request->trail_price, 'B', $product
                    );
                    break;

                default:
                    throw new \Exception('Invalid bracket order type');
            }

            Log::info('Bracket order placed successfully', [
                'user_id' => Auth::id(),
                'order_data' => $request->all(),
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bracket order placed successfully',
                'order' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Bracket order failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        try {
            $flatTradeService = new FlatTradeService();
            $result = $flatTradeService->cancelOrder($request->order_id);

            Log::info('Order cancelled successfully', [
                'user_id' => Auth::id(),
                'order_id' => $request->order_id,
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Order cancellation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'order_id' => $request->order_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get market data for a symbol
     */
    public function getMarketData(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'exchange' => 'nullable|string|in:NSE,BSE',
        ]);

        try {
            $flatTradeService = new FlatTradeService();
            $exchange = $request->exchange ?? 'NSE';

            $quotes = $flatTradeService->getQuotes($request->symbol, $exchange);

            return response()->json([
                'success' => true,
                'market_data' => $quotes,
            ]);

        } catch (\Exception $e) {
            Log::error('Market data fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'symbol' => $request->symbol,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Search for scrips
     */
    public function searchScrip(Request $request): JsonResponse
    {
        $request->validate([
            'search_text' => 'required|string|max:50',
            'exchange' => 'nullable|string|in:NSE,BSE',
        ]);

        try {
            $flatTradeService = new FlatTradeService();
            $exchange = $request->exchange ?? 'NSE';

            $results = $flatTradeService->searchScrip($request->search_text, $exchange);

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Scrip search failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'search_text' => $request->search_text,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get order book
     */
    public function getOrderBook(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $orderBook = $flatTradeService->getOrderBook();

            return response()->json([
                'success' => true,
                'order_book' => $orderBook,
            ]);

        } catch (\Exception $e) {
            Log::error('Order book fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get trade book
     */
    public function getTradeBook(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $tradeBook = $flatTradeService->getTradeBook();

            return response()->json([
                'success' => true,
                'trade_book' => $tradeBook,
            ]);

        } catch (\Exception $e) {
            Log::error('Trade book fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get position book
     */
    public function getPositionBook(Request $request): JsonResponse
    {
        $request->validate([
            'exchange' => 'nullable|string|in:NSE,BSE',
            'symbol' => 'nullable|string|max:20',
            'quantity' => 'nullable|integer|min:1',
            'product' => 'nullable|string|in:C,H,B',
            'previous_product' => 'nullable|string|in:I',
            'transaction_type' => 'nullable|string|in:B,S',
            'position_type' => 'nullable|string|in:DAY',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $positionBook = $flatTradeService->getPositionBook(
                $request->exchange ?? 'NSE',
                $request->symbol ?? 'INFY-EQ',
                $request->quantity ?? 10,
                $request->product ?? 'C',
                $request->previous_product ?? 'I',
                $request->transaction_type ?? 'B',
                $request->position_type ?? 'DAY'
            );

            return response()->json([
                'success' => true,
                'position_book' => $positionBook,
            ]);

        } catch (\Exception $e) {
            Log::error('Position book fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set alert
     */
    public function setAlert(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'exchange' => 'required|string|in:NSE,BSE',
            'alert_type' => 'nullable|string|max:20',
            'validity' => 'nullable|string|in:DAY,GTT',
            'remarks' => 'nullable|string|max:100',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $result = $flatTradeService->setAlert(
                $request->symbol,
                $request->exchange,
                $request->alert_type ?? '',
                $request->validity ?? 'DAY',
                $request->remarks ?? 'Alert set via API'
            );

            return response()->json([
                'success' => true,
                'message' => 'Alert set successfully',
                'alert' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Alert setting failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get pending alerts
     */
    public function getPendingAlerts(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $alerts = $flatTradeService->getPendingAlert();

            return response()->json([
                'success' => true,
                'alerts' => $alerts,
            ]);

        } catch (\Exception $e) {
            Log::error('Pending alerts fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel alert
     */
    public function cancelAlert(Request $request): JsonResponse
    {
        $request->validate([
            'alert_id' => 'required|string',
        ]);

        try {
            $flatTradeService = new FlatTradeService();
            $result = $flatTradeService->cancelAlert($request->alert_id);

            return response()->json([
                'success' => true,
                'message' => 'Alert cancelled successfully',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Alert cancellation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'alert_id' => $request->alert_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get account balance
     */
    public function getBalance(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $limits = $flatTradeService->getLimits();

            return response()->json([
                'success' => true,
                'balance' => $limits,
            ]);

        } catch (\Exception $e) {
            Log::error('Balance fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get holdings
     */
    public function getHoldings(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $holdings = $flatTradeService->getHoldings();

            return response()->json([
                'success' => true,
                'holdings' => $holdings,
            ]);

        } catch (\Exception $e) {
            Log::error('Holdings fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user details
     */
    public function getUserDetails(): JsonResponse
    {
        try {
            $flatTradeService = new FlatTradeService();
            $userDetails = $flatTradeService->getUserDetails();

            return response()->json([
                'success' => true,
                'user_details' => $userDetails,
            ]);

        } catch (\Exception $e) {
            Log::error('User details fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get market info (indices, top lists, etc.)
     */
    public function getMarketInfo(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:indices,top_list,top_list_names',
            'exchange' => 'nullable|string|in:NSE,BSE',
            'top_list_type' => 'nullable|string|in:T,L',
            'basket' => 'nullable|string',
            'criteria' => 'nullable|string',
        ]);

        try {
            $flatTradeService = new FlatTradeService();
            $exchange = $request->exchange ?? 'NSE';

            switch ($request->type) {
                case 'indices':
                    $result = $flatTradeService->getIndexList($exchange);
                    break;

                case 'top_list':
                    $result = $flatTradeService->getTopList(
                        $exchange,
                        $request->top_list_type ?? 'T',
                        $request->basket ?? 'NSEALL',
                        $request->criteria ?? 'LTP'
                    );
                    break;

                case 'top_list_names':
                    $result = $flatTradeService->getTopListName($exchange);
                    break;

                default:
                    throw new \Exception('Invalid market info type');
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Market info fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'type' => $request->type,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get time price series
     */
    public function getTimePriceSeries(Request $request): JsonResponse
    {
        $request->validate([
            'exchange' => 'required|string|in:NSE,BSE',
            'token' => 'required|string',
            'start_time' => 'required|integer',
            'end_time' => 'required|integer',
            'interval' => 'nullable|integer|min:1',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $result = $flatTradeService->getTimePriceSeries(
                $request->exchange,
                $request->token,
                $request->start_time,
                $request->end_time,
                $request->interval ?? 1
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Time price series fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get EOD chart data
     */
    public function getEODChartData(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string',
            'from_date' => 'required|integer',
            'to_date' => 'required|integer',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $result = $flatTradeService->getEODChartData(
                $request->symbol,
                $request->from_date,
                $request->to_date
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('EOD chart data fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Execute trade cycle (buy and sell with stop loss)
     */
    public function executeTradeCycle(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
            'entry_price' => 'required|numeric|min:0.01',
            'stop_loss_percent' => 'nullable|numeric|min:1|max:50',
            'target_percent' => 'nullable|numeric|min:1|max:100',
            'exchange' => 'nullable|string|in:NSE,BSE',
            'product' => 'nullable|string|in:C,H,B',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $exchange = $request->exchange ?? 'NSE';
            $symbol = $request->symbol;
            $quantity = $request->quantity;
            $entryPrice = $request->entry_price;
            $stopLossPercent = $request->stop_loss_percent ?? 5;
            $targetPercent = $request->target_percent ?? 10;
            $product = $request->product ?? 'B'; // Use Bracket order for trade cycle

            // Calculate stop loss and target prices
            $stopLossPrice = $entryPrice * (1 - $stopLossPercent / 100);
            $targetPrice = $entryPrice * (1 + $targetPercent / 100);

            // Place bracket order with calculated prices
            $result = $flatTradeService->placeBracketOrderLimit(
                $exchange,
                $symbol,
                $quantity,
                $entryPrice,
                $stopLossPrice,
                $targetPrice,
                1, // trail price
                'B', // buy transaction
                $product
            );

            Log::info('Trade cycle executed successfully', [
                'user_id' => Auth::id(),
                'trade_data' => $request->all(),
                'calculated_prices' => [
                    'entry_price' => $entryPrice,
                    'stop_loss_price' => $stopLossPrice,
                    'target_price' => $targetPrice,
                ],
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trade cycle executed successfully',
                'result' => $result,
                'calculated_prices' => [
                    'entry_price' => $entryPrice,
                    'stop_loss_price' => $stopLossPrice,
                    'target_price' => $targetPrice,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Trade cycle execution failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle trade executed webhook event
     */
    private function handleTradeExecuted(array $data): void
    {
        // TODO: Process trade execution data
        // TODO: Update local database with trade information
        // TODO: Send notifications if configured

        Log::info('Trade executed webhook processed', $data);
    }

    /**
     * Handle account updated webhook event
     */
    private function handleAccountUpdated(array $data): void
    {
        // TODO: Process account update data
        // TODO: Update user's account information

        Log::info('Account updated webhook processed', $data);
    }

    /**
     * Handle balance changed webhook event
     */
    private function handleBalanceChanged(array $data): void
    {
        // TODO: Process balance change data
        // TODO: Update account balance in local database

        Log::info('Balance changed webhook processed', $data);
    }

    /**
     * Handle order status changed webhook event
     */
    private function handleOrderStatusChanged(array $data): void
    {
        // TODO: Process order status change data
        // TODO: Update order status in local database

        Log::info('Order status changed webhook processed', $data);
    }
}
