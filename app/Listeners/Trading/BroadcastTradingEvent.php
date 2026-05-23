<?php

namespace App\Listeners\Trading;

use App\Trading\Alerts\AlertDispatcher;
use App\Trading\Events\CircuitBreakerTripped;
use App\Trading\Events\OrderPlaced;
use App\Trading\Events\OrderRejected;
use App\Trading\Events\PositionClosed;
use App\Trading\Events\StopLossHit;

/**
 * Single listener that forwards every domain event into the AlertDispatcher.
 * Keeps wiring in one place — adding a new event just means dispatching it.
 */
class BroadcastTradingEvent
{
    public function __construct(private AlertDispatcher $dispatcher) {}

    public function onOrderPlaced(OrderPlaced $e): void
    {
        $this->dispatcher->dispatch(
            event: 'OrderPlaced',
            title: "Order placed: {$e->request->symbol}",
            body: sprintf(
                '%s %d %s @ %s via %s',
                $e->request->side,
                $e->request->quantity,
                $e->request->symbol,
                $e->result->filledPrice ?? 'market',
                $e->result->brokerCode ?? '?',
            ),
            payload: [
                'symbol' => $e->request->symbol,
                'side' => $e->request->side,
                'qty' => $e->request->quantity,
                'broker' => $e->result->brokerCode,
                'order_id' => $e->result->orderId,
                'strategy_code' => $e->request->strategyCode,
            ],
        );
    }

    public function onOrderRejected(OrderRejected $e): void
    {
        $this->dispatcher->dispatch(
            event: 'OrderRejected',
            title: "Order rejected: {$e->request->symbol}",
            body: $e->decision->reason,
            payload: [
                'symbol' => $e->request->symbol,
                'rule_code' => $e->decision->ruleCode,
                'severity' => $e->decision->severity,
            ],
        );
    }

    public function onStopLossHit(StopLossHit $e): void
    {
        $this->dispatcher->dispatch(
            event: 'StopLossHit',
            title: "Stop-loss hit: {$e->symbol}",
            body: sprintf('Exited %d @ %.2f from entry %.2f', $e->quantity, $e->exitPrice, $e->entryPrice),
            payload: ['symbol' => $e->symbol, 'severity' => 'warning', 'strategy_code' => $e->strategyCode],
        );
    }

    public function onPositionClosed(PositionClosed $e): void
    {
        $this->dispatcher->dispatch(
            event: 'PositionClosed',
            title: "Position closed: {$e->symbol}",
            body: sprintf('P&L %.2f (%.2f%%)', $e->pnl, $e->pnlPercent),
            payload: ['symbol' => $e->symbol, 'pnl' => $e->pnl, 'pnl_pct' => $e->pnlPercent, 'strategy_code' => $e->strategyCode],
        );
    }

    public function onCircuitBreakerTripped(CircuitBreakerTripped $e): void
    {
        $this->dispatcher->dispatch(
            event: 'CircuitBreakerTripped',
            title: 'Circuit breaker tripped',
            body: $e->reason,
            payload: ['severity' => 'breaker'] + $e->context,
        );
    }

    public function subscribe($events): array
    {
        return [
            OrderPlaced::class => 'onOrderPlaced',
            OrderRejected::class => 'onOrderRejected',
            StopLossHit::class => 'onStopLossHit',
            PositionClosed::class => 'onPositionClosed',
            CircuitBreakerTripped::class => 'onCircuitBreakerTripped',
        ];
    }
}
