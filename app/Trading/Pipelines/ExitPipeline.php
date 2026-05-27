<?php

namespace App\Trading\Pipelines;

use App\Trading\Brokers\BrokerManager;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\PositionSnapshot;
use App\Trading\DataObjects\Signal;
use App\Trading\Exits\ExitEngine;
use App\Trading\Reconciliation\OrderReconciler;
use App\Trading\Services\TradeExecutor;

/**
 * One tick of the sell loop. Asks ExitEngine for each open position and
 * routes any actionable signal (partial book / hard stop / trailing stop /
 * time exit) through TradeExecutor → RiskGate → broker.
 */
final class ExitPipeline
{
    public function __construct(
        private readonly BrokerManager $brokers,
        private readonly TradeExecutor $executor,
        private readonly ExitEngine $exits,
        private readonly OrderReconciler $reconciler,
    ) {}

    public function run(array $options): array
    {
        $mode = $options['mode'] ?? 'paper';
        $barInterval = $options['bar_interval'] ?? '5m';
        $lookback = (int) ($options['lookback_bars'] ?? 120);
        $squareoffAt = $options['squareoff_at'] ?? null;

        $broker = $this->brokers->broker();
        $positions = array_filter($broker->positions(), fn ($p) => $p instanceof PositionSnapshot);

        $exited = [];
        foreach ($positions as $pos) {
            if ($pos->quantity <= 0) {
                continue;
            }

            $bars = [];
            try {
                $bars = $broker->historicalBars($pos->symbol, $barInterval, $lookback);
            } catch (\Throwable) {
                // ExitEngine handles missing bars by skipping ATR trail
            }

            $signal = $this->exits->decide($pos, $bars, ['squareoff_at' => $squareoffAt]);

            if ($signal->action !== Signal::ACTION_SELL && $signal->action !== Signal::ACTION_FLATTEN) {
                continue;
            }

            $qty = $signal->suggestedQty > 0 ? min($signal->suggestedQty, $pos->quantity) : $pos->quantity;
            $request = (new OrderRequest(
                symbol: $pos->symbol,
                side: OrderRequest::SIDE_SELL,
                quantity: $qty,
                type: OrderRequest::TYPE_MARKET,
                price: $pos->ltp,
                strategyCode: $options['strategy_code'] ?? null,
                meta: ['source' => 'exit_pipeline', 'exit_kind' => $signal->meta['exit_kind'] ?? 'unknown'],
            ))->withIdempotencyKey();

            $result = $this->executor->execute($request, [
                'command' => 'trade:unified',
                'exit_kind' => $signal->meta['exit_kind'] ?? null,
            ], paper: $mode !== 'live');

            if ($result->success) {
                if ($mode === 'live') {
                    $verdict = $this->reconciler->reconcile($request, $result);
                    $exited[] = ['symbol' => $pos->symbol, 'quantity' => $qty, 'kind' => $signal->meta['exit_kind'] ?? null, 'reconciliation' => $verdict];
                } else {
                    $exited[] = ['symbol' => $pos->symbol, 'quantity' => $qty, 'kind' => $signal->meta['exit_kind'] ?? null, 'mode' => $mode];
                }
            }
        }

        return ['status' => 'ok', 'exited' => $exited];
    }
}
