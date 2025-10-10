<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EnhancedTradingService
{
    protected FlatTradeService $flatTradeService;
    protected RiskManagementService $riskService;
    protected TradingStrategyService $strategyService;

    public function __construct(
        FlatTradeService $flatTradeService,
        RiskManagementService $riskService,
        TradingStrategyService $strategyService
    ) {
        $this->flatTradeService = $flatTradeService;
        $this->riskService = $riskService;
        $this->strategyService = $strategyService;
    }

    /**
     * Analyze market conditions and determine trading strategy
     */
    public function analyzeMarketConditions(): array
    {
        try {
            // Get Nifty 50 index data for market sentiment
            $niftyData = $this->getNiftyIndexData();
            
            // Analyze market volatility
            $volatility = $this->calculateMarketVolatility();
            
            // Get sector performance
            $sectorPerformance = $this->getSectorPerformance();
            
            // Determine market regime
            $marketRegime = $this->determineMarketRegime($niftyData, $volatility);
            
            return [
                'market_regime' => $marketRegime,
                'volatility_level' => $volatility,
                'nifty_trend' => $niftyData['trend'] ?? 'neutral',
                'sector_performance' => $sectorPerformance,
                'recommended_strategy' => $this->getRecommendedStrategy($marketRegime),
                'risk_multiplier' => $this->getRiskMultiplier($marketRegime, $volatility)
            ];
        } catch (\Exception $e) {
            Log::error('Market analysis failed', ['error' => $e->getMessage()]);
            return [
                'market_regime' => 'neutral',
                'volatility_level' => 'moderate',
                'recommended_strategy' => 'conservative',
                'risk_multiplier' => 0.5
            ];
        }
    }

    /**
     * Enhanced stock selection with multi-factor scoring
     */
    public function selectOptimalStocks(int $maxStocks = 5, array $marketConditions = []): array
    {
        try {
            $nifty50Stocks = $this->getNifty50Symbols();
            $stockScores = [];
            
            foreach ($nifty50Stocks as $symbol) {
                $score = $this->calculateStockScore($symbol, $marketConditions);
                if ($score['total_score'] > 0) {
                    $stockScores[] = array_merge(['symbol' => $symbol], $score);
                }
            }
            
            // Sort by total score descending
            usort($stockScores, fn($a, $b) => $b['total_score'] <=> $a['total_score']);
            
            return array_slice($stockScores, 0, $maxStocks);
        } catch (\Exception $e) {
            Log::error('Stock selection failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate comprehensive stock score
     */
    protected function calculateStockScore(string $symbol, array $marketConditions): array
    {
        try {
            $quote = $this->getStockQuote($symbol);
            if (!$quote) return ['total_score' => 0];

            // Technical indicators
            $technicalScore = $this->calculateTechnicalScore($symbol);
            
            // Fundamental factors
            $fundamentalScore = $this->calculateFundamentalScore($symbol, $quote);
            
            // Market momentum
            $momentumScore = $this->calculateMomentumScore($quote);
            
            // Risk factors
            $riskScore = $this->calculateRiskScore($quote);
            
            // Market condition adjustment
            $marketAdjustment = $this->getMarketConditionAdjustment($marketConditions, $quote);
            
            // Weighted total score
            $totalScore = (
                $technicalScore * 0.3 +
                $fundamentalScore * 0.25 +
                $momentumScore * 0.25 +
                $riskScore * 0.15 +
                $marketAdjustment * 0.05
            );

            return [
                'total_score' => round($totalScore, 2),
                'technical_score' => $technicalScore,
                'fundamental_score' => $fundamentalScore,
                'momentum_score' => $momentumScore,
                'risk_score' => $riskScore,
                'market_adjustment' => $marketAdjustment,
                'quote' => $quote
            ];
        } catch (\Exception $e) {
            Log::error("Stock score calculation failed for {$symbol}", ['error' => $e->getMessage()]);
            return ['total_score' => 0];
        }
    }

    /**
     * Calculate technical analysis score
     */
    protected function calculateTechnicalScore(string $symbol): float
    {
        try {
            // Get historical data for technical analysis
            $historicalData = $this->getHistoricalData($symbol, 20);
            if (empty($historicalData)) return 0;

            $prices = array_column($historicalData, 'close');
            $signal = $this->strategyService->generateTradeSignal($prices);
            
            // Convert signal to score
            switch ($signal[0]) {
                case 'BUY': return 80;
                case 'SELL': return 20;
                case 'HOLD': return 50;
                default: return 50;
            }
        } catch (\Exception $e) {
            return 50; // Neutral score on error
        }
    }

    /**
     * Calculate fundamental score
     */
    protected function calculateFundamentalScore(string $symbol, array $quote): float
    {
        $score = 50; // Base score
        
        // Price range check
        $ltp = $quote['ltp'] ?? 0;
        if ($ltp >= 100 && $ltp <= 5000) $score += 10;
        elseif ($ltp >= 50 && $ltp <= 10000) $score += 5;
        
        // Volume check
        $volume = $quote['volume'] ?? 0;
        if ($volume > 1000000) $score += 15;
        elseif ($volume > 500000) $score += 10;
        elseif ($volume > 100000) $score += 5;
        
        // Circuit limit check
        $upperCircuit = $quote['upper_circuit'] ?? 0;
        $lowerCircuit = $quote['lower_circuit'] ?? 0;
        if ($upperCircuit > 0 && $lowerCircuit > 0) {
            $circuitRange = ($upperCircuit - $lowerCircuit) / $ltp;
            if ($circuitRange > 0.1) $score += 10; // Good liquidity
        }
        
        return min(100, max(0, $score));
    }

    /**
     * Calculate momentum score
     */
    protected function calculateMomentumScore(array $quote): float
    {
        $changePercent = $quote['change_percent'] ?? 0;
        
        // Optimal momentum range
        if ($changePercent >= 1.0 && $changePercent <= 5.0) return 90;
        if ($changePercent >= 0.5 && $changePercent <= 8.0) return 70;
        if ($changePercent >= 0.0 && $changePercent <= 10.0) return 50;
        if ($changePercent < 0) return max(0, 50 + $changePercent * 5);
        
        return 30; // Too high momentum might be risky
    }

    /**
     * Calculate risk score
     */
    protected function calculateRiskScore(array $quote): float
    {
        $score = 50; // Base score
        
        // Price stability
        $ltp = $quote['ltp'] ?? 0;
        $high = $quote['high'] ?? 0;
        $low = $quote['low'] ?? 0;
        
        if ($high > 0 && $low > 0) {
            $dailyRange = ($high - $low) / $ltp;
            if ($dailyRange < 0.05) $score += 20; // Very stable
            elseif ($dailyRange < 0.1) $score += 10; // Stable
            elseif ($dailyRange > 0.2) $score -= 20; // Volatile
        }
        
        // Volume consistency
        $volume = $quote['volume'] ?? 0;
        if ($volume > 500000) $score += 15; // Good liquidity
        elseif ($volume < 100000) $score -= 20; // Low liquidity
        
        return min(100, max(0, $score));
    }

    /**
     * Get market condition adjustment
     */
    protected function getMarketConditionAdjustment(array $marketConditions, array $quote): float
    {
        $adjustment = 0;
        $marketRegime = $marketConditions['market_regime'] ?? 'neutral';
        $volatilityLevel = $marketConditions['volatility_level'] ?? 'moderate';
        
        // Adjust based on market regime
        switch ($marketRegime) {
            case 'bullish':
                if (($quote['change_percent'] ?? 0) > 0) $adjustment += 10;
                break;
            case 'bearish':
                if (($quote['change_percent'] ?? 0) < 0) $adjustment += 10;
                break;
            case 'volatile':
                $adjustment -= 15; // Reduce risk in volatile markets
                break;
        }
        
        // Adjust based on volatility
        if ($volatilityLevel === 'high') $adjustment -= 10;
        elseif ($volatilityLevel === 'low') $adjustment += 5;
        
        return $adjustment;
    }

    /**
     * Dynamic position sizing based on risk and market conditions
     */
    public function calculateOptimalPositionSize(
        string $symbol, 
        float $availableFunds, 
        array $marketConditions,
        array $portfolio = []
    ): array
    {
        try {
            $quote = $this->getStockQuote($symbol);
            if (!$quote) return ['quantity' => 0, 'reason' => 'No quote available'];

            $ltp = $quote['ltp'] ?? 0;
            if ($ltp <= 0) return ['quantity' => 0, 'reason' => 'Invalid price'];

            // Base position size calculation
            $riskMultiplier = $marketConditions['risk_multiplier'] ?? 0.5;
            $maxPositionValue = $availableFunds * 0.2 * $riskMultiplier; // Max 20% of funds per position
            
            // Adjust for portfolio correlation
            $correlationAdjustment = $this->calculateCorrelationAdjustment($symbol, $portfolio);
            $maxPositionValue *= $correlationAdjustment;
            
            // Calculate quantity
            $quantity = floor($maxPositionValue / $ltp);
            
            // Apply minimum and maximum limits
            $quantity = max(1, min($quantity, 100)); // Between 1 and 100 shares
            
            $totalValue = $quantity * $ltp;
            
            return [
                'quantity' => $quantity,
                'total_value' => $totalValue,
                'price' => $ltp,
                'risk_multiplier' => $riskMultiplier,
                'correlation_adjustment' => $correlationAdjustment,
                'max_position_value' => $maxPositionValue
            ];
        } catch (\Exception $e) {
            Log::error("Position sizing failed for {$symbol}", ['error' => $e->getMessage()]);
            return ['quantity' => 0, 'reason' => 'Calculation error'];
        }
    }

    /**
     * Enhanced exit strategy with multiple triggers
     */
    public function analyzeExitStrategy(array $position, array $marketConditions = []): array
    {
        try {
            // Handle FlatTrade position format
            $symbol = $position['tsym'] ?? $position['symbol'] ?? null;
            $currentPrice = (float) ($position['lp'] ?? $position['current_price'] ?? 0);
            $avgPrice = (float) ($position['netavgprc'] ?? $position['avg_price'] ?? 0);
            $quantity = (int) ($position['netqty'] ?? $position['quantity'] ?? 0);
            
            // Validate required data
            if (!$symbol || $currentPrice <= 0 || $avgPrice <= 0 || $quantity <= 0) {
                Log::warning('Invalid position data for exit analysis', [
                    'symbol' => $symbol,
                    'current_price' => $currentPrice,
                    'avg_price' => $avgPrice,
                    'quantity' => $quantity,
                    'position' => $position
                ]);
                return ['action' => 'hold', 'reason' => 'Invalid position data'];
            }
            
            $pnlPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
            
            // Technical exit signals
            $technicalSignal = $this->getTechnicalExitSignal($symbol);
            
            // Profit/Loss based exits
            $profitLossExit = $this->getProfitLossExit($pnlPercent, $marketConditions);
            
            // Time-based exits
            $timeBasedExit = $this->getTimeBasedExit($position);
            
            // Market condition exits
            $marketConditionExit = $this->getMarketConditionExit($marketConditions, $pnlPercent);
            
            // Combine all exit signals
            $exitSignals = [
                'technical' => $technicalSignal,
                'profit_loss' => $profitLossExit,
                'time_based' => $timeBasedExit,
                'market_condition' => $marketConditionExit
            ];
            
            // Determine final action
            $action = $this->determineExitAction($exitSignals, $pnlPercent);
            
            return [
                'action' => $action,
                'pnl_percent' => $pnlPercent,
                'signals' => $exitSignals,
                'confidence' => $this->calculateExitConfidence($exitSignals)
            ];
        } catch (\Exception $e) {
            Log::error("Exit strategy analysis failed", ['error' => $e->getMessage()]);
            return ['action' => 'hold', 'reason' => 'Analysis error'];
        }
    }

    /**
     * Get technical exit signal
     */
    protected function getTechnicalExitSignal(string $symbol): array
    {
        try {
            $historicalData = $this->getHistoricalData($symbol, 20);
            if (empty($historicalData)) return ['signal' => 'hold', 'confidence' => 0];

            $prices = array_column($historicalData, 'close');
            $signal = $this->strategyService->generateTradeSignal($prices);
            
            return [
                'signal' => $signal[0] === 'SELL' ? 'sell' : 'hold',
                'confidence' => $signal[0] === 'SELL' ? 0.8 : 0.2
            ];
        } catch (\Exception $e) {
            return ['signal' => 'hold', 'confidence' => 0];
        }
    }

    /**
     * Get profit/loss based exit
     */
    protected function getProfitLossExit(float $pnlPercent, array $marketConditions): array
    {
        $volatilityLevel = $marketConditions['volatility_level'] ?? 'moderate';
        
        // Dynamic thresholds based on market conditions
        $profitThreshold = $volatilityLevel === 'high' ? 3.0 : 5.0;
        $lossThreshold = $volatilityLevel === 'high' ? 2.0 : 3.0;
        
        if ($pnlPercent >= $profitThreshold) {
            return ['signal' => 'sell', 'confidence' => 0.9, 'reason' => 'Profit target reached'];
        }
        
        if ($pnlPercent <= -$lossThreshold) {
            return ['signal' => 'sell', 'confidence' => 0.95, 'reason' => 'Stop loss triggered'];
        }
        
        return ['signal' => 'hold', 'confidence' => 0.5];
    }

    /**
     * Get time-based exit
     */
    protected function getTimeBasedExit(array $position): array
    {
        // This would check position age and market timing
        // For now, return neutral signal
        return ['signal' => 'hold', 'confidence' => 0.3];
    }

    /**
     * Get market condition exit
     */
    protected function getMarketConditionExit(array $marketConditions, float $pnlPercent): array
    {
        $marketRegime = $marketConditions['market_regime'] ?? 'neutral';
        
        // Exit if market turns bearish and position is at loss
        if ($marketRegime === 'bearish' && $pnlPercent < 0) {
            return ['signal' => 'sell', 'confidence' => 0.7, 'reason' => 'Bearish market with loss'];
        }
        
        return ['signal' => 'hold', 'confidence' => 0.4];
    }

    /**
     * Determine final exit action
     */
    protected function determineExitAction(array $signals, float $pnlPercent): string
    {
        $sellVotes = 0;
        $totalConfidence = 0;
        
        foreach ($signals as $signal) {
            if ($signal['signal'] === 'sell') {
                $sellVotes += $signal['confidence'];
            }
            $totalConfidence += $signal['confidence'];
        }
        
        // Weighted decision
        $sellRatio = $totalConfidence > 0 ? $sellVotes / $totalConfidence : 0;
        
        if ($sellRatio >= 0.6) return 'sell';
        if ($sellRatio >= 0.4) return 'partial_sell';
        
        return 'hold';
    }

    /**
     * Calculate exit confidence
     */
    protected function calculateExitConfidence(array $signals): float
    {
        $confidences = array_column($signals, 'confidence');
        return array_sum($confidences) / count($confidences);
    }

    /**
     * Calculate correlation adjustment for portfolio diversification
     */
    protected function calculateCorrelationAdjustment(string $symbol, array $portfolio): float
    {
        // Simple correlation check - in real implementation, this would use historical correlation data
        $sector = $this->getStockSector($symbol);
        $portfolioSectors = array_map([$this, 'getStockSector'], array_column($portfolio, 'symbol'));
        
        $sectorCount = array_count_values($portfolioSectors);
        $currentSectorCount = $sectorCount[$sector] ?? 0;
        
        // Reduce position size if too many stocks from same sector
        if ($currentSectorCount >= 3) return 0.5;
        if ($currentSectorCount >= 2) return 0.7;
        
        return 1.0;
    }

    /**
     * Get stock sector (simplified implementation)
     */
    protected function getStockSector(string $symbol): string
    {
        // This would be a proper sector mapping in real implementation
        $sectorMap = [
            'RELIANCE' => 'Energy',
            'TCS' => 'IT',
            'HDFCBANK' => 'Banking',
            'INFY' => 'IT',
            // Add more mappings
        ];
        
        return $sectorMap[$symbol] ?? 'Other';
    }

    /**
     * Get Nifty index data
     */
    protected function getNiftyIndexData(): array
    {
        try {
            // This would fetch actual Nifty 50 index data
            return [
                'trend' => 'neutral',
                'change_percent' => 0.5
            ];
        } catch (\Exception $e) {
            return ['trend' => 'neutral', 'change_percent' => 0];
        }
    }

    /**
     * Calculate market volatility
     */
    protected function calculateMarketVolatility(): string
    {
        // This would calculate actual market volatility
        return 'moderate';
    }

    /**
     * Get sector performance
     */
    protected function getSectorPerformance(): array
    {
        // This would fetch actual sector performance data
        return [];
    }

    /**
     * Determine market regime
     */
    protected function determineMarketRegime(array $niftyData, string $volatility): string
    {
        $changePercent = $niftyData['change_percent'] ?? 0;
        
        if ($volatility === 'high') return 'volatile';
        if ($changePercent > 1.0) return 'bullish';
        if ($changePercent < -1.0) return 'bearish';
        
        return 'neutral';
    }

    /**
     * Get recommended strategy based on market regime
     */
    protected function getRecommendedStrategy(string $marketRegime): string
    {
        switch ($marketRegime) {
            case 'bullish': return 'aggressive';
            case 'bearish': return 'defensive';
            case 'volatile': return 'conservative';
            default: return 'moderate';
        }
    }

    /**
     * Get risk multiplier based on market conditions
     */
    protected function getRiskMultiplier(string $marketRegime, string $volatility): float
    {
        $baseMultiplier = 1.0;
        
        switch ($marketRegime) {
            case 'bullish': $baseMultiplier = 1.2; break;
            case 'bearish': $baseMultiplier = 0.6; break;
            case 'volatile': $baseMultiplier = 0.4; break;
        }
        
        switch ($volatility) {
            case 'high': $baseMultiplier *= 0.7; break;
            case 'low': $baseMultiplier *= 1.1; break;
        }
        
        return max(0.2, min(1.5, $baseMultiplier));
    }

    /**
     * Get historical data for a symbol
     */
    protected function getHistoricalData(string $symbol, int $days): array
    {
        try {
            // This would fetch actual historical data
            // For now, return empty array
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get stock quote
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
            
            if (isset($quote['stat']) && $quote['stat'] === 'Ok') {
                $ltp = (float) ($quote['lp'] ?? 0);
                $previousClose = (float) ($quote['c'] ?? 0);
                $changePercent = 0;
                
                if ($previousClose > 0) {
                    $changePercent = (($ltp - $previousClose) / $previousClose) * 100;
                }
                
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
                    'upper_circuit' => (float) ($quote['uc'] ?? 0),
                    'lower_circuit' => (float) ($quote['lc'] ?? 0),
                    'token' => $quote['token'] ?? $token,
                    'raw_data' => $quote
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get Nifty 50 symbols
     */
    protected function getNifty50Symbols(): array
    {
        return Cache::remember('nifty50_symbols', 3600, function() {
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
}
