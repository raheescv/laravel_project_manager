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
     * This endpoint receives the authorization code from FlatTrade after user authentication.
     * The code and client parameters are sent by FlatTrade as mentioned in their documentation.
     */
    public function handleOAuthRedirect(Request $request): RedirectResponse|JsonResponse
    {
        try {
            // Validate required parameters from FlatTrade
            $code = $request->query('code');
            $client = $request->query('client');
            $state = $request->query('state');

            if (! $code || ! $client) {
                Log::warning('FlatTrade OAuth redirect missing required parameters', [
                    'code' => $code,
                    'client' => $client,
                    'state' => $state,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return redirect()->route('flat_trade::dashboard')
                    ->with('error', 'Invalid authorization response from FlatTrade.');
            }

            // Log successful OAuth callback
            Log::info('FlatTrade OAuth redirect received', [
                'code' => $code,
                'client' => $client,
                'state' => $state,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            // Use FlatTradeService to authenticate
            $flatTradeService = new FlatTradeService();
            $redirectUri = route('flat_trade::oauth.redirect');

            if ($flatTradeService->authenticate($code, $redirectUri)) {
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

            // Log webhook receipt
            Log::info('FlatTrade webhook post back received', [
                'event_type' => $data['event_type'] ?? 'unknown',
                'timestamp' => $data['timestamp'] ?? now(),
                'payload_size' => strlen($payload),
                'ip' => $request->ip(),
            ]);

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
                    Log::info('FlatTrade webhook event type not handled', [
                        'event_type' => $eventType,
                        'data' => $data,
                    ]);
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
     * Display FlatTrade dashboard
     */
    public function dashboard(): View
    {
        // TODO: Fetch user's FlatTrade account data
        // TODO: Get recent trades, account balance, etc.

        return view('flat-trade.dashboard', [
            'account_connected' => false, // TODO: Check if user has connected account
            'recent_trades' => [],
            'account_balance' => 0,
            'account_status' => 'disconnected',
        ]);
    }

    /**
     * Initiate FlatTrade account connection
     */
    public function connect(): RedirectResponse
    {
        // TODO: Generate OAuth state parameter for security
        $state = bin2hex(random_bytes(16));

        // TODO: Store state in session or cache for validation

        // TODO: Build FlatTrade OAuth URL with proper parameters
        $oauthUrl = config('services.flat_trade.base_url') . '/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.flat_trade.client_id'),
            'redirect_uri' => route('flat_trade::oauth.redirect'),
            'state' => $state,
            'scope' => 'read,write', // Adjust based on FlatTrade's available scopes
        ]);

        return redirect($oauthUrl);
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
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
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
            'order_type' => 'required|in:MARKET,LIMIT',
            'price' => 'nullable|numeric|min:0.01',
            'max_price' => 'nullable|numeric|min:0.01',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $orderData = $request->only(['symbol', 'quantity', 'order_type', 'price']);
            $options = $request->only(['max_price']);

            if ($request->order_type === 'MARKET') {
                $result = $flatTradeService->smartBuy($orderData['symbol'], $orderData['quantity'], $options);
            } else {
                $result = $flatTradeService->placeBuyOrder($orderData);
            }

            Log::info('Buy order placed successfully', [
                'user_id' => Auth::id(),
                'order_data' => $orderData,
                'result' => $result
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
                'request_data' => $request->all()
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
            'order_type' => 'required|in:MARKET,LIMIT',
            'price' => 'nullable|numeric|min:0.01',
            'min_price' => 'nullable|numeric|min:0.01',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $orderData = $request->only(['symbol', 'quantity', 'order_type', 'price']);
            $options = $request->only(['min_price']);

            if ($request->order_type === 'MARKET') {
                $result = $flatTradeService->smartSell($orderData['symbol'], $orderData['quantity'], $options);
            } else {
                $result = $flatTradeService->placeSellOrder($orderData);
            }

            Log::info('Sell order placed successfully', [
                'user_id' => Auth::id(),
                'order_data' => $orderData,
                'result' => $result
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
                'request_data' => $request->all()
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
            'stop_loss_price' => 'required|numeric|min:0.01',
            'target_price' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|in:BUY,SELL',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $result = $flatTradeService->placeBracketOrder(
                $request->symbol,
                $request->quantity,
                $request->entry_price,
                $request->stop_loss_price,
                $request->target_price,
                $request->transaction_type
            );

            Log::info('Bracket order placed successfully', [
                'user_id' => Auth::id(),
                'order_data' => $request->all(),
                'result' => $result
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
                'request_data' => $request->all()
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
                'result' => $result
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
                'order_id' => $request->order_id
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
        ]);

        try {
            $flatTradeService = new FlatTradeService();
            $marketData = $flatTradeService->getMarketData($request->symbol);
            $analysis = $flatTradeService->getMarketAnalysis($request->symbol);

            return response()->json([
                'success' => true,
                'market_data' => $marketData,
                'analysis' => $analysis,
            ]);

        } catch (\Exception $e) {
            Log::error('Market data fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'symbol' => $request->symbol
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
            $balance = $flatTradeService->getAccountBalance();

            return response()->json([
                'success' => true,
                'balance' => $balance,
            ]);

        } catch (\Exception $e) {
            Log::error('Balance fetch failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
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
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Execute trade cycle
     */
    public function executeTradeCycle(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
            'entry_price' => 'required|numeric|min:0.01',
            'stop_loss_percent' => 'nullable|numeric|min:1|max:50',
            'target_percent' => 'nullable|numeric|min:1|max:100',
        ]);

        try {
            $flatTradeService = new FlatTradeService();

            $stopLossPercent = $request->stop_loss_percent ?? 5;
            $targetPercent = $request->target_percent ?? 10;

            $result = $flatTradeService->executeTradeCycle(
                $request->symbol,
                $request->quantity,
                $request->entry_price,
                $stopLossPercent,
                $targetPercent
            );

            Log::info('Trade cycle executed successfully', [
                'user_id' => Auth::id(),
                'trade_data' => $request->all(),
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trade cycle executed successfully',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Trade cycle execution failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
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
