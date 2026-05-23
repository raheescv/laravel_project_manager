<?php

namespace App\Trading\Brokers;

use App\Trading\Contracts\BrokerContract;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;

/**
 * Stub adapter for Zerodha Kite Connect.
 *
 * Filled out enough to slot into the BrokerManager — real HTTP calls
 * left as TODOs since the project doesn't ship a KiteService yet.
 * Keeps the interface intact so dashboards and the strategy layer can
 * already see "kite" as an available broker option.
 */
class KiteBrokerAdapter implements BrokerContract
{
    public function code(): string
    {
        return 'kite';
    }

    public function placeOrder(OrderRequest $request): OrderResult
    {
        return OrderResult::failure('Kite adapter not configured yet', brokerCode: $this->code());
    }

    public function cancelOrder(string $orderId): bool
    {
        return false;
    }

    public function positions(): array
    {
        return [];
    }

    public function holdings(): array
    {
        return [];
    }

    public function quote(string $symbol, string $exchange = 'NSE'): ?array
    {
        return null;
    }

    public function historicalBars(string $symbol, string $interval, int $lookback): array
    {
        return [];
    }

    public function availableFunds(): float
    {
        return 0.0;
    }

    public function isHealthy(): bool
    {
        return false;
    }
}
