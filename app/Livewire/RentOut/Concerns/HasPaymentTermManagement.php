<?php

namespace App\Livewire\RentOut\Concerns;

use App\Models\RentOut;
use Livewire\Attributes\On;

trait HasPaymentTermManagement
{
    public $rentOut;

    public function loadRentOut($id = null)
    {
        $this->rentOut = RentOut::with([
            'customer',
            'property',
            'building',
            'group',
            'type',
            'salesman',
            'paymentTerms',
            'securities',
            'cheques',
            'extends',
            'notes.creator',
            'services',
            'utilities',
            'utilityTerms.utility',
            'journals',
        ])->find($id ?? $this->rentOut->id);
    }

    #[On('rent-out-updated')]
    public function refreshRentOut()
    {
        $this->loadRentOut();
    }
}
