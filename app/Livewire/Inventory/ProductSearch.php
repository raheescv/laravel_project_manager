<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Scopes\AssignedBranchScope;
use Livewire\Component;
use Livewire\WithPagination;

class ProductSearch extends Component
{
    use WithPagination;

    public $search = '';

    public $productCode = '';

    public $productBarcode = '';

    public $productName = '';

    public $branch_id = '';

    public $showNonZeroOnly = true;

    public $showBarcodeCodes = false;

    public $branches = [];

    public $loading = false;

    public $limit = 50;

    public $sortField = 'products.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'productCode' => ['except' => ''],
        'productBarcode' => ['except' => ''],
        'productName' => ['except' => ''],
        'branch_id' => ['except' => ''],
        'showNonZeroOnly' => ['except' => false],
        'showBarcodeCodes' => ['except' => false],
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
        $this->reset(['search', 'productCode', 'productBarcode', 'productName', 'branch_id', 'showNonZeroOnly', 'showBarcodeCodes']);
    }

    public function setBarcode($barcode)
    {
        $this->productBarcode = $barcode;
        $this->resetPage();
    }

    public function setProductCode($code)
    {
        $this->productCode = $code;
        $this->resetPage();
    }

    public function quickBarcodeSearch($barcode)
    {
        $this->productBarcode = $barcode;
        $this->resetPage();

        // Auto-search when barcode is set
        $this->dispatch('barcode-scanned', barcode: $barcode);
    }

    public function clearBarcode()
    {
        $this->productBarcode = '';
        $this->resetPage();
    }

    public function getBarcodeProductsCount()
    {
        return Inventory::withoutGlobalScope(AssignedBranchScope::class)
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->where('products.type', 'product')
            ->where('inventories.barcode', '!=', '')
            ->whereNotNull('inventories.barcode')
            ->count();
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
        $query = Inventory::withoutGlobalScope(AssignedBranchScope::class)
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->where('products.type', 'product');
        $query = $query->orderBy($this->sortField, $this->sortDirection);

        $query = $query->when($this->branch_id, function ($query, $value) {
            return $query->where('branch_id', $value);
        });

        $query = $query->when($this->productCode, function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('products.code', 'like', "%{$value}%");
            });
        });

        if (! $this->showBarcodeCodes) {
            $query = $query->when($this->productBarcode, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('inventories.barcode', 'like', "%{$value}%");
                });
            });
        }
        $query = $query->when($this->productName, function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('products.name', 'like', "%{$value}%")
                    ->orWhere('products.name_arabic', 'like', "%{$value}%")
                    ->orWhere('products.code', 'like', "%{$value}%")
                    ->orWhere('inventories.barcode', 'like', "%{$value}%");
            });
        });

        $query = $query->when($this->showNonZeroOnly, function ($query, $value) {
            return $query->where('inventories.quantity', '>', 0);
        });

        if ($this->showBarcodeCodes && $this->productBarcode) {
            $codes = clone $query;
            $codes = $codes->where('inventories.barcode', $this->productBarcode)->pluck('products.code', 'products.code')->toArray();
            $query = $query->whereIn('products.code', $codes);
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
