<?php

namespace App\Examples;

use App\Services\FlatTradeService;

/**
 * FlatTrade Integration Usage Examples
 *
 * This class demonstrates how to use the FlatTradeService for various trading operations
 * based on the FlatTrade API documentation at https://pi.flattrade.in/docs
 */
class FlatTradeUsageExamples
{
    protected FlatTradeService $flatTradeService;

    public function __construct()
    {
        $this->flatTradeService = new FlatTradeService();
    }

    /**
     * Example 1: Basic Buy Order
     */
    public function basicBuyOrder(): array
    {
        try {
            // Place a market buy order for 10 shares of RELIANCE
            $result = $this->flatTradeService->smartBuy('RELIANCE', 10, [
                'order_type' => 'MARKET',
                'max_price' => 2500.00, // Price protection
            ]);

            return [
                'success' => true,
                'message' => 'Buy order placed successfully',
                'order' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 2: Limit Order with Price Analysis
     */
    public function limitOrderWithAnalysis(): array
    {
        try {
            $symbol = 'TCS';

            // Get market analysis first
            $analysis = $this->flatTradeService->getMarketAnalysis($symbol);

            // Calculate limit price (5% below current price)
            $limitPrice = $analysis['current_price'] * 0.95;

            // Place limit buy order
            $result = $this->flatTradeService->placeLimitOrder(
                $symbol,
                5, // quantity
                $limitPrice,
                'BUY'
            );

            return [
                'success' => true,
                'message' => 'Limit order placed successfully',
                'analysis' => $analysis,
                'limit_price' => $limitPrice,
                'order' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 3: Smart Sell with Holdings Check
     */
    public function smartSellWithHoldingsCheck(): array
    {
        try {
            $symbol = 'INFY';
            $quantity = 20;

            // Check holdings first
            $holdings = $this->flatTradeService->getHoldings();
            $symbolHolding = collect($holdings)->firstWhere('symbol', $symbol);

            if (! $symbolHolding || $symbolHolding['quantity'] < $quantity) {
                throw new \Exception("Insufficient holdings for {$symbol}");
            }

            // Get current market data
            $marketData = $this->flatTradeService->getMarketData($symbol);
            $currentPrice = $marketData['last_price'];

            // Place smart sell order with minimum price protection
            $result = $this->flatTradeService->smartSell($symbol, $quantity, [
                'order_type' => 'MARKET',
                'min_price' => $currentPrice * 0.98, // 2% below current price
            ]);

            return [
                'success' => true,
                'message' => 'Sell order placed successfully',
                'holdings' => $symbolHolding,
                'current_price' => $currentPrice,
                'order' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 4: Bracket Order for Risk Management
     */
    public function bracketOrderExample(): array
    {
        try {
            $symbol = 'HDFC';
            $quantity = 15;
            $entryPrice = 1500.00;
            $stopLossPercent = 3; // 3% stop loss
            $targetPercent = 8;   // 8% target

            $result = $this->flatTradeService->executeTradeCycle(
                $symbol,
                $quantity,
                $entryPrice,
                $stopLossPercent,
                $targetPercent
            );

            return [
                'success' => true,
                'message' => 'Bracket order placed successfully',
                'trade_details' => [
                    'symbol' => $symbol,
                    'quantity' => $quantity,
                    'entry_price' => $entryPrice,
                    'stop_loss_price' => $entryPrice * (1 - $stopLossPercent / 100),
                    'target_price' => $entryPrice * (1 + $targetPercent / 100),
                ],
                'order' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 5: Portfolio Analysis and Rebalancing
     */
    public function portfolioAnalysis(): array
    {
        try {
            // Get current holdings and balance
            $holdings = $this->flatTradeService->getHoldings();
            $balance = $this->flatTradeService->getAccountBalance();

            $analysis = [
                'total_holdings' => count($holdings),
                'total_value' => collect($holdings)->sum('current_value'),
                'available_cash' => $balance['available_cash'],
                'total_portfolio_value' => $balance['available_cash'] + collect($holdings)->sum('current_value'),
                'holdings' => $holdings,
            ];

            // Calculate portfolio allocation
            foreach ($analysis['holdings'] as &$holding) {
                $holding['allocation_percent'] = ($holding['current_value'] / $analysis['total_portfolio_value']) * 100;
            }

            return [
                'success' => true,
                'message' => 'Portfolio analysis completed',
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 6: Stop Loss Management
     */
    public function stopLossManagement(): array
    {
        try {
            $symbol = 'WIPRO';
            $quantity = 25;
            $currentPrice = 400.00;
            $stopLossPrice = $currentPrice * 0.95; // 5% stop loss

            // Place stop loss order
            $result = $this->flatTradeService->placeStopLossOrder(
                $symbol,
                $quantity,
                $stopLossPrice,
                'SELL'
            );

            return [
                'success' => true,
                'message' => 'Stop loss order placed successfully',
                'stop_loss_details' => [
                    'symbol' => $symbol,
                    'quantity' => $quantity,
                    'current_price' => $currentPrice,
                    'stop_loss_price' => $stopLossPrice,
                    'risk_amount' => ($currentPrice - $stopLossPrice) * $quantity,
                ],
                'order' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 7: Order History and Performance Tracking
     */
    public function orderHistoryAnalysis(): array
    {
        try {
            // Get order history for last 30 days
            $orderHistory = $this->flatTradeService->getOrderHistory([
                'from_date' => now()->subDays(30)->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ]);

            // Get trade history
            $tradeHistory = $this->flatTradeService->getTradeHistory([
                'from_date' => now()->subDays(30)->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ]);

            // Calculate performance metrics
            $totalTrades = count($tradeHistory);
            $successfulTrades = collect($tradeHistory)->where('status', 'COMPLETE')->count();
            $successRate = $totalTrades > 0 ? ($successfulTrades / $totalTrades) * 100 : 0;

            $analysis = [
                'period' => 'Last 30 days',
                'total_orders' => count($orderHistory),
                'total_trades' => $totalTrades,
                'successful_trades' => $successfulTrades,
                'success_rate' => round($successRate, 2),
                'order_history' => $orderHistory,
                'trade_history' => $tradeHistory,
            ];

            return [
                'success' => true,
                'message' => 'Order history analysis completed',
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Example 8: Market Data Analysis for Trading Decisions
     */
    public function marketDataAnalysis(array $symbols): array
    {
        try {
            $marketAnalysis = [];

            foreach ($symbols as $symbol) {
                $analysis = $this->flatTradeService->getMarketAnalysis($symbol);
                $marketAnalysis[$symbol] = $analysis;
            }

            // Find best opportunities
            $bestBuyOpportunity = collect($marketAnalysis)
                ->sortByDesc('change_percent')
                ->first();

            $bestSellOpportunity = collect($marketAnalysis)
                ->sortBy('change_percent')
                ->first();

            return [
                'success' => true,
                'message' => 'Market analysis completed',
                'market_data' => $marketAnalysis,
                'recommendations' => [
                    'best_buy_opportunity' => $bestBuyOpportunity,
                    'best_sell_opportunity' => $bestSellOpportunity,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
