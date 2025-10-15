<?php

namespace App\Console\Commands;

use App\Services\FlatTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SellNifty50PositionsCommand extends Command
{
    protected $signature = 'trade:sell-nifty50 
                            {--profit-threshold=2.0 : Minimum profit % to sell}
                            {--loss-threshold=3.0 : Maximum loss % to sell}
                            {--dry-run : Run in dry-run mode without placing actual orders}
                            {--order-type=market : Order type (market, limit)}
                            {--all : Sell all Nifty 50 positions regardless of P&L}';

    protected $description = 'Sell positions created from Nifty 50 trading command';

    protected FlatTradeService $flatTradeService;

    public function __construct(FlatTradeService $flatTradeService)
    {
        parent::__construct();
        $this->flatTradeService = $flatTradeService;
    }

    public function handle()
    {
        $this->info('💰 Starting Nifty 50 Position Selling Command');

        $profitThreshold = (float) $this->option('profit-threshold');
        $lossThreshold = (float) $this->option('loss-threshold');
        $dryRun = $this->option('dry-run');
        $orderType = $this->option('order-type');
        $sellAll = $this->option('all');

        $this->info('Configuration:');
        $this->info("- Profit threshold: {$profitThreshold}%");
        $this->info("- Loss threshold: {$lossThreshold}%");
        $this->info("- Order type: {$orderType}");
        $this->info('- Sell all: '.($sellAll ? 'YES' : 'NO'));
        $this->info('- Dry run: '.($dryRun ? 'YES' : 'NO'));

        try {
            // Step 1: Get Nifty 50 positions
            $this->info("\n📊 Fetching Nifty 50 positions...");
            $nifty50Positions = $this->getNifty50Positions();

            if (empty($nifty50Positions)) {
                $this->warn('No Nifty 50 positions found.');
                $this->info('💡 Tip: Run "php artisan trade:nifty50-real --dry-run" first to create positions');

                return;
            }

            $this->info('Found '.count($nifty50Positions).' Nifty 50 positions:');
            foreach ($nifty50Positions as $position) {
                $pnlColor = $position['pnl_percent'] >= 0 ? 'green' : 'red';
                $this->info("- {$position['symbol']}: {$position['quantity']} shares, P&L: {$position['pnl_percent']}%");
            }

            // Step 2: Filter positions for selling
            $sellCandidates = $this->filterSellCandidates($nifty50Positions, $profitThreshold, $lossThreshold, $sellAll);

            if (empty($sellCandidates)) {
                $this->info('No positions meet the selling criteria.');

                return;
            }

            $this->info("\n🎯 Positions to sell:");
            foreach ($sellCandidates as $candidate) {
                $this->info("- {$candidate['symbol']}: {$candidate['quantity']} shares, P&L: {$candidate['pnl_percent']}%");
            }

            // Step 3: Execute sell orders
            $this->info("\n💸 Executing sell orders...");
            $results = [];

            foreach ($sellCandidates as $candidate) {
                $this->info("\n📉 Selling: {$candidate['symbol']}");

                try {
                    $result = $this->executeSellOrder($candidate, $orderType, $dryRun);
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
            Log::error('SellNifty50PositionsCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get Nifty 50 positions from holdings and positions
     */
    protected function getNifty50Positions(): array
    {
        $nifty50Symbols = $this->getNifty50Symbols();
        $allPositions = [];

        // Get holdings
        try {
            $holdings = $this->flatTradeService->getHoldings('C');
            if (isset($holdings['stat']) && $holdings['stat'] === 'Ok' && isset($holdings['values'])) {
                foreach ($holdings['values'] as $holding) {
                    $symbol = $holding['tsym'] ?? '';
                    $quantity = (int) ($holding['qty'] ?? 0);

                    if ($quantity > 0 && $this->isNifty50Symbol($symbol, $nifty50Symbols)) {
                        $currentPrice = $this->getCurrentPrice($symbol);
                        $avgPrice = (float) ($holding['avgprc'] ?? 0);
                        $pnlPercent = 0;
                        $pnlValue = 0;

                        if ($avgPrice > 0 && $currentPrice > 0) {
                            $pnlPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
                            $pnlValue = ($currentPrice - $avgPrice) * $quantity;
                        }

                        $allPositions[] = [
                            'symbol' => $symbol,
                            'quantity' => $quantity,
                            'avg_price' => $avgPrice,
                            'current_price' => $currentPrice,
                            'pnl_percent' => $pnlPercent,
                            'pnl_value' => $pnlValue,
                            'type' => 'holding',
                            'raw_data' => $holding,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn('Error fetching holdings: '.$e->getMessage());
        }

        // Get positions
        try {
            $positions = $this->flatTradeService->getPositionBook('NSE', '', 0, '', '', '', '');
            if (isset($positions['stat']) && $positions['stat'] === 'Ok' && isset($positions['values'])) {
                foreach ($positions['values'] as $position) {
                    $symbol = $position['tsym'] ?? '';
                    $quantity = (int) ($position['netqty'] ?? 0);

                    if ($quantity > 0 && $this->isNifty50Symbol($symbol, $nifty50Symbols)) {
                        $currentPrice = $this->getCurrentPrice($symbol);
                        $avgPrice = (float) ($position['netprice'] ?? 0);
                        $pnlPercent = 0;
                        $pnlValue = 0;

                        if ($avgPrice > 0 && $currentPrice > 0) {
                            $pnlPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
                            $pnlValue = ($currentPrice - $avgPrice) * $quantity;
                        }

                        $allPositions[] = [
                            'symbol' => $symbol,
                            'quantity' => $quantity,
                            'avg_price' => $avgPrice,
                            'current_price' => $currentPrice,
                            'pnl_percent' => $pnlPercent,
                            'pnl_value' => $pnlValue,
                            'type' => 'position',
                            'raw_data' => $position,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn('Error fetching positions: '.$e->getMessage());
        }

        return $allPositions;
    }

    /**
     * Check if symbol is a Nifty 50 stock
     */
    protected function isNifty50Symbol(string $symbol, array $nifty50Symbols): bool
    {
        // Remove common suffixes
        $cleanSymbol = str_replace(['-EQ', '-BE', '-N1'], '', $symbol);

        return in_array($cleanSymbol, $nifty50Symbols);
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
            'BPCL', 'ICICIBANK', 'ADANIENT', 'HDFCLIFE', 'SBILIFE', 'BAJAJ-AUTO', 'UPL', 'SHREECEM',
        ];
    }

    /**
     * Filter positions for selling based on criteria
     */
    protected function filterSellCandidates(array $positions, float $profitThreshold, float $lossThreshold, bool $sellAll): array
    {
        if ($sellAll) {
            return $positions;
        }

        $candidates = [];
        foreach ($positions as $position) {
            $pnlPercent = $position['pnl_percent'];

            // Sell if profit threshold met
            if ($pnlPercent >= $profitThreshold) {
                $candidates[] = $position;
            }
            // Sell if loss threshold exceeded
            elseif ($pnlPercent <= -$lossThreshold) {
                $candidates[] = $position;
            }
        }

        return $candidates;
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
    protected function executeSellOrder(array $candidate, string $orderType, bool $dryRun): array
    {
        $symbol = $candidate['symbol'];
        $quantity = $candidate['quantity'];
        $currentPrice = $candidate['current_price'];

        try {
            $this->info("  Current Price: ₹{$currentPrice}");
            $this->info("  Average Price: ₹{$candidate['avg_price']}");
            $this->info("  P&L: {$candidate['pnl_percent']}% (₹{$candidate['pnl_value']})");
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
                    'pnl_value' => $candidate['pnl_value'],
                    'dry_run' => true,
                ];
            }

            // Place actual sell order
            $orderResult = $this->placeSellOrder($symbol, $quantity, $currentPrice, $orderType);

            // Log the sell trade
            Log::info('Nifty50 Position Sell Order Executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'sell_price' => $currentPrice,
                'avg_price' => $candidate['avg_price'],
                'pnl_percent' => $candidate['pnl_percent'],
                'pnl_value' => $candidate['pnl_value'],
                'order_type' => $orderType,
                'order_result' => $orderResult,
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'sell_price' => $currentPrice,
                'quantity' => $quantity,
                'pnl_percent' => $candidate['pnl_percent'],
                'pnl_value' => $candidate['pnl_value'],
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
    protected function placeSellOrder(string $symbol, int $quantity, float $price, string $orderType): array
    {
        switch ($orderType) {
            case 'market':
                return $this->flatTradeService->placeMarketOrder('NSE', $symbol, $quantity, 'S', 'C');

            case 'limit':
                return $this->flatTradeService->placeLimitOrder('NSE', $symbol, $quantity, $price, 'S', 'C');

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
        $this->info('💰 NIFTY 50 SELLING SUMMARY');
        $this->info(str_repeat('=', 60));

        $successful = array_filter($results, fn ($r) => $r['success']);
        $failed = array_filter($results, fn ($r) => ! $r['success']);

        $totalPnl = array_sum(array_column($successful, 'pnl_value'));
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
