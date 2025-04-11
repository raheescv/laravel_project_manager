<?php

namespace App\Livewire\SaleReturn;

use App\Actions\SaleReturn\Item\DeleteAction;
use Livewire\Component;

class ViewItems extends Component
{
    protected $listeners = [
        'SaleReturn-View-Items-Component' => 'open',
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
                $this->singleCartCalculator($value['inventory_id']);
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

    public function removeItem($index)
    {
        try {
            $id = $this->items[$index]['id'] ?? '';
            if ($id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            unset($this->items[$index]);
            $this->mount($this->status, $this->items);
            $this->dispatch('SaleReturn-Delete-Sync-Items-Component', $index);
            $this->dispatch('success', ['message' => 'item removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function submit()
    {
        $this->dispatch('SaleReturn-Edited-Items-Component', $this->items);
        $this->dispatch('ToggleViewItemsModal');
    }

    public function render()
    {
        return view('livewire.sale-return.view-items');
    }
}
