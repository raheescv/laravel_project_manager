<?php

namespace App\Console\Commands;

use App\Services\OptimizedTradingService;
use App\Services\UnifiedTradingStrategyService;
use App\Services\PerformanceTrackingService;
use App\Services\FlatTradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuickTradingCommand extends Command
{
    protected $signature = 'trade:quick 
                            {--loss-threshold=1.0 : Loss threshold for selling}
                            {--profit-threshold=2 : Profit threshold for selling}
                            {--max-profit-target=5 : Maximum profit target before selling}
                            {--min-profit-keep=1 : Minimum profit to keep position}
                            {--trigger-profit=2 : Trigger point for partial profit taking}
                            {--max-stocks=10 : Maximum stocks to buy}
                            {--quantity=5 : Quantity per stock}
                            {--strategy=adaptive : Trading strategy (adaptive, conservative, aggressive)}
                            {--market-aware : Enable market condition awareness}
                            {--use-history : Use historical performance for decisions}
                            {--auto-trigger : Enable automatic trigger-based selling}
                            {--dry-run : Run in dry-run mode}';

    protected $description = 'Enhanced Quick Trading: Intelligent buy/sell with advanced profit checking and trading magic';

    protected OptimizedTradingService $tradingService;
    protected UnifiedTradingStrategyService $unifiedStrategyService;
    protected PerformanceTrackingService $performanceService;
    protected FlatTradeService $flatTradeService;

    public function __construct(
        OptimizedTradingService $tradingService,
        UnifiedTradingStrategyService $unifiedStrategyService,
        PerformanceTrackingService $performanceService,
        FlatTradeService $flatTradeService
    ) {
        parent::__construct();
        $this->tradingService = $tradingService;
        $this->unifiedStrategyService = $unifiedStrategyService;
        $this->performanceService = $performanceService;
        $this->flatTradeService = $flatTradeService;
    }

    public function handle()
    {
        $lossThreshold = (float) $this->option('loss-threshold');
        $profitThreshold = (float) $this->option('profit-threshold');
        $maxProfitTarget = (float) $this->option('max-profit-target');
        $minProfitKeep = (float) $this->option('min-profit-keep');
        $triggerProfit = (float) $this->option('trigger-profit');
        $maxStocks = (int) $this->option('max-stocks');
        $quantity = (int) $this->option('quantity');
        $strategy = $this->option('strategy');
        $marketAware = $this->option('market-aware');
        $useHistory = $this->option('use-history');
        $autoTrigger = $this->option('auto-trigger');
        $dryRun = $this->option('dry-run');

        $this->info('🚀 Starting Enhanced Quick Trading Command');
        $this->info("Configuration:");
        $this->info("- Loss threshold: {$lossThreshold}%");
        $this->info("- Profit threshold: {$profitThreshold}%");
        $this->info("- Max profit target: {$maxProfitTarget}%");
        $this->info("- Min profit to keep: {$minProfitKeep}%");
        $this->info("- Trigger profit: {$triggerProfit}%");
        $this->info("- Max stocks to buy: {$maxStocks}");
        $this->info("- Quantity per stock: {$quantity}");
        $this->info("- Strategy: {$strategy}");
        $this->info("- Market aware: " . ($marketAware ? 'YES' : 'NO'));
        $this->info("- Use history: " . ($useHistory ? 'YES' : 'NO'));
        $this->info("- Auto trigger: " . ($autoTrigger ? 'YES' : 'NO'));
        $this->info("- Dry run: " . ($dryRun ? 'YES' : 'NO'));

        try {
            // Step 1: Analyze market conditions and performance
            $this->analyzeMarketAndPerformance($useHistory);
            
            // Step 2: Intelligent position analysis and selling with advanced profit logic
            $this->handleAdvancedProfitSelling($lossThreshold, $profitThreshold, $maxProfitTarget, $minProfitKeep, $triggerProfit, $strategy, $marketAware, $autoTrigger, $dryRun);
            // Step 3: Advanced stock selection and buying
            $this->handleAdvancedBuying($maxStocks, $quantity, $strategy, $marketAware, $dryRun);

            // Step 4: Generate performance insights
            $this->generateTradingInsights();

        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            Log::error('Enhanced Quick Trading Command failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Analyze market conditions and historical performance
     */
    protected function analyzeMarketAndPerformance(bool $useHistory): void
    {
        $this->info("\n🔍 Analyzing Market Conditions & Performance...");
        
        try {
            // Get current portfolio performance
            $portfolioPerformance = $this->performanceService->getPortfolioPerformance();
            
            if (!empty($portfolioPerformance)) {
                $this->info("📈 Portfolio Performance:");
                $this->info("- Total P&L: ₹" . number_format($portfolioPerformance['total_pnl'] ?? 0, 2));
                $this->info("- P&L %: " . number_format($portfolioPerformance['total_pnl_percent'] ?? 0, 2) . "%");
                $this->info("- Positions: " . ($portfolioPerformance['position_count'] ?? 0));
            }
            
            if ($useHistory) {
                // Get historical performance analytics
                $analytics = $this->performanceService->getPerformanceAnalytics('month');
                
                if (!empty($analytics)) {
                    $this->info("📊 Historical Performance (Last Month):");
                    $this->info("- Win Rate: " . number_format($analytics['win_rate'] ?? 0, 1) . "%");
                    $this->info("- Total Trades: " . ($analytics['total_trades'] ?? 0));
                    $this->info("- Avg P&L per Trade: ₹" . number_format($analytics['avg_pnl_per_trade'] ?? 0, 2));
                    $this->info("- Sharpe Ratio: " . number_format($analytics['sharpe_ratio'] ?? 0, 3));
                }
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️ Could not analyze performance: " . $e->getMessage());
        }
    }

    /**
     * Handle advanced profit-based selling with intelligent triggers
     */
    protected function handleAdvancedProfitSelling(float $lossThreshold, float $profitThreshold, float $maxProfitTarget, float $minProfitKeep, float $triggerProfit, string $strategy, bool $marketAware, bool $autoTrigger, bool $dryRun): void
    {
        $this->info("\n🎯 Advanced Profit-Based Position Analysis...");
        
        $options = [
            'profit_threshold' => $profitThreshold,
            'loss_threshold' => $lossThreshold,
            'max_profit_target' => $maxProfitTarget,
            'min_profit_keep' => $minProfitKeep,
            'trigger_profit' => $triggerProfit,
            'strategy' => $strategy,
            'market_aware' => $marketAware,
            'auto_trigger' => $autoTrigger,
            'dry_run' => $dryRun
        ];

        $positions = $this->unifiedStrategyService->analyzePositionsForSelling($options);
        
        if (empty($positions)) {
            $this->info("✅ No positions require selling");
            return;
        }

        $this->info("Found " . count($positions) . " positions for advanced analysis:");
        foreach ($positions as $position) {
            $pnlPercent = $position['pnl_percent'] ?? 0;
            $confidence = $position['confidence'] ?? 0;
            $priority = $position['priority'] ?? 0;
            
            $this->info("- {$position['symbol']}: {$pnlPercent}% P&L");
            $this->info("  Reason: {$position['sell_reason']}");
            $this->info("  Confidence: " . number_format($confidence * 100, 1) . "%, Priority: {$priority}");
            
            // Show profit-based decision logic
            $this->showProfitDecisionLogic($pnlPercent, $maxProfitTarget, $minProfitKeep, $triggerProfit);
        }
        $this->info("\n💸 Executing advanced profit-based sell orders...");
        $sellResults = [];
        
        foreach ($positions as $position) {
            $result = $this->executeAdvancedProfitSellOrder($position, $options);
            $sellResults[] = $result;
            
            if ($result['success']) {
                $this->info("✅ Sold {$position['symbol']} - {$position['pnl_percent']}% P&L");
                $this->info("  Reason: {$position['sell_reason']}");
                if (isset($result['trigger_type'])) {
                    $this->info("  Trigger Type: {$result['trigger_type']}");
                }
            } else {
                $this->warn("⚠️ Failed to sell {$position['symbol']}: {$result['error']}");
            }
        }

        $this->displayEnhancedSellSummary($sellResults);
    }

    /**
     * Show profit-based decision logic for transparency
     */
    protected function showProfitDecisionLogic(float $pnlPercent, float $maxProfitTarget, float $minProfitKeep, float $triggerProfit): void
    {
        if ($pnlPercent >= $maxProfitTarget) {
            $this->info("  📈 DECISION: SELL - Exceeds max profit target ({$maxProfitTarget}%)");
        } elseif ($pnlPercent >= $triggerProfit) {
            $this->info("  🎯 DECISION: TRIGGER - Partial profit taking at {$triggerProfit}%");
        } elseif ($pnlPercent >= $minProfitKeep) {
            $this->info("  💎 DECISION: KEEP - Good profit ({$minProfitKeep}%+) - Hold for more gains");
        } elseif ($pnlPercent > 0) {
            $this->info("  ⏳ DECISION: WAIT - Small profit, waiting for better exit");
        } elseif ($pnlPercent >= -1.0) {
            $this->info("  📉 DECISION: SELL - Small loss (< 1%) - Cut losses early");
        } else {
            $this->info("  📉 DECISION: REVIEW - Larger loss, check stop-loss");
        }
    }

    /**
     * Handle advanced stock selection and buying
     */
    protected function handleAdvancedBuying(int $maxStocks, int $quantity, string $strategy, bool $marketAware, bool $dryRun): void
    {
        $this->info("\n🎯 Advanced Stock Selection & Buying...");
        // Check available funds with detailed analysis
        $availableFunds = $this->tradingService->getAvailableFunds();
        $this->info("Available funds: ₹" . number_format($availableFunds, 2));

        // Enhanced balance checking
        if (!$this->checkSufficientBalance($availableFunds, $maxStocks, $quantity)) {
            $this->warn("❌ Insufficient balance for trading operations");
            $this->showBalanceAnalysis($availableFunds, $maxStocks, $quantity);
            return;
        }

        $options = [
            'max_stocks' => $maxStocks,
            'quantity' => $quantity,
            'strategy' => $strategy,
            'market_aware' => $marketAware,
            'symbol_filter' => 'all', // Use all symbols for better opportunities
            'dry_run' => $dryRun,
            'min_profit' => 2.0,
            'max_loss' => 3.0,
            'margin_safety' => 0.1
        ];

        $stocks = $this->unifiedStrategyService->selectOptimalStocksForPurchase($maxStocks, $options);
        
        if (empty($stocks)) {
            $this->warn("No suitable stocks found for buying");
            return;
        }

        $this->info("🎯 Selected stocks for purchase:");
        foreach ($stocks as $stock) {
            $score = $stock['total_score'] ?? 0;
            $positionSize = $stock['position_size'] ?? [];
            $this->info("- {$stock['symbol']}: Score {$score}");
            $this->info("  Technical: {$stock['technical_score']}, Momentum: {$stock['momentum_score']}");
            $this->info("  Risk: {$stock['risk_score']}, Liquidity: {$stock['liquidity_score']}");
            $this->info("  Position: {$positionSize['quantity']} shares @ ₹{$positionSize['price']}");
        }

        $this->info("\n💰 Executing progressive buy orders...");
        $buyResults = [];
        $remainingFunds = $availableFunds;
        
        foreach ($stocks as $stock) {
            // Check if we can afford this stock
            $stockCost = $this->calculateStockCost($stock, $options['quantity']);
            
            if ($remainingFunds < $stockCost) {
                $this->info("💰 Insufficient balance for {$stock['symbol']} (₹" . number_format($stockCost, 2) . " required)");
                $this->info("  Remaining balance: ₹" . number_format($remainingFunds, 2));
                $this->info("  Skipping remaining stocks...");
                break;
            }
            
            $result = $this->executeAdvancedBuyOrder($stock, $options);
            $buyResults[] = $result;
        
        if ($result['success']) {
                $this->info("✅ Bought {$stock['symbol']} - Score: {$stock['total_score']}");
                $this->info("  Entry: ₹{$result['entry_price']}, Stop Loss: ₹{$result['stop_loss']}, Target: ₹{$result['target_price']}");
                $this->info("  Cost: ₹" . number_format($stockCost, 2));
                
                // Update remaining funds
                $remainingFunds -= $stockCost;
                $this->info("  Remaining Balance: ₹" . number_format($remainingFunds, 2));
            } else {
                $this->warn("⚠️ Failed to buy {$stock['symbol']}: {$result['error']}");
            }
        }

        $this->displayEnhancedBuySummary($buyResults);
    }

    /**
     * Calculate cost for a specific stock
     */
    protected function calculateStockCost(array $stock, int $quantity): float
    {
        try {
            $symbol = $stock['symbol'] ?? '';
            if (!$symbol) return 0;
            
            $quote = $this->getStockQuote($symbol);
            if ($quote && isset($quote['ltp']) && $quote['ltp'] > 0) {
                return ($quote['ltp'] * $quantity) * 1.2; // With safety margin
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if sufficient balance is available for trading
     */
    protected function checkSufficientBalance(float $availableFunds, int $maxStocks, int $quantity): bool
    {
        // Check if we have minimum balance for at least one stock
        $minRequiredForOneStock = $this->calculateMinRequiredForOneStock($quantity);
        
        if ($minRequiredForOneStock == 0) {
            $this->info("⚠️ Could not calculate minimum required funds - proceeding with available balance");
            return true;
        }
        
        if ($availableFunds < $minRequiredForOneStock) {
            $this->warn("⚠️ Insufficient funds for even one stock");
            $this->info("Available: ₹" . number_format($availableFunds, 2));
            $this->info("Minimum required for one stock: ₹" . number_format($minRequiredForOneStock, 2));
            $this->info("Shortfall: ₹" . number_format($minRequiredForOneStock - $availableFunds, 2));
            return false;
        }

        // Calculate how many stocks we can actually afford
        $affordableStocks = $this->calculateAffordableStockCount($availableFunds, $maxStocks, $quantity);
        
        if ($affordableStocks == 0) {
            $this->warn("⚠️ Cannot afford any stocks with current balance");
            return false;
        }
        
        if ($affordableStocks < $maxStocks) {
            $this->info("💰 Balance allows for {$affordableStocks} stocks out of {$maxStocks} planned");
            $this->info("Will buy stocks progressively until balance is exhausted");
        }

        return true;
    }

    /**
     * Calculate minimum required funds for one stock
     */
    protected function calculateMinRequiredForOneStock(int $quantity): float
    {
        try {
            // Get current market prices for top stocks
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            
            if (!isset($topGainers['values']) || empty($topGainers['values'])) {
                return 0;
            }
            
            // Find the cheapest stock from top gainers
            $minPrice = PHP_FLOAT_MAX;
            foreach ($topGainers['values'] as $stock) {
                $symbol = $stock['tsym'] ?? '';
                if (!$symbol) continue;
                
                $quote = $this->getStockQuote($symbol);
                if ($quote && isset($quote['ltp']) && $quote['ltp'] > 0) {
                    $minPrice = min($minPrice, $quote['ltp']);
                }
            }
            
            if ($minPrice == PHP_FLOAT_MAX) {
                return 0;
            }
            
            // Add 20% safety margin
            return ($minPrice * $quantity) * 1.2;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate how many stocks we can actually afford
     */
    protected function calculateAffordableStockCount(float $availableFunds, int $maxStocks, int $quantity): int
    {
        try {
            // Get current market prices for top stocks
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            
            if (!isset($topGainers['values']) || empty($topGainers['values'])) {
                return 0;
            }
            
            $affordableCount = 0;
            $remainingFunds = $availableFunds;
            
            // Check each stock to see how many we can afford
            foreach ($topGainers['values'] as $stock) {
                if ($affordableCount >= $maxStocks) break;
                
                $symbol = $stock['tsym'] ?? '';
                if (!$symbol) continue;
                
                $quote = $this->getStockQuote($symbol);
                if ($quote && isset($quote['ltp']) && $quote['ltp'] > 0) {
                    $stockCost = ($quote['ltp'] * $quantity) * 1.2; // With safety margin
                    
                    if ($remainingFunds >= $stockCost) {
                        $remainingFunds -= $stockCost;
                        $affordableCount++;
                    } else {
                        break; // Can't afford more stocks
                    }
                }
            }
            
            return $affordableCount;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate required funds based on actual stock prices
     */
    protected function calculateRequiredFunds(int $maxStocks, int $quantity): float
    {
        try {
            // Get current market prices for top stocks
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            
            if (!isset($topGainers['values']) || empty($topGainers['values'])) {
                // No market data available - return 0
                return 0;
            }
            
            $totalCost = 0;
            $stocksChecked = 0;
            
            // Calculate cost based on actual stock prices
            foreach ($topGainers['values'] as $stock) {
                if ($stocksChecked >= $maxStocks) break;
                
                $symbol = $stock['tsym'] ?? '';
                if (!$symbol) continue;
                
                $quote = $this->getStockQuote($symbol);
                if ($quote && isset($quote['ltp']) && $quote['ltp'] > 0) {
                    $stockCost = $quote['ltp'] * $quantity;
                    $totalCost += $stockCost;
                    $stocksChecked++;
                }
            }
            
            // If we couldn't get enough stock prices, use average of available prices
            if ($stocksChecked < $maxStocks && $stocksChecked > 0) {
                $avgCostPerStock = $totalCost / $stocksChecked;
                $totalCost += $avgCostPerStock * ($maxStocks - $stocksChecked);
            }
            
            // If no stock prices available, return 0
            if ($totalCost == 0) {
                return 0;
            }
            
            // Add 20% safety margin
            return $totalCost * 1.2;
            
        } catch (\Exception $e) {
            // No fallback - return 0 if calculation fails
            return 0;
        }
    }

    /**
     * Get stock quote for a symbol
     */
    protected function getStockQuote(string $symbol): ?array
    {
        try {
            $searchResult = $this->flatTradeService->searchScrip($symbol, 'NSE');
            if (!isset($searchResult['values'][0]['token'])) {
                return null;
            }

            $token = $searchResult['values'][0]['token'];
            $quote = $this->flatTradeService->getQuotes($token, 'NSE');
            
            if (isset($quote['stat']) && $quote['stat'] === 'Ok') {
                return [
                    'ltp' => (float) ($quote['lp'] ?? 0),
                    'symbol' => $quote['tsym'] ?? $symbol,
                    'token' => $quote['token'] ?? $token
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Show detailed balance analysis
     */
    protected function showBalanceAnalysis(float $availableFunds, int $maxStocks, int $quantity): void
    {
        $this->info("\n💰 Progressive Balance Analysis:");
        $this->info("- Available Funds: ₹" . number_format($availableFunds, 2));
        $this->info("- Planned Stocks: {$maxStocks}");
        $this->info("- Quantity per Stock: {$quantity}");
        
        // Calculate how many stocks we can actually afford
        $affordableStocks = $this->calculateAffordableStockCount($availableFunds, $maxStocks, $quantity);
        
        if ($affordableStocks > 0) {
            $this->info("- Affordable Stocks: {$affordableStocks} out of {$maxStocks}");
            $this->info("- Strategy: Progressive buying (buy until balance exhausted)");
            
            // Calculate affordable quantities based on actual stock prices
            $this->calculateAffordableQuantities($availableFunds, $maxStocks, $quantity);
        } else {
            $this->info("- Cannot afford any stocks with current balance");
            $this->info("- Please add more funds or reduce quantity");
        }
    }

    /**
     * Calculate affordable quantities based on actual stock prices
     */
    protected function calculateAffordableQuantities(float $availableFunds, int $maxStocks, int $quantity): void
    {
        try {
            // Get current market prices for top stocks
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            
            if (!isset($topGainers['values']) || empty($topGainers['values'])) {
                $this->info("\n📊 What you can afford:");
                $this->info("- Cannot calculate without current stock prices");
                $this->info("- Please check market data availability");
                return;
            }
            
            $totalCost = 0;
            $stocksChecked = 0;
            $stockPrices = [];
            
            // Calculate cost based on actual stock prices
            foreach ($topGainers['values'] as $stock) {
                if ($stocksChecked >= $maxStocks) break;
                
                $symbol = $stock['tsym'] ?? '';
                if (!$symbol) continue;
                
                $quote = $this->getStockQuote($symbol);
                if ($quote && isset($quote['ltp']) && $quote['ltp'] > 0) {
                    $stockCost = $quote['ltp'] * $quantity;
                    $totalCost += $stockCost;
                    $stockPrices[] = $quote['ltp'];
                    $stocksChecked++;
                }
            }
            
            if ($stocksChecked > 0) {
                $avgPrice = array_sum($stockPrices) / count($stockPrices);
                $maxAffordableStocks = floor($availableFunds / ($avgPrice * $quantity * 1.2));
                $maxAffordableQuantity = floor($availableFunds / ($avgPrice * $maxStocks * 1.2));
                
                $this->info("\n📊 What you can afford (based on actual prices):");
                $this->info("- Average stock price: ₹" . number_format($avgPrice, 2));
                $this->info("- Max stocks with current quantity: {$maxAffordableStocks}");
                $this->info("- Max quantity with current stocks: {$maxAffordableQuantity}");
                
                if ($maxAffordableStocks > 0) {
                    $this->info("\n💡 Suggestions:");
                    $this->info("- Reduce max-stocks to {$maxAffordableStocks}");
                    $this->info("- Or reduce quantity to {$maxAffordableQuantity}");
                    $this->info("- Or add more funds to your account");
                } else {
                    $this->info("\n💡 Suggestions:");
                    $this->info("- Add more funds to your account");
                    $this->info("- Reduce quantity significantly");
                    $this->info("- Wait for profitable positions to be sold first");
                }
            }
            
        } catch (\Exception $e) {
            $this->info("\n📊 What you can afford:");
            $this->info("- Cannot calculate without current stock prices");
            $this->info("- Please check market data availability");
        }
    }

    /**
     * Generate trading insights and recommendations
     */
    protected function generateTradingInsights(): void
    {
        $this->info("\n🔮 Trading Magic Insights...");
        
        try {
            $portfolioPerformance = $this->performanceService->getPortfolioPerformance();
            $analytics = $this->performanceService->getPerformanceAnalytics('week');
            
            if (!empty($portfolioPerformance) && !empty($analytics)) {
                $this->info("📊 Current Portfolio Status:");
                $this->info("- Total Value: ₹" . number_format($portfolioPerformance['total_current_value'] ?? 0, 2));
                $this->info("- Unrealized P&L: ₹" . number_format($portfolioPerformance['unrealized_pnl'] ?? 0, 2));
                $this->info("- Win Rate: " . number_format($analytics['win_rate'] ?? 0, 1) . "%");
                
                // Generate trading magic recommendations
                $recommendations = $this->generateTradingMagicRecommendations($portfolioPerformance, $analytics);
                
                if (!empty($recommendations)) {
                    $this->info("\n🎯 Trading Magic Recommendations:");
                    foreach ($recommendations as $recommendation) {
                        $this->info("- {$recommendation}");
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️ Could not generate insights: " . $e->getMessage());
        }
    }

    /**
     * Generate trading magic recommendations based on performance
     */
    protected function generateTradingMagicRecommendations(array $portfolio, array $analytics): array
    {
        $recommendations = [];
        
        $winRate = $analytics['win_rate'] ?? 0;
        $totalPnl = $portfolio['total_pnl'] ?? 0;
        $positionCount = $portfolio['position_count'] ?? 0;
        
        // Win rate based recommendations
        if ($winRate > 70) {
            $recommendations[] = "🎉 Excellent win rate! Consider increasing position sizes for high-confidence trades";
        } elseif ($winRate < 40) {
            $recommendations[] = "⚠️ Low win rate detected. Focus on quality over quantity - be more selective";
        }
        
        // P&L based recommendations
        if ($totalPnl > 0) {
            $recommendations[] = "💰 Profitable portfolio! Consider taking partial profits on winners";
        } else {
            $recommendations[] = "📉 Negative P&L. Review stop-loss levels and entry criteria";
        }
        
        // Position count recommendations
        if ($positionCount < 3) {
            $recommendations[] = "📈 Low diversification. Consider adding 1-2 more positions";
        } elseif ($positionCount > 8) {
            $recommendations[] = "🎯 High diversification. Focus on your best performers";
        }
        
        // Advanced profit-based recommendations
        $recommendations[] = "🎯 Profit Strategy: Sell if profit < 2%, Keep if profit > 2%, Trigger at 8%";
        $recommendations[] = "📊 Smart Triggers: Partial sell at 8%, Full sell at 5%, Stop-loss at -3%";
        $recommendations[] = "📉 Loss Management: Sell if loss < 1% - Cut small losses early";
        $recommendations[] = "⏰ Best trading times: 9:30-10:30 AM and 2:30-3:30 PM";
        $recommendations[] = "🔍 Monitor volume spikes for better entry/exit timing";
        $recommendations[] = "💎 Hold winners longer - let profits run to 8% before taking profits";
        
        return $recommendations;
    }

    /**
     * Execute advanced profit-based sell order with intelligent triggers
     */
    protected function executeAdvancedProfitSellOrder(array $position, array $options): array
    {
        $symbol = $position['symbol'];
        $quantity = $position['quantity'];
        $pnlPercent = $position['pnl_percent'] ?? 0;
        $dryRun = $options['dry_run'] ?? false;
        $maxProfitTarget = $options['max_profit_target'] ?? 10;
        $triggerProfit = $options['trigger_profit'] ?? 8;
        $autoTrigger = $options['auto_trigger'] ?? false;

        // Determine sell quantity based on profit level
        $sellQuantity = $this->calculateSellQuantity($quantity, $pnlPercent, $maxProfitTarget, $triggerProfit);
        $triggerType = $this->determineTriggerType($pnlPercent, $maxProfitTarget, $triggerProfit);

        if ($dryRun) {
            $this->info("  [DRY RUN] Would sell {$sellQuantity} shares of {$symbol} (P&L: {$pnlPercent}%)");
            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => 'DRY_RUN_' . time(),
                'dry_run' => true,
                'reason' => $position['sell_reason'] ?? 'Manual sell',
                'trigger_type' => $triggerType,
                'sell_quantity' => $sellQuantity,
                'original_quantity' => $quantity
            ];
        }
        try {
            // Execute sell order
            $orderResult = $this->tradingService->placeOrder($symbol, $sellQuantity, 'market', 'C', 'S');
            
            // Record the trade for performance tracking
            $this->recordCompletedTrade($position, 'sell', $orderResult);
            
            Log::info('Advanced profit sell order executed', [
                'symbol' => $symbol,
                'quantity' => $sellQuantity,
                'original_quantity' => $quantity,
                'pnl_percent' => $pnlPercent,
                'trigger_type' => $triggerType,
                'reason' => $position['sell_reason'] ?? 'Manual sell',
                'result' => $orderResult
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'order_result' => $orderResult,
                'reason' => $position['sell_reason'] ?? 'Manual sell',
                'trigger_type' => $triggerType,
                'sell_quantity' => $sellQuantity,
                'original_quantity' => $quantity
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
     * Calculate sell quantity based on profit level and strategy
     */
    protected function calculateSellQuantity(int $totalQuantity, float $pnlPercent, float $maxProfitTarget, float $triggerProfit): int
    {
        // If profit exceeds max target, sell all
        if ($pnlPercent >= $maxProfitTarget) {
            return $totalQuantity;
        }
        
        // If profit reaches trigger point, sell 50% for partial profit taking
        if ($pnlPercent >= $triggerProfit) {
            return max(1, intval($totalQuantity * 0.5));
        }
        
        // If loss exceeds threshold, sell all
        if ($pnlPercent <= -3.0) {
            return $totalQuantity;
        }
        
        // NEW: Sell if loss is less than 1% (small losses)
        if ($pnlPercent < 0 && $pnlPercent >= -1.0) {
            return $totalQuantity;
        }
        
        // For small profits, sell all
        if ($pnlPercent <= 2.0) {
            return $totalQuantity;
        }
        
        // Default: sell all
        return $totalQuantity;
    }

    /**
     * Determine trigger type based on profit level
     */
    protected function determineTriggerType(float $pnlPercent, float $maxProfitTarget, float $triggerProfit): string
    {
        if ($pnlPercent >= $maxProfitTarget) {
            return 'MAX_PROFIT_TARGET';
        } elseif ($pnlPercent >= $triggerProfit) {
            return 'PARTIAL_PROFIT_TRIGGER';
        } elseif ($pnlPercent <= -3.0) {
            return 'STOP_LOSS_TRIGGER';
        } elseif ($pnlPercent < 0 && $pnlPercent >= -1.0) {
            return 'SMALL_LOSS_EXIT';
        } elseif ($pnlPercent <= 2.0) {
            return 'MIN_PROFIT_EXIT';
        } else {
            return 'MANUAL_SELL';
        }
    }

    /**
     * Execute advanced buy order with intelligent position sizing
     */
    protected function executeAdvancedBuyOrder(array $stock, array $options): array
    {
        $symbol = $stock['symbol'];
        $positionSize = $stock['position_size'] ?? [];
        $quantity = $positionSize['quantity'] ?? $options['quantity'];
        $price = $positionSize['price'] ?? 0;
        $dryRun = $options['dry_run'] ?? false;

        if ($dryRun) {
            $this->info("  [DRY RUN] Would buy {$quantity} shares of {$symbol} @ ₹{$price}");
            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => 'DRY_RUN_' . time(),
                'dry_run' => true,
                'entry_price' => $price,
                'stop_loss' => $stock['stop_loss'] ?? 0,
                'target_price' => $stock['target_price'] ?? 0
            ];
        }

        try {
            $orderResult = $this->tradingService->placeOrder($symbol, $quantity, 'market', 'C', 'B');
            
            // Record the trade for performance tracking
            $this->recordCompletedTrade($stock, 'buy', $orderResult);
            
            Log::info('Advanced buy order executed', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'price' => $price,
                'score' => $stock['total_score'] ?? 0,
                'result' => $orderResult
            ]);

            return [
                'symbol' => $symbol,
                'success' => true,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'order_result' => $orderResult,
                'entry_price' => $price,
                'stop_loss' => $stock['stop_loss'] ?? 0,
                'target_price' => $stock['target_price'] ?? 0
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
     * Record completed trade for performance tracking
     */
    protected function recordCompletedTrade(array $tradeData, string $action, array $orderResult): void
    {
        try {
            $currentPrice = $tradeData['current_price'] ?? $tradeData['entry_price'] ?? 0;
            $entryPrice = $tradeData['entry_price'] ?? $tradeData['current_price'] ?? 0;
            $quantity = $tradeData['quantity'] ?? 0;
            
            // Calculate PnL based on action
            $pnl = 0;
            if ($action === 'sell' && $entryPrice > 0) {
                // For sell orders, calculate profit/loss
                $pnl = ($currentPrice - $entryPrice) * $quantity;
            } elseif ($action === 'buy') {
                // For buy orders, PnL is 0 (no realized profit/loss yet)
                $pnl = 0;
            }
            
            $tradeRecord = [
                'symbol' => $tradeData['symbol'],
                'action' => $action,
                'quantity' => $quantity,
                'price' => $currentPrice,
                'entry_price' => $entryPrice,
                'pnl' => $pnl,
                'order_id' => $orderResult['norenordno'] ?? 'UNKNOWN',
                'timestamp' => now(),
                'strategy' => 'enhanced_quick_trading',
                'market_conditions' => [
                    'action' => $action,
                    'confidence' => $tradeData['confidence'] ?? 0,
                    'score' => $tradeData['total_score'] ?? 0
                ]
            ];

            // Store in performance tracking
            $this->performanceService->trackStrategyPerformance('enhanced_quick_trading', $tradeRecord);
            
        } catch (\Exception $e) {
            Log::error('Failed to record trade', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display enhanced sell summary with detailed analysis
     */
    protected function displayEnhancedSellSummary(array $results): void
    {
        $successful = array_filter($results, fn($r) => $r['success']);
        $failed = array_filter($results, fn($r) => !$r['success']);
        
        $this->info("\n📊 Enhanced Profit-Based Sell Summary:");
        $this->info("Total sell orders: " . count($results));
        $this->info("Successful: " . count($successful));
        $this->info("Failed: " . count($failed));
        
        if (!empty($successful)) {
            $this->info("\n✅ Successful Sells:");
            foreach ($successful as $result) {
                $reason = $result['reason'] ?? 'Manual sell';
                $triggerType = $result['trigger_type'] ?? 'MANUAL';
                $sellQuantity = $result['sell_quantity'] ?? 0;
                $originalQuantity = $result['original_quantity'] ?? 0;
                
                $this->info("- {$result['symbol']}: {$reason}");
                $this->info("  Trigger: {$triggerType}");
                $this->info("  Quantity: {$sellQuantity}/{$originalQuantity} shares");
            }
        }
        
        if (!empty($failed)) {
            $this->info("\n❌ Failed Orders:");
            foreach ($failed as $result) {
                $this->info("- {$result['symbol']}: {$result['error']}");
            }
        }
        
        // Show profit strategy summary
        $this->info("\n🎯 Profit Strategy Summary:");
        $this->info("- Sell all if profit < 2% (small profits)");
        $this->info("- Sell all if loss < 1% (small losses) - Cut losses early");
        $this->info("- Partial sell (50%) at 8% profit trigger");
        $this->info("- Sell all if profit > 5% (max target)");
        $this->info("- Keep positions with 2%+ profit for more gains");
        $this->info("- Stop-loss at -3% to limit larger losses");
    }

    /**
     * Display enhanced buy summary with detailed analysis
     */
    protected function displayEnhancedBuySummary(array $results): void
    {
        $successful = array_filter($results, fn($r) => $r['success']);
        $failed = array_filter($results, fn($r) => !$r['success']);
        
        $this->info("\n📈 Enhanced Buy Summary:");
        $this->info("Total buy orders: " . count($results));
        $this->info("Successful: " . count($successful));
        $this->info("Failed: " . count($failed));
        
        if (!empty($successful)) {
            $this->info("\n✅ Successful Buys:");
            foreach ($successful as $result) {
                $entryPrice = $result['entry_price'] ?? 0;
                $stopLoss = $result['stop_loss'] ?? 0;
                $targetPrice = $result['target_price'] ?? 0;
                $this->info("- {$result['symbol']}: Entry ₹{$entryPrice}");
                $this->info("  Stop Loss: ₹{$stopLoss}, Target: ₹{$targetPrice}");
            }
        }
        
        if (!empty($failed)) {
            $this->info("\n❌ Failed Orders:");
            foreach ($failed as $result) {
                $this->info("- {$result['symbol']}: {$result['error']}");
            }
        }
    }
}
