<?php

namespace App\Services;

class TradingStrategyService
{
    public function calculateSMA($prices, $period)
    {
        if (count($prices) < $period) {
            return null;
        }

        return array_sum(array_slice($prices, -$period)) / $period;
    }

    public function calculateEMA($prices, $period)
    {
        if (count($prices) < $period) {
            return null;
        }
        $k = 2 / ($period + 1);
        $ema = $prices[0];

        foreach ($prices as $price) {
            $ema = ($price * $k) + ($ema * (1 - $k));
        }

        return round($ema, 2);
    }

    public function calculateRSI($prices, $period = 14)
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

    public function generateTradeSignal($prices)
    {
        $sma9 = $this->calculateSMA($prices, 9);
        $ema9 = $this->calculateEMA($prices, 9);
        $rsi14 = $this->calculateRSI($prices, 14);
        $lastPrice = end($prices);

        if ($ema9 > $sma9 && $rsi14 < 30) {
            return 'BUY';
        } elseif ($ema9 < $sma9 && $rsi14 > 70) {
            return 'SELL';
        }

        return 'HOLD';
    }
}
