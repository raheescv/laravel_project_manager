<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOutPayment;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicesTab extends Component
{
    public $rentOutId;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openServiceModal()
    {
        $this->dispatch('open-service-modal', rentOutId: $this->rentOutId);
    }

    public function openServiceChargeModal()
    {
        $this->dispatch('open-service-charge-modal', rentOutId: $this->rentOutId);
    }

    public function openServicePaymentModal()
    {
        $this->dispatch('open-service-payment-modal', rentOutId: $this->rentOutId);
    }

    public function render()
    {
        $servicePayments = RentOutPayment::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->orderBy('date', 'desc')
            ->get();

        $categorySummary = RentOutPayment::where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->selectRaw('category, sum(credit) as credit, sum(debit) as debit')
            ->groupBy('category')
            ->get();

        return view('livewire.rent-out.tabs.services-tab', [
            'servicePayments' => $servicePayments,
            'categorySummary' => $categorySummary,
        ]);
    }
}
