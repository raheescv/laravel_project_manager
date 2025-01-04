<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Queue\SerializesModels;

class InventoryActionOccurred
{
    use SerializesModels;

    public function __construct(public string $action, public Inventory $newInventory, public ?Inventory $oldInventory = null) {}
}
