<?php

namespace App\Livewire\PurchaseRequest;

use App\Models\PurchaseRequest;
use Livewire\Component;

class View extends Component
{
    public PurchaseRequest $purchase_request;

    public function mount(int $purchase_request_id)
    {
        $this->purchase_request = PurchaseRequest::with('products.product', 'creator')
            ->findOrFail($purchase_request_id);
    }

    public function render()
    {
        return view('livewire.purchase-request.view');
    }
}
