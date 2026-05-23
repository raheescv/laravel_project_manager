<?php

namespace App\Trading\Events;

use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OrderRequest $request,
        public OrderResult $result,
    ) {}
}
