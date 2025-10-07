<?php

namespace App\Console\Commands;

use App\Services\FlatTradeService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProfitBasedTradingCommand extends Command
{
    protected $signature = 'trade:profit-based 
                            {symbol : Trading symbol (e.g., INFY-EQ)}
                            {--quantity=10 : Quantity to trade}
                            {--exchange=NSE : Exchange (NSE/BSE)}
                            {--min-profit-percent=5 : Minimum profit percentage to trigger buy}
                            {--max-loss-percent=3 : Maximum loss percentage to trigger sell}
                            {--lookback-days=30 : Number of days to analyze for history}
                            {--dry-run : Run without placing actual orders}';

    protected $description = 'Fetch item history and make buy/sell decisions based on profit analysis';

    protected $flatTradeService;

    public function __construct(FlatTradeService $flatTradeService)
    {
        parent::__construct();
        $this->flatTradeService = $flatTradeService;
    }

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $quantity = $this->option('quantity');
        $exchange = $this->option('exchange');
        $minProfitPercent = $this->option('min-profit-percent');
        $maxLossPercent = $this->option('max-loss-percent');
        $lookbackDays = $this->option('lookback-days');
        $dryRun = $this->option('dry-run');

        $this->info("Starting profit-based trading analysis for {$symbol}");
        $this->info("Exchange: {$exchange}, Quantity: {$quantity}");
        $this->info("Min Profit: {$minProfitPercent}%, Max Loss: {$maxLossPercent}%");
        $this->info("Lookback Period: {$lookbackDays} days");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual orders will be placed");
        }

        try {
            // Step 1: Fetch historical data
            $this->info("Fetching historical data...");
            $historicalData = $this->fetchHistoricalData($symbol, $exchange, $lookbackDays);
            
            if (empty($historicalData)) {
                $this->error("No historical data found for {$symbol}");
                return 1;
            }

            // Step 2: Analyze profit potential
            $this->info("Analyzing profit potential...");
            $analysis = $this->analyzeProfitPotential($historicalData, $minProfitPercent, $maxLossPercent);
            // Step 3: Get current market data
            $this->info("Fetching current market data...");
            $currentPrice = $this->getCurrentPrice($symbol, $exchange);
            if (!$currentPrice) {
                $this->error("Could not fetch current price for {$symbol}");
                return 1;
            }

            // Step 4: Make trading decision
            $this->info("Making trading decision...");
            $decision = $this->makeTradingDecision($analysis, $currentPrice, $minProfitPercent, $maxLossPercent);
            
            // Step 5: Execute trade if decision is made
            if ($decision['action'] !== 'HOLD') {
                $this->executeTrade($symbol, $exchange, $quantity, $decision, $currentPrice, $dryRun);
            } else {
                $this->info("Decision: HOLD - No profitable opportunity found");
            }

            // Step 6: Log the analysis
            $this->logAnalysis($symbol, $analysis, $currentPrice, $decision);

            return 0;

        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("Profit-based trading error for {$symbol}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Fetch historical data for the symbol
     */
    protected function fetchHistoricalData(string $symbol, string $exchange, int $lookbackDays): array
    {
        try {
            // Calculate date range
            $fromDate = now()->subDays($lookbackDays)->format('Y-m-d');
            $fromDate = strtotime($fromDate);
            $toDate = now()->format('Y-m-d');
            $toDate = strtotime($toDate);
            
            $this->info("Fetching data from {$fromDate} to {$toDate}");
            
            // Use FlatTrade EOD chart data
            $response = $this->flatTradeService->getEODChartData($symbol, (int)$fromDate, (int)$toDate);
            
            $this->info("Raw response: " . json_encode($response));
            
            // Check if response is an array of JSON strings (current format)
            if (is_array($response) && !empty($response)) {
                $parsedData = [];
                foreach ($response as $jsonString) {
                    $data = json_decode($jsonString, true);
                    if ($data && isset($data['time'], $data['into'], $data['inth'], $data['intl'], $data['intc'], $data['intv'])) {
                        // Convert to standard OHLC format: [date, open, high, low, close, volume]
                        $parsedData[] = [
                            $data['time'],                    // date
                            (float)$data['into'],             // open
                            (float)$data['inth'],             // high
                            (float)$data['intl'],             // low
                            (float)$data['intc'],             // close
                            (float)$data['intv']              // volume
                        ];
                    }
                }
                if (!empty($parsedData)) {
                    $this->info("Successfully parsed " . count($parsedData) . " data points");
                    return $parsedData;
                }
            }
            $this->warn("No historical data found in FlatTrade response");
            return [];
            
        } catch (Exception $e) {
            $this->error("Error fetching historical data: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Analyze profit potential from historical data
     */
    protected function analyzeProfitPotential(array $historicalData, float $minProfitPercent, float $maxLossPercent): array
    {
        if (empty($historicalData)) {
            return [
                'avg_price' => 0,
                'max_price' => 0,
                'min_price' => 0,
                'volatility' => 0,
                'profit_potential' => 0,
                'risk_level' => 'HIGH',
                'trend' => 'UNKNOWN'
            ];
        }

        // Extract closing prices (assuming format: [date, open, high, low, close, volume])
        $closingPrices = array_map(function($candle) {
            return is_array($candle) ? (float)$candle[4] : (float)$candle;
        }, $historicalData);

        $avgPrice = array_sum($closingPrices) / count($closingPrices);
        $maxPrice = max($closingPrices);
        $minPrice = min($closingPrices);
        
        // Calculate volatility (standard deviation)
        $variance = array_sum(array_map(function($price) use ($avgPrice) {
            return pow($price - $avgPrice, 2);
        }, $closingPrices)) / count($closingPrices);
        $volatility = sqrt($variance);
        
        // Calculate profit potential
        $maxProfitPercent = (($maxPrice - $avgPrice) / $avgPrice) * 100;
        $maxLossPercent = (($avgPrice - $minPrice) / $avgPrice) * 100;
        
        // Determine trend
        $trend = $this->determineTrend($closingPrices);
        
        // Determine risk level
        $riskLevel = $this->determineRiskLevel($volatility, $avgPrice);
        
        return [
            'avg_price' => round($avgPrice, 2),
            'max_price' => round($maxPrice, 2),
            'min_price' => round($minPrice, 2),
            'volatility' => round($volatility, 2),
            'profit_potential' => round($maxProfitPercent, 2),
            'loss_potential' => round($maxLossPercent, 2),
            'risk_level' => $riskLevel,
            'trend' => $trend,
            'data_points' => count($closingPrices)
        ];
    }

    /**
     * Get current market price
     */
    protected function getCurrentPrice(string $symbol, string $exchange): ?float
    {
        try {
            // First try to get quotes
            $quotes = $this->flatTradeService->getQuotes($symbol, $exchange);
            $this->info("Quotes response: " . json_encode($quotes));
            if (isset($quotes['stat']) && $quotes['stat'] === 'Ok') {
                // Try different possible price keys
                if (isset($quotes['lp'])) {
                    return (float)$quotes['lp'];
                } elseif (isset($quotes['ltp'])) {
                    return (float)$quotes['ltp'];
                } elseif (isset($quotes['last_price'])) {
                    return (float)$quotes['last_price'];
                } elseif (isset($quotes['price'])) {
                    return (float)$quotes['price'];
                }
            }
            // Generate mock current price for testing
            $this->warn("No current price available");
            return 0;
            
        } catch (Exception $e) {
            $this->error("Error fetching current price: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Make trading decision based on analysis
     */
    protected function makeTradingDecision(array $analysis, float $currentPrice, float $minProfitPercent, float $maxLossPercent): array
    {
        $avgPrice = $analysis['avg_price'];
        $profitPotential = $analysis['profit_potential'];
        $lossPotential = $analysis['loss_potential'];
        $trend = $analysis['trend'];
        $riskLevel = $analysis['risk_level'];
        
        // Calculate potential profit/loss from current price
        $potentialProfitPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
        $potentialLossPercent = (($avgPrice - $currentPrice) / $avgPrice) * 100;
        
        $this->info("Analysis Results:");
        $this->info("- Average Price: ₹{$avgPrice}");
        $this->info("- Current Price: ₹{$currentPrice}");
        $this->info("- Potential Profit: {$potentialProfitPercent}%");
        $this->info("- Potential Loss: {$potentialLossPercent}%");
        $this->info("- Trend: {$trend}");
        $this->info("- Risk Level: {$riskLevel}");
        
        // Decision logic
        if ($potentialProfitPercent >= $minProfitPercent && $trend === 'UPWARD') {
            return [
                'action' => 'BUY',
                'reason' => "Price below average with upward trend. Potential profit: {$potentialProfitPercent}%",
                'confidence' => $this->calculateConfidence($analysis, $potentialProfitPercent)
            ];
        }
        
        if ($potentialLossPercent >= $maxLossPercent && $trend === 'DOWNWARD') {
            return [
                'action' => 'SELL',
                'reason' => "Price above average with downward trend. Potential loss: {$potentialLossPercent}%",
                'confidence' => $this->calculateConfidence($analysis, $potentialLossPercent)
            ];
        }
        
        if ($riskLevel === 'LOW' && $potentialProfitPercent >= ($minProfitPercent * 0.7)) {
            return [
                'action' => 'BUY',
                'reason' => "Low risk opportunity with decent profit potential: {$potentialProfitPercent}%",
                'confidence' => $this->calculateConfidence($analysis, $potentialProfitPercent)
            ];
        }
        
        return [
            'action' => 'HOLD',
            'reason' => "No clear profitable opportunity found",
            'confidence' => 0
        ];
    }

    /**
     * Execute the trading decision
     */
    protected function executeTrade(string $symbol, string $exchange, int $quantity, array $decision, float $currentPrice, bool $dryRun): void
    {
        $action = $decision['action'];
        $reason = $decision['reason'];
        $confidence = $decision['confidence'];
        
        $this->info("Trading Decision: {$action}");
        $this->info("Reason: {$reason}");
        $this->info("Confidence: {$confidence}%");
        
        if ($dryRun) {
            $this->warn("DRY RUN: Would place {$action} order for {$quantity} shares of {$symbol} at ₹{$currentPrice}");
            return;
        }
        
        try {
            if ($action === 'BUY') {
                $result = $this->flatTradeService->placeMarketOrder($exchange, $symbol, $quantity, 'B', 'C');
                $this->info("BUY order placed successfully: " . json_encode($result));
                
                // Log the trade
                Log::info("Profit-based BUY order executed", [
                    'symbol' => $symbol,
                    'exchange' => $exchange,
                    'quantity' => $quantity,
                    'price' => $currentPrice,
                    'reason' => $reason,
                    'confidence' => $confidence,
                    'order_result' => $result
                ]);
                
            } elseif ($action === 'SELL') {
                $result = $this->flatTradeService->placeMarketOrder($exchange, $symbol, $quantity, 'S', 'C');
                $this->info("SELL order placed successfully: " . json_encode($result));
                
                // Log the trade
                Log::info("Profit-based SELL order executed", [
                    'symbol' => $symbol,
                    'exchange' => $exchange,
                    'quantity' => $quantity,
                    'price' => $currentPrice,
                    'reason' => $reason,
                    'confidence' => $confidence,
                    'order_result' => $result
                ]);
            }
            
        } catch (Exception $e) {
            $this->error("Failed to place {$action} order: " . $e->getMessage());
            Log::error("Failed to execute profit-based trade", [
                'symbol' => $symbol,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine trend from price data
     */
    protected function determineTrend(array $prices): string
    {
        if (count($prices) < 2) {
            return 'UNKNOWN';
        }
        
        $firstHalf = array_slice($prices, 0, floor(count($prices) / 2));
        $secondHalf = array_slice($prices, floor(count($prices) / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $changePercent = (($secondAvg - $firstAvg) / $firstAvg) * 100;
        
        if ($changePercent > 2) {
            return 'UPWARD';
        } elseif ($changePercent < -2) {
            return 'DOWNWARD';
        } else {
            return 'SIDEWAYS';
        }
    }

    /**
     * Determine risk level based on volatility
     */
    protected function determineRiskLevel(float $volatility, float $avgPrice): string
    {
        $volatilityPercent = ($volatility / $avgPrice) * 100;
        
        if ($volatilityPercent < 2) {
            return 'LOW';
        } elseif ($volatilityPercent < 5) {
            return 'MEDIUM';
        } else {
            return 'HIGH';
        }
    }

    /**
     * Calculate confidence level for the decision
     */
    protected function calculateConfidence(array $analysis, float $potentialPercent): float
    {
        $baseConfidence = min(100, abs($potentialPercent) * 10); // Base on potential percentage
        
        // Adjust based on risk level
        $riskAdjustment = match($analysis['risk_level']) {
            'LOW' => 20,
            'MEDIUM' => 0,
            'HIGH' => -20,
            default => 0
        };
        
        // Adjust based on trend
        $trendAdjustment = match($analysis['trend']) {
            'UPWARD' => 15,
            'DOWNWARD' => -15,
            'SIDEWAYS' => 0,
            default => 0
        };
        
        // Adjust based on data points
        $dataAdjustment = min(10, $analysis['data_points'] / 5);
        
        return max(0, min(100, $baseConfidence + $riskAdjustment + $trendAdjustment + $dataAdjustment));
    }

    /**
     * Log the analysis results
     */
    protected function logAnalysis(string $symbol, array $analysis, float $currentPrice, array $decision): void
    {
        Log::info("Profit-based trading analysis completed", [
            'symbol' => $symbol,
            'analysis' => $analysis,
            'current_price' => $currentPrice,
            'decision' => $decision,
            'timestamp' => now()
        ]);
    }
}
