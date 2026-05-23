<?php

namespace App\Trading\DataObjects;

final class PositionSnapshot
{
    public function __construct(
        public readonly string $symbol,
        public readonly int $quantity,
        public readonly float $avgPrice,
        public readonly float $ltp,
        public readonly string $exchange = 'NSE',
        public readonly string $product = 'C',
        public readonly array $raw = [],
    ) {}

    public function pnlAbsolute(): float
    {
        return ($this->ltp - $this->avgPrice) * $this->quantity;
    }

    public function pnlPercent(): float
    {
        if ($this->avgPrice <= 0) {
            return 0.0;
        }

        return (($this->ltp - $this->avgPrice) / $this->avgPrice) * 100.0;
    }

    public function notionalValue(): float
    {
        return $this->ltp * $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'quantity' => $this->quantity,
            'avg_price' => $this->avgPrice,
            'ltp' => $this->ltp,
            'pnl_absolute' => $this->pnlAbsolute(),
            'pnl_percent' => $this->pnlPercent(),
            'exchange' => $this->exchange,
            'product' => $this->product,
        ];
    }
}
