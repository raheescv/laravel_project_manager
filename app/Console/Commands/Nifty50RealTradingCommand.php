<?php

namespace App\Console\Commands;

use App\Services\FlatTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Nifty50RealTradingCommand extends Command
{
    protected $signature = 'trade:nifty50-real 
                            {--quantity=1 : Quantity to buy for each stock}
                            {--max-stocks=5 : Maximum number of stocks to trade}
                            {--min-profit=2.0 : Minimum profit percentage required}
                            {--max-loss=3.0 : Maximum loss percentage allowed}
                            {--dry-run : Run in dry-run mode without placing actual orders}
                            {--order-type=market : Order type (market, limit, bracket)}
                            {--product=C : Product type (C=CNC, H=Holding, B=Bracket)}';

    protected $description = 'Execute real trading orders for best performing Nifty 50 stocks';

    protected FlatTradeService $flatTradeService;

    public function __construct(FlatTradeService $flatTradeService)
    {
        parent::__construct();
        $this->flatTradeService = $flatTradeService;
    }

    public function handle()
    {
        $this->info('🚀 Starting Nifty 50 Real Trading Command');
        
        $quantity = (int) $this->option('quantity');
        $maxStocks = (int) $this->option('max-stocks');
        $minProfit = (float) $this->option('min-profit');
        $maxLoss = (float) $this->option('max-loss');
        $dryRun = $this->option('dry-run');
        $orderType = $this->option('order-type');
        $product = $this->option('product');

        $this->info("Configuration:");
        $this->info("- Quantity per stock: {$quantity}");
        $this->info("- Max stocks to trade: {$maxStocks}");
        $this->info("- Min profit required: {$minProfit}%");
        $this->info("- Max loss allowed: {$maxLoss}%");
        $this->info("- Order type: {$orderType}");
        $this->info("- Product: {$product}");
        $this->info("- Dry run: " . ($dryRun ? 'YES' : 'NO'));

        try {
            // Step 1: Get best performing Nifty 50 stocks
            $this->info("\n📊 Fetching best performing Nifty 50 stocks...");
            $bestStocks = $this->getBestNifty50Stocks($maxStocks);
            if (empty($bestStocks)) {
                $this->error('No suitable stocks found for trading.');
                return;
            }

            $this->info("Found " . count($bestStocks) . " suitable stocks:");
            foreach ($bestStocks as $stock) {
                $this->info("- {$stock['symbol']}: {$stock['change_percent']}% change, LTP: ₹{$stock['ltp']}");
            }

            // Step 2: Analyze each stock and place orders
            $this->info("\n💰 Analyzing stocks and placing orders...");
            $results = [];
            
            foreach ($bestStocks as $stock) {
                $this->info("\n📈 Processing: {$stock['symbol']}");
                
                try {
                    $result = $this->processStock($stock, $quantity, $minProfit, $maxLoss, $orderType, $product, $dryRun);
                    $results[] = $result;
                    
                    if ($result['success']) {
                        $this->info("✅ Order placed successfully for {$stock['symbol']}");
                    } else {
                        $this->warn("⚠️ Order failed for {$stock['symbol']}: {$result['error']}");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error processing {$stock['symbol']}: " . $e->getMessage());
                    $results[] = [
                        'symbol' => $stock['symbol'],
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Step 3: Summary
            $this->displaySummary($results, $dryRun);

        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            Log::error('Nifty50RealTradingCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get best performing Nifty 50 stocks
     */
    protected function getBestNifty50Stocks(int $maxStocks): array
    {
        try {
            // Get top gainers from NSE
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            
            if (!isset($topGainers['values']) || empty($topGainers['values'])) {
                $this->warn('No top gainers data available');
                return [];
            }

            // Filter for Nifty 50 stocks and get their details
            $nifty50Stocks = $this->getNifty50Symbols();
            $bestStocks = [];
            foreach ($topGainers['values'] as $stock) {
                if (count($bestStocks) >= $maxStocks) break;
                
                $symbol = $stock['tsym'] ?? '';
                
                // Check if it's a Nifty 50 stock
                if (in_array($symbol, $nifty50Stocks) || true) {
                    // Get detailed quote for analysis
                    $quote = $this->getStockQuote($symbol);
                    
                    if ($quote && $this->isStockSuitableForTrading($quote)) {
                        $bestStocks[] = [
                            'symbol' => $symbol,
                            'ltp' => $quote['ltp'] ?? 0,
                            'change_percent' => $quote['change_percent'] ?? 0,
                            'volume' => $quote['volume'] ?? 0,
                            'high' => $quote['high'] ?? 0,
                            'low' => $quote['low'] ?? 0,
                            'quote' => $quote
                        ];
                    }
                }
            }
            return $bestStocks;

        } catch (\Exception $e) {
            $this->error("Error fetching best stocks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Nifty 50 stock symbols
     */
    protected function getNifty50Symbols(): array
    {
        return Cache::remember('nifty50_symbols', 3600, function() {
            // Nifty 50 stocks as of 2024
            return [
                'RELIANCE', 'TCS', 'HDFCBANK', 'INFY', 'HINDUNILVR', 'ITC', 'SBIN', 'BHARTIARTL',
                'KOTAKBANK', 'LT', 'ASIANPAINT', 'AXISBANK', 'MARUTI', 'SUNPHARMA', 'TITAN', 'ULTRACEMCO',
                'WIPRO', 'NESTLEIND', 'ONGC', 'POWERGRID', 'NTPC', 'TECHM', 'TATAMOTORS', 'BAJFINANCE',
                'HCLTECH', 'BAJAJFINSV', 'DRREDDY', 'JSWSTEEL', 'TATASTEEL', 'COALINDIA', 'GRASIM', 'BRITANNIA',
                'EICHERMOT', 'HEROMOTOCO', 'DIVISLAB', 'CIPLA', 'APOLLOHOSP', 'ADANIPORTS', 'INDUSINDBK', 'TATACONSUM',
                'BPCL', 'ICICIBANK', 'ADANIENT', 'HDFCLIFE', 'SBILIFE', 'BAJAJ-AUTO', 'UPL', 'SHREECEM'
            ];
        });
    }

    /**
     * Get stock quote details
     */
    protected function getStockQuote(string $symbol): ?array
    {
        try {
            // Search for the symbol to get token
            $searchResult = $this->flatTradeService->searchScrip($symbol, 'NSE');
            
            if (!isset($searchResult['values']) || empty($searchResult['values'])) {
                return null;
            }

            $token = $searchResult['values'][0]['token'] ?? null;
            if (!$token) {
                return null;
            }

            // Get quote using token
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
            $this->warn("Error getting quote for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if stock is suitable for trading
     */
    protected function isStockSuitableForTrading(array $quote): bool
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
        if ($changePercent < 1.0) return false; // Minimum 1% gain
        if ($volume < 100000) return false; // Minimum volume
        if ($high <= $low) return false; // Valid price range
        
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
     * Process individual stock and place order
     */
    protected function processStock(array $stock, int $quantity, float $minProfit, float $maxLoss, string $orderType, string $product, bool $dryRun): array
    {
        $symbol = $stock['symbol'];
        $ltp = $stock['ltp'];
        
        try {
            // Calculate order parameters
            $entryPrice = $ltp;
            $stopLossPrice = $entryPrice * (1 - $maxLoss / 100);
            $targetPrice = $entryPrice * (1 + $minProfit / 100);

            $this->info("  Entry Price: ₹{$entryPrice}");
            $this->info("  Stop Loss: ₹{$stopLossPrice} ({$maxLoss}%)");
            $this->info("  Target: ₹{$targetPrice} ({$minProfit}%)");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would place {$orderType} order for {$quantity} shares");
                return [
                    'symbol' => $symbol,
                    'success' => true,
                    'order_id' => 'DRY_RUN_' . time(),
                    'entry_price' => $entryPrice,
                    'stop_loss' => $stopLossPrice,
                    'target' => $targetPrice,
                    'quantity' => $quantity,
                    'dry_run' => true
                ];
            }

            // Place actual order based on type
            $orderResult = $this->placeOrder($symbol, $quantity, $entryPrice, $stopLossPrice, $targetPrice, $orderType, $product);

            // Log the trade
            Log::info('Nifty50 Real Trade Executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLossPrice,
                'target' => $targetPrice,
                'order_type' => $orderType,
                'product' => $product,
                'order_result' => $orderResult
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLossPrice,
                'target' => $targetPrice,
                'quantity' => $quantity,
                'order_result' => $orderResult
            ];

        } catch (\Exception $e) {
            return [
                'symbol' => $symbol,
                'success' => false,
                'error' => $e->getMessage()
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
     * Display trading summary
     */
    protected function displaySummary(array $results, bool $dryRun): void
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info("📊 TRADING SUMMARY");
        $this->info(str_repeat('=', 60));
        
        $successful = array_filter($results, fn($r) => $r['success']);
        $failed = array_filter($results, fn($r) => !$r['success']);
        
        $this->info("Total Stocks Processed: " . count($results));
        $this->info("Successful Orders: " . count($successful));
        $this->info("Failed Orders: " . count($failed));
        
        if ($dryRun) {
            $this->info("\n🔍 DRY RUN MODE - No actual orders were placed");
        }
        
        if (!empty($successful)) {
            $this->info("\n✅ SUCCESSFUL ORDERS:");
            foreach ($successful as $result) {
                $this->info("  {$result['symbol']}: Order ID {$result['order_id']} - ₹{$result['entry_price']}");
            }
        }
        
        if (!empty($failed)) {
            $this->info("\n❌ FAILED ORDERS:");
            foreach ($failed as $result) {
                $this->info("  {$result['symbol']}: {$result['error']}");
            }
        }
        
        $this->info("\n" . str_repeat('=', 60));
    }
}
