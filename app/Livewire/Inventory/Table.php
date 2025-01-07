<?php

namespace App\Livewire\Inventory;

use App\Exports\ProductExport;
use App\Jobs\Export\ExportProductJob;
use App\Models\Inventory;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $department_id = '';

    public $main_category_id = '';

    public $sub_category_id = '';

    public $product_id = '';

    public $non_zero = true;

    public $branch_id = '';

    public $unit_id = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'inventories.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Inventory-Refresh-Component' => '$refresh',
    ];

    public function export()
    {
        $count = Inventory::count();
        if ($count > 2000) {
            ExportProductJob::dispatch(auth()->user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'inventory_'.now()->timestamp.'.xlsx';

            return Excel::download(new ProductExport, $exportFileName);
        }
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
        $data = Inventory::orderBy($this->sortField, $this->sortDirection)
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('departments', 'products.department_id', '=', 'departments.id')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->join('categories as main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->join('categories as sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
            ->when($this->search, function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.name_arabic', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('branches.name', 'like', "%{$value}%")
                        ->orWhere('departments.name', 'like', "%{$value}%")
                        ->orWhere('units.name', 'like', "%{$value}%")
                        ->orWhere('main_categories.name', 'like', "%{$value}%")
                        ->orWhere('sub_categories.name', 'like', "%{$value}%")
                        ->orWhere('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%")
                        ->orWhere('inventories.quantity', 'like', "%{$value}%")
                        ->orWhere('inventories.cost', 'like', "%{$value}%");
                });
            })
            ->when($this->department_id ?? '', function ($query, $value) {
                $query->where('department_id', $value);
            })
            ->when($this->main_category_id ?? '', function ($query, $value) {
                $query->where('main_category_id', $value);
            })
            ->when($this->sub_category_id ?? '', function ($query, $value) {
                $query->where('sub_category_id', $value);
            })
            ->when($this->unit_id ?? '', function ($query, $value) {
                $query->where('unit_id', $value);
            })
            ->when($this->non_zero ?? false, function ($query, $value) {
                $query->where('quantity', '!=', 0);
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                $query->where('product_id', $value);
            })
            ->latest('inventories.created_at')
            ->select(
                'inventories.id',
                'inventories.cost',
                'inventories.quantity',
                'inventories.barcode',
                'inventories.batch',
                'inventories.created_at',
                'product_id',
                'products.name',
                'products.code',
                'products.name_arabic',
                'products.department_id',
                'departments.name as department_name',
                'products.main_category_id',
                'main_categories.name as main_category_name',
                'products.sub_category_id',
                'sub_categories.name as sub_category_name',
                'products.unit_id',
                'units.name as unit_name',
                'branch_id',
                'branches.name as branch_name',
            )
            ->paginate($this->limit);

        return view('livewire.inventory.table', [
            'data' => $data,
        ]);
    }
}
