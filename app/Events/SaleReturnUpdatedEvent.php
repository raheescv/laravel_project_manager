<?php

namespace App\Events;

use App\Models\SaleReturn;
use Illuminate\Queue\SerializesModels;

class SaleReturnUpdatedEvent
{
    use SerializesModels;

    public function __construct(public string $action, public SaleReturn $model) {}
}
