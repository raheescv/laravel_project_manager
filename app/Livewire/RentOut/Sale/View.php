<?php

namespace App\Livewire\RentOut\Sale;

use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public function mount($id)
    {
        $this->loadRentOut($id);
    }

    public function render()
    {
        return view('livewire.rent-out.sale.view');
    }
}
