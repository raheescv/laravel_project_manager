<?php

namespace App\Trading\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CircuitBreakerTripped
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $reason,
        public array $context = [],
    ) {}
}
