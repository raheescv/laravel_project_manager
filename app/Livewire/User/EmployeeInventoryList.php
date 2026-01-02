<?php

namespace App\Livewire\User;

use App\Models\Inventory;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeInventoryList extends Component
{
    use WithPagination;

    public $employee_id;

    public $search = '';

    public $limit = 10;

    public $sortField = 'inventories.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'EmployeeInventory-Refresh-Component' => '$refresh',
    ];

    public function mount($employee_id)
    {
        $this->employee_id = $employee_id;
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

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Inventory::withoutGlobalScopes()
            ->where('employee_id', $this->employee_id)
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('branches', 'inventories.branch_id', '=', 'branches.id')
            ->when($this->search, function ($query, $value) {
                $value = trim($value);
                return $query->where(function ($q) use ($value) {
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('products.name_arabic', 'like', "%{$value}%")
                        ->orWhere('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%");
                });
            })
            ->select([
                'inventories.id',
                'inventories.cost',
                'inventories.quantity',
                'inventories.total',
                'inventories.barcode',
                'inventories.batch',
                'inventories.created_at',
                'inventories.product_id',
                'inventories.branch_id',
                'products.name',
                'products.code',
                'products.mrp',
                'products.size',
                'products.name_arabic',
                'brands.name as brand_name',
                'branches.name as branch_name',
            ])
            ->orderBy($this->sortField, $this->sortDirection);

        // Calculate totals
        $totalData = clone $query;
        $total = $totalData->sum('inventories.total');
        $quantity = $totalData->sum('inventories.quantity');

        // Apply pagination
        $data = $query->paginate($this->limit);

        return view('livewire.user.employee-inventory-list', [
            'data' => $data,
            'total' => $total,
            'quantity' => $quantity,
        ]);
    }
}

