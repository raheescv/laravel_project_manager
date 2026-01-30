<?php

namespace App\Livewire\Inventory;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $inventory_visible_column;

    public function mount()
    {
        $config = Configuration::where('key', 'inventory_visible_column')->value('value');
        $this->inventory_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'branch' => true,
            'department' => true,
            'main_category' => true,
            'sub_category' => true,
            'unit' => true,
            'brand' => true,
            'size' => true,
            'code' => true,
            'product_name' => true,
            'quantity' => true,
            'cost' => true,
            'total' => true,
            'mrp' => true,
            'barcode' => true,
            'batch' => true,
        ];
    }

    public function toggleColumn($column)
    {
        $this->inventory_visible_column[$column] = ! $this->inventory_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'inventory_visible_column'], ['value' => json_encode($this->inventory_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->inventory_visible_column = $this->getDefaultColumns();
        Configuration::updateOrCreate(['key' => 'inventory_visible_column'], ['value' => json_encode($this->inventory_visible_column)]);
    }

    public function render()
    {
        return view('livewire.inventory.column-visibility');
    }
}
