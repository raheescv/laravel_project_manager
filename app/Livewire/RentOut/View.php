<?php

namespace App\Livewire\RentOut;

use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use App\Support\RentOutConfig;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public $agreementType = 'lease';

    public function mount($id, $agreementType = 'lease')
    {
        $this->agreementType = $agreementType;
        $this->loadRentOut($id);
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    public function render()
    {
        return view('livewire.rent-out.view', [
            'config' => $this->config,
        ]);
    }
}
