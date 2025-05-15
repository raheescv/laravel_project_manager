<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\Item\DeleteAction;
use Livewire\Component;

class ViewItems extends Component
{
    protected $listeners = [
        'Sale-View-Items-Component' => 'open',
    ];

    public $status = null;

    public $items = [];

    protected function rules()
    {
        return [
            'items.*.unit_price' => ['numeric', 'min:0'],
            'items.*.quantity' => ['numeric', 'min:1'],
            'items.*.discount' => ['numeric', 'min:0'],
            'items.*.tax' => ['numeric', 'min:0', 'max:50'],
        ];
    }

    public function mount($status = null, $items = [])
    {
        $this->status = $status;
        $this->items = is_array($items) ? $this->sanitizeItems($items) : [];
    }

    protected function sanitizeItems(array $items): array
    {
        return array_map(function ($item) {
            return array_merge([
                'unit_price' => 0,
                'quantity' => 1,
                'discount' => 0,
                'tax' => 0,
                'gross_amount' => 0,
                'net_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
            ], $item);
        }, $items);
    }

    public function open($status, $items)
    {
        $this->mount($status, $items);
        $this->dispatch('ToggleViewItemsModal');
    }

    public function updated($key, $value)
    {
        if (! preg_match('/^items\.(\d+)-(\d+)\.(unit_price|quantity|discount|tax)$/', $key, $matches)) {
            return;
        }
        $index = $matches[1].'-'.$matches[2];
        $field = $matches[3];

        $this->items[$index][$field] = is_numeric($value) ? (float) $value : 0;
        $this->cartCalculator($index);
    }

    public function cartCalculator($key = null)
    {
        if ($key !== null && isset($this->items[$key])) {
            $this->singleCartCalculator($key);
        } else {
            foreach (array_keys($this->items) as $itemKey) {
                $this->singleCartCalculator($itemKey);
            }
        }
    }

    protected function singleCartCalculator($key)
    {
        if (! isset($this->items[$key])) {
            return;
        }

        $item = &$this->items[$key];

        $unit_price = (float) ($item['unit_price'] ?? 0);
        $quantity = (float) ($item['quantity'] ?? 1);
        $discount = (float) ($item['discount'] ?? 0);
        $tax_rate = (float) ($item['tax'] ?? 0);

        $gross_amount = $unit_price * $quantity;
        $net_amount = $gross_amount - $discount;
        $tax_amount = $net_amount * ($tax_rate / 100);

        $item['gross_amount'] = round($gross_amount, 2);
        $item['net_amount'] = round($net_amount, 2);
        $item['tax_amount'] = round($tax_amount, 2);
        $item['total'] = round($net_amount + $tax_amount, 2);
    }

    public function submit()
    {
        $this->cartCalculator(); // Ensure all calculations are up to date
        $this->dispatch('Sale-Edited-Items-Component', $this->items);
        $this->dispatch('ToggleViewItemsModal');
    }

    public function removeItem($index)
    {
        try {
            if (isset($this->items[$index]['id'])) {
                $response = (new DeleteAction())->execute($this->items[$index]['id']);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            unset($this->items[$index]);

            $this->dispatch('Sale-Delete-Sync-Items-Component', $index);
            $this->dispatch('success', ['message' => 'Item removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.sale.view-items');
    }
}
