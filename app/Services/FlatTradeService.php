<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    protected ?string $jKey = null;

    public function __construct()
    {
        $this->baseUrl = config('services.flat_trade.base_url', 'https://piconnect.flattrade.in/PiConnectTP');
        $this->authApiUrl = config('services.flat_trade.auth_api_url', 'https://authapi.flattrade.in');
        $this->apiKey = config('services.flat_trade.api_key');
        $this->apiSecret = config('services.flat_trade.api_secret');
        $this->clientId = config('services.flat_trade.client_id');
        $this->clientSecret = config('services.flat_trade.client_secret');
        $this->jKey = config('services.flat_trade.j_key');
    }

    /**
     * Generate SHA-256 hash for api_secret as per FlatTrade documentation
     * SHA-256 hash of (api_key + request_code + api_secret)
     */
    protected function generateApiSecretHash(string $requestCode): string
    {
        $stringToHash = $this->apiKey.$requestCode.$this->apiSecret;

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
            $payload = [
                'api_key' => $this->apiKey,
                'request_code' => $requestCode,
                'api_secret' => $apiSecretHash,
            ];
            $url = $this->authApiUrl.'/trade/apitoken';

            $response = Http::post($url, $payload);
            if ($response->successful()) {
                $data = $response->json();
                info($data);
                // Check if the response indicates success
                if (isset($data['stat']) && $data['stat'] === 'Ok' && isset($data['token'])) {
                    $this->accessToken = $data['token'];
                    $this->clientId = $data['client'] ?? $this->clientId;

                    // Store tokens securely in cache
                    config(['services.flat_trade.j_key' => $this->accessToken]);

                    writeToEnv('FLAT_TRADE_J_KEY', $this->accessToken);

                    Artisan::call('optimize:clear');

                    Log::info('FlatTrade authentication successful', [
                        'client' => $this->clientId,
                        'token_received' => ! empty($this->accessToken),
                        'user_id' => Auth::id(),
                    ]);

                    return true;
                } else {
                    Log::error('FlatTrade authentication failed - invalid response', [
                        'response' => $data,
                        'status_code' => $response->status(),
                    ]);

                    return false;
                }
            }

            Log::error('FlatTrade authentication failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('FlatTrade authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

        if (! $this->accessToken) {
            Log::warning('No FlatTrade access token available - re-authentication required');

            return null;
        }

        return $this->accessToken;
    }

    /**
     * Make PiConnect API request with jData and jKey format
     */
    protected function makePiConnectRequest(string $endpoint, array $jData): array
    {
        if (! $this->jKey) {
            throw new \Exception('jKey not configured. Please set FLAT_TRADE_J_KEY in environment.');
        }

        $url = $this->baseUrl.'/'.$endpoint;
        // Format data as jData=JSON&jKey=KEY
        $jDataJson = json_encode($jData);
        $postData = "jData={$jDataJson}&jKey={$this->jKey}";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $response = Http::withHeaders($headers)->withBody($postData, 'application/x-www-form-urlencoded')->post($url);
        if (! $response->successful()) {
            Log::error('PiConnect API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new \Exception('API request failed: '.$response->body());
        }

        return $response->json();
    }

    // ===========================================
    // PLACE ORDER APIs
    // ===========================================

    /**
     * Place Market Order
     */
    public function placeMarketOrder(string $exchange, string $symbol, int $quantity, string $transactionType = 'B', string $product = 'C', int $marketProtection = 5): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'mkt_protection' => (string) $marketProtection,
            'prc' => '0',
            'dscqty' => '0',
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'MKT',
            'ret' => 'DAY',
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Limit Order
     */
    public function placeLimitOrder(string $exchange, string $symbol, int $quantity, float $price, string $transactionType = 'B', string $product = 'C'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'dscqty' => '0',
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'LMT',
            'ret' => 'DAY',
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Stop Loss Limit Order
     */
    public function placeStopLossLimitOrder(string $exchange, string $symbol, int $quantity, float $price, float $triggerPrice, string $transactionType = 'B', string $product = 'C'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'trgprc' => (string) $triggerPrice,
            'dscqty' => '0',
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'SL-LMT',
            'ret' => 'DAY',
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Stop Loss Market Order
     */
    public function placeStopLossMarketOrder(string $exchange, string $symbol, int $quantity, float $triggerPrice, string $transactionType = 'B', string $product = 'C', int $marketProtection = 5): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'mkt_protection' => (string) $marketProtection,
            'prc' => '0',
            'trgprc' => (string) $triggerPrice,
            'dscqty' => '0',
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'SL-MKT',
            'ret' => 'DAY',
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Cover Order Limit
     */
    public function placeCoverOrderLimit(string $exchange, string $symbol, int $quantity, float $price, float $bookLossPrice, string $transactionType = 'B', string $product = 'H'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'LMT',
            'ret' => 'DAY',
            'blprc' => (string) $bookLossPrice,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Cover Order Market
     */
    public function placeCoverOrderMarket(string $exchange, string $symbol, int $quantity, float $bookLossPrice, string $transactionType = 'B', string $product = 'H', int $marketProtection = 5): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'mkt_protection' => (string) $marketProtection,
            'prc' => '0',
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'MKT',
            'ret' => 'DAY',
            'blprc' => (string) $bookLossPrice,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Cover Order Stop Loss Limit
     */
    public function placeCoverOrderStopLossLimit(string $exchange, string $symbol, int $quantity, float $price, float $triggerPrice, float $bookLossPrice, string $transactionType = 'B', string $product = 'H'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'trgprc' => (string) $triggerPrice,
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'SL-LMT',
            'ret' => 'DAY',
            'blprc' => (string) $bookLossPrice,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Bracket Order Limit
     */
    public function placeBracketOrderLimit(string $exchange, string $symbol, int $quantity, float $price, float $bookLossPrice, float $bookProfitPrice, float $trailPrice, string $transactionType = 'B', string $product = 'B'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'LMT',
            'ret' => 'DAY',
            'blprc' => (string) $bookLossPrice,
            'bpprc' => (string) $bookProfitPrice,
            'trailprc' => (string) $trailPrice,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Bracket Order Market
     */
    public function placeBracketOrderMarket(string $exchange, string $symbol, int $quantity, float $bookLossPrice, float $bookProfitPrice, float $trailPrice, string $transactionType = 'B', string $product = 'B', int $marketProtection = 5): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'mkt_protection' => (string) $marketProtection,
            'prc' => '0',
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'MKT',
            'ret' => 'DAY',
            'blprc' => (string) $bookLossPrice,
            'bpprc' => (string) $bookProfitPrice,
            'trailprc' => (string) $trailPrice,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    /**
     * Place Bracket Order Stop Loss Limit
     */
    public function placeBracketOrderStopLossLimit(string $exchange, string $symbol, int $quantity, float $price, float $triggerPrice, float $bookLossPrice, float $bookProfitPrice, float $trailPrice, string $transactionType = 'B', string $product = 'B'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'trgprc' => (string) $triggerPrice,
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => 'SL-LMT',
            'ret' => 'DAY',
            'blprc' => (string) $bookLossPrice,
            'bpprc' => (string) $bookProfitPrice,
            'trailprc' => (string) $trailPrice,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PlaceOrder', $jData);
    }

    // ===========================================
    // HOLDINGS AND LIMITS APIs
    // ===========================================

    /**
     * Get Holdings
     */
    public function getHoldings(string $product = 'C'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'prd' => $product,
        ];

        return $this->makePiConnectRequest('Holdings', $jData);
    }

    /**
     * Get Limits
     */
    public function getLimits(): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('Limits', $jData);
    }

    // ===========================================
    // MARKET INFO APIs
    // ===========================================

    /**
     * Get Index List
     */
    public function getIndexList(string $exchange): array
    {
        $jData = [
            'uid' => $this->clientId,
            'exch' => $exchange,
        ];

        return $this->makePiConnectRequest('GetIndexList', $jData);
    }

    /**
     * Get Top List Name
     */
    public function getTopListName(string $exchange): array
    {
        $jData = [
            'uid' => $this->clientId,
            'exch' => $exchange,
        ];

        return $this->makePiConnectRequest('TopListName', $jData);
    }

    /**
     * Get Top List
     */
    public function getTopList(string $exchange, string $topListType = 'T', string $basket = 'NSEALL', string $criteria = 'LTP'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'exch' => $exchange,
            'tb' => $topListType,
            'bskt' => $basket,
            'crt' => $criteria,
        ];

        return $this->makePiConnectRequest('TopList', $jData);
    }

    /**
     * Get Time Price Series
     */
    public function getTimePriceSeries(string $exchange, string $token, int $startTime, int $endTime, int $interval = 1): array
    {
        $jData = [
            'uid' => $this->clientId,
            'exch' => $exchange,
            'token' => $token,
            'st' => (string) $startTime,
            'et' => (string) $endTime,
            'intrv' => (string) $interval,
        ];

        return $this->makePiConnectRequest('TPSeries', $jData);
    }

    /**
     * Get EOD Chart Data
     */
    public function getEODChartData(string $symbol, int $fromDate, int $toDate): array
    {
        $jData = [
            'sym' => $symbol,
            'from' => (string) $fromDate,
            'to' => (string) $toDate,
        ];

        return $this->makePiConnectRequest('EODChartData', $jData);
    }

    /**
     * Get Option Greek
     */
    public function getOptionGreek(string $expiryDate = '', string $strikePrice = '', string $spotPrice = '', string $interestRate = '', string $volatility = '', string $optionType = ''): array
    {
        $jData = [
            'exd' => $expiryDate,
            'strprc' => $strikePrice,
            'sptprc' => $spotPrice,
            'int_rate' => $interestRate,
            'volatility' => $volatility,
            'optt' => $optionType,
        ];

        return $this->makePiConnectRequest('GetOptionGreek', $jData);
    }

    /**
     * Get Exchange Message
     */
    public function getExchangeMessage(string $exchange): array
    {
        $jData = [
            'uid' => $this->clientId,
            'exch' => $exchange,
        ];

        return $this->makePiConnectRequest('ExchMsg', $jData);
    }

    /**
     * Get Broker Message
     */
    public function getBrokerMessage(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetBrokerMsg', $jData);
    }

    /**
     * Calculate Span
     */
    public function calculateSpan(array $positions): array
    {
        $jData = [
            'actid' => $this->clientId,
            'pos' => $positions,
        ];

        return $this->makePiConnectRequest('SpanCalc', $jData);
    }

    // ===========================================
    // ALERTS APIs
    // ===========================================

    /**
     * Set Alert
     */
    public function setAlert(string $symbol, string $exchange, string $alertType = '', string $validity = 'DAY', string $remarks = 'Testing'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'tsym' => $symbol,
            'exch' => $exchange,
            'ai_t' => $alertType,
            'validity' => $validity,
            'remarks' => $remarks,
        ];

        return $this->makePiConnectRequest('SetAlert', $jData);
    }

    /**
     * Modify Alert
     */
    public function modifyAlert(string $symbol, string $exchange, string $alertId, string $alertType = '', string $validity = 'DAY', string $remarks = 'Testing'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'tsym' => $symbol,
            'exch' => $exchange,
            'ai_t' => $alertType,
            'al_id' => $alertId,
            'validity' => $validity,
            'remarks' => $remarks,
        ];

        return $this->makePiConnectRequest('ModifyAlert', $jData);
    }

    /**
     * Cancel Alert
     */
    public function cancelAlert(string $alertId): array
    {
        $jData = [
            'uid' => $this->clientId,
            'al_id' => $alertId,
        ];

        return $this->makePiConnectRequest('CancelAlert', $jData);
    }

    /**
     * Get Pending Alert
     */
    public function getPendingAlert(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetPendingAlert', $jData);
    }

    /**
     * Get Enabled Alert Types
     */
    public function getEnabledAlertTypes(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetEnabledAlertTypes', $jData);
    }

    /**
     * Get Unsettled Trading Date
     */
    public function getUnsettledTradingDate(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetUnStledTradingDate', $jData);
    }

    // ===========================================
    // FUNDS APIs
    // ===========================================

    /**
     * Get Max Payout Amount
     */
    public function getMaxPayoutAmount(): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetMaxPayoutAmount', $jData);
    }

    /**
     * Request Funds Payout
     */
    public function requestFundsPayout(float $amount, string $remarks = ''): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'payout' => (string) $amount,
            'remarks' => $remarks,
        ];

        return $this->makePiConnectRequest('FundsPayOutReq', $jData);
    }

    /**
     * Get Payin Report
     */
    public function getPayinReport(string $fromDate = '', string $toDate = ''): array
    {
        $jData = [
            'actid' => $this->clientId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        return $this->makePiConnectRequest('GetPayinReport', $jData);
    }

    /**
     * Get Payout Report
     */
    public function getPayoutReport(string $fromDate = '', string $toDate = ''): array
    {
        $jData = [
            'actid' => $this->clientId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        return $this->makePiConnectRequest('GetPayoutReport', $jData);
    }

    /**
     * Cancel Payout
     */
    public function cancelPayout(string $transactionRefNumber, string $brokerName = ''): array
    {
        $jData = [
            'actid' => $this->clientId,
            'uid' => $this->clientId,
            'trans_ref_num' => $transactionRefNumber,
            'brkname' => $brokerName,
        ];

        return $this->makePiConnectRequest('CancelPayout', $jData);
    }

    // ===========================================
    // USER DETAILS APIs
    // ===========================================

    /**
     * Get User Details
     */
    public function getUserDetails(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('UserDetails', $jData);
    }

    // ===========================================
    // SCRIPS APIs
    // ===========================================

    /**
     * Search Scrip
     */
    public function searchScrip(string $searchText, string $exchange): array
    {
        $jData = [
            'uid' => $this->clientId,
            'stext' => $searchText,
            'exch' => $exchange,
        ];

        return $this->makePiConnectRequest('SearchScrip', $jData);
    }

    /**
     * Get Quotes
     */
    public function getQuotes(string $token, string $exchange): array
    {
        $jData = [
            'uid' => $this->clientId,
            'token' => $token,
            'exch' => $exchange,
        ];

        return $this->makePiConnectRequest('GetQuotes', $jData);
    }

    // ===========================================
    // ORDERS AND TRADES APIs
    // ===========================================

    /**
     * Get Order Margin
     */
    public function getOrderMargin(string $exchange, string $symbol, int $quantity, float $price, string $product = 'C', string $transactionType = 'B', string $priceType = 'LMT', int $originalQuantity = 0, float $originalPrice = 0): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => $priceType,
            'rorgqty' => (string) $originalQuantity,
            'rorgprc' => (string) $originalPrice,
        ];

        return $this->makePiConnectRequest('GetOrderMargin', $jData);
    }

    /**
     * Get Basket Margin
     */
    public function getBasketMargin(string $exchange, string $symbol, int $quantity, float $price, string $product = 'C', string $transactionType = 'B', string $priceType = 'LMT', int $originalQuantity = 0, float $originalPrice = 0): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
            'prd' => $product,
            'trantype' => $transactionType,
            'prctyp' => $priceType,
            'rorgqty' => (string) $originalQuantity,
            'rorgprc' => (string) $originalPrice,
        ];

        return $this->makePiConnectRequest('GetBasketMargin', $jData);
    }

    /**
     * Get Order Book
     */
    public function getOrderBook(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('OrderBook', $jData);
    }

    /**
     * Get Multi Leg Order Book
     */
    public function getMultiLegOrderBook(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('MultiLegOrderBook', $jData);
    }

    /**
     * Get Single Order History
     */
    public function getSingleOrderHistory(string $orderNumber): array
    {
        $jData = [
            'uid' => $this->clientId,
            'norenordno' => $orderNumber,
        ];

        return $this->makePiConnectRequest('SingleOrdHist', $jData);
    }

    /**
     * Get Trade Book
     */
    public function getTradeBook(): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('TradeBook', $jData);
    }

    /**
     * Get Position Book
     */
    public function getPositionBook(string $exchange = 'NSE', string $symbol = 'INFY-EQ', int $quantity = 10, string $product = 'C', string $previousProduct = 'I', string $transactionType = 'B', string $positionType = 'DAY'): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prd' => $product,
            'prevprd' => $previousProduct,
            'trantype' => $transactionType,
            'postype' => $positionType,
            'ordersource' => 'API',
        ];

        return $this->makePiConnectRequest('PositionBook', $jData);
    }

    /**
     * Exit SNO Order
     */
    public function exitSNOOrder(string $product, string $orderNumber): array
    {
        $jData = [
            'uid' => $this->clientId,
            'prd' => $product,
            'norenordno' => $orderNumber,
        ];

        return $this->makePiConnectRequest('ExitSNOOrder', $jData);
    }

    // ===========================================
    // GTT AND OCO APIs
    // ===========================================

    /**
     * Place GTT Order
     */
    public function placeGTTOrder(string $symbol, string $exchange, string $alertType, string $validity, float $conditionValue, string $transactionType, string $priceType, string $product, string $retention, int $quantity, float $price): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'tsym' => $symbol,
            'exch' => $exchange,
            'ai_t' => $alertType,
            'validity' => $validity,
            'd' => (string) $conditionValue,
            'trantype' => $transactionType,
            'prctyp' => $priceType,
            'prd' => $product,
            'ret' => $retention,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
        ];

        return $this->makePiConnectRequest('PlaceGTTOrder', $jData);
    }

    /**
     * Modify GTT Order
     */
    public function modifyGTTOrder(string $symbol, string $exchange, string $alertId, string $alertType, string $validity, float $conditionValue, string $transactionType, string $priceType, string $product, string $retention, int $quantity, float $price): array
    {
        $jData = [
            'uid' => $this->clientId,
            'actid' => $this->clientId,
            'exch' => $exchange,
            'ai_t' => $alertType,
            'validity' => $validity,
            'al_id' => $alertId,
            'd' => (string) $conditionValue,
            'trantype' => $transactionType,
            'prctyp' => $priceType,
            'prd' => $product,
            'ret' => $retention,
            'tsym' => $symbol,
            'qty' => (string) $quantity,
            'prc' => (string) $price,
        ];

        return $this->makePiConnectRequest('ModifyGTTOrder', $jData);
    }

    /**
     * Cancel GTT Order
     */
    public function cancelGTTOrder(string $alertId): array
    {
        $jData = [
            'uid' => $this->clientId,
            'al_id' => $alertId,
        ];

        return $this->makePiConnectRequest('CancelGTTOrder', $jData);
    }

    /**
     * Place OCO Order
     */
    public function placeOCOOrder(string $symbol, string $exchange, string $alertType, string $validity, array $oiVariables, array $placeOrderParams, array $placeOrderParamsLeg2): array
    {
        $jData = [
            'uid' => $this->clientId,
            'ai_t' => $alertType,
            'validity' => $validity,
            'tsym' => $symbol,
            'exch' => $exchange,
            'oivariable' => $oiVariables,
            'place_order_params' => $placeOrderParams,
            'place_order_params_leg2' => $placeOrderParamsLeg2,
        ];

        return $this->makePiConnectRequest('PlaceOCOOrder', $jData);
    }

    /**
     * Modify OCO Order
     */
    public function modifyOCOOrder(string $symbol, string $exchange, string $alertId, string $alertType, string $validity, array $oiVariables, array $placeOrderParams, array $placeOrderParamsLeg2): array
    {
        $jData = [
            'uid' => $this->clientId,
            'ai_t' => $alertType,
            'validity' => $validity,
            'tsym' => $symbol,
            'exch' => $exchange,
            'al_id' => $alertId,
            'oivariable' => $oiVariables,
            'place_order_params' => $placeOrderParams,
            'place_order_params_leg2' => $placeOrderParamsLeg2,
        ];

        return $this->makePiConnectRequest('ModifyOCOOrder', $jData);
    }

    /**
     * Cancel OCO Order
     */
    public function cancelOCOOrder(string $alertId): array
    {
        $jData = [
            'uid' => $this->clientId,
            'al_id' => $alertId,
        ];

        return $this->makePiConnectRequest('CancelOCOOrder', $jData);
    }

    /**
     * Get Pending GTT Order
     */
    public function getPendingGTTOrder(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetPendingGTTOrder', $jData);
    }

    /**
     * Get Enabled GTTs
     */
    public function getEnabledGTTs(): array
    {
        $jData = [
            'uid' => $this->clientId,
        ];

        return $this->makePiConnectRequest('GetEnabledGTTs', $jData);
    }

    // ===========================================
    // UTILITY METHODS
    // ===========================================

    /**
     * Disconnect and clear tokens
     */
    public function disconnect(): bool
    {
        try {
            // Clear cache tokens
            $this->accessToken = null;
            $this->refreshToken = null;

            Log::info('FlatTrade disconnected successfully', [
                'user_id' => Auth::id(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('FlatTrade disconnect error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return false;
        }
    }
}
