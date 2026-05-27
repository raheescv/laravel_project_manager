<?php

namespace App\Trading\Strategies;

use App\Trading\Contracts\Strategy;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\Signal;
use App\Trading\Support\Indicators;

/**
 * Composite momentum scorer.
 *
 * Picks stocks with strong short-term momentum, positive trend, and
 * acceptable volatility. Pure — no DB writes, no broker calls — so it can
 * run identically in live and backtest contexts.
 */
class MomentumScoreStrategy implements Strategy
{
    public function code(): string
    {
        return 'momentum_score';
    }

    public function name(): string
    {
        return 'Momentum Score';
    }

    public function defaultParameters(): array
    {
        return [
            'fast_ema' => 9,
            'slow_ema' => 21,
            'rsi_period' => 14,
            'min_rsi' => 50,
            'max_rsi' => 75,
            'min_score' => 0.55,
            'stop_loss_pct' => 3.0,
            'target_pct' => 5.0,
        ];
    }

    public function score(string $symbol, array $bars, array $context = []): Signal
    {
        $params = array_replace($this->defaultParameters(), $context['parameters'] ?? []);

        if (count($bars) < max($params['slow_ema'], $params['rsi_period']) + 2) {
            return Signal::hold($symbol, ['reason' => 'not enough bars']);
        }

        $closes = array_map(fn (Bar $b) => $b->close, $bars);
        $latest = end($bars);

        $emaFast = Indicators::ema($closes, $params['fast_ema']);
        $emaSlow = Indicators::ema($closes, $params['slow_ema']);
        $rsi = Indicators::rsi($closes, $params['rsi_period']);
        $momentum = Indicators::pctChange($closes, 5);

        $trendUp = $emaFast > $emaSlow;
        $rsiHealthy = $rsi >= $params['min_rsi'] && $rsi <= $params['max_rsi'];
        $hasMomentum = $momentum > 0;

        $score = 0.0;
        $score += $trendUp ? 0.4 : 0.0;
        $score += $rsiHealthy ? 0.3 : 0.0;
        $score += $hasMomentum ? 0.3 * min(1.0, $momentum / 5.0) : 0.0;

        $meta = [
            'ema_fast' => $emaFast,
            'ema_slow' => $emaSlow,
            'rsi' => $rsi,
            'momentum_5' => $momentum,
        ];

        if (! $trendUp || ! $rsiHealthy || $score < $params['min_score']) {
            return new Signal(
                symbol: $symbol,
                action: Signal::ACTION_HOLD,
                confidence: $score,
                score: $score,
                meta: $meta,
            );
        }

        $stopLoss = $latest->close * (1 - $params['stop_loss_pct'] / 100);
        $target = $latest->close * (1 + $params['target_pct'] / 100);

        return new Signal(
            symbol: $symbol,
            action: Signal::ACTION_BUY,
            confidence: $score,
            score: $score,
            suggestedPrice: $latest->close,
            stopLoss: $stopLoss,
            target: $target,
            meta: $meta,
        );
    }
}
