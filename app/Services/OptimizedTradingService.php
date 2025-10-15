<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OptimizedTradingService
{
    protected FlatTradeService $flatTradeService;

    public function __construct(FlatTradeService $flatTradeService)
    {
        $this->flatTradeService = $flatTradeService;
    }

    /**
     * Simplified stock selection with optimized scoring
     */
    public function selectStocks(array $config): array
    {
        try {
            $maxStocks = $config['max_stocks'] ?? 5;
            $symbolFilter = $config['symbol_filter'] ?? 'all';
            $customSymbols = $config['custom_symbols'] ?? [];

            // Get stock candidates
            $candidates = $this->getCandidates($symbolFilter, $customSymbols, $maxStocks * 2);
            if (empty($candidates)) {
                return [];
            }

            // Score and select stocks
            $scoredStocks = [];
            foreach ($candidates as $stock) {
                $score = $this->calculateScore($stock);
                if ($score > 50) { // Minimum threshold
                    $scoredStocks[] = array_merge($stock, ['score' => $score]);
                }
            }

            // Sort by score and return top stocks
            usort($scoredStocks, fn ($a, $b) => $b['score'] <=> $a['score']);

            return array_slice($scoredStocks, 0, $maxStocks);

        } catch (\Exception $e) {
            Log::error('Stock selection failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Simplified position analysis for selling
     */
    public function analyzePositions(array $config): array
    {
        try {
            $positions = $this->getPositions();
            $sellCandidates = [];

            foreach ($positions as $position) {
                if ($this->shouldSell($position, $config)) {
                    $sellCandidates[] = array_merge($position, [
                        'reason' => $this->getSellReason($position, $config),
                        'priority' => $this->getSellPriority($position, $config),
                    ]);
                }
            }

            // Sort by priority
            usort($sellCandidates, fn ($a, $b) => $b['priority'] <=> $a['priority']);

            return $sellCandidates;

        } catch (\Exception $e) {
            Log::error('Position analysis failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get all positions for sell-all mode
     */
    public function getAllPositions(): array
    {
        try {
            $positions = $this->getPositions();

            // Add default reason and priority for sell-all
            foreach ($positions as &$position) {
                $position['reason'] = 'Sell all positions';
                $position['priority'] = 100; // High priority for sell-all
            }

            return $positions;
        } catch (\Exception $e) {
            Log::error('Get all positions failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get stock candidates based on filter
     */
    protected function getCandidates(string $filter, array $customSymbols, int $limit): array
    {
        try {
            $topGainers = $this->flatTradeService->getTopList('NSE', 'T', 'NSEALL', 'CHANGE');
            if (! isset($topGainers['values'])) {
                return [];
            }
            $candidates = [];
            foreach ($topGainers['values'] as $stock) {
                if (count($candidates) >= $limit) {
                    break;
                }

                $symbol = $stock['tsym'] ?? '';
                if ($this->isValidCandidate($symbol, $filter, $customSymbols)) {
                    $candidates[] = $stock;
                }
            }

            return $candidates;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if symbol is valid candidate
     */
    protected function isValidCandidate(string $symbol, string $filter, array $customSymbols): bool
    {
        if (strlen($symbol) < 3) {
            return false;
        }
        switch ($filter) {
            case 'nifty50':
                return in_array($symbol, $this->getNifty50Symbols());
            case 'custom':
                return in_array($symbol, $customSymbols);
            case 'all':
            default:
                return $this->isValidSymbol($symbol);
        }
    }

    /**
     * Simplified stock scoring
     */
    protected function calculateScore(array $stock): float
    {
        $symbol = $stock['tsym'] ?? '';
        $quote = $this->getQuote($symbol);
        if (! $quote) {
            return 0;
        }

        $score = 50; // Base score

        // Price range scoring
        $ltp = $quote['ltp'] ?? 0;
        if ($ltp >= 50 && $ltp <= 5000) {
            $score += 20;
        } elseif ($ltp >= 10 && $ltp <= 10000) {
            $score += 10;
        }

        // Volume scoring
        $volume = $quote['volume'] ?? 0;
        if ($volume > 1000000) {
            $score += 20;
        } elseif ($volume > 500000) {
            $score += 15;
        } elseif ($volume > 100000) {
            $score += 10;
        }

        // Change percentage scoring
        $changePercent = $quote['change_percent'] ?? 0;
        if ($changePercent >= 1 && $changePercent <= 5) {
            $score += 15;
        } elseif ($changePercent >= 0.5 && $changePercent <= 8) {
            $score += 10;
        } elseif ($changePercent < 0) {
            $score -= 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * Check if position should be sold
     */
    protected function shouldSell(array $position, array $config): bool
    {
        $pnlPercent = $position['pnl_percent'] ?? 0;
        $profitThreshold = $config['profit_threshold'] ?? 5.0;
        $lossThreshold = $config['loss_threshold'] ?? 3.0;

        // If profit threshold is 0, only sell losing positions
        if ($profitThreshold == 0) {
            return $pnlPercent <= -$lossThreshold;
        }

        return $pnlPercent >= $profitThreshold || $pnlPercent <= -$lossThreshold;
    }

    /**
     * Get sell reason
     */
    protected function getSellReason(array $position, array $config): string
    {
        $pnlPercent = $position['pnl_percent'] ?? 0;
        $profitThreshold = $config['profit_threshold'] ?? 5.0;
        $lossThreshold = $config['loss_threshold'] ?? 3.0;

        if ($pnlPercent >= $profitThreshold) {
            return "Profit target reached ({$pnlPercent}%)";
        }
        if ($pnlPercent <= -$lossThreshold) {
            return "Stop loss triggered ({$pnlPercent}%)";
        }

        return 'Manual sell';
    }

    /**
     * Get sell priority
     */
    protected function getSellPriority(array $position, array $config): int
    {
        $pnlPercent = $position['pnl_percent'] ?? 0;
        $lossThreshold = $config['loss_threshold'] ?? 3.0;

        if ($pnlPercent <= -$lossThreshold) {
            return 100;
        } // High priority for losses
        if ($pnlPercent >= 5) {
            return 80;
        } // Medium-high priority for profits

        return 50; // Normal priority
    }

    /**
     * Get current positions
     */
    protected function getPositions(): array
    {
        try {
            $positions = $this->flatTradeService->getPositionBook('NSE', '', 0, '', '', '', '');

            if (is_array($positions) && ! empty($positions)) {
                if (isset($positions[0]['stat']) && $positions[0]['stat'] === 'Ok') {
                    return $this->formatPositions($positions);
                }
            }

            if (isset($positions['stat']) && $positions['stat'] === 'Ok' && isset($positions['netqty'])) {
                return $this->formatPositions([$positions]);
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format positions
     */
    protected function formatPositions(array $positions): array
    {
        $formatted = [];

        foreach ($positions as $position) {
            $symbol = $position['tsym'] ?? null;
            $quantity = (int) ($position['netqty'] ?? 0);
            $avgPrice = (float) ($position['netavgprc'] ?? 0);
            $currentPrice = (float) ($position['lp'] ?? 0);

            if (! $symbol || $quantity <= 0 || $avgPrice <= 0 || $currentPrice <= 0) {
                continue;
            }

            $pnlPercent = $avgPrice > 0 ? (($currentPrice - $avgPrice) / $avgPrice) * 100 : 0;

            $formatted[] = [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'avg_price' => $avgPrice,
                'current_price' => $currentPrice,
                'pnl_percent' => $pnlPercent,
                'pnl' => ($currentPrice - $avgPrice) * $quantity,
            ];
        }

        return $formatted;
    }

    /**
     * Get stock quote
     */
    protected function getQuote(string $symbol): ?array
    {
        try {
            $searchResult = $this->flatTradeService->searchScrip($symbol, 'NSE');
            if (! isset($searchResult['values'][0]['token'])) {
                return null;
            }

            $token = $searchResult['values'][0]['token'];
            $quote = $this->flatTradeService->getQuotes($token, 'NSE');

            if (isset($quote['stat']) && $quote['stat'] === 'Ok') {
                $ltp = (float) ($quote['lp'] ?? 0);
                $previousClose = (float) ($quote['c'] ?? 0);
                $changePercent = $previousClose > 0 ? (($ltp - $previousClose) / $previousClose) * 100 : 0;

                return [
                    'ltp' => $ltp,
                    'change_percent' => $changePercent,
                    'volume' => (int) ($quote['v'] ?? 0),
                    'high' => (float) ($quote['h'] ?? 0),
                    'low' => (float) ($quote['l'] ?? 0),
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if symbol is valid
     */
    protected function isValidSymbol(string $symbol): bool
    {
        return strlen($symbol) >= 3 &&
               preg_match('/[A-Z]/', $symbol) &&
               ! preg_match('/^[0-9]+$/', $symbol);
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
     * Get available funds
     */
    public function getAvailableFunds(): float
    {
        try {
            $response = $this->flatTradeService->getMaxPayoutAmount();
            if (isset($response['stat']) && $response['stat'] === 'Ok') {
                return (float) ($response['payout'] ?? 0);
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Place order
     */
    public function placeOrder(string $symbol, int $quantity, string $orderType, string $product, string $transactionType = 'B'): array
    {
        try {
            switch ($orderType) {
                case 'market':
                    return $this->flatTradeService->placeMarketOrder('NSE', $symbol, $quantity, $transactionType, $product);
                case 'limit':
                    return $this->flatTradeService->placeLimitOrder('NSE', $symbol, $quantity, 0, $transactionType, $product);
                default:
                    throw new \Exception("Unsupported order type: {$orderType}");
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
