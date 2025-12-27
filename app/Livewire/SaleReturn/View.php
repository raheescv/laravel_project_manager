<?php

namespace App\Livewire\SaleReturn;

use App\Models\SaleReturn;
use Livewire\Component;

class View extends Component
{
    public $table_id;

    public $items = [];

    public $payments = [];

    public $sale_return;

    public $sale_returns = [];

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if ($this->table_id) {
            $this->sale_return = SaleReturn::with('account:id,name', 'branch:id,name', 'items.product:id,name', 'items.employee:id,name', 'createdUser:id,name', 'updatedUser:id,name')->find($this->table_id);
            if (!$this->sale_return) {
                return redirect()->route('sale_return::index');
            }
            $this->sale_returns = $this->sale_return->toArray();
            $item_ids = [];
            $this->items = $this->sale_return->items
                ->mapWithKeys(function ($item) use (&$item_ids) {
                    $key = $item['employee_id'] . '-' . $item['inventory_id'];
                    $item_ids[] = $item['id'];

                    return [
                        $key => [
                            'id' => $item['id'],
                            'key' => $key,
                            'sale_id' => $item->saleItem?->sale_id,
                            'invoice_no' => $item->saleItem?->sale?->invoice_no,
                            'inventory_id' => $item['inventory_id'],
                            'product_id' => $item['product_id'],
                            'employee_id' => $item['employee_id'],
                            'name' => $item['name'],
                            'employee_name' => $item['employee_name'],
                            'tax_amount' => $item['tax_amount'],
                            'unit_price' => $item['unit_price'],
                            'quantity' => round($item['quantity'], 3),
                            'gross_amount' => $item['gross_amount'],
                            'discount' => $item['discount'],
                            'tax' => $item['tax'],
                            'total' => $item['total'],
                            'effective_total' => $item['effective_total'],
                            'created_by' => $item['created_by'],
                        ],
                    ];
                })
                ->toArray();
        }
    }

    public function render()
    {
        return view('livewire.sale-return.view');
    }
}
