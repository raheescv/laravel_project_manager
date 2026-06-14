<?php

namespace App\Livewire\Product;

use App\Models\ProductRawMaterial;
use Livewire\Component;

class RawMaterialForm extends Component
{
    protected $listeners = [
        'Product-RawMaterial-Create-Component' => 'openCreate',
        'Product-RawMaterial-Update-Component' => 'openEdit',
    ];

    public ?int $product_id = null;

    public ?int $table_id = null;

    public ?int $raw_material_id = null;

    public string $quantity = '';

    public function openCreate(int $product_id): void
    {
        $this->resetState();
        $this->product_id = $product_id;
        $this->dispatch('ToggleProductRawMaterialModal');
        $this->dispatch('raw-material-modal-opened');
    }

    public function openEdit(int $product_id, int $table_id): void
    {
        $this->resetState();
        $this->product_id = $product_id;

        $record = ProductRawMaterial::with('rawMaterial:id,name')->findOrFail($table_id);
        $this->table_id = $record->id;
        $this->raw_material_id = $record->raw_material_id;
        $this->quantity = (string) $record->quantity;

        $this->dispatch('ToggleProductRawMaterialModal');
        $this->dispatch('raw-material-modal-opened', rawMaterialId: $this->raw_material_id);
    }

    public function setRawMaterial(int $id): void
    {
        $this->raw_material_id = $id;
    }

    protected function resetState(): void
    {
        $this->reset(['table_id', 'raw_material_id', 'quantity']);
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'raw_material_id' => ['required', 'integer', 'different:product_id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    protected $messages = [
        'raw_material_id.required' => 'Please select a raw material.',
        'raw_material_id.different' => 'A product cannot be its own raw material.',
        'quantity.required' => 'Quantity is required.',
        'quantity.min' => 'Quantity must be greater than zero.',
    ];

    public function save(bool $close = true): void
    {
        // TODO(C7): review save authz — nested product raw-material quick-add/edit modal sub-component
        $this->validate();

        try {
            $data = [
                'product_id' => $this->product_id,
                'raw_material_id' => $this->raw_material_id,
                'quantity' => $this->quantity,
            ];

            if ($this->table_id) {
                ProductRawMaterial::where('id', $this->table_id)->update($data);
            } else {
                $exists = ProductRawMaterial::where('product_id', $this->product_id)
                    ->where('raw_material_id', $this->raw_material_id)
                    ->exists();

                if ($exists) {
                    $this->addError('raw_material_id', 'This raw material is already added.');

                    return;
                }

                ProductRawMaterial::create($data);
            }

            $this->dispatch('success', ['message' => $this->table_id ? 'Raw material updated.' : 'Raw material added.']);
            $this->dispatch('Product-RawMaterials-Refresh');

            if ($close) {
                $this->dispatch('ToggleProductRawMaterialModal');
            } else {
                $this->resetState();
                $this->product_id = $data['product_id'];
                $this->dispatch('raw-material-modal-opened');
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.product.raw-material-form');
    }
}
