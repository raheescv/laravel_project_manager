<?php

namespace App\Trading\Pipelines;

use App\Trading\Brokers\BrokerManager;
use App\Trading\Contracts\Strategy;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\PositionSnapshot;
use App\Trading\DataObjects\Signal;
use App\Trading\Exits\ExitEngine;
use App\Trading\Reconciliation\OrderReconciler;
use App\Trading\Regime\RegimeFilter;
use App\Trading\Services\TradeExecutor;
use App\Trading\Sizing\PositionSizer;
use Illuminate\Support\Facades\Cache;

/**
 * One tick of the buy loop.
 *
 *   universe → bars → strategy.score → regime gate → size → execute
 *
 * RiskGate runs inside TradeExecutor — no rule is bypassed. Every per-symbol
 * decision takes a short Cache::lock so a cron re-run can't double-fire on
 * the same tick.
 */
final class EntryPipeline
{
    public function __construct(
        private readonly BrokerManager $brokers,
        private readonly TradeExecutor $executor,
        private readonly PositionSizer $sizer,
        private readonly ExitEngine $exits,
        private readonly RegimeFilter $regime,
        private readonly OrderReconciler $reconciler,
    ) {}

    public function run(Strategy $strategy, array $symbols, array $options): array
    {
        $mode = $options['mode'] ?? 'paper';
        $maxConcurrent = (int) ($options['max_concurrent'] ?? 3);
        $barInterval = $options['bar_interval'] ?? '5m';
        $lookback = (int) ($options['lookback_bars'] ?? 120);

        $regimeCheck = $this->regime->allowsLongEntries();
        if (! $regimeCheck['ok']) {
            return ['status' => 'skipped_regime', 'reason' => $regimeCheck['reason'], 'placed' => []];
        }

        $broker = $this->brokers->broker();
        $heldSymbols = array_map(
            fn (PositionSnapshot $p) => strtoupper($p->symbol),
            array_filter($broker->positions(), fn ($p) => $p instanceof PositionSnapshot),
        );

        if (count($heldSymbols) >= $maxConcurrent) {
            return ['status' => 'skipped_cap', 'reason' => 'max concurrent reached', 'placed' => []];
        }

        $equity = $broker->availableFunds();
        $availableFunds = $equity;
        $placed = [];
        $slotsLeft = $maxConcurrent - count($heldSymbols);

        foreach ($symbols as $symbol) {
            $symbol = strtoupper($symbol);
            if ($slotsLeft <= 0) {
                break;
            }
            if (in_array($symbol, $heldSymbols, true)) {
                continue;
            }

            $lock = Cache::lock("trade:tick:{$symbol}", 30);
            if (! $lock->get()) {
                continue;
            }

            try {
                $bars = $broker->historicalBars($symbol, $barInterval, $lookback);
                if (empty($bars)) {
                    continue;
                }

                $signal = $strategy->score($symbol, $bars, [
                    'parameters' => $options['parameters'] ?? [],
                ]);

                if ($signal->action !== Signal::ACTION_BUY) {
                    continue;
                }

                $entry = $signal->suggestedPrice ?? 0.0;
                $stop = $signal->stopLoss ?? 0.0;
                $target = $signal->target ?? 0.0;
                if ($entry <= 0 || $stop <= 0 || $stop >= $entry) {
                    continue;
                }

                $sized = $this->sizer->size($equity, $entry, $stop, $availableFunds);
                if ($sized['quantity'] <= 0) {
                    continue;
                }

                if (($entry / max(0.0001, $stop) - 1) > 0 && ($target - $entry) / max(0.0001, $entry - $stop) < 1.5) {
                    continue;
                }

                $request = (new OrderRequest(
                    symbol: $symbol,
                    side: OrderRequest::SIDE_BUY,
                    quantity: $sized['quantity'],
                    type: OrderRequest::TYPE_MARKET,
                    price: $entry,
                    stopLoss: $stop,
                    target: $target,
                    strategyCode: $strategy->code(),
                    meta: ['source' => 'entry_pipeline', 'risk' => $sized],
                ))->withIdempotencyKey();

                $result = $this->executor->execute($request, [
                    'command' => 'trade:unified',
                    'open_positions_count' => count($heldSymbols),
                ], paper: $mode !== 'live');

                if ($result->success) {
                    $this->exits->recordEntry($symbol, $entry, $stop, $target);
                    $availableFunds -= $sized['notional'];
                    $heldSymbols[] = $symbol;
                    $slotsLeft--;

                    if ($mode === 'live') {
                        $verdict = $this->reconciler->reconcile($request, $result);
                        $placed[] = compact('symbol') + ['quantity' => $sized['quantity'], 'reconciliation' => $verdict];
                    } else {
                        $placed[] = ['symbol' => $symbol, 'quantity' => $sized['quantity'], 'mode' => $mode];
                    }
                }
            } finally {
                $lock->release();
            }
        }

        return ['status' => 'ok', 'placed' => $placed];
    }
}
