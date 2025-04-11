<?php

namespace App\Livewire\SaleReturn;

use Livewire\Component;

class EditItem extends Component
{
    protected $listeners = [
        'SaleReturn-Edit-Item-Component' => 'open',
    ];

    public $index;

    public $item;

    public function mount($index = '', $item = [])
    {
        $this->index = $index;
        $this->item = $item;
    }

    public function open($index, $item)
    {
        $this->mount($index, $item);
        $this->dispatch('ToggleEditItemModal');
    }

    public function updated($key, $value)
    {
        $this->singleCartCalculator();
    }

    public function singleCartCalculator()
    {
        $gross_amount = $this->item['unit_price'] * $this->item['quantity'];
        $net_amount = $gross_amount - $this->item['discount'];
        $tax_amount = $net_amount * $this->item['tax'] / 100;

        $this->item['gross_amount'] = round($gross_amount, 2);
        $this->item['net_amount'] = round($net_amount, 2);
        $this->item['tax_amount'] = round($tax_amount, 2);
        $this->item['total'] = round($net_amount + $tax_amount, 2);
    }

    public function submit()
    {
        $this->dispatch('SaleReturn-Edited-Item-Component', $this->index, $this->item);
        $this->dispatch('ToggleEditItemModal');
    }

    public function render()
    {
        return view('livewire.sale-return.edit-item');
    }
}
