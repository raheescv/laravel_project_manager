<?php

namespace App\Livewire\Inventory;

use App\Exports\ProductExport;
use App\Jobs\Export\ExportProductJob;
use App\Models\Configuration;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
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

    public $brand_id = '';

    public $size = '';

    public $barcode = '';

    public $unit_id = '';

    public $limit = 100;

    public $code = '';

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'inventories.id';

    public $sortDirection = 'desc';

    public $inventory_visible_column = [];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Inventory-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $config = Configuration::where('key', 'inventory_visible_column')->value('value');
        $this->branch_id = [session('branch_id')];

        $this->inventory_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'branch' => true,
            'department' => true,
            'main_category' => true,
            'sub_category' => true,
            'unit' => true,
            'brand_id' => true,
            'size' => true,
            'code' => true,
            'product_name' => true,
            'quantity' => true,
            'cost' => true,
            'total' => true,
            'barcode' => true,
            'batch' => true,
        ];
    }

    public function export()
    {
        $count = Inventory::count();
        $filter = [
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'main_category_id' => $this->main_category_id,
            'sub_category_id' => $this->sub_category_id,
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
        ];
        if ($count > 2000) {
            ExportProductJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'inventory_'.now()->timestamp.'.xlsx';

            return Excel::download(new ProductExport($filter), $exportFileName);
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
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->join('categories as main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('categories as sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.name_arabic', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('branches.name', 'like', "%{$value}%")
                        ->orWhere('departments.name', 'like', "%{$value}%")
                        ->orWhere('units.name', 'like', "%{$value}%")
                        ->orWhere('brands.name', 'like', "%{$value}%")
                        ->orWhere('main_categories.name', 'like', "%{$value}%")
                        ->orWhere('sub_categories.name', 'like', "%{$value}%")
                        ->orWhere('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%")
                        ->orWhere('products.size', $value)
                        ->orWhere('inventories.quantity', 'like', "%{$value}%")
                        ->orWhere('inventories.cost', 'like', "%{$value}%");
                });
            })
            ->when($this->code ?? '', function ($query, $value) {
                return $query->where('products.code', $value);
            })
            ->when($this->brand_id ?? '', function ($query, $value) {
                return $query->where('brand_id', $value);
            })
            ->when($this->department_id ?? '', function ($query, $value) {
                return $query->where('department_id', $value);
            })
            ->when($this->main_category_id ?? '', function ($query, $value) {
                return $query->where('main_category_id', $value);
            })
            ->when($this->size ?? '', function ($query, $value) {
                return $query->where('products.size', $value);
            })
            ->when($this->barcode ?? '', function ($query, $value) {
                return $query->where('inventories.barcode', $value);
            })
            ->when($this->sub_category_id ?? '', function ($query, $value) {
                return $query->where('sub_category_id', $value);
            })
            ->when($this->unit_id ?? '', function ($query, $value) {
                return $query->where('unit_id', $value);
            })
            ->when($this->non_zero ?? false, function ($query, $value) {
                return $query->where('quantity', '!=', 0);
            })
            ->when($this->branch_id ?? [], function ($query, $value) {
                return $query->whereIn('branch_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                return $query->where('product_id', $value);
            })
            ->where('products.type', 'product')
            ->latest('inventories.created_at')
            ->select(
                'inventories.id',
                'inventories.cost',
                'inventories.quantity',
                'inventories.total',
                'inventories.barcode',
                'inventories.batch',
                'inventories.created_at',
                'product_id',
                'products.name',
                'products.code',
                'brands.name as brand_name',
                'products.size',
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
            );
        $totalData = clone $data;
        $total = $totalData->sum('total');
        $quantity = $totalData->sum('quantity');
        $data = $data->paginate($this->limit);

        return view('livewire.inventory.table', [
            'data' => $data,
            'total' => $total,
            'quantity' => $quantity,
        ]);
    }
}
