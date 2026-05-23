<?php

namespace App\Trading\Strategies;

use App\Trading\Contracts\Strategy;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\Signal;
use App\Trading\Support\Indicators;

/**
 * Classic mean-reversion: buy when price falls a configurable number of
 * standard deviations below its rolling mean and RSI is oversold.
 */
class MeanReversionStrategy implements Strategy
{
    public function code(): string
    {
        return 'mean_reversion';
    }

    public function name(): string
    {
        return 'Mean Reversion';
    }

    public function defaultParameters(): array
    {
        return [
            'lookback' => 20,
            'z_threshold' => -1.5,
            'rsi_period' => 14,
            'rsi_max' => 30,
            'stop_loss_pct' => 2.0,
            'target_pct' => 3.0,
        ];
    }

    public function score(string $symbol, array $bars, array $context = []): Signal
    {
        $params = array_replace($this->defaultParameters(), $context['parameters'] ?? []);

        if (count($bars) < $params['lookback'] + 2) {
            return Signal::hold($symbol, ['reason' => 'not enough bars']);
        }

        $closes = array_map(fn (Bar $b) => $b->close, $bars);
        $latest = end($bars);

        $window = array_slice($closes, -1 * $params['lookback']);
        $mean = array_sum($window) / count($window);
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $window)) / count($window);
        $std = $variance > 0 ? sqrt($variance) : 0.0;
        $z = $std > 0 ? ($latest->close - $mean) / $std : 0.0;
        $rsi = Indicators::rsi($closes, $params['rsi_period']);

        $meta = ['z' => $z, 'mean' => $mean, 'std' => $std, 'rsi' => $rsi];

        if ($z > $params['z_threshold'] || $rsi > $params['rsi_max']) {
            return Signal::hold($symbol, $meta);
        }

        $score = min(1.0, abs($z) / 3.0);

        return new Signal(
            symbol: $symbol,
            action: Signal::ACTION_BUY,
            confidence: $score,
            score: $score,
            suggestedPrice: $latest->close,
            stopLoss: $latest->close * (1 - $params['stop_loss_pct'] / 100),
            target: $latest->close * (1 + $params['target_pct'] / 100),
            meta: $meta,
        );
    }
}
