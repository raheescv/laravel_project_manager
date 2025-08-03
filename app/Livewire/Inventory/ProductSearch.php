<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\Inventory;
use Livewire\Component;
use Livewire\WithPagination;

class ProductSearch extends Component
{
    use WithPagination;

    public $search = '';

    public $productCode = '';

    public $productName = '';

    public $branch_id = '';

    public $showNonZeroOnly = true;

    public $branches = [];

    public $loading = false;

    public $limit = 50;

    public $sortField = 'products.name';
    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'productCode' => ['except' => ''],
        'productName' => ['except' => ''],
        'branch_id' => ['except' => ''],
        'showNonZeroOnly' => ['except' => false],
        'limit' => ['except' => 25],
        'sortField' => ['except' => 'products.name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount()
    {
        $this->branches = Branch::get();
    }

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'productCode', 'productName', 'branch_id', 'showNonZeroOnly']);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $query = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->where('products.type', 'product');
        $query = $query->orderBy($this->sortField, $this->sortDirection);

        if ($this->branch_id) {
            $query = $query->whereIn('branch_id', $this->branch_id);
        }
        if ($this->productCode) {
            $query = $query->where('products.code', 'like', "%{$this->productCode}%")
                ->orWhere('inventories.barcode', 'like', "%{$this->productCode}%");
        }

        if ($this->productName) {
            $query = $query->where('products.name', 'like', "%{$this->productName}%")
                ->orWhere('products.name_arabic', 'like', "%{$this->productName}%");
        }

        if ($this->showNonZeroOnly) {
            $query = $query->where('inventories.quantity', '>', 0);
        }

        $query = $query->select(
            'inventories.id',
            'inventories.quantity',
            'inventories.barcode',
            'products.name',
            'products.name_arabic',
            'products.code',
            'products.size',
            'products.mrp',
            'branches.name as branch_name',
        );
        $products = $query->paginate($this->limit);

        return view('livewire.inventory.product-search', compact('products'));
    }
}
