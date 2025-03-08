<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use Livewire\Component;

class View extends Component
{
    public $product;

    public $search = '';

    public $log_search = '';

    public $product_id = '';

    public $branch_id = '';

    public $sortField = 'inventory_logs.id';

    public $sortDirection = 'desc';

    protected $listeners = [
        'Inventory-Refresh-Component' => '$refresh',
    ];

    public function mount($product_id)
    {
        $this->product_id = $product_id;
        $this->product = Product::find($this->product_id);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $data = Inventory::join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%")
                        ->orWhere('inventories.quantity', 'like', "%{$value}%")
                        ->orWhere('inventories.cost', 'like', "%{$value}%")
                        ->orWhere('branches.name', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->where('product_id', $this->product_id)
            ->latest('inventories.created_at')
            ->select(
                'inventories.id',
                'inventories.branch_id',
                'inventories.barcode',
                'inventories.batch',
                'inventories.cost',
                'inventories.quantity',
            )
            ->get();
        $logs = InventoryLog::with('branch')->orderBy($this->sortField, $this->sortDirection)
            ->when($this->log_search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('inventory_logs.remarks', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->where('product_id', $this->product_id)
            ->latest()
            ->paginate();

        return view('livewire.inventory.view', [
            'data' => $data,
            'logs' => $logs,
        ]);
    }
}
