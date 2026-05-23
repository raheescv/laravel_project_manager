<?php

namespace App\Trading\DataObjects;

/**
 * Immutable OHLCV bar — the atom of price history.
 */
final class Bar
{
    public function __construct(
        public readonly string $symbol,
        public readonly int $timestamp,
        public readonly float $open,
        public readonly float $high,
        public readonly float $low,
        public readonly float $close,
        public readonly int $volume,
        public readonly string $interval = '1m',
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            symbol: $row['symbol'],
            timestamp: (int) ($row['timestamp'] ?? $row['ts'] ?? time()),
            open: (float) $row['open'],
            high: (float) $row['high'],
            low: (float) $row['low'],
            close: (float) $row['close'],
            volume: (int) ($row['volume'] ?? 0),
            interval: $row['interval'] ?? '1m',
        );
    }

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'timestamp' => $this->timestamp,
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
            'volume' => $this->volume,
            'interval' => $this->interval,
        ];
    }
}
