<?php

namespace App\Http\Controllers;

use App\Services\FlatTradeService;
use App\Services\RiskManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class Nifty50TradingController extends Controller
{
    protected FlatTradeService $flatTradeService;
    protected RiskManagementService $riskService;

    public function __construct(FlatTradeService $flatTradeService, RiskManagementService $riskService)
    {
        $this->flatTradeService = $flatTradeService;
        $this->riskService = $riskService;
    }

    /**
     * Get best performing Nifty 50 stocks
     */
    public function getBestStocks(Request $request): JsonResponse
    {
        try {
            $maxStocks = $request->get('max_stocks', 10);
            $minChangePercent = $request->get('min_change_percent', 1.0);
            
            $stocks = $this->fetchBestNifty50Stocks($maxStocks, $minChangePercent);
            
            return response()->json([
                'success' => true,
                'data' => $stocks,
                'count' => count($stocks)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching best Nifty 50 stocks', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stocks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute real trading orders for Nifty 50 stocks
     */
    public function executeRealTrading(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stocks' => 'required|array|min:1|max:10',
            'stocks.*.symbol' => 'required|string|max:20',
            'stocks.*.quantity' => 'required|integer|min:1|max:1000',
            'order_type' => 'required|in:market,limit,bracket',
            'product' => 'required|in:C,H,B',
            'min_profit_percent' => 'nullable|numeric|min:0.1|max:50',
            'max_loss_percent' => 'nullable|numeric|min:0.1|max:50',
            'confirm_trading' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$request->confirm_trading) {
            return response()->json([
                'success' => false,
                'message' => 'Trading confirmation required'
            ], 400);
        }

        try {
            // Risk management checks
            $riskCheck = $this->riskService->validateTradingRequest($request->all());
            if (!$riskCheck['approved']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Risk management check failed',
                    'reason' => $riskCheck['reason']
                ], 400);
            }

            $results = [];
            $totalInvestment = 0;

            foreach ($request->stocks as $stockData) {
                $result = $this->processStockOrder($stockData, $request->all());
                $results[] = $result;
                
                if ($result['success']) {
                    $totalInvestment += $result['investment_amount'];
                }
            }

            // Log the trading session
            Log::info('Nifty50 Real Trading Session Executed', [
                'user_id' => Auth::id(),
                'total_stocks' => count($request->stocks),
                'successful_orders' => count(array_filter($results, fn($r) => $r['success'])),
                'total_investment' => $totalInvestment,
                'order_type' => $request->order_type,
                'product' => $request->product
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trading orders processed',
                'data' => [
                    'results' => $results,
                    'summary' => [
                        'total_stocks' => count($request->stocks),
                        'successful_orders' => count(array_filter($results, fn($r) => $r['success'])),
                        'failed_orders' => count(array_filter($results, fn($r) => !$r['success'])),
                        'total_investment' => $totalInvestment
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Nifty50 Real Trading Failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Trading execution failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get market status and trading hours
     */
    public function getMarketStatus(): JsonResponse
    {
        try {
            $marketStatus = $this->flatTradeService->getMarketStatus();
            
            return response()->json([
                'success' => true,
                'data' => $marketStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get market status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's current positions and holdings
     */
    public function getUserPositions(): JsonResponse
    {
        try {
            $holdings = $this->flatTradeService->getHoldings();
            $positions = $this->flatTradeService->getPositions();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'holdings' => $holdings,
                    'positions' => $positions
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get positions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch best performing Nifty 50 stocks
     */
    protected function fetchBestNifty50Stocks(int $maxStocks, float $minChangePercent): array
    {
        try {
            // Get top gainers from NSE
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            
            if (!isset($topGainers['values']) || empty($topGainers['values'])) {
                return [];
            }

            $nifty50Stocks = $this->getNifty50Symbols();
            $bestStocks = [];
            
            foreach ($topGainers['values'] as $stock) {
                if (count($bestStocks) >= $maxStocks) break;
                
                $symbol = $stock['tsym'] ?? '';
                
                if (in_array($symbol, $nifty50Stocks)) {
                    $quote = $this->getStockQuote($symbol);
                    
                    if ($quote && $this->isStockSuitableForTrading($quote, $minChangePercent)) {
                        $bestStocks[] = [
                            'symbol' => $symbol,
                            'ltp' => $quote['ltp'] ?? 0,
                            'change_percent' => $quote['chp'] ?? 0,
                            'volume' => $quote['v'] ?? 0,
                            'high' => $quote['h'] ?? 0,
                            'low' => $quote['l'] ?? 0,
                            'open' => $quote['o'] ?? 0,
                            'previous_close' => $quote['pc'] ?? 0,
                            'market_cap' => $quote['mc'] ?? 0,
                            'suitability_score' => $this->calculateSuitabilityScore($quote)
                        ];
                    }
                }
            }

            // Sort by suitability score
            usort($bestStocks, fn($a, $b) => $b['suitability_score'] <=> $a['suitability_score']);

            return $bestStocks;

        } catch (\Exception $e) {
            Log::error('Error fetching best Nifty 50 stocks', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Process individual stock order
     */
    protected function processStockOrder(array $stockData, array $requestData): array
    {
        $symbol = $stockData['symbol'];
        $quantity = $stockData['quantity'];
        $orderType = $requestData['order_type'];
        $product = $requestData['product'];
        $minProfit = $requestData['min_profit_percent'] ?? 2.0;
        $maxLoss = $requestData['max_loss_percent'] ?? 3.0;

        try {
            // Get current quote
            $quote = $this->getStockQuote($symbol);
            if (!$quote) {
                throw new \Exception("Unable to get quote for {$symbol}");
            }

            $ltp = $quote['ltp'];
            $entryPrice = $ltp;
            $stopLossPrice = $entryPrice * (1 - $maxLoss / 100);
            $targetPrice = $entryPrice * (1 + $minProfit / 100);
            $investmentAmount = $entryPrice * $quantity;

            // Place order
            $orderResult = $this->placeOrder($symbol, $quantity, $entryPrice, $stopLossPrice, $targetPrice, $orderType, $product);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLossPrice,
                'target' => $targetPrice,
                'quantity' => $quantity,
                'investment_amount' => $investmentAmount,
                'order_result' => $orderResult
            ];

        } catch (\Exception $e) {
            return [
                'symbol' => $symbol,
                'success' => false,
                'error' => $e->getMessage(),
                'investment_amount' => 0
            ];
        }
    }

    /**
     * Place order based on type
     */
    protected function placeOrder(string $symbol, int $quantity, float $entryPrice, float $stopLossPrice, float $targetPrice, string $orderType, string $product): array
    {
        switch ($orderType) {
            case 'market':
                return $this->flatTradeService->placeMarketOrder('NSE', $symbol, $quantity, 'B', $product);
                
            case 'limit':
                return $this->flatTradeService->placeLimitOrder('NSE', $symbol, $quantity, $entryPrice, 'B', $product);
                
            case 'bracket':
                return $this->flatTradeService->placeBracketOrderLimit(
                    'NSE', $symbol, $quantity, $entryPrice, $stopLossPrice, $targetPrice, 1, 'B', $product
                );
                
            default:
                throw new \Exception("Unsupported order type: {$orderType}");
        }
    }

    /**
     * Get Nifty 50 stock symbols
     */
    protected function getNifty50Symbols(): array
    {
        return [
            'RELIANCE', 'TCS', 'HDFCBANK', 'INFY', 'HINDUNILVR', 'ITC', 'SBIN', 'BHARTIARTL',
            'KOTAKBANK', 'LT', 'ASIANPAINT', 'AXISBANK', 'MARUTI', 'SUNPHARMA', 'TITAN', 'ULTRACEMCO',
            'WIPRO', 'NESTLEIND', 'ONGC', 'POWERGRID', 'NTPC', 'TECHM', 'TATAMOTORS', 'BAJFINANCE',
            'HCLTECH', 'BAJAJFINSV', 'DRREDDY', 'JSWSTEEL', 'TATASTEEL', 'COALINDIA', 'GRASIM', 'BRITANNIA',
            'EICHERMOT', 'HEROMOTOCO', 'DIVISLAB', 'CIPLA', 'APOLLOHOSP', 'ADANIPORTS', 'INDUSINDBK', 'TATACONSUM',
            'BPCL', 'ICICIBANK', 'ADANIENT', 'HDFCLIFE', 'SBILIFE', 'BAJAJ-AUTO', 'UPL', 'SHREECEM'
        ];
    }

    /**
     * Get stock quote details
     */
    protected function getStockQuote(string $symbol): ?array
    {
        try {
            $searchResult = $this->flatTradeService->searchScrip($symbol, 'NSE');
            
            if (!isset($searchResult['values']) || empty($searchResult['values'])) {
                return null;
            }

            $token = $searchResult['values'][0]['token'] ?? null;
            if (!$token) {
                return null;
            }

            $quote = $this->flatTradeService->getQuotes($token, 'NSE');
            
            // Handle the actual FlatTrade API response format
            if (isset($quote['stat']) && $quote['stat'] === 'Ok') {
                // Calculate change percentage from current price and previous close
                $ltp = (float) ($quote['lp'] ?? 0);
                $previousClose = (float) ($quote['c'] ?? 0);
                $changePercent = 0;
                
                if ($previousClose > 0) {
                    $changePercent = (($ltp - $previousClose) / $previousClose) * 100;
                }
                
                // Return formatted quote data
                return [
                    'symbol' => $quote['tsym'] ?? $symbol,
                    'ltp' => $ltp,
                    'previous_close' => $previousClose,
                    'change_percent' => $changePercent,
                    'change_value' => $ltp - $previousClose,
                    'high' => (float) ($quote['h'] ?? 0),
                    'low' => (float) ($quote['l'] ?? 0),
                    'open' => (float) ($quote['o'] ?? 0),
                    'volume' => (int) ($quote['v'] ?? 0),
                    'last_traded_quantity' => (int) ($quote['ltq'] ?? 0),
                    'last_traded_time' => $quote['ltt'] ?? '',
                    'last_traded_date' => $quote['ltd'] ?? '',
                    'upper_circuit' => (float) ($quote['uc'] ?? 0),
                    'lower_circuit' => (float) ($quote['lc'] ?? 0),
                    'week52_high' => (float) ($quote['wk52_h'] ?? 0),
                    'week52_low' => (float) ($quote['wk52_l'] ?? 0),
                    'total_buy_quantity' => (int) ($quote['tbq'] ?? 0),
                    'total_sell_quantity' => (int) ($quote['tsq'] ?? 0),
                    'bid_price_1' => (float) ($quote['bp1'] ?? 0),
                    'ask_price_1' => (float) ($quote['sp1'] ?? 0),
                    'bid_quantity_1' => (int) ($quote['bq1'] ?? 0),
                    'ask_quantity_1' => (int) ($quote['sq1'] ?? 0),
                    'company_name' => $quote['cname'] ?? '',
                    'isin' => $quote['isin'] ?? '',
                    'segment' => $quote['seg'] ?? '',
                    'instrument_name' => $quote['instname'] ?? '',
                    'token' => $quote['token'] ?? $token,
                    'exchange' => $quote['exch'] ?? 'NSE',
                    'order_message' => $quote['ord_msg'] ?? '',
                    'raw_data' => $quote // Keep raw data for debugging
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning("Error getting quote for {$symbol}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if stock is suitable for trading
     */
    protected function isStockSuitableForTrading(array $quote, float $minChangePercent): bool
    {
        $ltp = $quote['ltp'] ?? 0;
        $changePercent = $quote['change_percent'] ?? 0;
        $volume = $quote['volume'] ?? 0;
        $high = $quote['high'] ?? 0;
        $low = $quote['low'] ?? 0;
        $upperCircuit = $quote['upper_circuit'] ?? 0;
        $lowerCircuit = $quote['lower_circuit'] ?? 0;
        $orderMessage = $quote['order_message'] ?? '';

        // Basic filters
        if ($ltp < 50 || $ltp > 10000) return false; // Price range
        if ($changePercent < $minChangePercent) return false; // Minimum gain
        if ($volume < 100000) return false; // Minimum volume
        if ($high <= $low) return false; // Valid price range
        if ($changePercent > 20) return false; // Avoid extremely volatile stocks
        
        // Check for circuit limits (avoid stocks hitting upper/lower circuit)
        if ($ltp >= $upperCircuit * 0.99) return false; // Near upper circuit
        if ($ltp <= $lowerCircuit * 1.01) return false; // Near lower circuit
        
        // Check for warning messages
        if (!empty($orderMessage) && (
            strpos($orderMessage, 'Loss making') !== false ||
            strpos($orderMessage, 'under') !== false ||
            strpos($orderMessage, 'warning') !== false
        )) {
            return false;
        }

        return true;
    }

    /**
     * Calculate suitability score for stock selection
     */
    protected function calculateSuitabilityScore(array $quote): float
    {
        $ltp = $quote['ltp'] ?? 0;
        $changePercent = $quote['change_percent'] ?? 0;
        $volume = $quote['volume'] ?? 0;
        $high = $quote['high'] ?? 0;
        $low = $quote['low'] ?? 0;

        $score = 0;

        // Change percentage score (higher is better, but not too high)
        if ($changePercent >= 1 && $changePercent <= 10) {
            $score += $changePercent * 2;
        } elseif ($changePercent > 10) {
            $score += 20 - ($changePercent - 10); // Penalty for extreme volatility
        }

        // Volume score (higher volume is better)
        if ($volume > 1000000) {
            $score += 10;
        } elseif ($volume > 500000) {
            $score += 5;
        }

        // Price stability score
        if ($high > 0 && $low > 0) {
            $priceRange = (($high - $low) / $ltp) * 100;
            if ($priceRange < 5) {
                $score += 5; // Stable price range
            }
        }

        return $score;
    }
}
