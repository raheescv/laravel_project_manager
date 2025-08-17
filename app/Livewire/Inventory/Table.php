<?php

namespace App\Livewire\Inventory;

use App\Exports\InventoryExport;
use App\Jobs\Export\ExportInventoryJob;
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
            $filters = [
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
            ];

            // Get filtered count for better decision making
            $filteredCount = $this->getFilteredCount($filters);

            if ($filteredCount > 2000) {
                ExportInventoryJob::dispatch(Auth::user(), $filters);
                $this->dispatch('success', ['message' => 'Export started! You will receive the file in your mailbox shortly.']);
            } else {
                $exportFileName = 'inventory_'.now()->format('Y-m-d_H-i-s').'.xlsx';

                return Excel::download(new InventoryExport($filters), $exportFileName);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Export failed: '.$e->getMessage()]);
        }
    }

    protected function getFilteredCount($filters)
    {
        // Cache the filtered count for better performance
        $cacheKey = 'inventory_filtered_count_'.md5(serialize($filters));

        return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            return Inventory::query()
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->where('products.type', 'product')
                ->when($filters['branch_id'] ?? null, function ($query, $value) {
                    return $query->whereIn('branch_id', $value);
                })
                ->when($filters['department_id'] ?? null, function ($query, $value) {
                    return $query->where('department_id', $value);
                })
                ->when($filters['main_category_id'] ?? null, function ($query, $value) {
                    return $query->where('main_category_id', $value);
                })
                ->when($filters['sub_category_id'] ?? null, function ($query, $value) {
                    return $query->where('sub_category_id', $value);
                })
                ->when($filters['product_id'] ?? null, function ($query, $value) {
                    return $query->where('product_id', $value);
                })
                ->when($filters['unit_id'] ?? null, function ($query, $value) {
                    return $query->where('unit_id', $value);
                })
                ->when($filters['brand_id'] ?? null, function ($query, $value) {
                    return $query->where('brand_id', $value);
                })
                ->when($filters['non_zero'] ?? false, function ($query, $value) {
                    return $query->where('quantity', '!=', 0);
                })
                ->when($filters['size'] ?? null, function ($query, $value) {
                    return $query->where('products.size', $value);
                })
                ->when($filters['barcode'] ?? null, function ($query, $value) {
                    return $query->where('inventories.barcode', $value);
                })
                ->when($filters['code'] ?? null, function ($query, $value) {
                    return $query->where('products.code', $value);
                })
                ->when($filters['search'] ?? null, function ($query, $value) {
                    return $query->where(function ($q) use ($value) {
                        $value = trim($value);
                        $q->where('products.name', 'like', "%{$value}%")
                            ->orWhere('products.name_arabic', 'like', "%{$value}%")
                            ->orWhere('products.code', 'like', "%{$value}%")
                            ->orWhere('products.size', $value)
                            ->orWhere('inventories.barcode', 'like', "%{$value}%")
                            ->orWhere('inventories.batch', 'like', "%{$value}%");
                    });
                })
                ->count();
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
        return Inventory::query()
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
            ])
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('departments', 'products.department_id', '=', 'departments.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->join('categories as main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('categories as sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
            ->where('products.type', 'product')
            ->when($this->search, function ($query, $value) {
                $value = trim($value);

                return $query->where(function ($q) use ($value) {
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
            ->when($this->code, function ($query, $value) {
                return $query->where('products.code', $value);
            })
            ->when($this->brand_id, function ($query, $value) {
                return $query->where('products.brand_id', $value);
            })
            ->when($this->department_id, function ($query, $value) {
                return $query->where('products.department_id', $value);
            })
            ->when($this->main_category_id, function ($query, $value) {
                return $query->where('products.main_category_id', $value);
            })
            ->when($this->size, function ($query, $value) {
                return $query->where('products.size', $value);
            })
            ->when($this->barcode, function ($query, $value) {
                return $query->where('inventories.barcode', $value);
            })
            ->when($this->sub_category_id, function ($query, $value) {
                return $query->where('products.sub_category_id', $value);
            })
            ->when($this->unit_id, function ($query, $value) {
                return $query->where('products.unit_id', $value);
            })
            ->when($this->non_zero, function ($query, $value) {
                return $query->where('inventories.quantity', '!=', 0);
            })
            ->when($this->branch_id, function ($query, $value) {
                return $query->whereIn('inventories.branch_id', $value);
            })
            ->when($this->product_id, function ($query, $value) {
                return $query->where('inventories.product_id', $value);
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }
}
