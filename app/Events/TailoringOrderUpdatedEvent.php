<?php

namespace App\Events;

use App\Models\TailoringOrder;
use Illuminate\Queue\SerializesModels;

class TailoringOrderUpdatedEvent
{
    use SerializesModels;

    public function __construct(public string $action, public TailoringOrder $model) {}
}
