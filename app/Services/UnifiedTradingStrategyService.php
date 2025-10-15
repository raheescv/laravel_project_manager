<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UnifiedTradingStrategyService
{
    protected FlatTradeService $flatTradeService;

    protected TradingStrategyService $strategyService;

    protected PerformanceTrackingService $performanceService;

    public function __construct(
        FlatTradeService $flatTradeService,
        TradingStrategyService $strategyService,
        PerformanceTrackingService $performanceService
    ) {
        $this->flatTradeService = $flatTradeService;
        $this->strategyService = $strategyService;
        $this->performanceService = $performanceService;
    }

    /**
     * Enhanced stock selection with multi-factor analysis for all symbols
     */
    public function selectOptimalStocksForPurchase(int $maxStocks = 5, array $options = []): array
    {
        try {
            $quantity = $options['quantity'] ?? 10;
            $minProfit = $options['min_profit'] ?? 2.0;
            $maxLoss = $options['max_loss'] ?? 3.0;
            $maxInvestment = $options['max_investment'] ?? 0;
            $marginSafety = $options['margin_safety'] ?? 0.1;
            $symbolFilter = $options['symbol_filter'] ?? 'all'; // 'all', 'nifty50', 'custom'
            $customSymbols = $options['custom_symbols'] ?? [];

            // Get available funds
            $availableFunds = $this->getAvailableFunds();
            if ($maxInvestment > 0 && $maxInvestment < $availableFunds) {
                $availableFunds = $maxInvestment;
            }
            $safeFunds = $availableFunds * (1 - $marginSafety);

            // Get market conditions
            $marketConditions = $this->analyzeMarketConditions();

            // Get stock candidates based on filter
            $stockCandidates = $this->getStockCandidates($symbolFilter, $customSymbols, $maxStocks * 3);
            if (empty($stockCandidates)) {
                return [];
            }

            $scoredStocks = [];

            foreach ($stockCandidates as $stock) {
                if (count($scoredStocks) >= $maxStocks * 2) {
                    break;
                } // Get more candidates for scoring

                $symbol = $stock['tsym'] ?? $stock['symbol'] ?? '';

                if ($symbol) {
                    $score = $this->calculateStockScore($symbol, $marketConditions, $options);
                    if ($score['total_score'] > 0) {
                        $scoredStocks[] = array_merge(['symbol' => $symbol], $score);
                    }
                }
            }

            // Sort by score and select best ones
            usort($scoredStocks, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);
            $selectedStocks = array_slice($scoredStocks, 0, $maxStocks);

            // Calculate position sizes and validate funds
            $finalStocks = [];
            $usedFunds = 0;

            foreach ($selectedStocks as $stock) {
                $positionSize = $this->calculateOptimalPositionSize(
                    $stock['symbol'],
                    $quantity,
                    $stock['quote']['ltp'],
                    $safeFunds - $usedFunds,
                    $marketConditions
                );

                if ($positionSize['quantity'] > 0) {
                    $finalStocks[] = array_merge($stock, [
                        'position_size' => $positionSize,
                        'entry_price' => $stock['quote']['ltp'],
                        'stop_loss' => $stock['quote']['ltp'] * (1 - $maxLoss / 100),
                        'target_price' => $stock['quote']['ltp'] * (1 + $minProfit / 100),
                    ]);
                    $usedFunds += $positionSize['total_value'];
                }
            }

            return $finalStocks;
        } catch (\Exception $e) {
            Log::error('Stock selection failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Enhanced position analysis for selling
     */
    public function analyzePositionsForSelling(array $options = []): array
    {
        try {
            $profitThreshold = $options['profit_threshold'] ?? 5.0;
            $lossThreshold = $options['loss_threshold'] ?? 3.0;
            $symbol = $options['symbol'] ?? null;
            $sellAll = $options['sell_all'] ?? false;

            $positions = $this->getCurrentPositions();
            $sellCandidates = [];

            foreach ($positions as $position) {
                if ($symbol && $position['symbol'] !== $symbol) {
                    continue;
                }

                $sellDecision = $this->analyzeSellDecision($position, $options);

                if ($sellDecision['should_sell'] || $sellAll) {
                    $sellCandidates[] = array_merge($position, [
                        'sell_reason' => $sellDecision['reason'],
                        'confidence' => $sellDecision['confidence'],
                        'priority' => $sellDecision['priority'],
                    ]);
                }
            }

            // Sort by priority (high priority first)
            usort($sellCandidates, fn ($a, $b) => $b['priority'] <=> $a['priority']);

            return $sellCandidates;
        } catch (\Exception $e) {
            Log::error('Position analysis failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Calculate comprehensive stock score
     */
    protected function calculateStockScore(string $symbol, array $marketConditions, array $options): array
    {
        try {
            $quote = $this->getStockQuote($symbol);
            if (! $quote) {
                return ['total_score' => 0];
            }

            // Technical analysis score
            $technicalScore = $this->calculateTechnicalScore($symbol);

            // Fundamental score
            $fundamentalScore = $this->calculateFundamentalScore($quote);

            // Momentum score
            $momentumScore = $this->calculateMomentumScore($quote);

            // Risk score
            $riskScore = $this->calculateRiskScore($quote);

            // Market condition adjustment
            $marketAdjustment = $this->getMarketConditionAdjustment($marketConditions, $quote);

            // Volume and liquidity score
            $liquidityScore = $this->calculateLiquidityScore($quote);

            // Price action score
            $priceActionScore = $this->calculatePriceActionScore($quote);

            // Weighted total score
            $totalScore = (
                $technicalScore * 0.25 +
                $fundamentalScore * 0.20 +
                $momentumScore * 0.20 +
                $riskScore * 0.15 +
                $liquidityScore * 0.10 +
                $priceActionScore * 0.05 +
                $marketAdjustment * 0.05
            );

            return [
                'total_score' => round($totalScore, 2),
                'technical_score' => $technicalScore,
                'fundamental_score' => $fundamentalScore,
                'momentum_score' => $momentumScore,
                'risk_score' => $riskScore,
                'liquidity_score' => $liquidityScore,
                'price_action_score' => $priceActionScore,
                'market_adjustment' => $marketAdjustment,
                'quote' => $quote,
            ];
        } catch (\Exception $e) {
            Log::error("Stock score calculation failed for {$symbol}", ['error' => $e->getMessage()]);

            return ['total_score' => 0];
        }
    }

    /**
     * Analyze sell decision for a position
     */
    protected function analyzeSellDecision(array $position, array $options): array
    {
        $symbol = $position['symbol'];
        $pnlPercent = $position['pnl_percent'];
        $profitThreshold = $options['profit_threshold'] ?? 5.0;
        $lossThreshold = $options['loss_threshold'] ?? 3.0;

        $reasons = [];
        $confidence = 0;
        $priority = 0;

        // Technical analysis
        $technicalSignal = $this->getTechnicalExitSignal($symbol);
        if ($technicalSignal['signal'] === 'sell') {
            $reasons[] = 'Technical sell signal';
            $confidence += 0.3;
            $priority += 30;
        }

        // Profit/Loss analysis
        if ($pnlPercent >= $profitThreshold) {
            $reasons[] = "Profit target reached ({$pnlPercent}%)";
            $confidence += 0.4;
            $priority += 40;
        }

        if ($pnlPercent <= -$lossThreshold) {
            $reasons[] = "Stop loss triggered ({$pnlPercent}%)";
            $confidence += 0.5;
            $priority += 50;
        }

        // Time-based analysis
        $holdingDays = $position['entry_time']->diffInDays(now());
        if ($holdingDays > 5 && $pnlPercent < 0) {
            $reasons[] = 'Long holding period with loss';
            $confidence += 0.2;
            $priority += 20;
        }

        // Market condition analysis
        $marketConditions = $this->analyzeMarketConditions();
        if ($marketConditions['market_regime'] === 'bearish' && $pnlPercent < 0) {
            $reasons[] = 'Bearish market with loss';
            $confidence += 0.3;
            $priority += 30;
        }

        // Volume analysis
        $currentQuote = $this->getStockQuote($symbol);
        if ($currentQuote && $this->isVolumeDeclining($currentQuote)) {
            $reasons[] = 'Declining volume';
            $confidence += 0.1;
            $priority += 10;
        }

        $shouldSell = $confidence >= 0.4 || $priority >= 40;

        return [
            'should_sell' => $shouldSell,
            'reason' => implode(', ', $reasons),
            'confidence' => min(1.0, $confidence),
            'priority' => $priority,
        ];
    }

    /**
     * Calculate technical analysis score
     */
    protected function calculateTechnicalScore(string $symbol): float
    {
        try {
            // Get historical data for technical analysis
            $historicalData = $this->getHistoricalData($symbol, 20);
            if (empty($historicalData)) {
                return 50;
            }

            $prices = array_column($historicalData, 'close');
            $signal = $this->strategyService->generateTradeSignal($prices);

            // Convert signal to score
            switch ($signal[0]) {
                case 'BUY': return 85;
                case 'SELL': return 15;
                case 'HOLD': return 50;
                default: return 50;
            }
        } catch (\Exception $e) {
            return 50;
        }
    }

    /**
     * Calculate fundamental score
     */
    protected function calculateFundamentalScore(array $quote): float
    {
        $score = 50;

        $ltp = $quote['ltp'] ?? 0;
        $volume = $quote['volume'] ?? 0;
        $upperCircuit = $quote['upper_circuit'] ?? 0;
        $lowerCircuit = $quote['lower_circuit'] ?? 0;

        // Price range check
        if ($ltp >= 100 && $ltp <= 5000) {
            $score += 15;
        } elseif ($ltp >= 50 && $ltp <= 10000) {
            $score += 10;
        }

        // Volume check
        if ($volume > 1000000) {
            $score += 20;
        } elseif ($volume > 500000) {
            $score += 15;
        } elseif ($volume > 100000) {
            $score += 10;
        }

        // Circuit limit check
        if ($upperCircuit > 0 && $lowerCircuit > 0) {
            $circuitRange = ($upperCircuit - $lowerCircuit) / $ltp;
            if ($circuitRange > 0.1) {
                $score += 15;
            }
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
        if ($changePercent >= 1.0 && $changePercent <= 5.0) {
            return 90;
        }
        if ($changePercent >= 0.5 && $changePercent <= 8.0) {
            return 75;
        }
        if ($changePercent >= 0.0 && $changePercent <= 10.0) {
            return 60;
        }
        if ($changePercent < 0) {
            return max(0, 50 + $changePercent * 5);
        }

        return 30; // Too high momentum might be risky
    }

    /**
     * Calculate risk score
     */
    protected function calculateRiskScore(array $quote): float
    {
        $score = 50;

        $ltp = $quote['ltp'] ?? 0;
        $high = $quote['high'] ?? 0;
        $low = $quote['low'] ?? 0;
        $volume = $quote['volume'] ?? 0;

        // Price stability
        if ($high > 0 && $low > 0) {
            $dailyRange = ($high - $low) / $ltp;
            if ($dailyRange < 0.05) {
                $score += 25;
            } elseif ($dailyRange < 0.1) {
                $score += 15;
            } elseif ($dailyRange > 0.2) {
                $score -= 25;
            }
        }

        // Volume consistency
        if ($volume > 500000) {
            $score += 20;
        } elseif ($volume < 100000) {
            $score -= 30;
        }

        return min(100, max(0, $score));
    }

    /**
     * Calculate liquidity score
     */
    protected function calculateLiquidityScore(array $quote): float
    {
        $score = 50;
        $volume = $quote['volume'] ?? 0;
        $ltp = $quote['ltp'] ?? 0;

        // Volume-based liquidity
        if ($volume > 2000000) {
            $score += 30;
        } elseif ($volume > 1000000) {
            $score += 20;
        } elseif ($volume > 500000) {
            $score += 10;
        } elseif ($volume < 100000) {
            $score -= 20;
        }

        // Price-based liquidity (higher price = better liquidity)
        if ($ltp > 1000) {
            $score += 10;
        } elseif ($ltp > 500) {
            $score += 5;
        } elseif ($ltp < 50) {
            $score -= 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * Calculate price action score
     */
    protected function calculatePriceActionScore(array $quote): float
    {
        $score = 50;
        $changePercent = $quote['change_percent'] ?? 0;
        $high = $quote['high'] ?? 0;
        $low = $quote['low'] ?? 0;
        $ltp = $quote['ltp'] ?? 0;

        // Price position within daily range
        if ($high > 0 && $low > 0 && $ltp > 0) {
            $positionInRange = ($ltp - $low) / ($high - $low);
            if ($positionInRange > 0.8) {
                $score += 20;
            } // Near high
            elseif ($positionInRange < 0.2) {
                $score -= 10;
            } // Near low
        }

        // Change percentage
        if ($changePercent > 0) {
            $score += 10;
        } else {
            $score -= 5;
        }

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

        switch ($marketRegime) {
            case 'bullish':
                if (($quote['change_percent'] ?? 0) > 0) {
                    $adjustment += 15;
                }
                break;
            case 'bearish':
                if (($quote['change_percent'] ?? 0) < 0) {
                    $adjustment += 10;
                } else {
                    $adjustment -= 15;
                }
                break;
            case 'volatile':
                $adjustment -= 20;
                break;
        }

        if ($volatilityLevel === 'high') {
            $adjustment -= 15;
        } elseif ($volatilityLevel === 'low') {
            $adjustment += 10;
        }

        return $adjustment;
    }

    /**
     * Calculate optimal position size
     */
    protected function calculateOptimalPositionSize(string $symbol, int $quantity, float $price, float $availableFunds, array $marketConditions): array
    {
        $riskMultiplier = $marketConditions['risk_multiplier'] ?? 0.5;
        $maxPositionValue = $availableFunds * 0.2 * $riskMultiplier;

        $calculatedQuantity = min($quantity, floor($maxPositionValue / $price));
        $calculatedQuantity = max(1, $calculatedQuantity);

        $totalValue = $calculatedQuantity * $price;

        return [
            'quantity' => $calculatedQuantity,
            'total_value' => $totalValue,
            'price' => $price,
            'risk_multiplier' => $riskMultiplier,
        ];
    }

    /**
     * Get technical exit signal
     */
    protected function getTechnicalExitSignal(string $symbol): array
    {
        try {
            $historicalData = $this->getHistoricalData($symbol, 20);
            if (empty($historicalData)) {
                return ['signal' => 'hold', 'confidence' => 0];
            }

            $prices = array_column($historicalData, 'close');
            $signal = $this->strategyService->generateTradeSignal($prices);

            return [
                'signal' => $signal[0] === 'SELL' ? 'sell' : 'hold',
                'confidence' => $signal[0] === 'SELL' ? 0.8 : 0.2,
            ];
        } catch (\Exception $e) {
            return ['signal' => 'hold', 'confidence' => 0];
        }
    }

    /**
     * Check if volume is declining
     */
    protected function isVolumeDeclining(array $quote): bool
    {
        // This would compare current volume with historical average
        // For now, return false as we don't have historical volume data
        return false;
    }

    /**
     * Analyze market conditions
     */
    protected function analyzeMarketConditions(): array
    {
        try {
            // Get Nifty 50 index data for market sentiment
            $niftyData = $this->getNiftyIndexData();

            // Analyze market volatility
            $volatility = $this->calculateMarketVolatility();

            // Determine market regime
            $marketRegime = $this->determineMarketRegime($niftyData, $volatility);

            return [
                'market_regime' => $marketRegime,
                'volatility_level' => $volatility,
                'nifty_trend' => $niftyData['trend'] ?? 'neutral',
                'risk_multiplier' => $this->getRiskMultiplier($marketRegime, $volatility),
            ];
        } catch (\Exception $e) {
            return [
                'market_regime' => 'neutral',
                'volatility_level' => 'moderate',
                'risk_multiplier' => 0.5,
            ];
        }
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

            if (is_array($positions) && ! empty($positions)) {
                if (isset($positions[0]['stat']) && $positions[0]['stat'] === 'Ok') {
                    return $this->formatPositionsForAnalysis($positions);
                }
            }

            if (isset($positions['stat']) && $positions['stat'] === 'Ok' && isset($positions['netqty'])) {
                return $this->formatPositionsForAnalysis([$positions]);
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format positions for analysis
     */
    protected function formatPositionsForAnalysis(array $positions): array
    {
        $formattedPositions = [];

        foreach ($positions as $position) {
            $symbol = $position['tsym'] ?? null;
            $quantity = (int) ($position['netqty'] ?? 0);
            $avgPrice = (float) ($position['netavgprc'] ?? 0);
            $currentPrice = (float) ($position['lp'] ?? 0);

            if (! $symbol || $quantity <= 0 || $avgPrice <= 0 || $currentPrice <= 0) {
                continue;
            }

            $pnl = ($currentPrice - $avgPrice) * $quantity;
            $pnlPercent = $avgPrice > 0 ? (($currentPrice - $avgPrice) / $avgPrice) * 100 : 0;

            $formattedPositions[] = [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'avg_price' => $avgPrice,
                'current_price' => $currentPrice,
                'pnl' => $pnl,
                'pnl_percent' => $pnlPercent,
                'entry_time' => Carbon::parse($position['entry_time'] ?? now()->subDays(1)),
                'raw_data' => $position,
            ];
        }

        return $formattedPositions;
    }

    /**
     * Get stock quote
     */
    protected function getStockQuote(string $symbol): ?array
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
                    'raw_data' => $quote,
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
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
     * Get stock candidates based on filter
     */
    protected function getStockCandidates(string $symbolFilter, array $customSymbols, int $maxCandidates): array
    {
        try {
            switch ($symbolFilter) {
                case 'nifty50':
                    return $this->getNifty50Candidates($maxCandidates);

                case 'custom':
                    return $this->getCustomSymbolCandidates($customSymbols, $maxCandidates);

                case 'all':
                default:
                    return $this->getAllSymbolCandidates($maxCandidates);
            }
        } catch (\Exception $e) {
            Log::error('Failed to get stock candidates', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get Nifty 50 candidates
     */
    protected function getNifty50Candidates(int $maxCandidates): array
    {
        try {
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            if (! isset($topGainers['values']) || empty($topGainers['values'])) {
                return [];
            }

            $nifty50Stocks = $this->getNifty50Symbols();
            $candidates = [];

            foreach ($topGainers['values'] as $stock) {
                if (count($candidates) >= $maxCandidates) {
                    break;
                }

                $symbol = $stock['tsym'] ?? '';
                if (in_array($symbol, $nifty50Stocks)) {
                    $candidates[] = $stock;
                }
            }

            return $candidates;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get custom symbol candidates
     */
    protected function getCustomSymbolCandidates(array $customSymbols, int $maxCandidates): array
    {
        $candidates = [];

        foreach ($customSymbols as $symbol) {
            if (count($candidates) >= $maxCandidates) {
                break;
            }

            $quote = $this->getStockQuote($symbol);
            if ($quote) {
                $candidates[] = [
                    'tsym' => $symbol,
                    'symbol' => $symbol,
                    'quote' => $quote,
                ];
            }
        }

        return $candidates;
    }

    /**
     * Get all symbol candidates (top gainers from all stocks)
     */
    protected function getAllSymbolCandidates(int $maxCandidates): array
    {
        try {
            // Get top gainers from all stocks
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            if (! isset($topGainers['values']) || empty($topGainers['values'])) {
                return [];
            }

            $candidates = [];
            foreach ($topGainers['values'] as $stock) {
                if (count($candidates) >= $maxCandidates) {
                    break;
                }

                $symbol = $stock['tsym'] ?? '';
                if ($symbol && $this->isValidStockSymbol($symbol)) {
                    $candidates[] = $stock;
                }
            }

            return $candidates;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if stock symbol is valid for trading
     */
    protected function isValidStockSymbol(string $symbol): bool
    {
        // Filter out invalid symbols
        $invalidPatterns = [
            '/^[0-9]+$/',  // Pure numbers
            '/^[A-Z]{1,2}$/',  // Single or double letters
            '/^[A-Z]+[0-9]+$/',  // Letters followed by numbers only
            '/^[0-9]+[A-Z]+$/',  // Numbers followed by letters only
        ];

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $symbol)) {
                return false;
            }
        }

        // Must be at least 3 characters
        if (strlen($symbol) < 3) {
            return false;
        }

        // Must contain at least one letter
        if (! preg_match('/[A-Z]/', $symbol)) {
            return false;
        }

        return true;
    }

    /**
     * Get Nifty 50 symbols
     */
    protected function getNifty50Symbols(): array
    {
        return Cache::remember('nifty50_symbols', 3600, function () {
            return [
                'RELIANCE', 'TCS', 'HDFCBANK', 'INFY', 'HINDUNILVR', 'ITC', 'SBIN', 'BHARTIARTL',
                'KOTAKBANK', 'LT', 'ASIANPAINT', 'AXISBANK', 'MARUTI', 'SUNPHARMA', 'TITAN', 'ULTRACEMCO',
                'WIPRO', 'NESTLEIND', 'ONGC', 'POWERGRID', 'NTPC', 'TECHM', 'TATAMOTORS', 'BAJFINANCE',
                'HCLTECH', 'BAJAJFINSV', 'DRREDDY', 'JSWSTEEL', 'TATASTEEL', 'COALINDIA', 'GRASIM', 'BRITANNIA',
                'EICHERMOT', 'HEROMOTOCO', 'DIVISLAB', 'CIPLA', 'APOLLOHOSP', 'ADANIPORTS', 'INDUSINDBK', 'TATACONSUM',
                'BPCL', 'ICICIBANK', 'ADANIENT', 'HDFCLIFE', 'SBILIFE', 'BAJAJ-AUTO', 'UPL', 'SHREECEM',
            ];
        });
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
                'change_percent' => 0.5,
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
     * Determine market regime
     */
    protected function determineMarketRegime(array $niftyData, string $volatility): string
    {
        $changePercent = $niftyData['change_percent'] ?? 0;

        if ($volatility === 'high') {
            return 'volatile';
        }
        if ($changePercent > 1.0) {
            return 'bullish';
        }
        if ($changePercent < -1.0) {
            return 'bearish';
        }

        return 'neutral';
    }

    /**
     * Get risk multiplier based on market conditions
     */
    protected function getRiskMultiplier(string $marketRegime, string $volatility): float
    {
        $baseMultiplier = 1.0;

        switch ($marketRegime) {
            case 'bullish': $baseMultiplier = 1.2;
                break;
            case 'bearish': $baseMultiplier = 0.6;
                break;
            case 'volatile': $baseMultiplier = 0.4;
                break;
        }

        switch ($volatility) {
            case 'high': $baseMultiplier *= 0.7;
                break;
            case 'low': $baseMultiplier *= 1.1;
                break;
        }

        return max(0.2, min(1.5, $baseMultiplier));
    }
}
