<?php

namespace App\Trading\DataObjects;

/**
 * A Strategy's decision for a single symbol at a single moment.
 */
final class Signal
{
    public const ACTION_BUY = 'BUY';

    public const ACTION_SELL = 'SELL';

    public const ACTION_HOLD = 'HOLD';

    public const ACTION_FLATTEN = 'FLATTEN';

    public function __construct(
        public readonly string $symbol,
        public readonly string $action,
        public readonly float $confidence = 0.5,
        public readonly float $score = 0.0,
        public readonly ?float $suggestedPrice = null,
        public readonly ?float $stopLoss = null,
        public readonly ?float $target = null,
        public readonly int $suggestedQty = 0,
        public readonly array $meta = [],
    ) {}

    public function isActionable(): bool
    {
        return in_array($this->action, [self::ACTION_BUY, self::ACTION_SELL, self::ACTION_FLATTEN], true);
    }

    public static function hold(string $symbol, array $meta = []): self
    {
        return new self(symbol: $symbol, action: self::ACTION_HOLD, meta: $meta);
    }

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'action' => $this->action,
            'confidence' => $this->confidence,
            'score' => $this->score,
            'suggested_price' => $this->suggestedPrice,
            'stop_loss' => $this->stopLoss,
            'target' => $this->target,
            'suggested_qty' => $this->suggestedQty,
            'meta' => $this->meta,
        ];
    }
}
