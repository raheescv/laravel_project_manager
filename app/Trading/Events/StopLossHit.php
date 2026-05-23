<?php

namespace App\Trading\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StopLossHit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $symbol,
        public float $entryPrice,
        public float $exitPrice,
        public int $quantity,
        public ?string $strategyCode = null,
    ) {}
}
