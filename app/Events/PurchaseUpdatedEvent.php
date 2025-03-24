<?php

namespace App\Events;

use App\Models\Purchase;
use Illuminate\Queue\SerializesModels;

class PurchaseUpdatedEvent
{
    use SerializesModels;

    public function __construct(public string $action, public Purchase $purchase) {}
}
