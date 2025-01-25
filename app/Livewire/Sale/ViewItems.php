<?php

namespace App\Livewire\Sale;

use Livewire\Component;

class ViewItems extends Component
{
    protected $listeners = [
        'Sale-View-Items-Component' => 'open',
    ];

    public $status;

    public $items;

    public function mount($status = null, $items = [])
    {
        $this->status = $status;
        $this->items = $items;
    }

    public function open($status, $items)
    {
        $this->mount($status, $items);
        $this->dispatch('ToggleViewItemsModal');
    }

    public function updated($key, $value)
    {
        if (preg_match('/^items\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                $this->items[$index][$indexes[2]] = 0;
            }
            $this->cartCalculator($index);
        }
    }

    public function cartCalculator($key = null)
    {
        if ($key) {
            $this->singleCartCalculator($key);
        } else {
            foreach ($this->items as $value) {
                $key = $value['employee_id'].'-'.$value['inventory_id'];
                $this->singleCartCalculator($key);
            }
        }
    }

    public function singleCartCalculator($key)
    {
        $gross_amount = $this->items[$key]['unit_price'] * $this->items[$key]['quantity'];
        $net_amount = $gross_amount - $this->items[$key]['discount'];
        $tax_amount = $net_amount * $this->items[$key]['tax'] / 100;

        $this->items[$key]['gross_amount'] = round($gross_amount, 2);
        $this->items[$key]['net_amount'] = round($net_amount, 2);
        $this->items[$key]['tax_amount'] = round($tax_amount, 2);
        $this->items[$key]['total'] = round($net_amount + $tax_amount, 2);
    }

    public function submit()
    {
        $this->dispatch('Sale-Edited-Items-Component', $this->items);
        $this->dispatch('ToggleViewItemsModal');
    }

    public function render()
    {
        return view('livewire.sale.view-items');
    }
}
