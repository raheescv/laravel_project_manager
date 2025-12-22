<?php

namespace App\Livewire\Product;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $product_visible_column;

    public function mount()
    {
        $config = Configuration::where('key', 'product_visible_column')->value('value');
        $this->product_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'id' => true,
            'department' => true,
            'main_category' => true,
            'sub_category' => true,
            'unit' => true,
            'brand' => true,
            'size' => true,
            'code' => true,
            'product_name' => true,
            'name_arabic' => true,
            'barcode' => true,
            'cost' => true,
            'mrp' => true,
        ];
    }

    public function toggleColumn($column)
    {
        $this->product_visible_column[$column] = ! $this->product_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'product_visible_column'], ['value' => json_encode($this->product_visible_column)]);
    }

    public function render()
    {
        return view('livewire.product.column-visibility');
    }
}

