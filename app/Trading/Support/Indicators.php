<?php

namespace App\Trading\Support;

/**
 * Stateless technical indicators. Each method takes a closes[] series
 * (chronological asc) and returns a scalar.
 *
 * Kept dependency-free so strategies and the backtester can share them.
 */
final class Indicators
{
    public static function sma(array $values, int $period): float
    {
        if (count($values) < $period || $period <= 0) {
            return 0.0;
        }

        $slice = array_slice($values, -$period);

        return array_sum($slice) / $period;
    }

    public static function ema(array $values, int $period): float
    {
        $n = count($values);
        if ($n < $period || $period <= 0) {
            return self::sma($values, $n);
        }

        $k = 2 / ($period + 1);
        $ema = self::sma(array_slice($values, 0, $period), $period);
        for ($i = $period; $i < $n; $i++) {
            $ema = $values[$i] * $k + $ema * (1 - $k);
        }

        return $ema;
    }

    public static function rsi(array $values, int $period = 14): float
    {
        $n = count($values);
        if ($n < $period + 1) {
            return 50.0;
        }

        $gains = $losses = 0.0;
        for ($i = $n - $period; $i < $n; $i++) {
            $delta = $values[$i] - $values[$i - 1];
            if ($delta >= 0) {
                $gains += $delta;
            } else {
                $losses += -$delta;
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        if ($avgLoss == 0.0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;

        return 100.0 - (100.0 / (1 + $rs));
    }

    public static function pctChange(array $values, int $lookback): float
    {
        $n = count($values);
        if ($n < $lookback + 1) {
            return 0.0;
        }
        $prev = $values[$n - $lookback - 1];
        if ($prev == 0.0) {
            return 0.0;
        }

        return (($values[$n - 1] - $prev) / $prev) * 100;
    }

    /**
     * Average True Range (Wilder smoothing). Returns ATR in price units —
     * not percent. Caller divides by price when a relative measure is needed.
     */
    public static function atr(array $highs, array $lows, array $closes, int $period = 14): float
    {
        $n = min(count($highs), count($lows), count($closes));
        if ($n < $period + 1) {
            return 0.0;
        }

        $tr = [];
        for ($i = 1; $i < $n; $i++) {
            $tr[] = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1]),
            );
        }

        $atr = array_sum(array_slice($tr, 0, $period)) / $period;
        for ($i = $period; $i < count($tr); $i++) {
            $atr = (($atr * ($period - 1)) + $tr[$i]) / $period;
        }

        return $atr;
    }

    public static function maxDrawdown(array $equity): float
    {
        $peak = $equity[0] ?? 0.0;
        $maxDd = 0.0;
        foreach ($equity as $e) {
            $peak = max($peak, $e);
            if ($peak > 0) {
                $dd = ($e - $peak) / $peak;
                $maxDd = min($maxDd, $dd);
            }
        }

        return $maxDd * 100;
    }

    public static function sharpe(array $returns, float $rf = 0.0): float
    {
        $n = count($returns);
        if ($n < 2) {
            return 0.0;
        }
        $mean = array_sum($returns) / $n;
        $var = array_sum(array_map(fn ($r) => ($r - $mean) ** 2, $returns)) / ($n - 1);
        $std = $var > 0 ? sqrt($var) : 0.0;
        if ($std == 0.0) {
            return 0.0;
        }

        return ($mean - $rf) / $std * sqrt(252);
    }

    public static function sortino(array $returns, float $rf = 0.0): float
    {
        $n = count($returns);
        if ($n < 2) {
            return 0.0;
        }
        $mean = array_sum($returns) / $n;
        $downside = array_filter($returns, fn ($r) => $r < 0);
        if (empty($downside)) {
            return 0.0;
        }
        $downStd = sqrt(array_sum(array_map(fn ($r) => $r ** 2, $downside)) / count($downside));
        if ($downStd == 0.0) {
            return 0.0;
        }

        return ($mean - $rf) / $downStd * sqrt(252);
    }
}
