<?php

namespace App\Trading\Pipelines;

use App\Trading\Brokers\BrokerManager;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\PositionSnapshot;
use App\Trading\Exits\ExitEngine;
use App\Trading\Reconciliation\OrderReconciler;
use App\Trading\Services\TradeExecutor;

/**
 * Hard flatten — no signal logic. Used for end-of-day forced squareoff
 * to avoid the broker's auto-MIS-squareoff slippage.
 */
final class SquareoffPipeline
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
        $broker = $this->brokers->broker();
        $positions = array_filter($broker->positions(), fn ($p) => $p instanceof PositionSnapshot);

        $flattened = [];
        foreach ($positions as $pos) {
            if ($pos->quantity <= 0) {
                continue;
            }

            $request = (new OrderRequest(
                symbol: $pos->symbol,
                side: OrderRequest::SIDE_SELL,
                quantity: $pos->quantity,
                type: OrderRequest::TYPE_MARKET,
                price: $pos->ltp,
                meta: ['source' => 'squareoff_pipeline'],
            ))->withIdempotencyKey();

            $result = $this->executor->execute($request, [
                'command' => 'trade:unified',
                'action' => 'squareoff',
            ], paper: $mode !== 'live');

            if ($result->success) {
                $this->exits->clear($pos->symbol);
                if ($mode === 'live') {
                    $verdict = $this->reconciler->reconcile($request, $result);
                    $flattened[] = ['symbol' => $pos->symbol, 'quantity' => $pos->quantity, 'reconciliation' => $verdict];
                } else {
                    $flattened[] = ['symbol' => $pos->symbol, 'quantity' => $pos->quantity, 'mode' => $mode];
                }
            }
        }

        return ['status' => 'ok', 'flattened' => $flattened];
    }
}
