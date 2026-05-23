<?php

namespace App\Trading\Brokers;

use App\Services\FyersService;
use App\Trading\Contracts\BrokerContract;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\DataObjects\PositionSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * Adapter for Fyers. Mirrors FlatTradeBrokerAdapter — calls existing
 * FyersService methods defensively (method_exists guards) so we don't
 * break if a method is missing.
 */
class FyersBrokerAdapter implements BrokerContract
{
    public function __construct(private FyersService $service) {}

    public function code(): string
    {
        return 'fyers';
    }

    public function placeOrder(OrderRequest $request): OrderResult
    {
        try {
            if (! method_exists($this->service, 'placeOrder')) {
                return OrderResult::failure('FyersService::placeOrder missing', brokerCode: $this->code());
            }

            $raw = $this->service->placeOrder([
                'symbol' => $request->symbol,
                'qty' => $request->quantity,
                'side' => $request->side === OrderRequest::SIDE_BUY ? 1 : -1,
                'type' => $request->type === OrderRequest::TYPE_LIMIT ? 1 : 2,
                'price' => $request->price ?? 0,
                'stopLoss' => $request->stopLoss ?? 0,
                'productType' => $request->product,
            ]);

            $orderId = $raw['id'] ?? $raw['order_id'] ?? null;
            if (! $orderId) {
                return OrderResult::failure('Broker did not return an order id', $raw, $this->code());
            }

            return OrderResult::ok(orderId: (string) $orderId, brokerCode: $this->code(), raw: $raw);
        } catch (\Throwable $e) {
            Log::error('Fyers order failed', ['err' => $e->getMessage()]);

            return OrderResult::failure($e->getMessage(), brokerCode: $this->code());
        }
    }

    public function cancelOrder(string $orderId): bool
    {
        try {
            return method_exists($this->service, 'cancelOrder')
                ? (bool) $this->service->cancelOrder($orderId)
                : false;
        } catch (\Throwable) {
            return false;
        }
    }

    public function positions(): array
    {
        try {
            $raw = method_exists($this->service, 'getPositions') ? $this->service->getPositions() : [];
            $rows = $raw['netPositions'] ?? $raw['positions'] ?? $raw ?? [];

            return collect($rows)->map(fn ($p) => new PositionSnapshot(
                symbol: $p['symbol'] ?? $p['tsym'] ?? '',
                quantity: (int) ($p['netQty'] ?? $p['netqty'] ?? 0),
                avgPrice: (float) ($p['avgPrice'] ?? $p['netavgprc'] ?? 0),
                ltp: (float) ($p['ltp'] ?? $p['lp'] ?? 0),
                raw: $p,
            ))->filter(fn ($s) => $s->quantity !== 0)->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function holdings(): array
    {
        return $this->positions();
    }

    public function quote(string $symbol, string $exchange = 'NSE'): ?array
    {
        try {
            if (method_exists($this->service, 'getQuotes')) {
                $q = $this->service->getQuotes($symbol);
                if (! $q) {
                    return null;
                }

                return [
                    'ltp' => (float) ($q['ltp'] ?? $q['lp'] ?? 0),
                    'bid' => isset($q['bid']) ? (float) $q['bid'] : null,
                    'ask' => isset($q['ask']) ? (float) $q['ask'] : null,
                    'ts' => time(),
                ];
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    public function historicalBars(string $symbol, string $interval, int $lookback): array
    {
        try {
            if (! method_exists($this->service, 'getHistoricalData')) {
                return [];
            }
            $raw = $this->service->getHistoricalData($symbol, $interval, $lookback);
            $rows = $raw['candles'] ?? $raw ?? [];

            return collect($rows)->map(function ($r) use ($symbol, $interval) {
                if (is_array($r) && array_is_list($r) && count($r) >= 6) {
                    // Fyers candle format: [ts, o, h, l, c, v]
                    return new Bar($symbol, (int) $r[0], (float) $r[1], (float) $r[2], (float) $r[3], (float) $r[4], (int) $r[5], $interval);
                }

                return Bar::fromArray(array_merge(['symbol' => $symbol, 'interval' => $interval], $r));
            })->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function availableFunds(): float
    {
        try {
            if (method_exists($this->service, 'getFunds')) {
                $raw = $this->service->getFunds();

                return (float) ($raw['available_funds'] ?? $raw['fund_limit'][0]['equityAmount'] ?? 0);
            }
        } catch (\Throwable) {
            // ignore
        }

        return 0.0;
    }

    public function isHealthy(): bool
    {
        return $this->availableFunds() >= 0;
    }
}
