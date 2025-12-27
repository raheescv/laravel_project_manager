<?php

namespace App\Livewire\Sale;

use App\Models\Account;
use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $sale_visible_column;

    public $paymentMethods = [];

    public function mount()
    {
        $config = Configuration::where('key', 'sale_visible_column')->value('value');
        $this->sale_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'created_at' => false,
            'reference_no' => false,
            'branch_id' => false,
            'created_by' => true,
            'customer' => true,
            'payment_method_name' => true,
            'gross_amount' => false,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => false,
            'freight' => false,
            'grand_total' => true,
            'paid' => true,
            'balance' => true,
            'status' => false,
        ];
    }

    public function toggleColumn($column)
    {
        $this->sale_visible_column[$column] = ! $this->sale_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'sale_visible_column'], ['value' => json_encode($this->sale_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->sale_visible_column = $this->getDefaultColumns();
        Configuration::updateOrCreate(['key' => 'sale_visible_column'], ['value' => json_encode($this->sale_visible_column)]);
    }

    public function render()
    {
        return view('livewire.sale.column-visibility');
    }
}
