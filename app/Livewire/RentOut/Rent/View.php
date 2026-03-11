<?php

namespace App\Livewire\RentOut\Rent;

use App\Models\RentOut;
use Livewire\Component;

class View extends Component
{
    public $rentOut;

    public function mount($id)
    {
        $this->rentOut = RentOut::with([
            'customer',
            'property',
            'building',
            'group',
            'salesman',
            'paymentTerms',
            'securities',
        ])->find($id);
    }

    public function render()
    {
        return view('livewire.rent-out.rent.view');
    }
}
