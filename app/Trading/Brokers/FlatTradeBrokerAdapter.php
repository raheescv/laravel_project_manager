<?php

namespace App\Trading\Brokers;

use App\Services\FlatTradeService;
use App\Trading\Contracts\BrokerContract;
use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\DataObjects\PositionSnapshot;
use Illuminate\Support\Facades\Cache;
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
            $exchange = $request->exchange ?: 'NSE';
            $tradingSymbol = $this->tradingSymbol($request->symbol);

            // FlatTrade's API blocks pure MKT orders ("ALGO_CHK: MKT Order
            // type not allowed for API order"). Convert every request to a
            // marketable LIMIT — a few bps past LTP — so fills behave like
            // market orders while the broker accepts the call.
            $limitPrice = $this->marketableLimitPrice($request);
            if (! $limitPrice) {
                return OrderResult::failure('Cannot determine limit price (no LTP/price)', [], $this->code());
            }

            $raw = $this->service->placeLimitOrder(
                $exchange,
                $tradingSymbol,
                $request->quantity,
                $limitPrice,
                $direction,
                $request->product,
            );

            if (is_array($raw) && (($raw['stat'] ?? null) === 'Not_Ok' || isset($raw['emsg']))) {
                $err = trim(($raw['emsg'] ?? 'PlaceOrder Not_Ok'));

                return OrderResult::failure('FlatTrade rejected: '.$err, $raw, $this->code());
            }

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

    private function tradingSymbol(string $symbol): string
    {
        $s = strtoupper(trim($symbol));

        return str_ends_with($s, '-EQ') ? $s : $s.'-EQ';
    }

    private function stripEqSuffix(string $symbol): string
    {
        $s = strtoupper(trim($symbol));

        return str_ends_with($s, '-EQ') ? substr($s, 0, -3) : $s;
    }

    /**
     * Compute a limit price that behaves like a market order — buys get a
     * small premium, sells a small discount — so the order fills against
     * the book immediately under normal conditions but stays inside
     * FlatTrade's API rules.
     */
    private function marketableLimitPrice(OrderRequest $request, float $slippageBps = 20.0): ?float
    {
        $base = $request->price && $request->price > 0
            ? (float) $request->price
            : $this->fetchLtp($request->symbol, $request->exchange ?: 'NSE');

        if (! $base || $base <= 0) {
            return null;
        }

        $factor = $request->side === OrderRequest::SIDE_BUY
            ? 1 + ($slippageBps / 10000)
            : 1 - ($slippageBps / 10000);

        return $this->snapToTick($base * $factor, $request->side);
    }

    /**
     * NSE rejects limit prices that aren't a multiple of the scrip's tick size
     * (₹0.05 for virtually all equities). Buys ceil to the next tick so they
     * stay marketable-above-LTP; sells floor to the previous tick so they stay
     * marketable-below-LTP.
     */
    private function snapToTick(float $price, string $side, float $tick = 0.05): float
    {
        if ($tick <= 0) {
            return round($price, 2);
        }
        $ticks = $price / $tick;
        $snapped = $side === OrderRequest::SIDE_BUY ? ceil($ticks) : floor($ticks);

        return round($snapped * $tick, 2);
    }

    private function fetchLtp(string $symbol, string $exchange): ?float
    {
        try {
            $token = $this->resolveToken($symbol);
            if (! $token) {
                return null;
            }
            $q = $this->service->getQuotes($token, $exchange);

            return (float) ($q['lp'] ?? $q['ltp'] ?? 0) ?: null;
        } catch (\Throwable) {
            return null;
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
            $raw = method_exists($this->service, 'getPositionBookAll')
                ? $this->service->getPositionBookAll()
                : [];

            // FlatTrade returns either a top-level array of positions or an
            // error envelope. The error case has no positions to read.
            if (is_array($raw) && (($raw['stat'] ?? null) === 'Not_Ok' || isset($raw['emsg']))) {
                return [];
            }
            $rows = is_array($raw) ? ($raw['values'] ?? (array_is_list($raw) ? $raw : [])) : [];

            return collect($rows)->map(fn ($p) => new PositionSnapshot(
                symbol: $this->stripEqSuffix($p['tsym'] ?? $p['symbol'] ?? ''),
                quantity: (int) ($p['netqty'] ?? $p['daybuyqty'] ?? 0) - (int) ($p['daysellqty'] ?? 0),
                avgPrice: (float) ($p['netavgprc'] ?? $p['daybuyavgprc'] ?? 0),
                ltp: (float) ($p['lp'] ?? $p['ltp'] ?? 0),
                exchange: $p['exch'] ?? 'NSE',
                product: $p['prd'] ?? 'C',
                raw: $p,
            ))->filter(fn ($s) => $s->quantity !== 0 && $s->symbol !== '')->values()->all();
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
            $token = $this->resolveToken($symbol);
            if (! $token) {
                $this->recordBarsError($symbol, 'no_token');

                return [];
            }

            $minutes = $this->intervalMinutes($interval);
            $endTime = time();
            // Pad heavily — TPSeries returns one bar per minutes interval and
            // intraday session is short; lookback × interval is the minimum,
            // 2× covers weekends/holidays.
            $startTime = $endTime - ($minutes * 60 * max($lookback, 1) * 2);

            $raw = $this->service->getTimePriceSeries('NSE', $token, $startTime, $endTime, $minutes);

            if (is_array($raw) && (($raw['stat'] ?? null) === 'Not_Ok' || isset($raw['emsg']))) {
                $err = trim(($raw['emsg'] ?? 'TPSeries Not_Ok').'');
                $this->recordBarsError($symbol, 'api_error: '.$err);

                return [];
            }

            $rows = is_array($raw) ? ($raw['values'] ?? $raw) : [];
            if (! is_array($rows) || empty($rows)) {
                $this->recordBarsError($symbol, 'empty_response');

                return [];
            }

            $bars = collect($rows)
                ->filter(fn ($r) => is_array($r))
                ->map(fn ($r) => new Bar(
                    symbol: $symbol,
                    timestamp: (int) ($r['time'] ?? $r['ssboe'] ?? strtotime($r['stat'] ?? 'now')),
                    open: (float) ($r['into'] ?? $r['open'] ?? 0),
                    high: (float) ($r['inth'] ?? $r['high'] ?? 0),
                    low: (float) ($r['intl'] ?? $r['low'] ?? 0),
                    close: (float) ($r['intc'] ?? $r['close'] ?? 0),
                    volume: (int) ($r['intv'] ?? $r['v'] ?? 0),
                    interval: $interval,
                ))
                ->sortBy(fn (Bar $b) => $b->timestamp)
                ->values()
                ->all();

            if (empty($bars)) {
                $this->recordBarsError($symbol, 'parsed_empty');
            }

            return array_slice($bars, -$lookback);
        } catch (\Throwable $e) {
            $this->recordBarsError($symbol, 'exception: '.$e->getMessage());
            Log::warning('FlatTrade historicalBars failed', ['symbol' => $symbol, 'err' => $e->getMessage()]);

            return [];
        }
    }

    public static function lastBarsError(string $symbol): ?string
    {
        return Cache::get('trading:flat_trade:bars_error:'.strtoupper($symbol));
    }

    private function recordBarsError(string $symbol, string $reason): void
    {
        Cache::put('trading:flat_trade:bars_error:'.strtoupper($symbol), $reason, 120);
    }

    private function resolveToken(string $symbol): ?string
    {
        $clean = strtoupper(trim($symbol));
        if ($clean === '') {
            return null;
        }

        return Cache::remember("trading:flat_trade:token:{$clean}", 86400, function () use ($clean) {
            try {
                $stext = str_ends_with($clean, '-EQ') ? $clean : ($clean.'-EQ');
                $res = $this->service->searchScrip($stext, 'NSE');
                $matches = $res['values'] ?? [];
                foreach ($matches as $m) {
                    if (! is_array($m)) {
                        continue;
                    }
                    $tsym = strtoupper($m['tsym'] ?? '');
                    if ($tsym === $clean || $tsym === $clean.'-EQ') {
                        return (string) ($m['token'] ?? '');
                    }
                }

                return $matches[0]['token'] ?? null;
            } catch (\Throwable) {
                return;
            }
        });
    }

    private function intervalMinutes(string $interval): int
    {
        $interval = strtolower(trim($interval));
        if (str_ends_with($interval, 'm')) {
            return max(1, (int) substr($interval, 0, -1));
        }
        if (str_ends_with($interval, 'h')) {
            return max(1, (int) substr($interval, 0, -1)) * 60;
        }
        if ($interval === '1d' || $interval === 'd') {
            return 1440;
        }

        return max(1, (int) $interval);
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
