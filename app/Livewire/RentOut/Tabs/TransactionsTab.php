<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOutTransaction;
use Livewire\Attributes\On;
use Livewire\Component;

class TransactionsTab extends Component
{
    public $rentOutId;

    public $sortField = 'date';

    public $sortDirection = 'desc';

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
    public function refresh() {}

    public function render()
    {
        $payments = RentOutTransaction::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        return view('livewire.rent-out.tabs.transactions-tab', [
            'payments' => $payments,
        ]);
    }
}
