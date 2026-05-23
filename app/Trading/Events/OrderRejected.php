<?php

namespace App\Trading\Events;

use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OrderRequest $request,
        public RiskDecision $decision,
    ) {}
}
