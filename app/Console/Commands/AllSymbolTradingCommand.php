<?php

namespace App\Console\Commands;

use App\Services\FlatTradeService;
use App\Services\UnifiedTradingStrategyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AllSymbolTradingCommand extends Command
{
    protected $signature = 'trade:all-symbols 
                            {--quantity=10 : Quantity to buy for each stock}
                            {--max-stocks=5 : Maximum number of stocks to trade}
                            {--min-profit=2.0 : Minimum profit percentage required}
                            {--max-loss=3.0 : Maximum loss percentage allowed}
                            {--max-investment=0 : Maximum total investment amount (0 = no limit)}
                            {--margin-safety=0.1 : Margin safety factor (0.1 = 10% buffer)}
                            {--symbol-filter=all : Symbol filter (all, nifty50, custom)}
                            {--custom-symbols= : Comma-separated custom symbols}
                            {--dry-run : Run in dry-run mode without placing actual orders}
                            {--order-type=market : Order type (market, limit, bracket)}
                            {--product=C : Product type (C=CNC, H=Holding, B=Bracket)}';

    protected $description = 'Execute real trading orders for best performing stocks from all symbols';

    protected FlatTradeService $flatTradeService;
    protected UnifiedTradingStrategyService $strategyService;

    public function __construct(FlatTradeService $flatTradeService, UnifiedTradingStrategyService $strategyService)
    {
        parent::__construct();
        $this->flatTradeService = $flatTradeService;
        $this->strategyService = $strategyService;
    }

    public function handle()
    {
        $this->info('🚀 Starting All Symbols Trading Command');
        
        $quantity = (int) $this->option('quantity');
        $maxStocks = (int) $this->option('max-stocks');
        $minProfit = (float) $this->option('min-profit');
        $maxLoss = (float) $this->option('max-loss');
        $maxInvestment = (float) $this->option('max-investment');
        $marginSafety = (float) $this->option('margin-safety');
        $symbolFilter = $this->option('symbol-filter');
        $customSymbols = $this->option('custom-symbols') ? 
            array_map('trim', explode(',', $this->option('custom-symbols'))) : [];
        $dryRun = $this->option('dry-run');
        $orderType = $this->option('order-type');
        $product = $this->option('product');

        $this->info("Configuration:");
        $this->info("- Quantity per stock: {$quantity}");
        $this->info("- Max stocks to trade: {$maxStocks}");
        $this->info("- Min profit required: {$minProfit}%");
        $this->info("- Max loss allowed: {$maxLoss}%");
        $this->info("- Max investment: " . ($maxInvestment > 0 ? "₹{$maxInvestment}" : "No limit"));
        $this->info("- Margin safety: " . ($marginSafety * 100) . "%");
        $this->info("- Symbol filter: {$symbolFilter}");
        if ($symbolFilter === 'custom' && !empty($customSymbols)) {
            $this->info("- Custom symbols: " . implode(', ', $customSymbols));
        }
        $this->info("- Order type: {$orderType}");
        $this->info("- Product: {$product}");
        $this->info("- Dry run: " . ($dryRun ? 'YES' : 'NO'));

        try {
            // Step 1: Check available funds
            $this->info("\n💰 Checking available funds...");
            $maxPayoutAmount = $this->getMaxPayoutAmount();
            if (!$maxPayoutAmount['success']) {
                $this->error("❌ Failed to get available funds: {$maxPayoutAmount['error']}");
                return;
            }
            
            $availableFunds = $maxPayoutAmount['data']['raw_response']['payout'] ?? 0;
            $this->info("Available funds: ₹{$availableFunds}");
            
            // Apply max investment limit if set
            if ($maxInvestment > 0 && $maxInvestment < $availableFunds) {
                $availableFunds = $maxInvestment;
                $this->info("Applied max investment limit: ₹{$availableFunds}");
            }

            // Step 2: Get best performing stocks using unified strategy
            $this->info("\n📊 Analyzing stocks using unified trading strategy...");
            $strategyOptions = [
                'quantity' => $quantity,
                'min_profit' => $minProfit,
                'max_loss' => $maxLoss,
                'max_investment' => $maxInvestment,
                'margin_safety' => $marginSafety,
                'symbol_filter' => $symbolFilter,
                'custom_symbols' => $customSymbols
            ];
            
            $bestStocks = $this->strategyService->selectOptimalStocksForPurchase($maxStocks, $strategyOptions);
            if (empty($bestStocks)) {
                $this->error('No suitable stocks found for trading.');
                return;
            }

            $this->info("Found " . count($bestStocks) . " optimal stocks:");
            foreach ($bestStocks as $stock) {
                $this->info("- {$stock['symbol']}: Score {$stock['total_score']}, LTP: ₹{$stock['entry_price']}, Target: ₹{$stock['target_price']}, Stop Loss: ₹{$stock['stop_loss']}");
            }
            
            // Apply margin safety factor
            $safeFunds = $availableFunds * (1 - $marginSafety);
            $this->info("Safe funds after margin buffer: ₹{$safeFunds}");

            // Step 3: Execute orders using unified strategy
            $this->info("\n📈 Executing orders using unified strategy...");
            $results = [];
            $totalUsedFunds = 0;
            
            foreach ($bestStocks as $stock) {
                $this->info("\n📈 Processing: {$stock['symbol']}");
                
                try {
                    $result = $this->executeUnifiedOrder($stock, $orderType, $product, $dryRun);
                    $results[] = $result;
                    
                    if ($result['success']) {
                        $this->info("✅ Order placed successfully for {$stock['symbol']}");
                        $totalUsedFunds += $result['required_funds'] ?? 0;
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

            // Step 4: Summary
            $this->displaySummary($results, $dryRun);

        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            Log::error('All Symbols Trading Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get max payout amount
     */
    protected function getMaxPayoutAmount(): array
    {
        try {
            $response = $this->flatTradeService->getMaxPayoutAmount();
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute unified order using strategy data
     */
    protected function executeUnifiedOrder(array $stock, string $orderType, string $product, bool $dryRun): array
    {
        $symbol = $stock['symbol'];
        $quantity = $stock['position_size']['quantity'];
        $entryPrice = $stock['entry_price'];
        $stopLossPrice = $stock['stop_loss'];
        $targetPrice = $stock['target_price'];
        $totalValue = $stock['position_size']['total_value'];
        
        try {
            $this->info("  Entry Price: ₹{$entryPrice}");
            $this->info("  Stop Loss: ₹{$stopLossPrice}");
            $this->info("  Target: ₹{$targetPrice}");
            $this->info("  Quantity: {$quantity}");
            $this->info("  Total Value: ₹{$totalValue}");
            $this->info("  Strategy Score: {$stock['total_score']}");

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
                    'required_funds' => $totalValue,
                    'dry_run' => true
                ];
            }

            // Place actual order
            $orderResult = $this->placeOrder($symbol, $quantity, $entryPrice, $stopLossPrice, $targetPrice, $orderType, $product);

            // Log the trade
            Log::info('All Symbols Strategy Trade Executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLossPrice,
                'target' => $targetPrice,
                'order_type' => $orderType,
                'product' => $product,
                'strategy_score' => $stock['total_score'],
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
                'required_funds' => $totalValue,
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
        $this->info("\n📊 Trading Summary:");
        $this->info("==================");
        
        $successfulOrders = array_filter($results, fn($r) => $r['success']);
        $failedOrders = array_filter($results, fn($r) => !$r['success']);
        
        $this->info("Total orders: " . count($results));
        $this->info("Successful: " . count($successfulOrders));
        $this->info("Failed: " . count($failedOrders));
        
        if (!empty($successfulOrders)) {
            $this->info("\n✅ Successful Orders:");
            foreach ($successfulOrders as $result) {
                $this->info("- {$result['symbol']}: {$result['quantity']} shares @ ₹{$result['entry_price']}");
                if ($dryRun) {
                    $this->info("  [DRY RUN] Order ID: {$result['order_id']}");
                } else {
                    $this->info("  Order ID: {$result['order_id']}");
                }
            }
        }
        
        if (!empty($failedOrders)) {
            $this->info("\n❌ Failed Orders:");
            foreach ($failedOrders as $result) {
                $this->info("- {$result['symbol']}: {$result['error']}");
            }
        }
        
        $this->info("\n" . ($dryRun ? "🔍 Dry run completed" : "🎯 Trading completed"));
    }
}
