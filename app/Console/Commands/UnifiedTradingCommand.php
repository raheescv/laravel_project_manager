<?php

namespace App\Console\Commands;

use App\Services\OptimizedTradingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UnifiedTradingCommand extends Command
{
    protected $signature = 'trade:unified 
                            {--action=buy : Action to perform (buy, sell)}
                            {--max-stocks=5 : Maximum number of stocks}
                            {--quantity=10 : Quantity per stock}
                            {--profit-threshold=5.0 : Profit threshold for selling}
                            {--loss-threshold=3.0 : Loss threshold for selling}
                            {--symbol-filter=all : Symbol filter (all, nifty50, custom)}
                            {--custom-symbols= : Comma-separated custom symbols}
                            {--order-type=market : Order type (market, limit)}
                            {--product=C : Product type}
                            {--sell-all : Sell all positions regardless of profit/loss}
                            {--dry-run : Run in dry-run mode}';

    protected $description = 'Unified trading command for buying and selling stocks';

    protected OptimizedTradingService $tradingService;

    public function __construct(OptimizedTradingService $tradingService)
    {
        parent::__construct();
        $this->tradingService = $tradingService;
    }

    public function handle()
    {
        $action = $this->option('action');
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Starting Unified Trading Command - Action: {$action}");
        $this->displayConfig();

        try {
            if ($action === 'buy') {
                $this->handleBuyAction();
            } elseif ($action === 'sell') {
                $this->handleSellAction();
            } else {
                $this->error("Invalid action: {$action}. Use 'buy' or 'sell'");
                return;
            }
        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            Log::error('Unified Trading Command failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle buy action
     */
    protected function handleBuyAction(): void
    {
        $config = $this->getBuyConfig();
        
        $this->info("\n💰 Checking available funds...");
        $availableFunds = $this->tradingService->getAvailableFunds();
        $this->info("Available funds: ₹{$availableFunds}");

        if ($availableFunds <= 0) {
            $this->warn("No funds available for trading");
            return;
        }

        $this->info("\n📊 Analyzing stocks...");
        $stocks = $this->tradingService->selectStocks($config);
        
        if (empty($stocks)) {
            $this->warn("No suitable stocks found");
            return;
        }

        $this->info("Found " . count($stocks) . " stocks:");
        foreach ($stocks as $stock) {
            $this->info("- {$stock['tsym']}: Score {$stock['score']}");
        }

        $this->info("\n📈 Executing buy orders...");
        $results = [];
        
        foreach ($stocks as $stock) {
            $result = $this->executeBuyOrder($stock, $config);
            $results[] = $result;
            
            if ($result['success']) {
                $this->info("✅ Order placed for {$stock['tsym']}");
            } else {
                $this->warn("⚠️ Order failed for {$stock['tsym']}: {$result['error']}");
            }
        }

        $this->displaySummary($results, 'buy');
    }

    /**
     * Handle sell action
     */
    protected function handleSellAction(): void
    {
        $config = $this->getSellConfig();
        $sellAll = $this->option('sell-all');
        
        $this->info("\n📊 Analyzing positions...");
        
        if ($sellAll) {
            $this->info("🔄 Sell-all mode: Will sell all positions regardless of profit/loss");
            $positions = $this->tradingService->getAllPositions();
        } else {
            $positions = $this->tradingService->analyzePositions($config);
        }
        
        if (empty($positions)) {
            $this->info("No positions to sell");
            return;
        }

        $this->info("Found " . count($positions) . " positions to sell:");
        foreach ($positions as $position) {
            if ($sellAll) {
                $this->info("- {$position['symbol']}: {$position['pnl_percent']}% P&L, Quantity: {$position['quantity']}");
            } else {
                $this->info("- {$position['symbol']}: {$position['pnl_percent']}% P&L, Reason: {$position['reason']}");
            }
        }

        $this->info("\n💸 Executing sell orders...");
        $results = [];
        
        foreach ($positions as $position) {
            $result = $this->executeSellOrder($position, $config);
            $results[] = $result;
            
            if ($result['success']) {
                $this->info("✅ Order placed for {$position['symbol']}");
            } else {
                $this->warn("⚠️ Order failed for {$position['symbol']}: {$result['error']}");
            }
        }

        $this->displaySummary($results, 'sell');
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
            
            Log::info('Buy order executed', [
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
            
            Log::info('Sell order executed', [
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
     * Get buy configuration
     */
    protected function getBuyConfig(): array
    {
        return [
            'max_stocks' => (int) $this->option('max-stocks'),
            'quantity' => (int) $this->option('quantity'),
            'symbol_filter' => $this->option('symbol-filter'),
            'custom_symbols' => $this->option('custom-symbols') ? array_map('trim', explode(',', $this->option('custom-symbols'))) : [],
            'order_type' => $this->option('order-type'),
            'product' => $this->option('product'),
            'dry_run' => $this->option('dry-run')
        ];
    }

    /**
     * Get sell configuration
     */
    protected function getSellConfig(): array
    {
        return [
            'profit_threshold' => (float) $this->option('profit-threshold'),
            'loss_threshold' => (float) $this->option('loss-threshold'),
            'symbol_filter' => $this->option('symbol-filter'),
            'custom_symbols' => $this->option('custom-symbols') ? array_map('trim', explode(',', $this->option('custom-symbols'))) : [],
            'order_type' => $this->option('order-type'),
            'product' => $this->option('product'),
            'dry_run' => $this->option('dry-run')
        ];
    }

    /**
     * Display configuration
     */
    protected function displayConfig(): void
    {
        $this->info("Configuration:");
        $this->info("- Action: " . $this->option('action'));
        $this->info("- Max stocks: " . $this->option('max-stocks'));
        $this->info("- Quantity: " . $this->option('quantity'));
        $this->info("- Symbol filter: " . $this->option('symbol-filter'));
        if ($this->option('custom-symbols')) {
            $this->info("- Custom symbols: " . $this->option('custom-symbols'));
        }
        $this->info("- Order type: " . $this->option('order-type'));
        $this->info("- Product: " . $this->option('product'));
        if ($this->option('action') === 'sell') {
            $this->info("- Profit threshold: " . $this->option('profit-threshold') . "%");
            $this->info("- Loss threshold: " . $this->option('loss-threshold') . "%");
            $this->info("- Sell all: " . ($this->option('sell-all') ? 'YES' : 'NO'));
        }
        $this->info("- Dry run: " . ($this->option('dry-run') ? 'YES' : 'NO'));
    }

    /**
     * Display summary
     */
    protected function displaySummary(array $results, string $action): void
    {
        $this->info("\n📊 Trading Summary:");
        $this->info("==================");
        
        $successful = array_filter($results, fn($r) => $r['success']);
        $failed = array_filter($results, fn($r) => !$r['success']);
        
        $this->info("Total orders: " . count($results));
        $this->info("Successful: " . count($successful));
        $this->info("Failed: " . count($failed));
        
        if (!empty($successful)) {
            $this->info("\n✅ Successful Orders:");
            foreach ($successful as $result) {
                $this->info("- {$result['symbol']}");
            }
        }
        
        if (!empty($failed)) {
            $this->info("\n❌ Failed Orders:");
            foreach ($failed as $result) {
                $this->info("- {$result['symbol']}: {$result['error']}");
            }
        }
        
        $this->info("\n" . ($this->option('dry-run') ? "🔍 Dry run completed" : "🎯 Trading completed"));
    }
}
