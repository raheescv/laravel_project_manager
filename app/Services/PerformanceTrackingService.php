<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceTrackingService
{
    protected array $performanceMetrics;

    public function __construct()
    {
        $this->performanceMetrics = [
            'daily_pnl' => 0,
            'weekly_pnl' => 0,
            'monthly_pnl' => 0,
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'total_pnl' => 0,
            'avg_pnl_per_trade' => 0,
            'avg_profit_per_trade' => 0,
            'max_drawdown' => 0,
            'sharpe_ratio' => 0,
            'portfolio_value' => 0,
            'risk_adjusted_return' => 0
        ];
    }

    /**
     * Record a completed trade
     */
    public function recordTrade(array $tradeData): void
    {
        try {
            $tradeRecord = [
                'symbol' => $tradeData['symbol'],
                'entry_price' => $tradeData['entry_price'],
                'exit_price' => $tradeData['exit_price'],
                'quantity' => $tradeData['quantity'],
                'entry_time' => $tradeData['entry_time'],
                'exit_time' => $tradeData['exit_time'],
                'pnl' => $tradeData['pnl'],
                'pnl_percent' => $tradeData['pnl_percent'],
                'holding_period' => $tradeData['holding_period'],
                'strategy' => $tradeData['strategy'] ?? 'default',
                'market_conditions' => $tradeData['market_conditions'] ?? [],
                'created_at' => now()
            ];

            // Store in cache for quick access
            $this->storeTradeInCache($tradeRecord);
            
            // Update performance metrics
            $this->updatePerformanceMetrics($tradeRecord);
            
            // Log the trade
            Log::info('Trade recorded', $tradeRecord);
        } catch (\Exception $e) {
            Log::error('Failed to record trade', [
                'error' => $e->getMessage(),
                'trade_data' => $tradeData
            ]);
        }
    }

    /**
     * Get comprehensive performance analytics
     */
    public function getPerformanceAnalytics(string $period = 'all'): array
    {
        try {
            $trades = $this->getTradesForPeriod($period);
            
            if (empty($trades)) {
                return $this->performanceMetrics;
            }

            $analytics = [
                'period' => $period,
                'total_trades' => count($trades),
                'winning_trades' => count(array_filter($trades, fn($t) => $t['pnl'] > 0)),
                'losing_trades' => count(array_filter($trades, fn($t) => $t['pnl'] < 0)),
                'total_pnl' => array_sum(array_column($trades, 'pnl')),
                'avg_pnl_per_trade' => array_sum(array_column($trades, 'pnl')) / count($trades),
                'win_rate' => $this->calculateWinRate($trades),
                'avg_profit_per_winning_trade' => $this->calculateAvgProfitPerWinningTrade($trades),
                'avg_loss_per_losing_trade' => $this->calculateAvgLossPerLosingTrade($trades),
                'max_profit' => max(array_column($trades, 'pnl')),
                'max_loss' => min(array_column($trades, 'pnl')),
                'max_drawdown' => $this->calculateMaxDrawdown($trades),
                'sharpe_ratio' => $this->calculateSharpeRatio($trades),
                'profit_factor' => $this->calculateProfitFactor($trades),
                'avg_holding_period' => $this->calculateAvgHoldingPeriod($trades),
                'best_performing_stocks' => $this->getBestPerformingStocks($trades),
                'worst_performing_stocks' => $this->getWorstPerformingStocks($trades),
                'strategy_performance' => $this->getStrategyPerformance($trades),
                'market_condition_performance' => $this->getMarketConditionPerformance($trades),
                'monthly_returns' => $this->getMonthlyReturns($trades),
                'risk_metrics' => $this->calculateRiskMetrics($trades)
            ];

            return $analytics;
        } catch (\Exception $e) {
            Log::error('Performance analytics failed', ['error' => $e->getMessage()]);
            return $this->performanceMetrics;
        }
    }

    /**
     * Get real-time portfolio performance
     */
    public function getPortfolioPerformance(): array
    {
        try {
            $currentPositions = $this->getCurrentPositions();
            $totalInvested = array_sum(array_column($currentPositions, 'invested_value'));
            $totalCurrentValue = array_sum(array_column($currentPositions, 'current_value'));
            $unrealizedPnl = $totalCurrentValue - $totalInvested;
            $unrealizedPnlPercent = $totalInvested > 0 ? ($unrealizedPnl / $totalInvested) * 100 : 0;

            $realizedPnl = $this->getRealizedPnl();
            $totalPnl = $realizedPnl + $unrealizedPnl;

            return [
                'total_invested' => $totalInvested,
                'total_current_value' => $totalCurrentValue,
                'unrealized_pnl' => $unrealizedPnl,
                'unrealized_pnl_percent' => $unrealizedPnlPercent,
                'realized_pnl' => $realizedPnl,
                'total_pnl' => $totalPnl,
                'total_pnl_percent' => $totalInvested > 0 ? ($totalPnl / $totalInvested) * 100 : 0,
                'position_count' => count($currentPositions),
                'top_gainers' => $this->getTopGainers($currentPositions),
                'top_losers' => $this->getTopLosers($currentPositions),
                'sector_allocation' => $this->getSectorAllocation($currentPositions),
                'risk_metrics' => $this->calculatePortfolioRiskMetrics($currentPositions)
            ];
        } catch (\Exception $e) {
            Log::error('Portfolio performance calculation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(string $period = 'month'): array
    {
        try {
            $analytics = $this->getPerformanceAnalytics($period);
            $portfolio = $this->getPortfolioPerformance();
            
            // Ensure analytics has required keys with defaults
            $analytics = array_merge([
                'total_trades' => 0,
                'win_rate' => 0,
                'total_pnl' => 0,
                'avg_pnl_per_trade' => 0,
                'max_drawdown' => 0,
                'sharpe_ratio' => 0
            ], $analytics);
            
            // Ensure portfolio has required keys with defaults
            $portfolio = array_merge([
                'total_invested' => 0,
                'total_current_value' => 0,
                'total_pnl' => 0,
                'total_pnl_percent' => 0
            ], $portfolio);
            
            $report = [
                'report_period' => $period,
                'generated_at' => now()->toISOString(),
                'summary' => [
                    'total_trades' => $analytics['total_trades'],
                    'win_rate' => round($analytics['win_rate'], 2),
                    'total_pnl' => round($analytics['total_pnl'], 2),
                    'avg_pnl_per_trade' => round($analytics['avg_pnl_per_trade'], 2),
                    'max_drawdown' => round($analytics['max_drawdown'], 2),
                    'sharpe_ratio' => round($analytics['sharpe_ratio'], 2)
                ],
                'portfolio_summary' => [
                    'total_invested' => round($portfolio['total_invested'], 2),
                    'current_value' => round($portfolio['total_current_value'], 2),
                    'total_pnl' => round($portfolio['total_pnl'], 2),
                    'total_pnl_percent' => round($portfolio['total_pnl_percent'], 2)
                ],
                'detailed_analytics' => $analytics,
                'portfolio_details' => $portfolio,
                'recommendations' => $this->generateRecommendations($analytics, $portfolio),
                'risk_assessment' => $this->assessRisk($analytics, $portfolio)
            ];

            return $report;
        } catch (\Exception $e) {
            Log::error('Performance report generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Track strategy performance
     */
    public function trackStrategyPerformance(string $strategy, array $tradeData): void
    {
        try {
            $strategyKey = "strategy_performance_{$strategy}";
            $performance = Cache::get($strategyKey, [
                'total_trades' => 0,
                'winning_trades' => 0,
                'total_pnl' => 0,
                'avg_pnl' => 0,
                'win_rate' => 0,
                'last_updated' => now()
            ]);

            $performance['total_trades']++;
            if ($tradeData['pnl'] > 0) {
                $performance['winning_trades']++;
            }
            $performance['total_pnl'] += $tradeData['pnl'];
            $performance['avg_pnl'] = $performance['total_pnl'] / $performance['total_trades'];
            $performance['win_rate'] = ($performance['winning_trades'] / $performance['total_trades']) * 100;
            $performance['last_updated'] = now();

            Cache::put($strategyKey, $performance, 86400 * 30); // 30 days
        } catch (\Exception $e) {
            Log::error('Strategy performance tracking failed', [
                'strategy' => $strategy,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get market condition performance
     */
    public function getMarketConditionPerformance(array $trades): array
    {
        $performance = [];
        
        foreach ($trades as $trade) {
            $conditions = $trade['market_conditions'] ?? [];
            $regime = $conditions['market_regime'] ?? 'unknown';
            
            if (!isset($performance[$regime])) {
                $performance[$regime] = [
                    'trades' => 0,
                    'total_pnl' => 0,
                    'winning_trades' => 0
                ];
            }
            
            $performance[$regime]['trades']++;
            $performance[$regime]['total_pnl'] += $trade['pnl'];
            if ($trade['pnl'] > 0) {
                $performance[$regime]['winning_trades']++;
            }
        }
        
        // Calculate percentages
        foreach ($performance as $regime => $data) {
            $performance[$regime]['win_rate'] = $data['trades'] > 0 ? 
                ($data['winning_trades'] / $data['trades']) * 100 : 0;
            $performance[$regime]['avg_pnl'] = $data['trades'] > 0 ? 
                $data['total_pnl'] / $data['trades'] : 0;
        }
        
        return $performance;
    }

    /**
     * Calculate risk-adjusted return metrics
     */
    public function calculateRiskAdjustedReturn(array $trades): array
    {
        if (empty($trades)) {
            return ['sharpe_ratio' => 0, 'sortino_ratio' => 0, 'calmar_ratio' => 0];
        }

        $returns = array_column($trades, 'pnl_percent');
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStandardDeviation($returns);
        
        // Sharpe Ratio (assuming risk-free rate of 6% annually)
        $riskFreeRate = 6.0 / 365; // Daily risk-free rate
        $sharpeRatio = $stdDev > 0 ? ($avgReturn - $riskFreeRate) / $stdDev : 0;
        
        // Sortino Ratio (downside deviation)
        $downsideReturns = array_filter($returns, fn($r) => $r < 0);
        $downsideStdDev = $this->calculateStandardDeviation($downsideReturns);
        $sortinoRatio = $downsideStdDev > 0 ? $avgReturn / $downsideStdDev : 0;
        
        // Calmar Ratio (return vs max drawdown)
        $maxDrawdown = $this->calculateMaxDrawdown($trades);
        $calmarRatio = $maxDrawdown > 0 ? $avgReturn / abs($maxDrawdown) : 0;

        return [
            'sharpe_ratio' => round($sharpeRatio, 3),
            'sortino_ratio' => round($sortinoRatio, 3),
            'calmar_ratio' => round($calmarRatio, 3),
            'avg_return' => round($avgReturn, 3),
            'volatility' => round($stdDev, 3),
            'downside_volatility' => round($downsideStdDev, 3)
        ];
    }

    /**
     * Generate trading recommendations based on performance
     */
    protected function generateRecommendations(array $analytics, array $portfolio): array
    {
        $recommendations = [];
        
        // Ensure arrays have required keys with defaults
        $analytics = array_merge([
            'win_rate' => 0,
            'max_drawdown' => 0,
            'total_pnl' => 0
        ], $analytics);
        
        $portfolio = array_merge([
            'position_count' => 0
        ], $portfolio);
        
        // Win rate recommendations
        if ($analytics['win_rate'] < 40) {
            $recommendations[] = [
                'type' => 'strategy',
                'priority' => 'high',
                'message' => 'Low win rate detected. Consider reviewing entry/exit criteria.',
                'action' => 'Review and optimize trading strategy'
            ];
        }
        
        // Drawdown recommendations
        if ($analytics['max_drawdown'] < -15) {
            $recommendations[] = [
                'type' => 'risk',
                'priority' => 'high',
                'message' => 'High drawdown detected. Consider reducing position sizes.',
                'action' => 'Implement stricter risk management'
            ];
        }
        
        // Portfolio concentration recommendations
        if ($portfolio['position_count'] < 5) {
            $recommendations[] = [
                'type' => 'diversification',
                'priority' => 'medium',
                'message' => 'Low diversification. Consider adding more positions.',
                'action' => 'Increase portfolio diversification'
            ];
        }
        
        // Performance recommendations
        if ($analytics['total_pnl'] > 0 && $analytics['win_rate'] > 60) {
            $recommendations[] = [
                'type' => 'optimization',
                'priority' => 'low',
                'message' => 'Good performance. Consider scaling up successful strategies.',
                'action' => 'Scale up winning strategies'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Assess overall risk level
     */
    protected function assessRisk(array $analytics, array $portfolio): array
    {
        $riskScore = 0;
        $riskFactors = [];
        
        // Ensure arrays have required keys with defaults
        $analytics = array_merge([
            'win_rate' => 0,
            'max_drawdown' => 0,
            'sharpe_ratio' => 0
        ], $analytics);
        
        $portfolio = array_merge([
            'position_count' => 0,
            'total_pnl_percent' => 0
        ], $portfolio);
        
        // Win rate risk
        if ($analytics['win_rate'] < 40) {
            $riskScore += 30;
            $riskFactors[] = 'Low win rate';
        }
        
        // Drawdown risk
        if ($analytics['max_drawdown'] < -15) {
            $riskScore += 25;
            $riskFactors[] = 'High drawdown';
        }
        
        // Volatility risk
        if ($analytics['sharpe_ratio'] < 0.5) {
            $riskScore += 20;
            $riskFactors[] = 'Low risk-adjusted returns';
        }
        
        // Concentration risk
        if ($portfolio['position_count'] < 5) {
            $riskScore += 15;
            $riskFactors[] = 'Low diversification';
        }
        
        // Portfolio risk
        if ($portfolio['total_pnl_percent'] < -10) {
            $riskScore += 25;
            $riskFactors[] = 'Negative portfolio performance';
        }
        
        $riskLevel = 'low';
        if ($riskScore >= 70) $riskLevel = 'high';
        elseif ($riskScore >= 40) $riskLevel = 'medium';
        
        return [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'recommendations' => $this->getRiskRecommendations($riskLevel, $riskFactors)
        ];
    }

    /**
     * Get risk recommendations
     */
    protected function getRiskRecommendations(string $riskLevel, array $riskFactors): array
    {
        $recommendations = [];
        
        switch ($riskLevel) {
            case 'high':
                $recommendations[] = 'Consider reducing position sizes immediately';
                $recommendations[] = 'Review and tighten stop-loss levels';
                $recommendations[] = 'Consider pausing trading until risk factors are addressed';
                break;
            case 'medium':
                $recommendations[] = 'Monitor positions more closely';
                $recommendations[] = 'Consider reducing position sizes';
                $recommendations[] = 'Review trading strategy';
                break;
            case 'low':
                $recommendations[] = 'Continue current strategy';
                $recommendations[] = 'Monitor for any changes in market conditions';
                break;
        }
        
        return $recommendations;
    }

    // Helper methods for calculations

    protected function storeTradeInCache(array $trade): void
    {
        $key = 'trades_' . now()->format('Y-m-d');
        $trades = Cache::get($key, []);
        $trades[] = $trade;
        Cache::put($key, $trades, 86400 * 30); // 30 days
    }

    protected function updatePerformanceMetrics(array $trade): void
    {
        // Update daily metrics
        $dailyKey = 'daily_performance_' . now()->format('Y-m-d');
        $dailyMetrics = Cache::get($dailyKey, [
            'trades' => 0,
            'pnl' => 0,
            'winning_trades' => 0
        ]);
        
        $dailyMetrics['trades']++;
        $dailyMetrics['pnl'] += $trade['pnl'];
        if ($trade['pnl'] > 0) {
            $dailyMetrics['winning_trades']++;
        }
        
        Cache::put($dailyKey, $dailyMetrics, 86400 * 30);
    }

    protected function getTradesForPeriod(string $period): array
    {
        $trades = [];
        $endDate = now();
        
        switch ($period) {
            case 'day':
                $startDate = now()->subDay();
                break;
            case 'week':
                $startDate = now()->subWeek();
                break;
            case 'month':
                $startDate = now()->subMonth();
                break;
            case 'quarter':
                $startDate = now()->subQuarter();
                break;
            case 'year':
                $startDate = now()->subYear();
                break;
            default:
                $startDate = now()->subYear(); // Default to 1 year
        }
        
        // Get trades from cache for the period
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $key = 'trades_' . $currentDate->format('Y-m-d');
            $dayTrades = Cache::get($key, []);
            $trades = array_merge($trades, $dayTrades);
            $currentDate->addDay();
        }
        
        return $trades;
    }

    protected function calculateWinRate(array $trades): float
    {
        if (empty($trades)) return 0;
        $winningTrades = count(array_filter($trades, fn($t) => $t['pnl'] > 0));
        return ($winningTrades / count($trades)) * 100;
    }

    protected function calculateAvgProfitPerWinningTrade(array $trades): float
    {
        $winningTrades = array_filter($trades, fn($t) => $t['pnl'] > 0);
        if (empty($winningTrades)) return 0;
        return array_sum(array_column($winningTrades, 'pnl')) / count($winningTrades);
    }

    protected function calculateAvgLossPerLosingTrade(array $trades): float
    {
        $losingTrades = array_filter($trades, fn($t) => $t['pnl'] < 0);
        if (empty($losingTrades)) return 0;
        return array_sum(array_column($losingTrades, 'pnl')) / count($losingTrades);
    }

    protected function calculateMaxDrawdown(array $trades): float
    {
        if (empty($trades)) return 0;
        
        $cumulativePnl = 0;
        $maxCumulative = 0;
        $maxDrawdown = 0;
        
        foreach ($trades as $trade) {
            $cumulativePnl += $trade['pnl'];
            $maxCumulative = max($maxCumulative, $cumulativePnl);
            $drawdown = $maxCumulative - $cumulativePnl;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }
        
        return -$maxDrawdown; // Return as negative value
    }

    protected function calculateSharpeRatio(array $trades): float
    {
        if (empty($trades)) return 0;
        
        $returns = array_column($trades, 'pnl_percent');
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStandardDeviation($returns);
        
        if ($stdDev == 0) return 0;
        
        // Assuming 6% annual risk-free rate
        $riskFreeRate = 6.0 / 365; // Daily rate
        return ($avgReturn - $riskFreeRate) / $stdDev;
    }

    protected function calculateProfitFactor(array $trades): float
    {
        $grossProfit = array_sum(array_filter(array_column($trades, 'pnl'), fn($p) => $p > 0));
        $grossLoss = abs(array_sum(array_filter(array_column($trades, 'pnl'), fn($p) => $p < 0)));
        
        return $grossLoss > 0 ? $grossProfit / $grossLoss : 0;
    }

    protected function calculateAvgHoldingPeriod(array $trades): float
    {
        if (empty($trades)) return 0;
        
        $totalHoldingPeriod = array_sum(array_column($trades, 'holding_period'));
        return $totalHoldingPeriod / count($trades);
    }

    protected function getBestPerformingStocks(array $trades): array
    {
        $stockPerformance = [];
        
        foreach ($trades as $trade) {
            $symbol = $trade['symbol'];
            if (!isset($stockPerformance[$symbol])) {
                $stockPerformance[$symbol] = ['trades' => 0, 'total_pnl' => 0];
            }
            $stockPerformance[$symbol]['trades']++;
            $stockPerformance[$symbol]['total_pnl'] += $trade['pnl'];
        }
        
        // Calculate average PnL per stock
        foreach ($stockPerformance as $symbol => $data) {
            $stockPerformance[$symbol]['avg_pnl'] = $data['total_pnl'] / $data['trades'];
        }
        
        // Sort by average PnL descending
        uasort($stockPerformance, fn($a, $b) => $b['avg_pnl'] <=> $a['avg_pnl']);
        
        return array_slice($stockPerformance, 0, 5, true);
    }

    protected function getWorstPerformingStocks(array $trades): array
    {
        $stockPerformance = [];
        
        foreach ($trades as $trade) {
            $symbol = $trade['symbol'];
            if (!isset($stockPerformance[$symbol])) {
                $stockPerformance[$symbol] = ['trades' => 0, 'total_pnl' => 0];
            }
            $stockPerformance[$symbol]['trades']++;
            $stockPerformance[$symbol]['total_pnl'] += $trade['pnl'];
        }
        
        // Calculate average PnL per stock
        foreach ($stockPerformance as $symbol => $data) {
            $stockPerformance[$symbol]['avg_pnl'] = $data['total_pnl'] / $data['trades'];
        }
        
        // Sort by average PnL ascending
        uasort($stockPerformance, fn($a, $b) => $a['avg_pnl'] <=> $b['avg_pnl']);
        
        return array_slice($stockPerformance, 0, 5, true);
    }

    protected function getStrategyPerformance(array $trades): array
    {
        $strategyPerformance = [];
        
        foreach ($trades as $trade) {
            $strategy = $trade['strategy'] ?? 'default';
            if (!isset($strategyPerformance[$strategy])) {
                $strategyPerformance[$strategy] = [
                    'trades' => 0,
                    'total_pnl' => 0,
                    'winning_trades' => 0
                ];
            }
            
            $strategyPerformance[$strategy]['trades']++;
            $strategyPerformance[$strategy]['total_pnl'] += $trade['pnl'];
            if ($trade['pnl'] > 0) {
                $strategyPerformance[$strategy]['winning_trades']++;
            }
        }
        
        // Calculate metrics for each strategy
        foreach ($strategyPerformance as $strategy => $data) {
            $strategyPerformance[$strategy]['win_rate'] = 
                $data['trades'] > 0 ? ($data['winning_trades'] / $data['trades']) * 100 : 0;
            $strategyPerformance[$strategy]['avg_pnl'] = 
                $data['trades'] > 0 ? $data['total_pnl'] / $data['trades'] : 0;
        }
        
        return $strategyPerformance;
    }

    protected function getMonthlyReturns(array $trades): array
    {
        $monthlyReturns = [];
        
        foreach ($trades as $trade) {
            $month = Carbon::parse($trade['exit_time'])->format('Y-m');
            if (!isset($monthlyReturns[$month])) {
                $monthlyReturns[$month] = 0;
            }
            $monthlyReturns[$month] += $trade['pnl'];
        }
        
        ksort($monthlyReturns);
        return $monthlyReturns;
    }

    protected function calculateRiskMetrics(array $trades): array
    {
        if (empty($trades)) {
            return [
                'volatility' => 0,
                'var_95' => 0,
                'cvar_95' => 0,
                'max_daily_loss' => 0
            ];
        }
        
        $returns = array_column($trades, 'pnl_percent');
        $volatility = $this->calculateStandardDeviation($returns);
        
        // Value at Risk (VaR) 95%
        sort($returns);
        $varIndex = floor(count($returns) * 0.05);
        $var95 = $returns[$varIndex] ?? 0;
        
        // Conditional Value at Risk (CVaR) 95%
        $tailReturns = array_slice($returns, 0, $varIndex + 1);
        $cvar95 = empty($tailReturns) ? 0 : array_sum($tailReturns) / count($tailReturns);
        
        // Maximum daily loss
        $maxDailyLoss = min($returns);
        
        return [
            'volatility' => round($volatility, 3),
            'var_95' => round($var95, 3),
            'cvar_95' => round($cvar95, 3),
            'max_daily_loss' => round($maxDailyLoss, 3)
        ];
    }

    protected function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        
        return sqrt($variance);
    }

    protected function getCurrentPositions(): array
    {
        try {
            $flatTradeService = app(\App\Services\FlatTradeService::class);
            $positions = $flatTradeService->getPositionBook('NSE', '', 0, '', '', '', '');
            
            if (is_array($positions) && !empty($positions)) {
                if (isset($positions[0]['stat']) && $positions[0]['stat'] === 'Ok') {
                    return $this->formatPositionsForPerformance($positions);
                }
            }
            
            if (isset($positions['stat']) && $positions['stat'] === 'Ok' && isset($positions['netqty'])) {
                return $this->formatPositionsForPerformance([$positions]);
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get current positions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Format FlatTrade positions for performance tracking
     */
    protected function formatPositionsForPerformance(array $positions): array
    {
        $formattedPositions = [];
        
        foreach ($positions as $position) {
            $symbol = $position['tsym'] ?? null;
            $quantity = (int) ($position['netqty'] ?? 0);
            $avgPrice = (float) ($position['netavgprc'] ?? 0);
            $currentPrice = (float) ($position['lp'] ?? 0);
            
            // Skip invalid positions
            if (!$symbol || $quantity <= 0 || $avgPrice <= 0 || $currentPrice <= 0) {
                continue;
            }
            
            $investedValue = $quantity * $avgPrice;
            $currentValue = $quantity * $currentPrice;
            $pnl = $currentValue - $investedValue;
            $pnlPercent = $investedValue > 0 ? ($pnl / $investedValue) * 100 : 0;
            
            $formattedPositions[] = [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'avg_price' => $avgPrice,
                'current_price' => $currentPrice,
                'invested_value' => $investedValue,
                'current_value' => $currentValue,
                'pnl' => $pnl,
                'pnl_percent' => $pnlPercent,
                'entry_time' => $position['entry_time'] ?? now()->subDays(1),
                'raw_data' => $position
            ];
        }
        return $formattedPositions;
    }

    protected function getRealizedPnl(): float
    {
        // This would calculate realized PnL from completed trades
        return 0;
    }

    protected function getTopGainers(array $positions): array
    {
        // Sort by PnL descending and return top 3
        usort($positions, fn($a, $b) => $b['pnl'] <=> $a['pnl']);
        return array_slice($positions, 0, 3);
    }

    protected function getTopLosers(array $positions): array
    {
        // Sort by PnL ascending and return top 3
        usort($positions, fn($a, $b) => $a['pnl'] <=> $b['pnl']);
        return array_slice($positions, 0, 3);
    }

    protected function getSectorAllocation(array $positions): array
    {
        $sectorAllocation = [];
        $totalValue = array_sum(array_column($positions, 'current_value'));
        
        foreach ($positions as $position) {
            $sector = $this->getStockSector($position['symbol']);
            $value = $position['current_value'];
            
            if (!isset($sectorAllocation[$sector])) {
                $sectorAllocation[$sector] = [
                    'value' => 0,
                    'percentage' => 0,
                    'positions' => 0
                ];
            }
            
            $sectorAllocation[$sector]['value'] += $value;
            $sectorAllocation[$sector]['positions']++;
        }
        
        // Calculate percentages
        foreach ($sectorAllocation as $sector => $data) {
            $sectorAllocation[$sector]['percentage'] = $totalValue > 0 ? 
                ($data['value'] / $totalValue) * 100 : 0;
        }
        
        return $sectorAllocation;
    }

    /**
     * Get stock sector (simplified implementation)
     */
    protected function getStockSector(string $symbol): string
    {
        $sectorMap = [
            'RELIANCE' => 'Energy',
            'TCS' => 'IT',
            'HDFCBANK' => 'Banking',
            'INFY' => 'IT',
            'HINDUNILVR' => 'FMCG',
            'ITC' => 'FMCG',
            'SBIN' => 'Banking',
            'BHARTIARTL' => 'Telecom',
            'KOTAKBANK' => 'Banking',
            'LT' => 'Infrastructure',
            'ASIANPAINT' => 'Paints',
            'AXISBANK' => 'Banking',
            'MARUTI' => 'Automobile',
            'SUNPHARMA' => 'Pharmaceuticals',
            'TITAN' => 'Jewellery',
            'ULTRACEMCO' => 'Cement',
            'WIPRO' => 'IT',
            'NESTLEIND' => 'FMCG',
            'ONGC' => 'Energy',
            'POWERGRID' => 'Power',
            'NTPC' => 'Power',
            'TECHM' => 'IT',
            'TATAMOTORS' => 'Automobile',
            'BAJFINANCE' => 'NBFC',
            'HCLTECH' => 'IT',
            'BAJAJFINSV' => 'NBFC',
            'DRREDDY' => 'Pharmaceuticals',
            'JSWSTEEL' => 'Steel',
            'TATASTEEL' => 'Steel',
            'COALINDIA' => 'Mining',
            'GRASIM' => 'Textiles',
            'BRITANNIA' => 'FMCG',
            'EICHERMOT' => 'Automobile',
            'HEROMOTOCO' => 'Automobile',
            'DIVISLAB' => 'Pharmaceuticals',
            'CIPLA' => 'Pharmaceuticals',
            'APOLLOHOSP' => 'Healthcare',
            'ADANIPORTS' => 'Infrastructure',
            'INDUSINDBK' => 'Banking',
            'TATACONSUM' => 'FMCG',
            'BPCL' => 'Energy',
            'ICICIBANK' => 'Banking',
            'ADANIENT' => 'Conglomerate',
            'HDFCLIFE' => 'Insurance',
            'SBILIFE' => 'Insurance',
            'BAJAJ-AUTO' => 'Automobile',
            'UPL' => 'Agrochemicals',
            'SHREECEM' => 'Cement'
        ];
        
        return $sectorMap[$symbol] ?? 'Other';
    }

    protected function calculatePortfolioRiskMetrics(array $positions): array
    {
        if (empty($positions)) {
            return [
                'portfolio_value' => 0,
                'concentration_risk' => 0,
                'sector_diversification' => 0,
                'position_count' => 0,
                'avg_position_size' => 0,
                'max_position_weight' => 0
            ];
        }

        $totalValue = array_sum(array_column($positions, 'current_value'));
        $positionCount = count($positions);
        
        // Calculate concentration risk (max position weight)
        $maxPositionWeight = 0;
        $positionWeights = [];
        
        foreach ($positions as $position) {
            $weight = $totalValue > 0 ? ($position['current_value'] / $totalValue) * 100 : 0;
            $positionWeights[] = $weight;
            $maxPositionWeight = max($maxPositionWeight, $weight);
        }
        
        // Calculate sector diversification
        $sectors = [];
        foreach ($positions as $position) {
            $sector = $this->getStockSector($position['symbol']);
            $sectors[$sector] = ($sectors[$sector] ?? 0) + 1;
        }
        
        $sectorCount = count($sectors);
        $sectorDiversification = $positionCount > 0 ? ($sectorCount / $positionCount) * 100 : 0;
        
        // Calculate average position size
        $avgPositionSize = $positionCount > 0 ? $totalValue / $positionCount : 0;
        
        // Calculate concentration risk score (higher is riskier)
        $concentrationRisk = $maxPositionWeight > 20 ? 'High' : 
                           ($maxPositionWeight > 10 ? 'Medium' : 'Low');

        return [
            'portfolio_value' => $totalValue,
            'concentration_risk' => $concentrationRisk,
            'max_position_weight' => round($maxPositionWeight, 2),
            'sector_diversification' => round($sectorDiversification, 2),
            'sector_count' => $sectorCount,
            'position_count' => $positionCount,
            'avg_position_size' => round($avgPositionSize, 2),
            'position_weights' => $positionWeights,
            'sector_breakdown' => $sectors
        ];
    }
}
