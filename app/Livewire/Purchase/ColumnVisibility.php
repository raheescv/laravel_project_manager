<?php

namespace App\Livewire\Purchase;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $purchase_visible_column;

    public function mount()
    {
        $config = Configuration::where('key', 'purchase_visible_column')->value('value');
        $this->purchase_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'branch_id' => false,
            'vendor' => true,
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
        $this->purchase_visible_column[$column] = ! $this->purchase_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'purchase_visible_column'], ['value' => json_encode($this->purchase_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->purchase_visible_column = $this->getDefaultColumns();
        Configuration::updateOrCreate(['key' => 'purchase_visible_column'], ['value' => json_encode($this->purchase_visible_column)]);
    }

    public function render()
    {
        return view('livewire.purchase.column-visibility');
    }
}
