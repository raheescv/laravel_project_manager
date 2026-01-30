<?php

namespace App\Livewire\SaleReturn;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $sale_return_visible_column;

    public function mount()
    {
        $config = Configuration::where('key', 'sale_return_visible_column')->value('value');
        $this->sale_return_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'reference_no' => true,
            'branch_id' => false,
            'customer' => true,
            'gross_amount' => true,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => true,
            'grand_total' => true,
            'paid' => false,
            'balance' => false,
            'status' => false,
        ];
    }

    public function toggleColumn($column)
    {
        $this->sale_return_visible_column[$column] = ! $this->sale_return_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'sale_return_visible_column'], ['value' => json_encode($this->sale_return_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->sale_return_visible_column = $this->getDefaultColumns();
        Configuration::updateOrCreate(['key' => 'sale_return_visible_column'], ['value' => json_encode($this->sale_return_visible_column)]);
    }

    public function render()
    {
        return view('livewire.sale-return.column-visibility');
    }
}
