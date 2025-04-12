<?php

namespace App\Livewire\InventoryTransfer;

use App\Models\InventoryLog;
use App\Models\InventoryTransfer;
use Livewire\Component;

class View extends Component
{
    public $fromBranch;

    public $toBranch;

    public $table_id;

    public $inventory_id;

    public $barcode_key;

    public $model;

    public $logs;

    public function refresh()
    {
        $this->mount($this->table_id);
    }

    public function mount($table_id)
    {
        $this->table_id = $table_id;
        $this->model = InventoryTransfer::with('fromBranch:id,name', 'toBranch:id,name', 'items.product:id,name')->find($this->table_id);
        if (! $this->model) {
            return redirect()->route('inventory::transfer::index');
        }
        $this->logs = InventoryLog::where('model', 'InventoryTransfer')->where('model_id', $this->table_id)->get();
    }

    public function render()
    {
        return view('livewire.inventory-transfer.view');
    }
}
