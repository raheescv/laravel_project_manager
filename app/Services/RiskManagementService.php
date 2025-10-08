<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RiskManagementService
{
    protected array $riskLimits;
    protected array $userRiskProfiles;

    public function __construct()
    {
        $this->riskLimits = [
            'max_daily_investment' => 100000, // ₹1 lakh
            'max_position_size' => 50000, // ₹50k per position
            'max_stocks_per_day' => 10,
            'max_loss_per_trade' => 5.0, // 5%
            'max_portfolio_loss' => 10.0, // 10%
            'min_volume_threshold' => 100000,
            'max_price_range' => 10000, // ₹10k max price
            'min_price_range' => 50, // ₹50 min price
        ];

        $this->userRiskProfiles = [
            'conservative' => [
                'max_daily_investment' => 50000,
                'max_position_size' => 25000,
                'max_stocks_per_day' => 5,
                'max_loss_per_trade' => 3.0,
            ],
            'moderate' => [
                'max_daily_investment' => 100000,
                'max_position_size' => 50000,
                'max_stocks_per_day' => 10,
                'max_loss_per_trade' => 5.0,
            ],
            'aggressive' => [
                'max_daily_investment' => 200000,
                'max_position_size' => 100000,
                'max_stocks_per_day' => 15,
                'max_loss_per_trade' => 8.0,
            ]
        ];
    }

    /**
     * Validate trading request against risk parameters
     */
    public function validateTradingRequest(array $requestData): array
    {
        try {
            $userId = Auth::id();
            $userProfile = $this->getUserRiskProfile($userId);
            
            // Check if market is open
            if (!$this->isMarketOpen()) {
                return [
                    'approved' => false,
                    'reason' => 'Market is currently closed'
                ];
            }

            // Check daily trading limits
            $dailyCheck = $this->checkDailyTradingLimits($userId, $requestData, $userProfile);
            if (!$dailyCheck['approved']) {
                return $dailyCheck;
            }

            // Check individual stock risks
            foreach ($requestData['stocks'] as $stock) {
                $stockRisk = $this->validateStockRisk($stock, $userProfile);
                if (!$stockRisk['approved']) {
                    return $stockRisk;
                }
            }

            // Check portfolio risk
            $portfolioRisk = $this->checkPortfolioRisk($userId, $requestData, $userProfile);
            if (!$portfolioRisk['approved']) {
                return $portfolioRisk;
            }

            return [
                'approved' => true,
                'reason' => 'All risk checks passed',
                'risk_profile' => $userProfile['type']
            ];

        } catch (\Exception $e) {
            Log::error('Risk validation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $requestData
            ]);

            return [
                'approved' => false,
                'reason' => 'Risk validation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's risk profile
     */
    protected function getUserRiskProfile(int $userId): array
    {
        $profileType = Cache::get("user_risk_profile_{$userId}", 'moderate');
        
        $profile = $this->userRiskProfiles[$profileType] ?? $this->userRiskProfiles['moderate'];
        $profile['type'] = $profileType;
        
        return $profile;
    }

    /**
     * Check if market is open
     */
    protected function isMarketOpen(): bool
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday
        
        // Market is closed on weekends
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return false;
        }

        $currentTime = $now->format('H:i');
        
        // Market hours: 9:15 AM to 3:30 PM IST
        $marketOpen = '09:15';
        $marketClose = '15:30';
        
        return $currentTime >= $marketOpen && $currentTime <= $marketClose;
    }

    /**
     * Check daily trading limits
     */
    protected function checkDailyTradingLimits(int $userId, array $requestData, array $userProfile): array
    {
        $today = now()->format('Y-m-d');
        $dailyKey = "daily_trading_{$userId}_{$today}";
        
        $dailyData = Cache::get($dailyKey, [
            'total_investment' => 0,
            'stocks_traded' => 0,
            'trades_count' => 0
        ]);

        // Calculate total investment for this request
        $requestInvestment = 0;
        foreach ($requestData['stocks'] as $stock) {
            // Estimate investment (this would need actual price lookup)
            $requestInvestment += $stock['quantity'] * 1000; // Rough estimate
        }

        // Check daily investment limit
        if ($dailyData['total_investment'] + $requestInvestment > $userProfile['max_daily_investment']) {
            return [
                'approved' => false,
                'reason' => "Daily investment limit exceeded. Max: ₹{$userProfile['max_daily_investment']}"
            ];
        }

        // Check daily stocks limit
        if ($dailyData['stocks_traded'] + count($requestData['stocks']) > $userProfile['max_stocks_per_day']) {
            return [
                'approved' => false,
                'reason' => "Daily stocks limit exceeded. Max: {$userProfile['max_stocks_per_day']}"
            ];
        }

        return ['approved' => true];
    }

    /**
     * Validate individual stock risk
     */
    protected function validateStockRisk(array $stock, array $userProfile): array
    {
        $symbol = $stock['symbol'];
        $quantity = $stock['quantity'];

        // Check quantity limits
        if ($quantity > 1000) {
            return [
                'approved' => false,
                'reason' => "Quantity too high for {$symbol}. Max: 1000"
            ];
        }

        // Check if stock is in restricted list
        if ($this->isStockRestricted($symbol)) {
            return [
                'approved' => false,
                'reason' => "Stock {$symbol} is restricted for trading"
            ];
        }

        // Check position size (would need actual price)
        $estimatedValue = $quantity * 1000; // Rough estimate
        if ($estimatedValue > $userProfile['max_position_size']) {
            return [
                'approved' => false,
                'reason' => "Position size too large for {$symbol}. Max: ₹{$userProfile['max_position_size']}"
            ];
        }

        return ['approved' => true];
    }

    /**
     * Check portfolio risk
     */
    protected function checkPortfolioRisk(int $userId, array $requestData, array $userProfile): array
    {
        // This would integrate with actual portfolio data
        // For now, we'll do basic checks
        
        $totalRequestValue = 0;
        foreach ($requestData['stocks'] as $stock) {
            $totalRequestValue += $stock['quantity'] * 1000; // Rough estimate
        }

        // Check if this would exceed portfolio limits
        if ($totalRequestValue > $userProfile['max_daily_investment'] * 0.5) {
            return [
                'approved' => false,
                'reason' => "Request would exceed portfolio risk limits"
            ];
        }

        return ['approved' => true];
    }

    /**
     * Check if stock is restricted
     */
    protected function isStockRestricted(string $symbol): bool
    {
        $restrictedStocks = [
            'PENNY', 'MICRO', 'SME' // Example restricted categories
        ];

        // Check against restricted list
        foreach ($restrictedStocks as $restricted) {
            if (strpos($symbol, $restricted) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update user's risk profile
     */
    public function updateUserRiskProfile(int $userId, string $profileType): bool
    {
        if (!isset($this->userRiskProfiles[$profileType])) {
            return false;
        }

        Cache::put("user_risk_profile_{$userId}", $profileType, 86400); // 24 hours
        
        Log::info('User risk profile updated', [
            'user_id' => $userId,
            'profile_type' => $profileType
        ]);

        return true;
    }

    /**
     * Get risk metrics for user
     */
    public function getUserRiskMetrics(int $userId): array
    {
        $profile = $this->getUserRiskProfile($userId);
        $today = now()->format('Y-m-d');
        $dailyKey = "daily_trading_{$userId}_{$today}";
        
        $dailyData = Cache::get($dailyKey, [
            'total_investment' => 0,
            'stocks_traded' => 0,
            'trades_count' => 0
        ]);

        return [
            'risk_profile' => $profile['type'],
            'daily_limits' => [
                'max_investment' => $profile['max_daily_investment'],
                'used_investment' => $dailyData['total_investment'],
                'remaining_investment' => $profile['max_daily_investment'] - $dailyData['total_investment'],
                'max_stocks' => $profile['max_stocks_per_day'],
                'used_stocks' => $dailyData['stocks_traded'],
                'remaining_stocks' => $profile['max_stocks_per_day'] - $dailyData['stocks_traded']
            ],
            'position_limits' => [
                'max_position_size' => $profile['max_position_size'],
                'max_loss_per_trade' => $profile['max_loss_per_trade']
            ]
        ];
    }

    /**
     * Record successful trade for risk tracking
     */
    public function recordTrade(int $userId, array $tradeData): void
    {
        $today = now()->format('Y-m-d');
        $dailyKey = "daily_trading_{$userId}_{$today}";
        
        $dailyData = Cache::get($dailyKey, [
            'total_investment' => 0,
            'stocks_traded' => 0,
            'trades_count' => 0
        ]);

        $dailyData['total_investment'] += $tradeData['investment_amount'];
        $dailyData['stocks_traded'] += 1;
        $dailyData['trades_count'] += 1;

        Cache::put($dailyKey, $dailyData, 86400); // 24 hours

        Log::info('Trade recorded for risk tracking', [
            'user_id' => $userId,
            'trade_data' => $tradeData,
            'daily_data' => $dailyData
        ]);
    }

    /**
     * Get market risk indicators
     */
    public function getMarketRiskIndicators(): array
    {
        return [
            'market_status' => $this->isMarketOpen() ? 'open' : 'closed',
            'volatility_level' => $this->getVolatilityLevel(),
            'recommended_max_position' => $this->getRecommendedMaxPosition(),
            'risk_warnings' => $this->getRiskWarnings()
        ];
    }

    /**
     * Get current volatility level
     */
    protected function getVolatilityLevel(): string
    {
        // This would integrate with actual market data
        // For now, return a default value
        return 'moderate';
    }

    /**
     * Get recommended max position size
     */
    protected function getRecommendedMaxPosition(): int
    {
        $volatilityLevel = $this->getVolatilityLevel();
        
        switch ($volatilityLevel) {
            case 'low':
                return 100000;
            case 'moderate':
                return 50000;
            case 'high':
                return 25000;
            default:
                return 50000;
        }
    }

    /**
     * Get current risk warnings
     */
    protected function getRiskWarnings(): array
    {
        $warnings = [];

        if (!$this->isMarketOpen()) {
            $warnings[] = 'Market is currently closed';
        }

        $volatilityLevel = $this->getVolatilityLevel();
        if ($volatilityLevel === 'high') {
            $warnings[] = 'High market volatility detected';
        }

        return $warnings;
    }
}
