<?php

namespace App\Livewire\PurchaseReturn;

use App\Models\PurchaseReturn;
use Livewire\Component;

class View extends Component
{
    public $purchaseReturn;

    public function mount($id)
    {
        $this->purchaseReturn = PurchaseReturn::with(['items.product', 'items.unit', 'account', 'payments.paymentMethod'])
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.purchase-return.view');
    }
}
