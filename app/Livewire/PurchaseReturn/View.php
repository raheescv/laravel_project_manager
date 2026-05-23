<?php

namespace App\Livewire\PurchaseReturn;

use App\Models\InventoryLog;
use App\Models\PurchaseReturn;
use Livewire\Component;

class View extends Component
{
    public $purchaseReturn;

    public $inventory_logs = [];

    public function mount($id)
    {
        $this->purchaseReturn = PurchaseReturn::with([
            'items.product:id,name',
            'items.unit:id,name',
            'account:id,name,email,mobile',
            'branch:id,name',
            'payments.paymentMethod:id,name',
            'journals.entries.account:id,name',
            'createdUser:id,name',
            'updatedUser:id,name',
            'cancelledUser:id,name',
        ])->findOrFail($id);

        $this->inventory_logs = InventoryLog::with('product:id,name')
            ->where('model', 'PurchaseReturn')
            ->where('model_id', $id)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function render()
    {
        return view('livewire.purchase-return.view');
    }
}
