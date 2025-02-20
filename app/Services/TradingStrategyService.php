<?php

namespace App\Services;

class TradingStrategyService
{
    private function calculateSMA($prices, $period)
    {
        if (count($prices) < $period) {
            return null;
        }

        return array_sum(array_slice($prices, -$period)) / $period;
    }

    private function calculateEMA(array $prices, $period)
    {
        if (count($prices) < $period) {
            return null;
        }

        $multiplier = 2 / ($period + 1);
        $ema = $prices[0]; // Start with first price as initial EMA

        foreach ($prices as $price) {
            $ema = ($price - $ema) * $multiplier + $ema;
        }

        return round($ema, 2);
    }

    private function calculateRSI($prices, $period = 14)
    {
        if (count($prices) < $period + 1) {
            return null;
        }
        $gains = $losses = 0;

        for ($i = 1; $i <= $period; $i++) {
            $diff = $prices[$i] - $prices[$i - 1];
            if ($diff > 0) {
                $gains += $diff;
            } else {
                $losses += abs($diff);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;
        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;

        return round(100 - (100 / (1 + $rs)), 2);
    }

    private function findSupportResistance(array $prices)
    {
        $support = min($prices);
        $resistance = max($prices);

        return [$support, $resistance];
    }

    public function generateTradeSignal($prices)
    {
        $sma5 = $this->calculateSMA($prices, 5);
        $sma20 = $this->calculateSMA($prices, 20);

        $ema5 = $this->calculateEMA($prices, 5);
        $ema20 = $this->calculateEMA($prices, 20);
        $rsi = $this->calculateRSI($prices);

        [$support, $resistance] = $this->findSupportResistance($prices);

        $price = end($prices);
        if ($rsi < 50 && $price <= $support && $ema5 > $ema20 && $sma5 > $sma20) {
            return 'BUY';
        } elseif ($rsi > 50 && $price >= $resistance && $ema5 < $ema20 && $sma5 < $sma20) {
            return 'SELL';
        }

        return 'HOLD';
    }
}
