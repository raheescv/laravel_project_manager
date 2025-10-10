<?php

namespace App\Console\Commands;

use App\Services\OptimizedTradingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QuickTradingCommand extends Command
{
    protected $signature = 'trade:quick 
                            {--loss-threshold=1.0 : Loss threshold for selling}
                            {--max-stocks=1 : Maximum stocks to buy}
                            {--quantity=10 : Quantity per stock}
                            {--dry-run : Run in dry-run mode}';

    protected $description = 'Quick trading: Buy best stock and sell losing positions every 5 minutes';

    protected OptimizedTradingService $tradingService;

    public function __construct(OptimizedTradingService $tradingService)
    {
        parent::__construct();
        $this->tradingService = $tradingService;
    }

    public function handle()
    {
        $lossThreshold = (float) $this->option('loss-threshold');
        $maxStocks = (int) $this->option('max-stocks');
        $quantity = (int) $this->option('quantity');
        $dryRun = $this->option('dry-run');

        $this->info('⚡ Starting Quick Trading Command');
        $this->info("Configuration:");
        $this->info("- Loss threshold: {$lossThreshold}%");
        $this->info("- Max stocks to buy: {$maxStocks}");
        $this->info("- Quantity per stock: {$quantity}");
        $this->info("- Dry run: " . ($dryRun ? 'YES' : 'NO'));

        try {
            // Step 1: Sell losing positions first
            $this->handleSellLosingPositions($lossThreshold, $dryRun);
            
            // Step 2: Buy best stock
            $this->handleBuyBestStock($maxStocks, $quantity, $dryRun);

        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            Log::error('Quick Trading Command failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle selling losing positions
     */
    protected function handleSellLosingPositions(float $lossThreshold, bool $dryRun): void
    {
        $this->info("\n📊 Checking for losing positions...");
        
        $config = [
            'profit_threshold' => 0, // Not used for loss-only selling
            'loss_threshold' => $lossThreshold,
            'order_type' => 'market',
            'product' => 'C',
            'dry_run' => $dryRun
        ];

        $positions = $this->tradingService->analyzePositions($config);
        
        if (empty($positions)) {
            $this->info("✅ No losing positions found");
            return;
        }

        $this->info("Found " . count($positions) . " losing positions to sell:");
        foreach ($positions as $position) {
            $this->info("- {$position['symbol']}: {$position['pnl_percent']}% P&L, Reason: {$position['reason']}");
        }

        $this->info("\n💸 Selling losing positions...");
        $sellResults = [];
        
        foreach ($positions as $position) {
            $result = $this->executeSellOrder($position, $config);
            $sellResults[] = $result;
            
            if ($result['success']) {
                $this->info("✅ Sold {$position['symbol']} - {$position['pnl_percent']}% P&L");
            } else {
                $this->warn("⚠️ Failed to sell {$position['symbol']}: {$result['error']}");
            }
        }

        $this->displaySellSummary($sellResults);
    }

    /**
     * Handle buying best stock
     */
    protected function handleBuyBestStock(int $maxStocks, int $quantity, bool $dryRun): void
    {
        $this->info("\n📈 Looking for best stock to buy...");
        
        // Check available funds
        $availableFunds = $this->tradingService->getAvailableFunds();
        $this->info("Available funds: ₹{$availableFunds}");

        if ($availableFunds <= 0) {
            $this->warn("No funds available for buying");
            return;
        }

        $config = [
            'max_stocks' => $maxStocks,
            'quantity' => $quantity,
            'symbol_filter' => 'all',
            'order_type' => 'market',
            'product' => 'C',
            'dry_run' => $dryRun
        ];

        $stocks = $this->tradingService->selectStocks($config);
        
        if (empty($stocks)) {
            $this->warn("No suitable stocks found for buying");
            return;
        }

        $bestStock = $stocks[0]; // Get the best stock
        $this->info("Best stock found: {$bestStock['tsym']} (Score: {$bestStock['score']})");

        $this->info("\n💰 Buying best stock...");
        $result = $this->executeBuyOrder($bestStock, $config);
        
        if ($result['success']) {
            $this->info("✅ Bought {$bestStock['tsym']} - Score: {$bestStock['score']}");
        } else {
            $this->warn("⚠️ Failed to buy {$bestStock['tsym']}: {$result['error']}");
        }
    }

    /**
     * Execute sell order
     */
    protected function executeSellOrder(array $position, array $config): array
    {
        $symbol = $position['symbol'];
        $quantity = $position['quantity'];
        $orderType = $config['order_type'];
        $product = $config['product'];
        $dryRun = $config['dry_run'];

        if ($dryRun) {
            $this->info("  [DRY RUN] Would sell {$quantity} shares of {$symbol}");
            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => 'DRY_RUN_' . time(),
                'dry_run' => true
            ];
        }

        try {
            $orderResult = $this->tradingService->placeOrder($symbol, $quantity, $orderType, $product);
            
            Log::info('Quick sell order executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'result' => $orderResult
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
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
     * Execute buy order
     */
    protected function executeBuyOrder(array $stock, array $config): array
    {
        $symbol = $stock['tsym'] ?? '';
        $quantity = $config['quantity'];
        $orderType = $config['order_type'];
        $product = $config['product'];
        $dryRun = $config['dry_run'];

        if ($dryRun) {
            $this->info("  [DRY RUN] Would buy {$quantity} shares of {$symbol}");
            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => 'DRY_RUN_' . time(),
                'dry_run' => true
            ];
        }

        try {
            $orderResult = $this->tradingService->placeOrder($symbol, $quantity, $orderType, $product);
            
            Log::info('Quick buy order executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'result' => $orderResult
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
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
     * Display sell summary
     */
    protected function displaySellSummary(array $results): void
    {
        $successful = array_filter($results, fn($r) => $r['success']);
        $failed = array_filter($results, fn($r) => !$r['success']);
        
        $this->info("\n📊 Sell Summary:");
        $this->info("Total sell orders: " . count($results));
        $this->info("Successful: " . count($successful));
        $this->info("Failed: " . count($failed));
        
        if (!empty($failed)) {
            $this->info("\n❌ Failed Orders:");
            foreach ($failed as $result) {
                $this->info("- {$result['symbol']}: {$result['error']}");
            }
        }
    }
}
