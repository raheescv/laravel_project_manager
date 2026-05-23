<?php

namespace App\Trading\DataObjects;

use Illuminate\Support\Str;

/**
 * Broker-agnostic intent to place an order.
 *
 * Every order in the system starts as one of these — RiskGate inspects it,
 * the BrokerContract executes it, and an OrderResult comes back.
 */
final class OrderRequest
{
    public const SIDE_BUY = 'BUY';

    public const SIDE_SELL = 'SELL';

    public const TYPE_MARKET = 'MARKET';

    public const TYPE_LIMIT = 'LIMIT';

    public function __construct(
        public readonly string $symbol,
        public readonly string $side,
        public readonly int $quantity,
        public readonly string $type = self::TYPE_MARKET,
        public readonly ?float $price = null,
        public readonly ?float $stopLoss = null,
        public readonly ?float $target = null,
        public readonly string $exchange = 'NSE',
        public readonly string $product = 'C',
        public readonly ?string $strategyCode = null,
        public readonly ?string $idempotencyKey = null,
        public readonly array $meta = [],
    ) {}

    public function withIdempotencyKey(?string $key = null): self
    {
        return new self(
            symbol: $this->symbol,
            side: $this->side,
            quantity: $this->quantity,
            type: $this->type,
            price: $this->price,
            stopLoss: $this->stopLoss,
            target: $this->target,
            exchange: $this->exchange,
            product: $this->product,
            strategyCode: $this->strategyCode,
            idempotencyKey: $key ?? (string) Str::uuid(),
            meta: $this->meta,
        );
    }

    public function notionalValue(): float
    {
        return (float) ($this->price ?? 0) * $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'side' => $this->side,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'price' => $this->price,
            'stop_loss' => $this->stopLoss,
            'target' => $this->target,
            'exchange' => $this->exchange,
            'product' => $this->product,
            'strategy_code' => $this->strategyCode,
            'idempotency_key' => $this->idempotencyKey,
            'meta' => $this->meta,
        ];
    }
}
