<?php

namespace App\Livewire\Report;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockAnalysisReport extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $branch_id;

    public $product_search = '';

    public $main_category_id = '';

    public $sub_category_id = '';

    public $brand_id = '';

    public $report_type = 'top_moving'; // non_moving, top_moving

    public $days_threshold = 30; // Number of days to consider for non-moving items

    public $limit = 10; // Number of top moving products to show

    public $group_by_code = false; // Group result rows / chart slices by product code

    public string $historyModalTitle = '';

    public string $historyModalType = '';

    public $historyRows = [];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->from_date = date('Y-m-d', strtotime('-30 days'));
        $this->to_date = date('Y-m-d');
    }

    public function updated($key, $value): void
    {
        $this->resetPage();
        if (in_array($key, ['from_date', 'to_date', 'branch_id', 'limit', 'report_type', 'product_search', 'main_category_id', 'sub_category_id', 'brand_id', 'group_by_code'])) {
            if ($this->report_type === 'top_moving') {
                $this->dispatch('stock-analysis-chart-updated', chartData: $this->getChartData());
            } else {
                $this->dispatch('stock-analysis-chart-cleared');
            }
        }
    }

    /**
     * Join main category, sub category, and brand for any query that already joins `products`.
     */
    protected function applyProductTaxonomyJoins(Builder $query): Builder
    {
        return $query
            ->leftJoin('categories as main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('categories as sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id');
    }

    protected function getNonMovingProducts(): Builder
    {
        $query = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id');
        $this->applyProductTaxonomyJoins($query);
        $query
            ->leftJoin('branches', 'inventories.branch_id', '=', 'branches.id')
            ->leftJoin(DB::raw('(
                SELECT product_id, branch_id, MAX(created_at) as last_movement
                FROM inventory_logs
                WHERE (quantity_in > 0 OR quantity_out > 0)
                GROUP BY product_id, branch_id
            ) as last_movements'), function ($join): void {
                $join->on('inventories.product_id', '=', 'last_movements.product_id')
                    ->on('inventories.branch_id', '=', 'last_movements.branch_id');
            })
            ->where('products.type', 'product')
            ->where('inventories.quantity', '>', 0)
            ->when($this->branch_id, function ($q) {
                return $q->where('inventories.branch_id', $this->branch_id);
            });

        $this->applyProductSearch($query);
        $this->applyProductDropdownFilters($query);

        $query->where(function ($q): void {
            $q->whereNull('last_movements.last_movement')
                ->orWhere('last_movements.last_movement', '<=', now()->subDays(intval($this->days_threshold)));
        });

        if ($this->group_by_code) {
            return $query
                ->groupBy('products.code')
                ->select(
                    DB::raw('MIN(products.id) as id'),
                    DB::raw('MIN(products.name) as name'),
                    'products.code',
                    DB::raw('MIN(products.main_category_id) as main_category_id'),
                    DB::raw('MIN(products.sub_category_id) as sub_category_id'),
                    DB::raw('MIN(products.brand_id) as brand_id'),
                    DB::raw('MIN(products.size) as size'),
                    DB::raw('MIN(main_categories.name) as main_category_name'),
                    DB::raw('MIN(sub_categories.name) as sub_category_name'),
                    DB::raw('MIN(brands.name) as brand_name'),
                    DB::raw('SUM(inventories.quantity) as quantity'),
                    DB::raw('AVG(inventories.cost) as cost'),
                    DB::raw('MAX(last_movements.last_movement) as last_movement'),
                    DB::raw('SUM(inventories.quantity * inventories.cost) as stock_value')
                )
                ->orderBy('last_movement', 'asc');
        }

        return $query->select(
            'products.id',
            'products.name',
            'products.code',
            'products.main_category_id',
            'products.sub_category_id',
            'products.brand_id',
            'products.size',
            'main_categories.name as main_category_name',
            'sub_categories.name as sub_category_name',
            'brands.name as brand_name',
            'inventories.quantity',
            'inventories.cost',
            'branches.name as branch_name',
            'last_movements.last_movement',
            DB::raw('inventories.quantity * inventories.cost as stock_value')
        )->orderBy('last_movements.last_movement', 'asc');
    }

    protected function getTopMovingProducts(): QueryBuilder
    {
        if ($this->group_by_code) {
            return $this->getGroupedTopMovingProducts();
        }

        return $this->getProductTopMovingProducts();
    }

    protected function getGroupedTopMovingProducts(): QueryBuilder
    {
        $saleItems = $this->baseSaleItemsQuery()
            ->select(
                DB::raw('MIN(products.id) as id'),
                DB::raw('MIN(products.name) as name'),
                'products.code',
                DB::raw('MIN(products.main_category_id) as main_category_id'),
                DB::raw('MIN(products.sub_category_id) as sub_category_id'),
                DB::raw('MIN(products.size) as size'),
                DB::raw('MIN(products.brand_id) as brand_id'),
                DB::raw('MIN(main_categories.name) as main_category_name'),
                DB::raw('MIN(sub_categories.name) as sub_category_name'),
                DB::raw('MIN(brands.name) as brand_name'),
                DB::raw('SUM(sale_items.base_unit_quantity) as sale_count'),
                DB::raw('0 as sale_return_count')
            )
            ->groupBy('products.code');

        $saleReturnItems = $this->baseSaleReturnItemsQuery()
            ->select(
                DB::raw('MIN(products.id) as id'),
                DB::raw('MIN(products.name) as name'),
                'products.code',
                DB::raw('MIN(products.main_category_id) as main_category_id'),
                DB::raw('MIN(products.sub_category_id) as sub_category_id'),
                DB::raw('MIN(products.size) as size'),
                DB::raw('MIN(products.brand_id) as brand_id'),
                DB::raw('MIN(main_categories.name) as main_category_name'),
                DB::raw('MIN(sub_categories.name) as sub_category_name'),
                DB::raw('MIN(brands.name) as brand_name'),
                DB::raw('0 as sale_count'),
                DB::raw('SUM(sale_return_items.base_unit_quantity) as sale_return_count')
            )
            ->groupBy('products.code');

        return $this->aggregateTopMovingUnion($saleItems->unionAll($saleReturnItems), [
            'code',
        ])->orderBy('sale_count', 'desc');
    }

    protected function getProductTopMovingProducts(): QueryBuilder
    {
        $saleItems = $this->baseSaleItemsQuery()
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.main_category_id',
                'products.sub_category_id',
                'products.size',
                'products.brand_id',
                'sales.branch_id',
                'branches.name as branch_name',
                'branches.code as branch_code',
                'main_categories.name as main_category_name',
                'sub_categories.name as sub_category_name',
                'brands.name as brand_name',
                DB::raw('SUM(sale_items.base_unit_quantity) as sale_count'),
                DB::raw('0 as sale_return_count')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.code',
                'products.main_category_id',
                'products.sub_category_id',
                'products.size',
                'products.brand_id',
                'sales.branch_id',
                'branches.name',
                'branches.code',
                'main_categories.name',
                'sub_categories.name',
                'brands.name',
            );

        $saleReturnItems = $this->baseSaleReturnItemsQuery()
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.main_category_id',
                'products.sub_category_id',
                'products.size',
                'products.brand_id',
                'sale_returns.branch_id',
                'branches.name as branch_name',
                'branches.code as branch_code',
                'main_categories.name as main_category_name',
                'sub_categories.name as sub_category_name',
                'brands.name as brand_name',
                DB::raw('0 as sale_count'),
                DB::raw('SUM(sale_return_items.base_unit_quantity) as sale_return_count')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.code',
                'products.main_category_id',
                'products.sub_category_id',
                'products.size',
                'products.brand_id',
                'sale_returns.branch_id',
                'branches.name',
                'branches.code',
                'main_categories.name',
                'sub_categories.name',
                'brands.name',
            );

        return $this->aggregateTopMovingUnion($saleItems->unionAll($saleReturnItems), [
            'id',
            'name',
            'code',
            'main_category_id',
            'sub_category_id',
            'size',
            'brand_id',
            'branch_id',
            'branch_name',
            'branch_code',
            'main_category_name',
            'sub_category_name',
            'brand_name',
        ])->orderBy('sale_count', 'desc');
    }

    protected function aggregateTopMovingUnion(Builder $unionQuery, array $groupColumns): QueryBuilder
    {
        $query = DB::query()
            ->fromSub($unionQuery, 'stock_analysis_movements')
            ->select(
                DB::raw('MIN(id) as id'),
                DB::raw('MIN(name) as name'),
                'code',
                DB::raw('MIN(main_category_id) as main_category_id'),
                DB::raw('MIN(sub_category_id) as sub_category_id'),
                DB::raw('MIN(size) as size'),
                DB::raw('MIN(brand_id) as brand_id'),
                DB::raw('MIN(main_category_name) as main_category_name'),
                DB::raw('MIN(sub_category_name) as sub_category_name'),
                DB::raw('MIN(brand_name) as brand_name'),
                DB::raw('SUM(sale_count) as sale_count'),
                DB::raw('SUM(sale_return_count) as sale_return_count')
            );

        if (in_array('branch_id', $groupColumns, true)) {
            $query->addSelect(
                'branch_id',
                DB::raw('MIN(branch_name) as branch_name'),
                DB::raw('MIN(branch_code) as branch_code'),
            );
        }

        return $query->groupBy($groupColumns);
    }

    protected function baseSaleItemsQuery(): Builder
    {
        $query = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id');

        $this->applyProductTaxonomyJoins($query);

        $query
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->whereBetween('sales.date', [$this->from_date, $this->to_date])
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->where('products.type', 'product')
            ->when($this->branch_id, function (Builder $query): void {
                $query->where('sales.branch_id', $this->branch_id);
            });

        $this->applyProductSearch($query);
        $this->applyProductDropdownFilters($query);

        return $query;
    }

    protected function baseSaleReturnItemsQuery(): Builder
    {
        $query = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id');

        $this->applyProductTaxonomyJoins($query);

        $query
            ->leftJoin('branches', 'sale_returns.branch_id', '=', 'branches.id')
            ->whereBetween('sale_returns.date', [$this->from_date, $this->to_date])
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->where('products.type', 'product')
            ->when($this->branch_id, function (Builder $query): void {
                $query->where('sale_returns.branch_id', $this->branch_id);
            });

        $this->applyProductSearch($query);
        $this->applyProductDropdownFilters($query);

        return $query;
    }

    protected function applyProductSearch(Builder $query): Builder
    {
        $search = trim((string) $this->product_search);

        return $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.code', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%")
                    ->orWhere('products.barcode_number', 'like', "%{$search}%")
                    ->orWhere('products.size', 'like', "%{$search}%");
            });
        });
    }

    protected function applyProductDropdownFilters(Builder $query): Builder
    {
        return $query
            ->when($this->main_category_id, function (Builder $query): void {
                $query->where('products.main_category_id', $this->main_category_id);
            })
            ->when($this->sub_category_id, function (Builder $query): void {
                $query->where('products.sub_category_id', $this->sub_category_id);
            })
            ->when($this->brand_id, function (Builder $query): void {
                $query->where('products.brand_id', $this->brand_id);
            });
    }

    public function getChartData(): array
    {
        $data = $this->getTopMovingProducts()
            ->limit($this->topMovingLimit())
            ->get();

        return [
            'labels' => $data->map(function ($product): string {
                if ($this->group_by_code) {
                    return (string) ($product->code ?: $product->name);
                }
                $branchCode = $product->branch_code ?: $product->branch_name;

                return $branchCode ? "{$product->name} ({$branchCode})" : $product->name;
            })->toArray(),
            'datasets' => [
                [
                    'data' => $data->pluck('sale_count')->map(fn ($quantity): float => (float) $quantity)->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    ],
                ],
            ],
        ];
    }

    protected function topMovingLimit(): int
    {
        return max(5, min(20, (int) $this->limit));
    }

    protected function attachChildren($rows): void
    {
        if (! $this->group_by_code) {
            return;
        }

        // Paginators must be unwrapped to their underlying item collection;
        // collect($paginator) returns the meta array, not the items.
        $items = $rows instanceof \Illuminate\Contracts\Pagination\Paginator
            ? $rows->getCollection()
            : collect($rows);

        $codes = $items->pluck('code')->filter()->unique()->values()->all();
        if (empty($codes)) {
            return;
        }

        $original = $this->group_by_code;
        $this->group_by_code = false;

        try {
            $details = $this->report_type === 'top_moving'
                ? $this->getTopMovingProducts()->whereIn('code', $codes)->get()->groupBy('code')
                : $this->getNonMovingProducts()->whereIn('products.code', $codes)->get()->groupBy('code');
        } finally {
            $this->group_by_code = $original;
        }

        foreach ($items as $row) {
            $row->children = $details->get($row->code, collect());
        }
    }

    public function openMovementHistory(string $type, ?int $productId = null, ?string $code = null, ?int $branchId = null): void
    {
        abort_unless(in_array($type, ['sale', 'sale_return'], true), 404);

        $this->historyModalType = $type;
        $this->historyModalTitle = $type === 'sale' ? 'Sales Count History' : 'Sales Return Count History';
        $this->historyRows = $type === 'sale'
            ? $this->saleHistoryRows($productId, $code, $branchId)
            : $this->saleReturnHistoryRows($productId, $code, $branchId);

        $this->dispatch('stock-analysis-history-modal');
    }

    protected function saleHistoryRows(?int $productId, ?string $code, ?int $branchId): Collection
    {
        $query = $this->baseSaleItemsQuery()
            ->leftJoin('units', 'sale_items.unit_id', '=', 'units.id')
            ->when($productId, function (Builder $query) use ($productId): void {
                $query->where('sale_items.product_id', $productId);
            })
            ->when(! $productId && $code, function (Builder $query) use ($code): void {
                $query->where('products.code', $code);
            })
            ->when($branchId, function (Builder $query) use ($branchId): void {
                $query->where('sales.branch_id', $branchId);
            });

        return $query
            ->select(
                'sales.date',
                'sales.invoice_no as document_no',
                'products.id as product_id',
                'products.name as product_name',
                'products.code',
                'products.size',
                'branches.name as branch_name',
                'branches.code as branch_code',
                'units.name as unit_name',
                'sale_items.quantity',
                'sale_items.base_unit_quantity'
            )
            ->orderBy('sales.date', 'desc')
            ->orderBy('sale_items.id', 'desc')
            ->limit(100)
            ->get();
    }

    protected function saleReturnHistoryRows(?int $productId, ?string $code, ?int $branchId): Collection
    {
        $query = $this->baseSaleReturnItemsQuery()
            ->leftJoin('units', 'sale_return_items.unit_id', '=', 'units.id')
            ->when($productId, function (Builder $query) use ($productId): void {
                $query->where('sale_return_items.product_id', $productId);
            })
            ->when(! $productId && $code, function (Builder $query) use ($code): void {
                $query->where('products.code', $code);
            })
            ->when($branchId, function (Builder $query) use ($branchId): void {
                $query->where('sale_returns.branch_id', $branchId);
            });

        return $query
            ->select(
                'sale_returns.date',
                'sale_returns.reference_no as document_no',
                'products.id as product_id',
                'products.name as product_name',
                'products.code',
                'products.size',
                'branches.name as branch_name',
                'branches.code as branch_code',
                'units.name as unit_name',
                'sale_return_items.quantity',
                'sale_return_items.base_unit_quantity'
            )
            ->orderBy('sale_returns.date', 'desc')
            ->orderBy('sale_return_items.id', 'desc')
            ->limit(100)
            ->get();
    }

    public function render()
    {
        $branches = Branch::orderBy('name')->pluck('name', 'id');

        $products = $this->report_type === 'non_moving'
            ? $this->getNonMovingProducts()->paginate(10)
            : $this->getTopMovingProducts()->limit($this->topMovingLimit())->get();

        $this->attachChildren($products);

        $chartData = $this->report_type === 'top_moving' ? $this->getChartData() : null;

        return view('livewire.report.stock-analysis-report', compact('products', 'branches', 'chartData'));
    }
}
