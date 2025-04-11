<?php

namespace App\Livewire\SaleReturn;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $sale_return_visible_column;

    public function mount()
    {
        $this->sale_return_visible_column = json_decode(Configuration::where('key', 'sale_return_visible_column')->value('value'), true);

    }

    public function toggleColumn($column)
    {
        $this->sale_return_visible_column[$column] = ! $this->sale_return_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'sale_return_visible_column'], ['value' => json_encode($this->sale_return_visible_column)]);
    }

    public function render()
    {
        return view('livewire.sale-return.column-visibility');
    }
}
