<?php

namespace App\Console\Commands;

use App\Services\FlatTradeService;
use App\Services\RiskManagementService;
use App\Services\UnifiedTradingStrategyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SellPositionsCommand extends Command
{
    protected $signature = 'trade:sell-positions 
                            {--symbol= : Specific symbol to sell (optional)}
                            {--all : Sell all positions}
                            {--profit-threshold=5.0 : Minimum profit % to sell}
                            {--loss-threshold=3.0 : Maximum loss % to sell}
                            {--dry-run : Run in dry-run mode without placing actual orders}
                            {--order-type=market : Order type (market, limit)}
                            {--product=C : Product type (C=CNC, H=Holding, B=Bracket)}';

    protected $description = 'Sell positions from already purchased orders with profit/loss analysis';

    protected FlatTradeService $flatTradeService;

    protected RiskManagementService $riskService;

    protected UnifiedTradingStrategyService $strategyService;

    public function __construct(FlatTradeService $flatTradeService, RiskManagementService $riskService, UnifiedTradingStrategyService $strategyService)
    {
        parent::__construct();
        $this->flatTradeService = $flatTradeService;
        $this->riskService = $riskService;
        $this->strategyService = $strategyService;
    }

    public function handle()
    {
        $this->info('💰 Starting Position Selling Command');

        $symbol = $this->option('symbol');
        $sellAll = $this->option('all');
        $profitThreshold = (float) $this->option('profit-threshold');
        $lossThreshold = (float) $this->option('loss-threshold');
        $dryRun = $this->option('dry-run');
        $orderType = $this->option('order-type');
        $product = $this->option('product');

        $this->info('Configuration:');
        $this->info('- Symbol filter: '.($symbol ?: 'All positions'));
        $this->info('- Sell all: '.($sellAll ? 'YES' : 'NO'));
        $this->info("- Profit threshold: {$profitThreshold}%");
        $this->info("- Loss threshold: {$lossThreshold}%");
        $this->info("- Order type: {$orderType}");
        $this->info("- Product: {$product}");
        $this->info('- Dry run: '.($dryRun ? 'YES' : 'NO'));

        try {
            // Step 1: Get current holdings and positions
            $this->info("\n📊 Fetching current holdings and positions...");
            $holdings = $this->getCurrentHoldings();
            $positions = $this->getCurrentPositions();
            if (empty($holdings) && empty($positions)) {
                $this->warn('No holdings or positions found.');

                return;
            }

            // Step 2: Analyze positions for selling opportunities using unified strategy
            $this->info("\n🔍 Analyzing positions using unified trading strategy...");
            $strategyOptions = [
                'profit_threshold' => $profitThreshold,
                'loss_threshold' => $lossThreshold,
                'symbol' => $symbol,
                'sell_all' => $sellAll,
            ];

            $sellCandidates = $this->strategyService->analyzePositionsForSelling($strategyOptions);

            if (empty($sellCandidates)) {
                $this->info('No positions meet the selling criteria.');

                return;
            }

            $this->info('Found '.count($sellCandidates).' positions to sell:');
            foreach ($sellCandidates as $candidate) {
                $this->info("- {$candidate['symbol']}: {$candidate['quantity']} shares, P&L: {$candidate['pnl_percent']}%, Reason: {$candidate['sell_reason']}, Priority: {$candidate['priority']}");
            }

            // Step 3: Execute sell orders
            $this->info("\n💸 Executing sell orders...");
            $results = [];

            foreach ($sellCandidates as $candidate) {
                $this->info("\n📉 Selling: {$candidate['symbol']}");

                try {
                    $result = $this->executeSellOrder($candidate, $orderType, $product, $dryRun);
                    $results[] = $result;

                    if ($result['success']) {
                        $this->info("✅ Sell order placed successfully for {$candidate['symbol']}");
                    } else {
                        $this->warn("⚠️ Sell order failed for {$candidate['symbol']}: {$result['error']}");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error selling {$candidate['symbol']}: ".$e->getMessage());
                    $results[] = [
                        'symbol' => $candidate['symbol'],
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Step 4: Summary
            $this->displaySellingSummary($results, $dryRun);

        } catch (\Exception $e) {
            $this->error('❌ Command failed: '.$e->getMessage());
            Log::error('SellPositionsCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get current holdings
     */
    protected function getCurrentHoldings(): array
    {
        try {
            $holdings = $this->flatTradeService->getHoldings('C'); // CNC holdings

            if (isset($holdings['stat']) && $holdings['stat'] === 'Ok' && isset($holdings['values'])) {
                return $holdings['values'];
            }

            return [];

        } catch (\Exception $e) {
            $this->error('Error fetching holdings: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get current positions
     */
    protected function getCurrentPositions(): array
    {
        try {
            // Try to get all positions without filters
            $positions = $this->flatTradeService->getPositionBook('NSE', '', 0, '', '', '', '');
            // this is the sample value
            // array:47 [[
            //     "stat" => "Ok"
            //     "uid" => "FZ26087"
            //     "actid" => "FZ26087"
            //     "exch" => "NSE"
            //     "tsym" => "SILVERBEES-EQ"
            //     "s_prdt_ali" => "CNC"
            //     "prd" => "C"
            //     "token" => "8080"
            //     "instname" => "EQ"
            //     "cname" => "NIPPONAMC - NETFSILVER"
            //     "frzqty" => "699331"
            //     "pp" => "2"
            //     "ls" => "1"
            //     "ti" => "0.01"
            //     "mult" => "1"
            //     "prcftr" => "1.000000"
            //     "daybuyqty" => "1"
            //     "daysellqty" => "0"
            //     "daybuyamt" => "150.49"
            //     "daybuyavgprc" => "150.49"
            //     "daysellamt" => "0.00"
            //     "daysellavgprc" => "0.00"
            //     "cfbuyqty" => "0"
            //     "cfsellqty" => "0"
            //     "cfbuyamt" => "0.00"
            //     "cfbuyavgprc" => "0.00"
            //     "cfsellamt" => "0.00"
            //     "cfsellavgprc" => "0.00"
            //     "openbuyqty" => "0"
            //     "opensellqty" => "0"
            //     "openbuyamt" => "0.00"
            //     "openbuyavgprc" => "0.00"
            //     "opensellamt" => "0.00"
            //     "opensellavgprc" => "0.00"
            //     "dayavgprc" => "150.49"
            //     "netqty" => "1"
            //     "netavgprc" => "150.49"
            //     "upldprc" => "0.00"
            //     "netupldprc" => "150.49"
            //     "lp" => "150.49"
            //     "urmtom" => "0.00"
            //     "bep" => "150.49"
            //     "totbuyamt" => "150.49"
            //     "totsellamt" => "0.00"
            //     "totbuyavgprc" => "150.49"
            //     "totsellavgprc" => "0.00"
            //     "rpnl" => "-0.00"
            // ]];
            // Handle the actual FlatTrade API response format
            // The API returns an array of position objects directly
            if (is_array($positions) && ! empty($positions)) {
                // Check if first element has 'stat' field (indicates it's a position object)
                if (isset($positions[0]['stat']) && $positions[0]['stat'] === 'Ok') {
                    $this->info('Found '.count($positions).' positions');

                    return $positions;
                }
            }

            // Fallback: check if it's a single position object
            if (isset($positions['stat']) && $positions['stat'] === 'Ok' && isset($positions['netqty'])) {
                $this->info("Found single position: {$positions['tsym']} with quantity: {$positions['netqty']}");

                return [$positions];
            }

            // If no positions found, try with default parameters
            $positions = $this->flatTradeService->getPositionBook();

            if (isset($positions['stat']) && $positions['stat'] === 'Ok') {
                if (isset($positions['values']) && is_array($positions['values'])) {
                    return $positions['values'];
                } elseif (isset($positions['netqty']) && (int) $positions['netqty'] > 0) {
                    return [$positions];
                }
            }

            return [];

        } catch (\Exception $e) {
            $this->error('Error fetching positions: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Analyze positions for selling opportunities
     */
    protected function analyzeSellCandidates(array $holdings, array $positions, ?string $symbol, bool $sellAll, float $profitThreshold, float $lossThreshold): array
    {
        $candidates = [];

        // Process holdings
        foreach ($holdings as $holding) {
            if ($this->shouldSellHolding($holding, $symbol, $sellAll, $profitThreshold, $lossThreshold)) {
                $candidates[] = $this->prepareSellCandidate($holding, 'holding');
            }
        }
        // Process positions
        foreach ($positions as $position) {
            if ($this->shouldSellPosition($position, $symbol, $sellAll, $profitThreshold, $lossThreshold)) {
                $candidates[] = $this->prepareSellCandidate($position, 'position');
            }
        }

        return $candidates;
    }

    /**
     * Check if holding should be sold
     */
    protected function shouldSellHolding(array $holding, ?string $symbol, bool $sellAll, float $profitThreshold, float $lossThreshold): bool
    {
        $holdingSymbol = $holding['tsym'] ?? '';

        info('holdingSymbol : '.$holdingSymbol);

        $quantity = (int) ($holding['qty'] ?? 0);

        info('quantity : '.$quantity);

        // Skip if no quantity
        if ($quantity <= 0) {
            return false;
        }

        // Skip if symbol filter doesn't match
        if ($symbol && $holdingSymbol !== $symbol) {
            return false;
        }

        // Get current market price
        $currentPrice = $this->getCurrentPrice($holdingSymbol);
        info('currentPrice : '.$currentPrice);

        if (! $currentPrice) {
            return false;
        }

        $avgPrice = (float) ($holding['avgprc'] ?? 0);
        if ($avgPrice <= 0) {
            return false;
        }

        // Calculate P&L percentage
        $pnlPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;

        // Check selling criteria
        if ($sellAll) {
            return true;
        }

        // Sell if profit threshold met
        info('pnlPercent : '.$pnlPercent);
        if ($pnlPercent >= $profitThreshold) {
            return true;
        }

        // Sell if loss threshold exceeded
        if ($pnlPercent <= -$lossThreshold) {
            return true;
        }

        return false;
    }

    /**
     * Check if position should be sold
     */
    protected function shouldSellPosition(array $position, ?string $symbol, bool $sellAll, float $profitThreshold, float $lossThreshold): bool
    {
        $positionSymbol = $position['tsym'] ?? '';
        $quantity = (int) ($position['netqty'] ?? 0);
        // Skip if no quantity or short position
        if ($quantity <= 0) {
            return false;
        }

        // Skip if symbol filter doesn't match
        if ($symbol && $positionSymbol !== $symbol) {
            return false;
        }

        // Use current price from position data (lp field) or get from API
        $currentPrice = (float) ($position['lp'] ?? 0);
        info('currentPrice : '.$currentPrice);
        if ($currentPrice <= 0) {
            $currentPrice = $this->getCurrentPrice($positionSymbol);
            if (! $currentPrice) {
                return false;
            }
        }

        $avgPrice = (float) ($position['netavgprc'] ?? 0);
        info('avgPrice : '.$avgPrice);
        if ($avgPrice <= 0) {
            return false;
        }

        // Calculate P&L percentage
        $pnlPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
        info('positionSymbol: '.$positionSymbol.' pnlPercent : '.$pnlPercent);

        // Check selling criteria
        if ($sellAll) {
            return true;
        }

        // Sell if profit threshold met
        if ($pnlPercent >= $profitThreshold) {
            return true;
        }

        // Sell if loss threshold exceeded
        if ($pnlPercent <= -$lossThreshold) {
            return true;
        }

        return false;
    }

    /**
     * Prepare sell candidate data
     */
    protected function prepareSellCandidate(array $data, string $type): array
    {
        $symbol = $data['tsym'] ?? '';
        $quantity = $type === 'holding' ? (int) ($data['qty'] ?? 0) : (int) ($data['netqty'] ?? 0);
        $avgPrice = $type === 'holding' ? (float) ($data['avgprc'] ?? 0) : (float) ($data['netavgprc'] ?? 0);

        // Use current price from position data (lp field) or get from API
        $currentPrice = (float) ($data['lp'] ?? 0);
        if ($currentPrice <= 0) {
            $currentPrice = $this->getCurrentPrice($symbol);
        }

        $pnlPercent = 0;
        if ($avgPrice > 0 && $currentPrice > 0) {
            $pnlPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
        }

        return [
            'symbol' => $symbol,
            'quantity' => $quantity,
            'avg_price' => $avgPrice,
            'current_price' => $currentPrice,
            'pnl_percent' => $pnlPercent,
            'pnl' => ($currentPrice - $avgPrice) * $quantity,
            'type' => $type,
            'raw_data' => $data,
        ];
    }

    /**
     * Get current market price for symbol
     */
    protected function getCurrentPrice(string $symbol): ?float
    {
        try {
            $searchResult = $this->flatTradeService->searchScrip($symbol, 'NSE');

            if (! isset($searchResult['values']) || empty($searchResult['values'])) {
                return null;
            }

            $token = $searchResult['values'][0]['token'] ?? null;
            if (! $token) {
                return null;
            }

            $quote = $this->flatTradeService->getQuotes($token, 'NSE');

            if (isset($quote['stat']) && $quote['stat'] === 'Ok') {
                return (float) ($quote['lp'] ?? 0);
            }

            return null;

        } catch (\Exception $e) {
            $this->warn("Error getting price for {$symbol}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Execute sell order
     */
    protected function executeSellOrder(array $candidate, string $orderType, string $product, bool $dryRun): array
    {
        $symbol = $candidate['symbol'];
        $quantity = $candidate['quantity'];
        $currentPrice = $candidate['current_price'];

        try {
            $this->info("  Current Price: ₹{$currentPrice}");
            $this->info("  Average Price: ₹{$candidate['avg_price']}");
            $this->info("  P&L: {$candidate['pnl_percent']}% (₹{$candidate['pnl']})");
            $this->info("  Quantity: {$quantity} shares");
            if ($dryRun) {
                $this->info("  [DRY RUN] Would place {$orderType} sell order for {$quantity} shares");

                return [
                    'symbol' => $symbol,
                    'success' => true,
                    'order_id' => 'DRY_RUN_SELL_'.time(),
                    'sell_price' => $currentPrice,
                    'quantity' => $quantity,
                    'pnl_percent' => $candidate['pnl_percent'],
                    'pnl' => $candidate['pnl'],
                    'dry_run' => true,
                ];
            }

            // Place actual sell order
            $orderResult = $this->placeSellOrder($symbol, $quantity, $currentPrice, $orderType, $product);

            // Log the sell trade
            Log::info('Position Sell Order Executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'sell_price' => $currentPrice,
                'avg_price' => $candidate['avg_price'],
                'pnl_percent' => $candidate['pnl_percent'],
                'pnl' => $candidate['pnl'],
                'order_type' => $orderType,
                'product' => $product,
                'order_result' => $orderResult,
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'sell_price' => $currentPrice,
                'quantity' => $quantity,
                'pnl_percent' => $candidate['pnl_percent'],
                'pnl' => $candidate['pnl'],
                'order_result' => $orderResult,
            ];

        } catch (\Exception $e) {
            return [
                'symbol' => $symbol,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Place sell order based on type
     */
    protected function placeSellOrder(string $symbol, int $quantity, float $price, string $orderType, string $product): array
    {
        switch ($orderType) {
            case 'market':
                return $this->flatTradeService->placeMarketOrder('NSE', $symbol, $quantity, 'S', $product);

            case 'limit':
                return $this->flatTradeService->placeLimitOrder('NSE', $symbol, $quantity, $price, 'S', $product);

            default:
                throw new \Exception("Unsupported order type: {$orderType}");
        }
    }

    /**
     * Display selling summary
     */
    protected function displaySellingSummary(array $results, bool $dryRun): void
    {
        $this->info("\n".str_repeat('=', 60));
        $this->info('💰 SELLING SUMMARY');
        $this->info(str_repeat('=', 60));

        $successful = array_filter($results, fn ($r) => $r['success']);
        $failed = array_filter($results, fn ($r) => ! $r['success']);

        $totalPnl = array_sum(array_column($successful, 'pnl'));
        $totalQuantity = array_sum(array_column($successful, 'quantity'));

        $this->info('Total Positions Processed: '.count($results));
        $this->info('Successful Sell Orders: '.count($successful));
        $this->info('Failed Sell Orders: '.count($failed));
        $this->info('Total Quantity Sold: '.$totalQuantity.' shares');
        $this->info('Total P&L Realized: ₹'.number_format($totalPnl, 2));

        if ($dryRun) {
            $this->info("\n🔍 DRY RUN MODE - No actual orders were placed");
        }

        if (! empty($successful)) {
            $this->info("\n✅ SUCCESSFUL SELL ORDERS:");
            foreach ($successful as $result) {
                $pnlColor = $result['pnl_percent'] >= 0 ? 'green' : 'red';
                $this->info("  {$result['symbol']}: Order ID {$result['order_id']} - ₹{$result['sell_price']} - P&L: {$result['pnl_percent']}%");
            }
        }

        if (! empty($failed)) {
            $this->info("\n❌ FAILED SELL ORDERS:");
            foreach ($failed as $result) {
                $this->info("  {$result['symbol']}: {$result['error']}");
            }
        }

        $this->info("\n".str_repeat('=', 60));
    }
}
