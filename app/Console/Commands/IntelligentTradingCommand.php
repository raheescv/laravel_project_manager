<?php

namespace App\Console\Commands;

use App\Services\FlatTradeService;
use App\Services\EnhancedTradingService;
use App\Services\PerformanceTrackingService;
use App\Services\RiskManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class IntelligentTradingCommand extends Command
{
    protected $signature = 'trade:intelligent 
                            {--max-stocks=5 : Maximum number of stocks to trade}
                            {--max-investment=0 : Maximum total investment amount (0 = no limit)}
                            {--strategy=adaptive : Trading strategy (conservative, moderate, aggressive, adaptive)}
                            {--dry-run : Run in dry-run mode without placing actual orders}
                            {--force-entry : Force entry even if market conditions are not ideal}
                            {--force-exit : Force exit of all positions}
                            {--portfolio-rebalance : Rebalance portfolio based on performance}';

    protected $description = 'Intelligent trading command with adaptive strategy, risk management, and performance optimization';

    protected FlatTradeService $flatTradeService;
    protected EnhancedTradingService $enhancedTradingService;
    protected PerformanceTrackingService $performanceService;
    protected RiskManagementService $riskService;

    public function __construct(
        FlatTradeService $flatTradeService,
        EnhancedTradingService $enhancedTradingService,
        PerformanceTrackingService $performanceService,
        RiskManagementService $riskService
    ) {
        parent::__construct();
        $this->flatTradeService = $flatTradeService;
        $this->enhancedTradingService = $enhancedTradingService;
        $this->performanceService = $performanceService;
        $this->riskService = $riskService;
    }

    public function handle()
    {
        $this->info('🧠 Starting Intelligent Trading Command');
        
        $maxStocks = (int) $this->option('max-stocks');
        $maxInvestment = (float) $this->option('max-investment');
        $strategy = $this->option('strategy');
        $dryRun = $this->option('dry-run');
        $forceEntry = $this->option('force-entry');
        $forceExit = $this->option('force-exit');
        $portfolioRebalance = $this->option('portfolio-rebalance');

        $this->info("Configuration:");
        $this->info("- Max stocks: {$maxStocks}");
        $this->info("- Max investment: " . ($maxInvestment > 0 ? "₹{$maxInvestment}" : "No limit"));
        $this->info("- Strategy: {$strategy}");
        $this->info("- Dry run: " . ($dryRun ? 'YES' : 'NO'));
        $this->info("- Force entry: " . ($forceEntry ? 'YES' : 'NO'));
        $this->info("- Force exit: " . ($forceExit ? 'YES' : 'NO'));
        $this->info("- Portfolio rebalance: " . ($portfolioRebalance ? 'YES' : 'NO'));

        try {
            // Step 1: Analyze market conditions
            $this->info("\n📊 Analyzing market conditions...");
            $marketConditions = $this->enhancedTradingService->analyzeMarketConditions();
            $this->displayMarketConditions($marketConditions);

            // Step 2: Get current portfolio performance
            $this->info("\n💰 Analyzing current portfolio...");
            $portfolioPerformance = $this->performanceService->getPortfolioPerformance();
            $this->displayPortfolioPerformance($portfolioPerformance);

            // Step 3: Handle force exit if requested
            if ($forceExit) {
                $this->info("\n🚪 Force exit requested - selling all positions...");
                $this->executeForceExit($dryRun);
                return;
            }

            // Step 4: Analyze existing positions for exit opportunities
            $this->info("\n🔍 Analyzing existing positions for exit opportunities...");
            $exitDecisions = $this->analyzeExitOpportunities($marketConditions);
            $this->executeExitDecisions($exitDecisions, $dryRun);
            // Step 5: Portfolio rebalancing if requested
            if ($portfolioRebalance) {
                $this->info("\n⚖️ Portfolio rebalancing requested...");
                $this->executePortfolioRebalancing($marketConditions, $dryRun);
            }

            // Step 6: Analyze entry opportunities
            if (!$forceExit) {
                $this->info("\n📈 Analyzing entry opportunities...");
                $entryDecisions = $this->analyzeEntryOpportunities($marketConditions, $maxStocks, $maxInvestment, $strategy, $forceEntry);
                $this->executeEntryDecisions($entryDecisions, $dryRun);
            }

            // Step 7: Generate performance report
            $this->info("\n📋 Generating performance report...");
            $performanceReport = $this->performanceService->generatePerformanceReport('week');
            $this->displayPerformanceReport($performanceReport);

            // Step 8: Strategy recommendations
            $this->info("\n💡 Strategy recommendations:");
            $this->displayStrategyRecommendations($marketConditions, $portfolioPerformance, $performanceReport);

        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            Log::error('IntelligentTradingCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Display market conditions
     */
    protected function displayMarketConditions(array $marketConditions): void
    {
        $this->info("Market Regime: " . strtoupper($marketConditions['market_regime']));
        $this->info("Volatility Level: " . strtoupper($marketConditions['volatility_level']));
        $this->info("Nifty Trend: " . strtoupper($marketConditions['nifty_trend']));
        $this->info("Recommended Strategy: " . strtoupper($marketConditions['recommended_strategy']));
        $this->info("Risk Multiplier: " . $marketConditions['risk_multiplier']);
    }

    /**
     * Display portfolio performance
     */
    protected function displayPortfolioPerformance(array $portfolio): void
    {
        if (empty($portfolio)) {
            $this->warn("No portfolio data available");
            return;
        }

        $this->info("Total Invested: ₹" . number_format($portfolio['total_invested'], 2));
        $this->info("Current Value: ₹" . number_format($portfolio['total_current_value'], 2));
        $this->info("Total P&L: ₹" . number_format($portfolio['total_pnl'], 2) . " (" . number_format($portfolio['total_pnl_percent'], 2) . "%)");
        $this->info("Unrealized P&L: ₹" . number_format($portfolio['unrealized_pnl'], 2) . " (" . number_format($portfolio['unrealized_pnl_percent'], 2) . "%)");
        $this->info("Position Count: " . $portfolio['position_count']);
    }

    /**
     * Analyze exit opportunities
     */
    protected function analyzeExitOpportunities(array $marketConditions): array
    {
        try {
            $positions = $this->getCurrentPositions();
            $exitDecisions = [];
            foreach ($positions as $position) {
                // Convert FlatTrade position format to our expected format
                $formattedPosition = $this->formatPositionForAnalysis($position);
                if (!$formattedPosition) {
                    continue; // Skip invalid positions
                }

                $exitAnalysis = $this->enhancedTradingService->analyzeExitStrategy($formattedPosition, $marketConditions);
                if ($exitAnalysis['action'] !== 'hold') {
                    $exitDecisions[] = [
                        'symbol' => $formattedPosition['symbol'],
                        'action' => $exitAnalysis['action'],
                        'confidence' => $exitAnalysis['confidence'],
                        'pnl_percent' => $exitAnalysis['pnl_percent'],
                        'signals' => $exitAnalysis['signals'],
                        'position' => $formattedPosition
                    ];
                }
            }

            return $exitDecisions;
        } catch (\Exception $e) {
            $this->error("Exit analysis failed: " . $e->getMessage());
            Log::error('Exit analysis failed', [ 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString() ]);
            return [];
        }
    }

    /**
     * Format FlatTrade position data for analysis
     */
    protected function formatPositionForAnalysis(array $position): ?array
    {
        try {
            // Handle FlatTrade position format
            $symbol = $position['tsym'] ?? null;
            $quantity = (int) ($position['netqty'] ?? 0);
            $avgPrice = (float) ($position['netavgprc'] ?? 0);
            $currentPrice = (float) ($position['lp'] ?? 0);
            
            // Skip if essential data is missing
            if (!$symbol || $quantity <= 0 || $avgPrice <= 0 || $currentPrice <= 0) {
                $this->warn("Skipping invalid position: " . json_encode($position));
                return null;
            }

            // Calculate P&L
            $pnl = ($currentPrice - $avgPrice) * $quantity;
            $pnlPercent = $avgPrice > 0 ? (($currentPrice - $avgPrice) / $avgPrice) * 100 : 0;

            return [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'avg_price' => $avgPrice,
                'current_price' => $currentPrice,
                'pnl' => $pnl,
                'pnl_percent' => $pnlPercent,
                'entry_time' => $position['entry_time'] ?? now()->subDays(1),
                'raw_data' => $position
            ];
        } catch (\Exception $e) {
            $this->warn("Error formatting position: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute exit decisions
     */
    protected function executeExitDecisions(array $exitDecisions, bool $dryRun): void
    {
        if (empty($exitDecisions)) {
            $this->info("No exit opportunities found");
            return;
        }

        $this->info("Found " . count($exitDecisions) . " exit opportunities:");
        
        foreach ($exitDecisions as $decision) {
            $this->info("\n📉 {$decision['symbol']}: {$decision['action']} (Confidence: {$decision['confidence']})");
            $this->info("  P&L: {$decision['pnl_percent']}%");
            
            if ($dryRun) {
                $this->info("  [DRY RUN] Would execute {$decision['action']} order");
            } else {
                $this->executeExitOrder($decision);
            }
        }
    }

    /**
     * Execute individual exit order
     */
    protected function executeExitOrder(array $decision): void
    {
        try {
            $symbol = $decision['symbol'];
            $quantity = $decision['position']['quantity'];
            $currentPrice = $decision['position']['current_price'];
            
            $orderResult = $this->flatTradeService->placeMarketOrder('NSE', $symbol, $quantity, 'S', 'C');
            
            if (isset($orderResult['stat']) && $orderResult['stat'] === 'Ok') {
                $this->info("  ✅ Exit order placed successfully");
                
                // Record the trade
                $this->performanceService->recordTrade([
                    'symbol' => $symbol,
                    'entry_price' => $decision['position']['avg_price'],
                    'exit_price' => $currentPrice,
                    'quantity' => $quantity,
                    'entry_time' => $decision['position']['entry_time'] ?? now()->subHour(),
                    'exit_time' => now(),
                    'pnl' => ($currentPrice - $decision['position']['avg_price']) * $quantity,
                    'pnl_percent' => $decision['pnl_percent'],
                    'holding_period' => 1, // Simplified
                    'strategy' => 'intelligent_exit',
                    'market_conditions' => []
                ]);
            } else {
                $this->warn("  ⚠️ Exit order failed: " . ($orderResult['emsg'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Exit order error: " . $e->getMessage());
        }
    }

    /**
     * Analyze entry opportunities
     */
    protected function analyzeEntryOpportunities(
        array $marketConditions, 
        int $maxStocks, 
        float $maxInvestment, 
        string $strategy,
        bool $forceEntry
    ): array
    {
        try {
            // Check if market conditions are favorable for entry
            if (!$forceEntry && !$this->isMarketFavorableForEntry($marketConditions)) {
                $this->warn("Market conditions not favorable for entry");
                return [];
            }

            // Get available funds
            $availableFunds = $this->getAvailableFunds();
            if ($maxInvestment > 0 && $maxInvestment < $availableFunds) {
                $availableFunds = $maxInvestment;
            }

            if ($availableFunds <= 0) {
                $this->warn("No funds available for trading");
                return [];
            }

            // Select optimal stocks
            $selectedStocks = $this->enhancedTradingService->selectOptimalStocks($maxStocks, $marketConditions);
            if (empty($selectedStocks)) {
                $this->warn("No suitable stocks found for entry");
                return [];
            }

            $entryDecisions = [];
            $usedFunds = 0;
            foreach ($selectedStocks as $stock) {
                if ($usedFunds >= $availableFunds * 0.8) break; // Use max 80% of funds
                
                $positionSize = $this->enhancedTradingService->calculateOptimalPositionSize(
                    $stock['symbol'], 
                    $availableFunds - $usedFunds, 
                    $marketConditions,
                    $this->getCurrentPositions()
                );

                if ($positionSize['quantity'] > 0) {
                    $entryDecisions[] = [
                        'symbol' => $stock['symbol'],
                        'quantity' => $positionSize['quantity'],
                        'price' => $positionSize['price'],
                        'total_value' => $positionSize['total_value'],
                        'score' => $stock['total_score'],
                        'risk_multiplier' => $positionSize['risk_multiplier'],
                        'quote' => $stock['quote']
                    ];
                    
                    $usedFunds += $positionSize['total_value'];
                }
            }

            return $entryDecisions;
        } catch (\Exception $e) {
            $this->error("Entry analysis failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute entry decisions
     */
    protected function executeEntryDecisions(array $entryDecisions, bool $dryRun): void
    {
        if (empty($entryDecisions)) {
            $this->info("No entry opportunities found");
            return;
        }

        $this->info("Found " . count($entryDecisions) . " entry opportunities:");
        
        foreach ($entryDecisions as $decision) {
            $this->info("\n📈 {$decision['symbol']}: {$decision['quantity']} shares @ ₹{$decision['price']}");
            $this->info("  Total Value: ₹{$decision['total_value']}");
            $this->info("  Score: {$decision['score']}");
            $this->info("  Risk Multiplier: {$decision['risk_multiplier']}");
            
            if ($dryRun) {
                $this->info("  [DRY RUN] Would place buy order");
            } else {
                $this->executeEntryOrder($decision);
            }
        }
    }

    /**
     * Execute individual entry order
     */
    protected function executeEntryOrder(array $decision): void
    {
        try {
            $symbol = $decision['symbol'];
            $quantity = $decision['quantity'];
            $price = $decision['price'];
            
            $orderResult = $this->flatTradeService->placeMarketOrder('NSE', $symbol, $quantity, 'B', 'C');
            
            if (isset($orderResult['stat']) && $orderResult['stat'] === 'Ok') {
                $this->info("  ✅ Entry order placed successfully");
                
                // Record the trade entry
                Log::info('Intelligent Trade Entry Executed', [
                    'symbol' => $symbol,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total_value' => $decision['total_value'],
                    'score' => $decision['score'],
                    'order_result' => $orderResult
                ]);
            } else {
                $this->warn("  ⚠️ Entry order failed: " . ($orderResult['emsg'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Entry order error: " . $e->getMessage());
        }
    }

    /**
     * Execute force exit of all positions
     */
    protected function executeForceExit(bool $dryRun): void
    {
        try {
            $positions = $this->getCurrentPositions();
            
            if (empty($positions)) {
                $this->info("No positions to exit");
                return;
            }

            $this->info("Exiting " . count($positions) . " positions:");
            
            foreach ($positions as $position) {
                $symbol = $position['tsym'] ?? 'Unknown';
                $quantity = (int) ($position['netqty'] ?? 0);
                
                $this->info("\n📉 {$symbol}: {$quantity} shares");
                
                if ($dryRun) {
                    $this->info("  [DRY RUN] Would place sell order");
                } else {
                    $orderResult = $this->flatTradeService->placeMarketOrder( 'NSE', $symbol, $quantity, 'S', 'C' );
                    if (isset($orderResult['stat']) && $orderResult['stat'] === 'Ok') {
                        $this->info("  ✅ Exit order placed successfully");
                    } else {
                        $this->warn("  ⚠️ Exit order failed: " . ($orderResult['emsg'] ?? 'Unknown error'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Force exit failed: " . $e->getMessage());
        }
    }

    /**
     * Execute portfolio rebalancing
     */
    protected function executePortfolioRebalancing(array $marketConditions, bool $dryRun): void
    {
        try {
            $positions = $this->getCurrentPositions();
            $performanceReport = $this->performanceService->generatePerformanceReport('month');
            
            // Identify underperforming positions
            $underperformingPositions = $this->identifyUnderperformingPositions($positions, $performanceReport);
            
            if (empty($underperformingPositions)) {
                $this->info("No rebalancing needed - all positions performing well");
                return;
            }

            $this->info("Rebalancing " . count($underperformingPositions) . " underperforming positions:");
            
            foreach ($underperformingPositions as $position) {
                $symbol = $position['tsym'] ?? 'Unknown';
                $quantity = (int) ($position['netqty'] ?? 0);
                $pnlPercent = $this->calculatePositionPnL($position);
                
                $this->info("\n📉 {$symbol}: P&L {$pnlPercent}%");
                
                if ($dryRun) {
                    $this->info("  [DRY RUN] Would exit underperforming position");
                } else {
                    $orderResult = $this->flatTradeService->placeMarketOrder(
                        'NSE', 
                        $symbol, 
                        $quantity, 
                        'S', 
                        'C'
                    );
                    
                    if (isset($orderResult['stat']) && $orderResult['stat'] === 'Ok') {
                        $this->info("  ✅ Rebalancing exit order placed");
                    } else {
                        $this->warn("  ⚠️ Rebalancing exit failed: " . ($orderResult['emsg'] ?? 'Unknown error'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Portfolio rebalancing failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate position P&L percentage
     */
    protected function calculatePositionPnL(array $position): float
    {
        $avgPrice = (float) ($position['netavgprc'] ?? 0);
        $currentPrice = (float) ($position['lp'] ?? 0);
        
        if ($avgPrice <= 0) return 0;
        
        return (($currentPrice - $avgPrice) / $avgPrice) * 100;
    }

    /**
     * Display performance report
     */
    protected function displayPerformanceReport(array $report): void
    {
        if (empty($report)) {
            $this->warn("No performance data available");
            return;
        }

        $summary = $report['summary'];
        $this->info("Performance Summary:");
        $this->info("- Total Trades: {$summary['total_trades']}");
        $this->info("- Win Rate: {$summary['win_rate']}%");
        $this->info("- Total P&L: ₹{$summary['total_pnl']}");
        $this->info("- Avg P&L per Trade: ₹{$summary['avg_pnl_per_trade']}");
        $this->info("- Max Drawdown: {$summary['max_drawdown']}%");
        $this->info("- Sharpe Ratio: {$summary['sharpe_ratio']}");
    }

    /**
     * Display strategy recommendations
     */
    protected function displayStrategyRecommendations(array $marketConditions, array $portfolio, array $performanceReport): void
    {
        $recommendations = $performanceReport['recommendations'] ?? [];
        
        if (empty($recommendations)) {
            $this->info("No specific recommendations at this time");
            return;
        }

        foreach ($recommendations as $recommendation) {
            $priority = strtoupper($recommendation['priority']);
            $this->info("- [{$priority}] {$recommendation['message']}");
            $this->info("  Action: {$recommendation['action']}");
        }
    }

    /**
     * Check if market is favorable for entry
     */
    protected function isMarketFavorableForEntry(array $marketConditions): bool
    {
        $regime = $marketConditions['market_regime'] ?? 'neutral';
        $volatility = $marketConditions['volatility_level'] ?? 'moderate';
        
        // Avoid entry in highly volatile or bearish markets
        if ($volatility === 'high') return false;
        if ($regime === 'bearish') return false;
        
        return true;
    }

    /**
     * Get available funds
     */
    protected function getAvailableFunds(): float
    {
        try {
            $response = $this->flatTradeService->getMaxPayoutAmount();
            
            if (isset($response['stat']) && $response['stat'] === 'Ok') {
                return (float) ($response['max_payout'] ?? 0);
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get current positions
     */
    protected function getCurrentPositions(): array
    {
        try {
            $positions = $this->flatTradeService->getPositionBook('NSE', '', 0, '', '', '', '');
            
            if (is_array($positions) && !empty($positions)) {
                if (isset($positions[0]['stat']) && $positions[0]['stat'] === 'Ok') {
                    return $positions;
                }
            }
            
            if (isset($positions['stat']) && $positions['stat'] === 'Ok' && isset($positions['netqty'])) {
                return [$positions];
            }
            
            return [];
        } catch (\Exception $e) {
            $this->warn("Error fetching positions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Identify underperforming positions
     */
    protected function identifyUnderperformingPositions(array $positions, array $performanceReport): array
    {
        $underperforming = [];
        
        foreach ($positions as $position) {
            $pnlPercent = $this->calculatePositionPnL($position);
            
            // Consider positions with >5% loss as underperforming
            if ($pnlPercent < -5.0) {
                $underperforming[] = $position;
            }
        }
        
        return $underperforming;
    }
}
