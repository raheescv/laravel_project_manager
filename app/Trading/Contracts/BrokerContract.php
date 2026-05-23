<?php

namespace App\Trading\Contracts;

use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\DataObjects\PositionSnapshot;

/**
 * Unified contract every broker adapter must implement.
 *
 * Any code that places orders, fetches positions, or pulls quotes
 * MUST go through this interface — never call broker SDKs directly.
 */
interface BrokerContract
{
    public function code(): string;

    public function placeOrder(OrderRequest $request): OrderResult;

    public function cancelOrder(string $orderId): bool;

    /** @return PositionSnapshot[] */
    public function positions(): array;

    /** @return PositionSnapshot[] */
    public function holdings(): array;

    /**
     * Latest traded price + bid/ask snapshot for a symbol.
     *
     * @return array{ltp: float, bid?: float, ask?: float, ts: int}|null
     */
    public function quote(string $symbol, string $exchange = 'NSE'): ?array;

    /**
     * Historical OHLCV bars.
     *
     * @return \App\Trading\DataObjects\Bar[]
     */
    public function historicalBars(string $symbol, string $interval, int $lookback): array;

    /**
     * Available funds (rupees) for new trades.
     */
    public function availableFunds(): float;

    public function isHealthy(): bool;
}
