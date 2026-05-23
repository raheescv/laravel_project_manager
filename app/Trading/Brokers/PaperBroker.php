<?php

namespace App\Trading\Brokers;

use App\Models\TradingPaperOrder;
use App\Trading\Contracts\BrokerContract;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\DataObjects\PositionSnapshot;

/**
 * In-process paper broker. Persists orders + positions to trading_paper_orders
 * so the dashboards can show paper P&L next to live P&L.
 */
class PaperBroker implements BrokerContract
{
    public function __construct(private float $startingFunds = 1_000_000.0) {}

    public function code(): string
    {
        return 'paper';
    }

    public function placeOrder(OrderRequest $request): OrderResult
    {
        $row = TradingPaperOrder::create([
            'strategy_code' => $request->strategyCode,
            'symbol' => $request->symbol,
            'side' => $request->side,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'filled_price' => $request->price ?? $this->quote($request->symbol, $request->exchange)['ltp'] ?? 0,
            'filled_qty' => $request->quantity,
            'stop_loss' => $request->stopLoss,
            'target' => $request->target,
            'status' => $request->side === OrderRequest::SIDE_BUY ? 'OPEN' : 'CLOSED',
            'opened_at' => now(),
            'closed_at' => $request->side === OrderRequest::SIDE_SELL ? now() : null,
        ]);

        // If this is a SELL, close any matching open BUY by FIFO.
        if ($request->side === OrderRequest::SIDE_SELL) {
            $this->settleSell($request, $row->filled_price);
        }

        return OrderResult::ok(
            orderId: 'PAPER-'.$row->id,
            brokerCode: $this->code(),
            filledPrice: (float) $row->filled_price,
            filledQty: $row->filled_qty,
            raw: ['paper_id' => $row->id],
        );
    }

    public function cancelOrder(string $orderId): bool
    {
        $id = (int) str_replace('PAPER-', '', $orderId);

        return (bool) TradingPaperOrder::where('id', $id)->update(['status' => 'CANCELED']);
    }

    public function positions(): array
    {
        return TradingPaperOrder::query()
            ->where('status', 'OPEN')
            ->get()
            ->map(fn ($o) => new PositionSnapshot(
                symbol: $o->symbol,
                quantity: (int) $o->filled_qty,
                avgPrice: (float) $o->filled_price,
                ltp: (float) ($this->quote($o->symbol)['ltp'] ?? $o->filled_price),
                raw: ['paper_id' => $o->id, 'strategy_code' => $o->strategy_code],
            ))
            ->all();
    }

    public function holdings(): array
    {
        return $this->positions();
    }

    public function quote(string $symbol, string $exchange = 'NSE'): ?array
    {
        // Paper broker has no market data of its own — defer to the configured
        // live broker via the BrokerManager when available.
        try {
            $live = app(BrokerManager::class)->live();
            if ($live && $live->code() !== $this->code()) {
                return $live->quote($symbol, $exchange);
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    public function historicalBars(string $symbol, string $interval, int $lookback): array
    {
        try {
            $live = app(BrokerManager::class)->live();
            if ($live && $live->code() !== $this->code()) {
                return $live->historicalBars($symbol, $interval, $lookback);
            }
        } catch (\Throwable) {
            // ignore
        }

        return [];
    }

    public function availableFunds(): float
    {
        $used = (float) TradingPaperOrder::query()
            ->where('status', 'OPEN')
            ->sum(\DB::raw('filled_price * filled_qty'));

        return max(0.0, $this->startingFunds - $used);
    }

    public function isHealthy(): bool
    {
        return true;
    }

    private function settleSell(OrderRequest $sell, float $exitPrice): void
    {
        $remainingQty = $sell->quantity;
        $opens = TradingPaperOrder::query()
            ->where('symbol', $sell->symbol)
            ->where('side', OrderRequest::SIDE_BUY)
            ->where('status', 'OPEN')
            ->orderBy('id')
            ->get();

        foreach ($opens as $open) {
            if ($remainingQty <= 0) {
                break;
            }
            $closingQty = min($remainingQty, (int) $open->filled_qty);
            $pnl = ($exitPrice - (float) $open->filled_price) * $closingQty;
            $open->status = $closingQty === (int) $open->filled_qty ? 'CLOSED' : 'OPEN';
            $open->exit_price = $exitPrice;
            $open->closed_at = now();
            $open->pnl = (float) ($open->pnl ?? 0) + $pnl;
            $open->filled_qty -= $closingQty;
            if ($open->filled_qty <= 0) {
                $open->status = 'CLOSED';
            }
            $open->save();
            $remainingQty -= $closingQty;
        }
    }
}
