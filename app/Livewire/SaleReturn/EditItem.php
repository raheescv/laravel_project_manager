<?php

namespace App\Livewire\SaleReturn;

use App\Models\Product;
use Livewire\Component;

class EditItem extends Component
{
    protected $listeners = [
        'SaleReturn-Edit-Item-Component' => 'open',
    ];

    public $index;

    public $item;

    public $units = [];

    public function mount($index = '', $item = [])
    {
        $this->index = $index;
        $this->item = $item;
    }

    public function open($index, $item)
    {
        $this->index = $index;
        $this->item = $item;
        $this->loadUnits();
        $this->dispatch('ToggleEditItemModal');
    }

    public function loadUnits()
    {
        $product = Product::with(['unit', 'units.subUnit'])->find($this->item['product_id']);
        if ($product) {
            $this->units = collect([
                [
                    'id' => $product->unit_id,
                    'name' => $product->unit->name ?? 'Base Unit',
                    'conversion_factor' => 1,
                ],
            ])->concat($product->units->map(function ($pu) {
                return [
                    'id' => $pu->sub_unit_id,
                    'name' => $pu->subUnit->name ?? '',
                    'conversion_factor' => $pu->conversion_factor,
                ];
            }))->toArray();
        } else {
            $this->units = [];
        }
    }

    public function handleUnitChange()
    {
        $selectedUnit = collect($this->units)->firstWhere('id', $this->item['unit_id']);
        if ($selectedUnit) {
            $this->item['unit_name'] = $selectedUnit['name'];
            $this->item['conversion_factor'] = $selectedUnit['conversion_factor'];
        }
        $this->singleCartCalculator();
    }

    public function updated($key, $value)
    {
        $this->singleCartCalculator();
    }

    public function singleCartCalculator()
    {
        $this->item['tax'] = $this->item['tax'] ?? 0;
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
