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
 * the same tick. Every drop reason is surfaced in the `skipped` summary so
 * an empty `placed` is debuggable instead of mysterious.
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
        $minRR = (float) ($options['min_rr'] ?? 1.5);

        $regimeCheck = $this->regime->allowsLongEntries();
        if (! $regimeCheck['ok']) {
            return ['status' => 'skipped_regime', 'reason' => $regimeCheck['reason'], 'placed' => [], 'skipped' => []];
        }

        $broker = $this->brokers->broker();
        $heldSymbols = array_map(
            fn (PositionSnapshot $p) => strtoupper($p->symbol),
            array_filter($broker->positions(), fn ($p) => $p instanceof PositionSnapshot),
        );

        if (count($heldSymbols) >= $maxConcurrent) {
            return ['status' => 'skipped_cap', 'reason' => 'max concurrent reached', 'placed' => [], 'skipped' => []];
        }

        $equity = $broker->availableFunds();
        $availableFunds = $equity;
        $placed = [];
        $skipped = [];
        $slotsLeft = $maxConcurrent - count($heldSymbols);

        foreach ($symbols as $symbol) {
            $symbol = strtoupper($symbol);
            if ($slotsLeft <= 0) {
                $skipped[] = ['symbol' => $symbol, 'reason' => 'slots_exhausted'];

                continue;
            }
            if (in_array($symbol, $heldSymbols, true)) {
                $skipped[] = ['symbol' => $symbol, 'reason' => 'already_held'];

                continue;
            }

            $lock = Cache::lock("trade:tick:{$symbol}", 30);
            if (! $lock->get()) {
                $skipped[] = ['symbol' => $symbol, 'reason' => 'locked_by_another_tick'];

                continue;
            }

            try {
                $bars = $broker->historicalBars($symbol, $barInterval, $lookback);
                if (empty($bars)) {
                    $skipped[] = [
                        'symbol' => $symbol,
                        'reason' => 'no_bars',
                        'why' => \App\Trading\Brokers\FlatTradeBrokerAdapter::lastBarsError($symbol) ?? 'unknown',
                    ];

                    continue;
                }

                $signal = $strategy->score($symbol, $bars, [
                    'parameters' => $options['parameters'] ?? [],
                ]);

                if ($signal->action !== Signal::ACTION_BUY) {
                    $skipped[] = [
                        'symbol' => $symbol,
                        'reason' => 'no_buy_signal',
                        'action' => $signal->action,
                        'score' => round($signal->score, 3),
                    ];

                    continue;
                }

                $entry = $signal->suggestedPrice ?? 0.0;
                $stop = $signal->stopLoss ?? 0.0;
                $target = $signal->target ?? 0.0;
                if ($entry <= 0 || $stop <= 0 || $stop >= $entry) {
                    $skipped[] = ['symbol' => $symbol, 'reason' => 'invalid_levels', 'entry' => $entry, 'stop' => $stop];

                    continue;
                }

                $rr = ($target - $entry) / max(0.0001, $entry - $stop);
                if ($rr < $minRR) {
                    $skipped[] = ['symbol' => $symbol, 'reason' => 'rr_below_min', 'rr' => round($rr, 2), 'min_rr' => $minRR];

                    continue;
                }

                $sized = $this->sizer->size($equity, $entry, $stop, $availableFunds);
                if ($sized['quantity'] <= 0) {
                    $skipped[] = [
                        'symbol' => $symbol,
                        'reason' => 'zero_qty',
                        'equity' => $equity,
                        'available' => $availableFunds,
                        'stop_distance' => $sized['stop_distance'],
                    ];

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

                if (! $result->success) {
                    $skipped[] = ['symbol' => $symbol, 'reason' => 'execute_failed', 'error' => $result->error];

                    continue;
                }

                $this->exits->recordEntry($symbol, $entry, $stop, $target);
                $availableFunds -= $sized['notional'];
                $heldSymbols[] = $symbol;
                $slotsLeft--;

                $placedRow = [
                    'symbol' => $symbol,
                    'quantity' => $sized['quantity'],
                    'entry' => $entry,
                    'stop' => $stop,
                    'target' => $target,
                    'rr' => round($rr, 2),
                ];
                if ($mode === 'live') {
                    $placedRow['reconciliation'] = $this->reconciler->reconcile($request, $result);
                } else {
                    $placedRow['mode'] = $mode;
                }
                $placed[] = $placedRow;
            } finally {
                $lock->release();
            }
        }

        return [
            'status' => 'ok',
            'regime' => $regimeCheck['reason'] ?? null,
            'equity' => $equity,
            'max_concurrent' => $maxConcurrent,
            'held_at_start' => count($heldSymbols) - count($placed),
            'placed' => $placed,
            'skipped' => $skipped,
        ];
    }
}
