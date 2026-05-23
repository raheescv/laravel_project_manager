<?php

namespace App\Trading\Brokers;

use App\Services\FlatTradeService;
use App\Trading\Contracts\BrokerContract;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\DataObjects\PositionSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * Adapter that fronts the existing FlatTradeService with the unified
 * BrokerContract. Lets new modules talk to FlatTrade without depending
 * on its specific method shapes.
 */
class FlatTradeBrokerAdapter implements BrokerContract
{
    public function __construct(private FlatTradeService $service) {}

    public function code(): string
    {
        return 'flat_trade';
    }

    public function placeOrder(OrderRequest $request): OrderResult
    {
        try {
            $direction = $request->side === OrderRequest::SIDE_BUY ? 'B' : 'S';
            $orderType = strtolower($request->type) === 'limit' ? 'limit' : 'market';

            // The existing service signature: placeOrder($symbol, $qty, $type, $product, $direction)
            $raw = $this->service->placeOrder(
                $request->symbol,
                $request->quantity,
                $orderType,
                $request->product,
                $direction,
            );

            $orderId = $raw['norenordno'] ?? $raw['order_id'] ?? null;
            if (! $orderId) {
                return OrderResult::failure('Broker did not return an order id', $raw, $this->code());
            }

            return OrderResult::ok(
                orderId: (string) $orderId,
                brokerCode: $this->code(),
                raw: $raw,
            );
        } catch (\Throwable $e) {
            Log::error('FlatTrade order failed', ['err' => $e->getMessage(), 'request' => $request->toArray()]);

            return OrderResult::failure($e->getMessage(), [], $this->code());
        }
    }

    public function cancelOrder(string $orderId): bool
    {
        try {
            if (method_exists($this->service, 'cancelOrder')) {
                $res = $this->service->cancelOrder($orderId);

                return (bool) ($res['stat'] ?? false);
            }
        } catch (\Throwable $e) {
            Log::warning('FlatTrade cancel failed', ['err' => $e->getMessage()]);
        }

        return false;
    }

    public function positions(): array
    {
        try {
            $raw = method_exists($this->service, 'getPositions')
                ? $this->service->getPositions()
                : [];
            $rows = $raw['values'] ?? $raw['positions'] ?? $raw ?? [];

            return collect($rows)->map(fn ($p) => new PositionSnapshot(
                symbol: $p['tsym'] ?? $p['symbol'] ?? '',
                quantity: (int) ($p['netqty'] ?? $p['qty'] ?? 0),
                avgPrice: (float) ($p['netavgprc'] ?? $p['avg_price'] ?? 0),
                ltp: (float) ($p['lp'] ?? $p['ltp'] ?? 0),
                exchange: $p['exch'] ?? 'NSE',
                product: $p['prd'] ?? 'C',
                raw: $p,
            ))->filter(fn ($s) => $s->quantity !== 0)->values()->all();
        } catch (\Throwable $e) {
            Log::warning('FlatTrade positions read failed', ['err' => $e->getMessage()]);

            return [];
        }
    }

    public function holdings(): array
    {
        try {
            $raw = method_exists($this->service, 'getHoldings')
                ? $this->service->getHoldings()
                : [];
            $rows = $raw['values'] ?? $raw['holdings'] ?? $raw ?? [];

            return collect($rows)->map(fn ($h) => new PositionSnapshot(
                symbol: $h['tsym'] ?? $h['symbol'] ?? '',
                quantity: (int) ($h['holdqty'] ?? $h['qty'] ?? 0),
                avgPrice: (float) ($h['upldprc'] ?? $h['avg_price'] ?? 0),
                ltp: (float) ($h['lp'] ?? 0),
                raw: $h,
            ))->filter(fn ($s) => $s->quantity !== 0)->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function quote(string $symbol, string $exchange = 'NSE'): ?array
    {
        try {
            $search = $this->service->searchScrip($symbol, $exchange);
            $token = $search['values'][0]['token'] ?? null;
            if (! $token) {
                return null;
            }
            $q = $this->service->getQuotes($token, $exchange);
            if (($q['stat'] ?? null) !== 'Ok') {
                return null;
            }

            return [
                'ltp' => (float) ($q['lp'] ?? 0),
                'bid' => isset($q['bp1']) ? (float) $q['bp1'] : null,
                'ask' => isset($q['sp1']) ? (float) $q['sp1'] : null,
                'ts' => time(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function historicalBars(string $symbol, string $interval, int $lookback): array
    {
        try {
            if (! method_exists($this->service, 'getHistoricalData')) {
                return [];
            }
            $raw = $this->service->getHistoricalData($symbol, $interval, $lookback);
            $rows = $raw['values'] ?? $raw ?? [];

            return collect($rows)->map(fn ($r) => new Bar(
                symbol: $symbol,
                timestamp: (int) ($r['time'] ?? $r['ts'] ?? strtotime($r['date'] ?? 'now')),
                open: (float) ($r['into'] ?? $r['open'] ?? 0),
                high: (float) ($r['inth'] ?? $r['high'] ?? 0),
                low: (float) ($r['intl'] ?? $r['low'] ?? 0),
                close: (float) ($r['intc'] ?? $r['close'] ?? 0),
                volume: (int) ($r['intv'] ?? $r['volume'] ?? 0),
                interval: $interval,
            ))->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function availableFunds(): float
    {
        try {
            if (method_exists($this->service, 'getLimits')) {
                $raw = $this->service->getLimits();

                return (float) ($raw['cash'] ?? $raw['available_funds'] ?? $raw['marginused'] ?? 0);
            }
        } catch (\Throwable) {
            // ignore
        }

        return 0.0;
    }

    public function isHealthy(): bool
    {
        try {
            return $this->availableFunds() >= 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
