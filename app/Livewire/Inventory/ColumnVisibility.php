<?php

namespace App\Livewire\Inventory;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $inventory_visible_column;

    public function mount()
    {
        $this->inventory_visible_column = json_decode(Configuration::where('key', 'inventory_visible_column')->value('value'), true);
    }

    public function toggleColumn($column)
    {
        $this->inventory_visible_column[$column] = ! $this->inventory_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'inventory_visible_column'], ['value' => json_encode($this->inventory_visible_column)]);
    }

    public function render()
    {
        return view('livewire.inventory.column-visibility');
    }
}
