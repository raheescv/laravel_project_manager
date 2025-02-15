<?php

namespace App\Livewire\Sale;

use App\Models\Sale;
use Livewire\Component;

class DraftTable extends Component
{
    protected $listeners = [
        'Sale-View-DraftTable-Component' => 'open',
    ];

    public $search;

    public function open()
    {
        $this->mount();
        $this->dispatch('ToggleDraftTableModal');
    }

    public function mount() {}

    public function render()
    {
        $lists = Sale::join('accounts', 'accounts.id', '=', 'sales.account_id')
            ->when($this->search ?? '', function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('sales.invoice_no', 'like', "%{$value}%")
                        ->orWhere('sales.date', 'like', "%{$value}%")
                        ->orWhere('sales.grand_total', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%");
                });
            })
            ->draft()
            ->latest('sales.updated_at')
            ->select(
                'sales.id',
                'date',
                'invoice_no',
                'customer_name',
                'customer_mobile',
                'grand_total',
                'accounts.name',
            )
            ->get();

        return view('livewire.sale.draft-table', compact('lists'));
    }
}
