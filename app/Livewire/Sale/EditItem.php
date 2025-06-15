<?php

namespace App\Livewire\Sale;

use App\Models\User;
use Livewire\Component;

class EditItem extends Component
{
    protected $listeners = [
        'Sale-Edit-Item-Component' => 'open',
    ];

    public $index;

    public $item;

    public function mount($index = '', $item = [])
    {
        $this->index = $index;
        $this->item = $item;
        if ($item) {
            $employee = User::find($item['employee_id']);
            $assistant = User::find($item['assistant_id'] ?? '');
            $this->dispatch('SelectEmployeeFromDropDown', ['name' => $employee->name, 'id' => $employee->id]);
            if ($assistant) {
                $this->dispatch('SelectAssistantFromDropDown', ['name' => $assistant->name, 'id' => $assistant->id]);
            } else {
                $this->dispatch('SelectAssistantFromDropDown', ['name' => '', 'id' => '']);
            }
        }
    }

    public function open($index, $item)
    {
        $this->mount($index, $item);
        $this->dispatch('ToggleEditItemModal');
    }

    public function updated($key, $value)
    {
        if (strpos($key, 'item.') === 0) {
            $property = substr($key, strlen('item.'));
            if (! is_numeric($value)) {
                $this->item[$property] = 0;
            }
        }
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
        $this->dispatch('Sale-Edited-Item-Component', $this->index, $this->item);
        $this->dispatch('ToggleEditItemModal');
    }

    public function render()
    {
        return view('livewire.sale.edit-item');
    }
}
