<?php

namespace App\Livewire\RentOut\Rent;

use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public function mount($id)
    {
        $this->loadRentOut($id);
        $this->resetSingleTerm();
    }

    protected function defaultTermLabel(): string
    {
        return 'rent payment';
    }

    public function render()
    {
        return view('livewire.rent-out.rent.view');
    }
}
