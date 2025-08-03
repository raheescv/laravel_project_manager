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

    public $selectedBranch = '';

    public $branches = [];

    public $loading = false;

    public $limit = 25;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'productCode' => ['except' => ''],
        'productName' => ['except' => ''],
        'selectedBranch' => ['except' => ''],
        'limit' => ['except' => 25],
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
        $this->reset(['search', 'productCode', 'productName', 'selectedBranch']);
    }

    public function render()
    {
        $query = Inventory::with(['product', 'branch'])
            ->whereHas('product', function ($q) {
                $q->where('type', 'product');
            });

        if ($this->selectedBranch) {
            $query->where('branch_id', $this->selectedBranch);
        }

        if ($this->productCode) {
            $query->whereHas('product', function ($q) {
                $q->where('code', 'like', "%{$this->productCode}%")
                    ->orWhere('barcode', 'like', "%{$this->productCode}%")
                    ->orWhere('inventories.barcode', 'like', "%{$this->productCode}%");
            });
        }

        if ($this->productName) {
            $query->whereHas('product', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('name', 'like', "%{$this->productName}%")
                        ->orWhere('name_arabic', 'like', "%{$this->productName}%");
                });
            });
        }

        $products = $query->paginate($this->limit);

        return view('livewire.inventory.product-search', compact('products'));
    }
}
