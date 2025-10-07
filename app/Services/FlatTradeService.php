<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FlatTradeService
{
    protected string $baseUrl;
    protected string $authApiUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $accessToken = null;
    protected ?string $refreshToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.flat_trade.base_url', 'https://api.flattrade.in');
        $this->authApiUrl = config('services.flat_trade.auth_api_url', 'https://authapi.flattrade.in');
        $this->apiKey = config('services.flat_trade.api_key');
        $this->apiSecret = config('services.flat_trade.api_secret');
        $this->clientId = config('services.flat_trade.client_id');
        $this->clientSecret = config('services.flat_trade.client_secret');
    }

    /**
     * Generate SHA-256 hash for api_secret as per FlatTrade documentation
     * SHA-256 hash of (api_key + request_code + api_secret)
     */
    protected function generateApiSecretHash(string $requestCode): string
    {
        $stringToHash = $this->apiKey . $requestCode . $this->apiSecret;
        return hash('sha256', $stringToHash);
    }

    /**
     * Authenticate with FlatTrade API using request_code
     */
    public function authenticate(string $requestCode): bool
    {
        try {
            // Generate the SHA-256 hash for api_secret
            $apiSecretHash = $this->generateApiSecretHash($requestCode);

            $response = Http::post($this->authApiUrl . '/trade/apitoken', [
                'api_key' => $this->apiKey,
                'request_code' => $requestCode,
                'api_secret' => $apiSecretHash,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if the response indicates success
                if (isset($data['status']) && $data['status'] === 'Ok' && isset($data['token'])) {
                    $this->accessToken = $data['token'];
                    $this->clientId = $data['client'] ?? $this->clientId;

                    // Store tokens securely
                    Cache::put('flat_trade_access_token', $this->accessToken, now()->addMinutes(55));
                    Cache::put('flat_trade_client_id', $this->clientId, now()->addDays(30));

                    Log::info('FlatTrade authentication successful', [
                        'client' => $this->clientId,
                        'token_received' => !empty($this->accessToken)
                    ]);
                    return true;
                } else {
                    Log::error('FlatTrade authentication failed - invalid response', [
                        'response' => $data,
                        'status_code' => $response->status()
                    ]);
                    return false;
                }
            }

            Log::error('FlatTrade authentication failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('FlatTrade authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Refresh access token - FlatTrade tokens may need to be re-authenticated
     * For now, we'll return false as FlatTrade doesn't seem to have a refresh token flow
     */
    public function refreshAccessToken(): bool
    {
        // FlatTrade doesn't appear to have a refresh token mechanism
        // Users will need to re-authenticate when token expires
        Log::info('FlatTrade token refresh not supported - re-authentication required');
        return false;
    }

    /**
     * Get valid access token
     */
    protected function getValidAccessToken(): ?string
    {
        $this->accessToken = Cache::get('flat_trade_access_token');
        $this->clientId = Cache::get('flat_trade_client_id', $this->clientId);

        if (!$this->accessToken) {
            Log::warning('No FlatTrade access token available - re-authentication required');
            return null;
        }

        return $this->accessToken;
    }

    /**
     * Make authenticated API request
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getValidAccessToken();
        if (!$token) {
            throw new \Exception('No valid access token available. Please re-authenticate with FlatTrade.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->$method($this->baseUrl . $endpoint, $data);

        if ($response->status() === 401) {
            // Token expired or invalid
            Log::warning('FlatTrade API token expired or invalid', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status()
            ]);
            throw new \Exception('Authentication failed. Please re-authenticate with FlatTrade.');
        }

        if (!$response->successful()) {
            Log::error('FlatTrade API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new \Exception('API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get user profile and account information
     */
    public function getUserProfile(): array
    {
        return $this->makeRequest('GET', '/user/profile');
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(): array
    {
        return $this->makeRequest('GET', '/user/balance');
    }

    /**
     * Get holdings (current positions)
     */
    public function getHoldings(): array
    {
        return $this->makeRequest('GET', '/user/holdings');
    }

    /**
     * Get order book for a symbol
     */
    public function getOrderBook(string $symbol): array
    {
        return $this->makeRequest('GET', "/market/orderbook/{$symbol}");
    }

    /**
     * Get live market data for a symbol
     */
    public function getMarketData(string $symbol): array
    {
        return $this->makeRequest('GET', "/market/quote/{$symbol}");
    }

    /**
     * Place a buy order
     */
    public function placeBuyOrder(array $orderData): array
    {
        $orderData['transaction_type'] = 'BUY';
        return $this->placeOrder($orderData);
    }

    /**
     * Place a sell order
     */
    public function placeSellOrder(array $orderData): array
    {
        $orderData['transaction_type'] = 'SELL';
        return $this->placeOrder($orderData);
    }

    /**
     * Place an order (buy or sell)
     */
    public function placeOrder(array $orderData): array
    {
        // Validate required fields
        $requiredFields = ['symbol', 'quantity', 'transaction_type'];
        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Set default values
        $orderData['product'] = $orderData['product'] ?? 'CNC'; // Cash and Carry
        $orderData['order_type'] = $orderData['order_type'] ?? 'MARKET';
        $orderData['validity'] = $orderData['validity'] ?? 'DAY';

        // Add timestamp
        $orderData['timestamp'] = now()->timestamp;

        Log::info('Placing FlatTrade order', $orderData);

        return $this->makeRequest('POST', '/orders', $orderData);
    }

    /**
     * Get order status
     */
    public function getOrderStatus(string $orderId): array
    {
        return $this->makeRequest('GET', "/orders/{$orderId}");
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderId): array
    {
        return $this->makeRequest('DELETE', "/orders/{$orderId}");
    }

    /**
     * Get order history
     */
    public function getOrderHistory(array $filters = []): array
    {
        $queryParams = http_build_query($filters);
        $endpoint = $queryParams ? "/orders?{$queryParams}" : '/orders';

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get trade history
     */
    public function getTradeHistory(array $filters = []): array
    {
        $queryParams = http_build_query($filters);
        $endpoint = $queryParams ? "/trades?{$queryParams}" : '/trades';

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Advanced Trading Methods
     */

    /**
     * Place a limit order with price protection
     */
    public function placeLimitOrder(string $symbol, int $quantity, float $price, string $transactionType = 'BUY'): array
    {
        $orderData = [
            'symbol' => $symbol,
            'quantity' => $quantity,
            'price' => $price,
            'order_type' => 'LIMIT',
            'transaction_type' => $transactionType,
            'product' => 'CNC',
            'validity' => 'DAY',
        ];

        return $this->placeOrder($orderData);
    }

    /**
     * Place a stop loss order
     */
    public function placeStopLossOrder(string $symbol, int $quantity, float $triggerPrice, string $transactionType = 'SELL'): array
    {
        $orderData = [
            'symbol' => $symbol,
            'quantity' => $quantity,
            'trigger_price' => $triggerPrice,
            'order_type' => 'SL',
            'transaction_type' => $transactionType,
            'product' => 'CNC',
            'validity' => 'DAY',
        ];

        return $this->placeOrder($orderData);
    }

    /**
     * Place a bracket order (entry + stop loss + target)
     */
    public function placeBracketOrder(string $symbol, int $quantity, float $entryPrice, float $stopLossPrice, float $targetPrice, string $transactionType = 'BUY'): array
    {
        $orderData = [
            'symbol' => $symbol,
            'quantity' => $quantity,
            'price' => $entryPrice,
            'order_type' => 'BRACKET',
            'transaction_type' => $transactionType,
            'product' => 'CNC',
            'validity' => 'DAY',
            'stop_loss_price' => $stopLossPrice,
            'target_price' => $targetPrice,
        ];

        return $this->placeOrder($orderData);
    }

    /**
     * Smart Trading Logic Methods
     */

    /**
     * Execute a smart buy order with market analysis
     */
    public function smartBuy(string $symbol, int $quantity, array $options = []): array
    {
        try {
            // Get current market data
            $marketData = $this->getMarketData($symbol);

            if (!$marketData || !isset($marketData['last_price'])) {
                throw new \Exception("Unable to get market data for {$symbol}");
            }

            $currentPrice = $marketData['last_price'];
            $orderType = $options['order_type'] ?? 'MARKET';
            $maxPrice = $options['max_price'] ?? null;

            // Price protection
            if ($maxPrice && $currentPrice > $maxPrice) {
                throw new \Exception("Current price {$currentPrice} exceeds maximum allowed price {$maxPrice}");
            }

            // Check if we have sufficient balance
            $balance = $this->getAccountBalance();
            $requiredAmount = $quantity * $currentPrice;

            if ($balance['available_cash'] < $requiredAmount) {
                throw new \Exception("Insufficient balance. Required: {$requiredAmount}, Available: {$balance['available_cash']}");
            }

            $orderData = [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'product' => 'CNC',
                'validity' => 'DAY',
            ];

            if ($orderType === 'LIMIT' && isset($options['price'])) {
                $orderData['price'] = $options['price'];
            }

            Log::info('Executing smart buy order', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'current_price' => $currentPrice,
                'order_type' => $orderType
            ]);

            return $this->placeBuyOrder($orderData);

        } catch (\Exception $e) {
            Log::error('Smart buy order failed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Execute a smart sell order with position analysis
     */
    public function smartSell(string $symbol, int $quantity, array $options = []): array
    {
        try {
            // Check current holdings
            $holdings = $this->getHoldings();
            $symbolHolding = collect($holdings)->firstWhere('symbol', $symbol);

            if (!$symbolHolding || $symbolHolding['quantity'] < $quantity) {
                throw new \Exception("Insufficient holdings for {$symbol}. Available: " . ($symbolHolding['quantity'] ?? 0));
            }

            // Get current market data
            $marketData = $this->getMarketData($symbol);
            $currentPrice = $marketData['last_price'] ?? 0;

            $orderType = $options['order_type'] ?? 'MARKET';
            $minPrice = $options['min_price'] ?? null;

            // Price protection for sell orders
            if ($minPrice && $currentPrice < $minPrice) {
                throw new \Exception("Current price {$currentPrice} is below minimum allowed price {$minPrice}");
            }

            $orderData = [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'product' => 'CNC',
                'validity' => 'DAY',
            ];

            if ($orderType === 'LIMIT' && isset($options['price'])) {
                $orderData['price'] = $options['price'];
            }

            Log::info('Executing smart sell order', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'current_price' => $currentPrice,
                'order_type' => $orderType
            ]);

            return $this->placeSellOrder($orderData);

        } catch (\Exception $e) {
            Log::error('Smart sell order failed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Execute a complete trade cycle (buy and sell with stop loss)
     */
    public function executeTradeCycle(string $symbol, int $quantity, float $entryPrice, float $stopLossPercent = 5, float $targetPercent = 10): array
    {
        try {
            $results = [];

            // Calculate stop loss and target prices
            $stopLossPrice = $entryPrice * (1 - $stopLossPercent / 100);
            $targetPrice = $entryPrice * (1 + $targetPercent / 100);

            // Place bracket order
            $bracketOrder = $this->placeBracketOrder(
                $symbol,
                $quantity,
                $entryPrice,
                $stopLossPrice,
                $targetPrice,
                'BUY'
            );

            $results['bracket_order'] = $bracketOrder;

            Log::info('Trade cycle initiated', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'stop_loss_price' => $stopLossPrice,
                'target_price' => $targetPrice
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Trade cycle failed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get market analysis for a symbol
     */
    public function getMarketAnalysis(string $symbol): array
    {
        try {
            $marketData = $this->getMarketData($symbol);
            $orderBook = $this->getOrderBook($symbol);

            $analysis = [
                'symbol' => $symbol,
                'current_price' => $marketData['last_price'] ?? 0,
                'change_percent' => $marketData['change_percent'] ?? 0,
                'volume' => $marketData['volume'] ?? 0,
                'bid_price' => $orderBook['bids'][0]['price'] ?? 0,
                'ask_price' => $orderBook['asks'][0]['price'] ?? 0,
                'spread' => 0,
                'timestamp' => now(),
            ];

            if ($analysis['bid_price'] && $analysis['ask_price']) {
                $analysis['spread'] = $analysis['ask_price'] - $analysis['bid_price'];
            }

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Market analysis failed', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Disconnect and clear tokens
     */
    public function disconnect(): bool
    {
        try {
            Cache::forget('flat_trade_access_token');
            Cache::forget('flat_trade_client_id');

            $this->accessToken = null;
            $this->refreshToken = null;

            Log::info('FlatTrade disconnected successfully');
            return true;

        } catch (\Exception $e) {
            Log::error('FlatTrade disconnect error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

