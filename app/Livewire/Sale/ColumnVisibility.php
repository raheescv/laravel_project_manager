<?php

namespace App\Livewire\Sale;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $sale_visible_column;

    public function mount()
    {
        $this->sale_visible_column = json_decode(Configuration::where('key', 'sale_visible_column')->value('value'), true);

    }

    public function toggleColumn($column)
    {
        $this->sale_visible_column[$column] = ! $this->sale_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'sale_visible_column'], ['value' => json_encode($this->sale_visible_column)]);
    }

    public function render()
    {
        return view('livewire.sale.column-visibility');
    }
}
