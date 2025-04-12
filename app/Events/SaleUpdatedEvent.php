<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Queue\SerializesModels;

class SaleUpdatedEvent
{
    use SerializesModels;

    public function __construct(public string $action, public Sale $model) {}
}
