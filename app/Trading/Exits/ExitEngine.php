<?php

namespace App\Trading\Exits;

use App\Models\TradingPaperOrder;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\PositionSnapshot;
use App\Trading\DataObjects\Signal;
use App\Trading\Support\Indicators;
use Illuminate\Support\Facades\Cache;

/**
 * Decides what to do with each OPEN position.
 *
 * The expectancy edge of any system lives here: cut losers fast at −1R,
 * book partial size at +1R, move the remainder's stop to breakeven, and
 * trail with ATR so winners run. A flat % stop/target leaves money on the
 * table — this engine is why the rewrite is worth doing.
 */
final class ExitEngine
{
    public function __construct(
        private readonly float $partialBookFraction = 0.5,
        private readonly float $trailAtrMultiple = 2.0,
        private readonly int $atrPeriod = 14,
    ) {}

    /**
     * @param  Bar[]  $bars  Chronological asc bars for the position symbol
     * @return Signal A SELL with `meta.exit_kind` set, or HOLD
     */
    public function decide(PositionSnapshot $pos, array $bars, array $context = []): Signal
    {
        $symbol = $pos->symbol;
        $ltp = $pos->ltp;
        $entry = $pos->avgPrice;

        $state = $this->loadState($symbol);
        $stop = (float) ($state['stop'] ?? ($entry * 0.97));
        $target = (float) ($state['target'] ?? ($entry * 1.05));
        $partialDone = (bool) ($state['partial_booked'] ?? false);
        $atOrAfter = (string) ($context['squareoff_at'] ?? '');

        if ($atOrAfter !== '' && now()->format('H:i') >= $atOrAfter) {
            return $this->flatten($pos, 'time_exit', $stop, $target);
        }

        if ($ltp <= $stop) {
            return $this->flatten($pos, $partialDone ? 'trailing_stop' : 'hard_stop', $stop, $target);
        }

        $rUp = $entry > 0 && $stop > 0 ? ($ltp - $entry) / ($entry - $stop) : 0.0;

        if (! $partialDone && $rUp >= 1.0 && $pos->quantity > 1) {
            $partialQty = max(1, (int) floor($pos->quantity * $this->partialBookFraction));
            $this->saveState($symbol, [
                'stop' => $entry,
                'target' => $target,
                'partial_booked' => true,
            ]);

            return new Signal(
                symbol: $symbol,
                action: Signal::ACTION_SELL,
                confidence: 1.0,
                score: $rUp,
                suggestedQty: $partialQty,
                meta: ['exit_kind' => 'partial_book_1R', 'stop' => $entry, 'r_multiple' => $rUp],
            );
        }

        if ($partialDone) {
            $atr = $this->atrFor($bars);
            if ($atr > 0) {
                $trail = $ltp - ($atr * $this->trailAtrMultiple);
                if ($trail > $stop) {
                    $this->saveState($symbol, ['stop' => $trail, 'target' => $target, 'partial_booked' => true]);
                }
            }
        }

        return Signal::hold($symbol, ['stop' => $stop, 'target' => $target, 'r_multiple' => $rUp]);
    }

    public function recordEntry(string $symbol, float $entry, float $stop, float $target): void
    {
        $this->saveState($symbol, ['stop' => $stop, 'target' => $target, 'partial_booked' => false]);
    }

    public function clear(string $symbol): void
    {
        Cache::forget($this->key($symbol));
    }

    private function flatten(PositionSnapshot $pos, string $kind, float $stop, float $target): Signal
    {
        $this->clear($pos->symbol);

        return new Signal(
            symbol: $pos->symbol,
            action: Signal::ACTION_FLATTEN,
            confidence: 1.0,
            score: 0.0,
            suggestedQty: $pos->quantity,
            meta: ['exit_kind' => $kind, 'stop' => $stop, 'target' => $target],
        );
    }

    private function atrFor(array $bars): float
    {
        if (count($bars) < $this->atrPeriod + 1) {
            return 0.0;
        }
        $highs = array_map(fn (Bar $b) => $b->high, $bars);
        $lows = array_map(fn (Bar $b) => $b->low, $bars);
        $closes = array_map(fn (Bar $b) => $b->close, $bars);

        return Indicators::atr($highs, $lows, $closes, $this->atrPeriod);
    }

    private function loadState(string $symbol): array
    {
        $cached = Cache::get($this->key($symbol));
        if (is_array($cached)) {
            return $cached;
        }

        $paper = TradingPaperOrder::query()
            ->where('symbol', $symbol)
            ->where('status', 'OPEN')
            ->latest('opened_at')
            ->first();

        if ($paper) {
            return [
                'stop' => (float) $paper->stop_loss,
                'target' => (float) $paper->target,
                'partial_booked' => false,
            ];
        }

        return [];
    }

    private function saveState(string $symbol, array $state): void
    {
        Cache::put($this->key($symbol), $state, now()->addHours(24));
    }

    private function key(string $symbol): string
    {
        return 'trading:exit_state:'.strtoupper($symbol);
    }
}
