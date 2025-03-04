<?php

namespace App\Services;

class TradingStrategyService
{
    private function calculateSMA(array $prices, int $period): ?float
    {
        if (count($prices) < $period) {
            return null;
        }
        $slice = array_slice($prices, -$period);

        return round(array_sum($slice) / $period, 2);
    }

    private function calculateEMA(array $prices, int $period): ?float
    {
        if (count($prices) < $period) {
            return null;
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($prices, 0, $period)) / $period; // Initial SMA

        foreach (array_slice($prices, $period) as $price) {
            $ema = ($price - $ema) * $multiplier + $ema;
        }

        return round($ema, 2);
    }

    private function calculateRSI(array $prices, int $period = 14): ?float
    {
        if (count($prices) < $period + 1) {
            return null;
        }

        $gains = $losses = 0;
        for ($i = 1; $i <= $period; $i++) {
            $diff = $prices[$i] - $prices[$i - 1];
            $gains += $diff > 0 ? $diff : 0;
            $losses += $diff < 0 ? abs($diff) : 0;
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        if ($avgLoss == 0) {
            return 100.0; // Max overbought
        }

        $rs = $avgGain / $avgLoss;

        return round(100 - (100 / (1 + $rs)), 2);
    }

    private function findSupportResistance(array $prices, float $tolerance = 0.02): array
    {
        $min = min($prices);
        $max = max($prices);
        $support = $min * (1 - $tolerance); // Slightly below min
        $resistance = $max * (1 + $tolerance); // Slightly above max

        return [round($support, 2), round($resistance, 2)];
    }

    private function getSMACrossover(array $prices, int $shortPeriod = 5, int $longPeriod = 20): string
    {
        $smaShortNow = $this->calculateSMA($prices, $shortPeriod);
        $smaLongNow = $this->calculateSMA($prices, $longPeriod);
        $smaShortPrev = $this->calculateSMA(array_slice($prices, 0, -1), $shortPeriod);
        $smaLongPrev = $this->calculateSMA(array_slice($prices, 0, -1), $longPeriod);

        if ($smaShortNow === null || $smaLongNow === null || $smaShortPrev === null || $smaLongPrev === null) {
            return 'NEUTRAL';
        }

        if ($smaShortNow > $smaLongNow && $smaShortPrev <= $smaLongPrev) {
            return 'UP'; // Bullish crossover
        } elseif ($smaShortNow < $smaLongNow && $smaShortPrev >= $smaLongPrev) {
            return 'DOWN'; // Bearish crossover
        }

        return 'NEUTRAL';
    }

    public function generateTradeSignal(array $prices)
    {
        $currentPrice = end($prices);
        if (count($prices) < 20) { // Minimum data for reliable signals
            return ['HOLD', $currentPrice];

            return ['signal' => 'HOLD', 'confidence' => 0.0, 'details' => 'Insufficient data'];
        }
        if ($currentPrice > 600) {
            return ['HOLD', $currentPrice];
        }
        $sma5 = $this->calculateSMA($prices, 5);
        $sma20 = $this->calculateSMA($prices, 20);
        $ema5 = $this->calculateEMA($prices, 5);
        $ema20 = $this->calculateEMA($prices, 20);
        $rsi = $this->calculateRSI($prices);
        [$support, $resistance] = $this->findSupportResistance($prices);
        $crossover = $this->getSMACrossover($prices);

        $signal = 'HOLD';
        $confidence = 0.0;
        $details = [];

        // Sell Conditions
        if ($rsi !== null && $rsi > 70 && // Overbought
        $currentPrice >= $resistance && // At or above resistance
        $ema5 < $ema20 && $sma5 < $sma20 && // Bearish trend
        $crossover === 'DOWN') { // Recent bearish crossover
            $signal = 'SELL';
            $confidence = 0.9;
            $details[] = 'Overbought RSI, price at resistance, bearish EMA/SMA crossover';
        }

        // Buy Conditions
        if ($rsi !== null && $rsi < 30 && // Oversold
        $currentPrice <= $support && // At or below support
        $ema5 > $ema20 && $sma5 > $sma20 && // Bullish trend
        $crossover === 'UP') { // Recent bullish crossover
            $signal = 'BUY';
            $confidence = 0.9;
            $details[] = 'Oversold RSI, price at support, bullish EMA/SMA crossover';
        }

        // Adjust confidence for weaker signals
        if ($signal === 'HOLD' && $rsi !== null) {
            if ($rsi < 40 && $ema5 > $ema20) {
                $signal = 'BUY';
                $confidence = 0.6; // Moderate confidence
                $details[] = 'Mildly oversold RSI with bullish EMA';
            } elseif ($rsi > 60 && $ema5 < $ema20) {
                $signal = 'SELL';
                $confidence = 0.6;
                $details[] = 'Mildly overbought RSI with bearish EMA';
            }
        }

        $result = [
            'signal' => $signal,
            'confidence' => $confidence,
            'price' => $currentPrice,
            'rsi' => $rsi,
            'sma5' => $sma5,
            'sma20' => $sma20,
            'ema5' => $ema5,
            'ema20' => $ema20,
            'support' => $support,
            'resistance' => $resistance,
            'details' => implode(', ', $details),
        ];
        if ($signal != 'HOLD') {
            // info($result);
        }

        return [$signal, $currentPrice];
    }
}
