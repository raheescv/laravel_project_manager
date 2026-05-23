<?php

namespace App\Trading\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PositionClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $symbol,
        public float $pnl,
        public float $pnlPercent,
        public int $quantity,
        public ?string $strategyCode = null,
    ) {}
}
