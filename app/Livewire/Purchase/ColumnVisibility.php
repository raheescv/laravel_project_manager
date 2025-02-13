<?php

namespace App\Livewire\Purchase;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $purchase_visible_column;

    public function mount()
    {
        $this->purchase_visible_column = json_decode(Configuration::where('key', 'purchase_visible_column')->value('value'), true);

    }

    public function toggleColumn($column)
    {
        $this->purchase_visible_column[$column] = ! $this->purchase_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'purchase_visible_column'], ['value' => json_encode($this->purchase_visible_column)]);
    }

    public function render()
    {
        return view('livewire.purchase.column-visibility');
    }
}
