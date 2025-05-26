<?php

namespace App\Events;

use App\Models\PurchaseReturn;
use Illuminate\Queue\SerializesModels;

class PurchaseReturnUpdatedEvent
{
    use SerializesModels;

    public function __construct(public string $action, public PurchaseReturn $purchaseReturn) {}
}
