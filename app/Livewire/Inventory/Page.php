<?php

namespace App\Livewire\Inventory;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Inventory-Page-Update-Component' => 'edit',
    ];

    public $inventories;

    public $table_id;

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleInventoryModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->inventories = [
                'batch',
                'barcode',
                'quantity',
                'remarks',
            ];
        } else {
            $inventory = Inventory::find($this->table_id);
            $this->inventories = $inventory->toArray();
            $this->inventories['remarks'] = null;
        }
    }

    protected function rules()
    {
        return [
            'inventories.batch' => ['required'],
            'inventories.barcode' => ['required'],
            'inventories.quantity' => ['required'],
            'inventories.remarks' => ['required'],
        ];
    }

    protected $messages = [
        'inventories.batch.required' => 'The batch field is required',
        'inventories.barcode.required' => 'The barcode field is required',
        'inventories.quantity.required' => 'The quantity field is required',
        'inventories.remarks.required' => 'The remarks field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            $response = (new UpdateAction)->execute($this->inventories, $this->table_id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            $this->dispatch('ToggleInventoryModal');
            $this->dispatch('RefreshInventoryTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory.page');
    }
}
