<?php

namespace App\Trading\Reconciliation;

use App\Models\TradingStrategyRun;
use App\Trading\Brokers\BrokerManager;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\DataObjects\PositionSnapshot;

/**
 * After an order is placed, verifies it actually appears in the broker's
 * position book within a bounded window. Updates the corresponding
 * TradingStrategyRun snapshot so the reports stop lying about fills.
 *
 * The broker contract intentionally exposes only positions() and not
 * orderBook(), so reconciliation here is presence-based: did a position
 * appear (or grow) for the symbol within N seconds? If not, the run is
 * flagged `unreconciled` for the postmortem to investigate.
 */
final class OrderReconciler
{
    public function __construct(
        private readonly BrokerManager $brokers,
        private readonly int $maxWaitSeconds = 6,
        private readonly int $pollIntervalMs = 1500,
    ) {}

    public function reconcile(OrderRequest $request, OrderResult $result, ?int $runId = null): array
    {
        if (! $result->success) {
            return ['status' => 'skipped', 'reason' => 'place_failed'];
        }

        $deadline = microtime(true) + $this->maxWaitSeconds;
        $expected = $this->priorQuantityFor($request->symbol);
        $expected += $request->side === OrderRequest::SIDE_BUY ? $request->quantity : -$request->quantity;

        $observed = null;
        $reconciled = false;

        do {
            try {
                $current = $this->quantityFor($request->symbol);
                if ($this->matches($current, $expected, $request->side)) {
                    $observed = $current;
                    $reconciled = true;
                    break;
                }
                $observed = $current;
            } catch (\Throwable) {
                // keep polling
            }
            usleep($this->pollIntervalMs * 1000);
        } while (microtime(true) < $deadline);

        $verdict = [
            'status' => $reconciled ? 'reconciled' : 'unreconciled',
            'expected_qty' => $expected,
            'observed_qty' => $observed,
            'broker_order_id' => $result->orderId,
        ];

        if ($runId) {
            $this->annotate($runId, $verdict);
        }

        return $verdict;
    }

    private function quantityFor(string $symbol): int
    {
        $broker = $this->brokers->broker();
        foreach ($broker->positions() as $p) {
            if (! $p instanceof PositionSnapshot) {
                continue;
            }
            if (strcasecmp($p->symbol, $symbol) === 0) {
                return $p->quantity;
            }
        }

        return 0;
    }

    private function priorQuantityFor(string $symbol): int
    {
        return $this->quantityFor($symbol);
    }

    private function matches(int $observed, int $expected, string $side): bool
    {
        return $side === OrderRequest::SIDE_BUY ? $observed >= $expected : $observed <= $expected;
    }

    private function annotate(int $runId, array $verdict): void
    {
        $run = TradingStrategyRun::find($runId);
        if (! $run) {
            return;
        }

        $snapshot = is_array($run->snapshot) ? $run->snapshot : [];
        $snapshot['reconciliation'] = $verdict;
        $run->snapshot = $snapshot;
        $run->save();
    }
}
