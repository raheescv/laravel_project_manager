<?php

namespace App\Livewire\Product;

use App\Models\ProductRawMaterial;
use Livewire\Component;

class RawMaterials extends Component
{
    protected $listeners = [
        'Product-RawMaterials-Refresh' => 'loadRawMaterials',
    ];

    public int $product_id;

    public array $rawMaterials = [];

    public function mount(int $product_id): void
    {
        $this->product_id = $product_id;
        $this->loadRawMaterials();
    }

    public function loadRawMaterials(): void
    {
        $this->rawMaterials = ProductRawMaterial::where('product_id', $this->product_id)
            ->with('rawMaterial:id,name,code,unit_id', 'rawMaterial.unit:id,name')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function create(): void
    {
        $this->dispatch('Product-RawMaterial-Create-Component', product_id: $this->product_id);
    }

    public function edit(int $id): void
    {
        $this->dispatch('Product-RawMaterial-Update-Component', product_id: $this->product_id, table_id: $id);
    }

    public function delete(int $id): void
    {
        // TODO(C7): sub-record (product raw material) delete during edit; no exact sub-permission, gated by edit
        abort_unless(auth()->user()?->can('product.edit'), 403);
        try {
            ProductRawMaterial::findOrFail($id)->delete();
            $this->loadRawMaterials();
            $this->dispatch('success', ['message' => 'Raw material removed.']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.product.raw-materials');
    }
}
