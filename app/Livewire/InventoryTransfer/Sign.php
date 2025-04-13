<?php

namespace App\Livewire\InventoryTransfer;

use App\Models\InventoryTransfer;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Sign extends Component
{
    public $signature;

    public $model;

    public function mount(InventoryTransfer $model)
    {
        $this->model = $model;
    }

    public function save()
    {
        // Validate signature
        $this->validate([
            'signature' => 'required|string',
        ]);

        // Save or process the signature (e.g. save as file or in database)
        $data = $this->signature;

        // Optional: Save to storage
        $name = 'signature_'.time().'.png';
        $path = "inventory-transfer/{$this->model->id}/signatures/".$name;

        $image = str_replace('data:image/png;base64,', '', $data);
        $image = str_replace(' ', '+', $image);
        Storage::disk('public')->put($path, base64_decode($image));

        $this->model->update(['signature' => $path]);

        return redirect(route('inventory::transfer::print', $this->model->id));
    }

    public function render()
    {
        return view('livewire.inventory-transfer.sign');
    }
}
