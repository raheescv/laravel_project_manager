<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\Account;
use App\Models\RentOutPayment;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicesTab extends Component
{
    public $rentOutId;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    public array $selectedPayments = [];

    public bool $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[On('rent-out-updated')]
    public function refresh()
    {
        $this->selectedPayments = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedPayments = RentOutPayment::where('rent_out_id', $this->rentOutId)
                ->whereIn('source', ['Service', 'ServiceCharge'])
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedPayments = [];
        }
    }

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

    public function editPayment($paymentId)
    {
        $this->dispatch('edit-service-payment', paymentId: $paymentId);
    }

    public function deleteSelected()
    {
        if (empty($this->selectedPayments)) {
            $this->dispatch('error', message: 'No payments selected.');

            return;
        }

        RentOutPayment::whereIn('id', $this->selectedPayments)
            ->where('rent_out_id', $this->rentOutId)
            ->delete();

        $this->selectedPayments = [];
        $this->selectAll = false;
        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: 'Selected service payments deleted.');
    }

    public function render()
    {
        $servicePayments = RentOutPayment::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        // Resolve category IDs to account names
        $categoryIds = $servicePayments->pluck('category')->filter()->unique()->values()->toArray();
        $categoryNames = Account::whereIn('id', $categoryIds)->pluck('name', 'id')->toArray();

        $categorySummary = RentOutPayment::where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->selectRaw('category, sum(credit) as credit, sum(debit) as debit')
            ->groupBy('category')
            ->get();

        return view('livewire.rent-out.tabs.services-tab', [
            'servicePayments' => $servicePayments,
            'categorySummary' => $categorySummary,
            'categoryNames' => $categoryNames,
        ]);
    }
}
