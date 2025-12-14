<?php

namespace App\Livewire\Inventory;

use App\Actions\Product\Inventory\GetAction;
use App\Actions\Product\InventoryProductWiseAction;
use App\Exports\InventoryExport;
use App\Exports\InventoryProductWiseExport;
use App\Jobs\Export\ExportInventoryJob;
use App\Jobs\Export\ExportInventoryProductWiseJob;
use App\Models\Configuration;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

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

    public $product_name = '';   // <— Missing property FIXED

    protected $listeners = [
        'Inventory-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        // Cache configuration to avoid repeated database queries
        $this->branch_id = [session('branch_id')];

        $config = Configuration::where('key', 'inventory_visible_column')->value('value');
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
        try {
            $filters = $this->getFilters();

            // Get filtered count for better decision making
            $filteredCount = $this->getFilteredCount($filters);

            if ($filteredCount > 2000) {
                ExportInventoryJob::dispatch(Auth::user(), $filters);
                $this->dispatch('success', ['message' => 'Export started! You will receive the file in your mailbox shortly.']);
            } else {
                $exportFileName = 'inventory_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                return Excel::download(new InventoryExport($filters), $exportFileName);
            }
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
        }
    }

    public function exportProductWise()
    {
        try {
            $filters = $this->getFilters();

            // Get filtered count for better decision making
            $filteredCount = $this->getProductWiseFilteredCount($filters);

            if ($filteredCount > 2000) {
                ExportInventoryProductWiseJob::dispatch(Auth::user(), $filters);
                $this->dispatch('success', ['message' => 'Product Wise Export started! You will receive the file in your mailbox shortly.']);
            } else {
                $exportFileName = 'inventory_product_wise_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                return Excel::download(new InventoryProductWiseExport($filters), $exportFileName);
            }
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => 'Product Wise Export failed: '.$e->getMessage()]);
        }
    }

    protected function getFilters()
    {
        return [
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'main_category_id' => $this->main_category_id,
            'sub_category_id' => $this->sub_category_id,
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
            'brand_id' => $this->brand_id,
            'size' => $this->size,
            'barcode' => $this->barcode,
            'code' => $this->code,
            'non_zero' => $this->non_zero,
            'search' => $this->search,
            'product_name' => $this->product_name,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ];
    }

    protected function getFilteredCount($filters)
    {
        // Cache the filtered count for better performance
        $cacheKey = 'inventory_filtered_count_'.md5(serialize($filters));

        return cache()->remember($cacheKey, now()->addMinutes(5), function () {
            return (new GetAction())->execute($this->getFilters())->count();
        });
    }

    protected function getProductWiseFilteredCount($filters)
    {
        // Cache the filtered count for better performance
        $cacheKey = 'inventory_product_wise_filtered_count_'.md5(serialize($filters));

        return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            $query = (new InventoryProductWiseAction())->execute($filters);

            return $query->count();
        });
    }

    public function clearCache()
    {
        // Clear all inventory-related cache when needed
        cache()->forget('inventory_visible_column_'.(Auth::user()?->id ?? 'guest'));
        $this->dispatch('success', ['message' => 'Cache cleared successfully']);
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
        $query = $this->buildQuery();

        // Clone for totals calculation
        $totalData = clone $query;
        $total = $totalData->sum('inventories.total');
        $quantity = $totalData->sum('inventories.quantity');

        // Apply pagination
        $data = $query->paginate($this->limit);

        return view('livewire.inventory.table', [
            'data' => $data,
            'total' => $total,
            'quantity' => $quantity,
        ]);
    }

    protected function buildQuery()
    {
        return (new GetAction())->execute($this->getFilters())
            ->select([
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
            ]);
    }
}
