<?php

namespace App\Livewire\Report\Sale;

use App\Exports\CategoryWiseSaleReportExport;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * /report/sale_category — completed sale lines less completed return lines,
 * rolled up to each product's main category, with a drill-down into the
 * category's best-selling products.
 *
 * The web twin of the mobile Reports "By Category" breakdown
 * (V1 Report\GetAction::categoryWise): same grouping, same net figures, with
 * the gross / discount / tax / returns split out. Products whose category was
 * deleted still count, under "Uncategorised".
 */
class CategoryWiseReport extends Component
{
    use WithPagination;

    public const SORTABLE = ['group_name', 'products_count', 'bills_count', 'quantity', 'gross_amount', 'discount', 'tax_amount', 'return_total', 'net_total'];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => '7 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
    ];

    public const PRODUCT_TYPES = ['' => 'All', 'product' => 'Products', 'service' => 'Services'];

    /** Drill-down depth: how many products a category expands to. */
    public const TOP_PRODUCTS = 10;

    /** Key of the expanded category row — a category id, or "none" for Uncategorised. */
    public const UNCATEGORISED = 'none';

    public $search = '';

    public $branch_id = '';

    public $employee_id = '';

    public $product_type = '';

    public $preset = 'today';

    public $from_date;

    public $to_date;

    public $limit = 25;

    public $sortField = 'net_total';

    public $sortDirection = 'desc';

    public $expanded = null;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->branch_id = session('branch_id');
        $this->setRange('today');
    }

    public function setRange($preset)
    {
        $today = Carbon::today();
        $range = match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            '7d' => [$today->copy()->subDays(6), $today],
            'this_month' => [$today->copy()->startOfMonth(), $today],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => null,
        };
        if (! $range) {
            return;
        }

        $this->preset = $preset;
        $this->from_date = $range[0]->toDateString();
        $this->to_date = $range[1]->toDateString();
        $this->resetView();
    }

    public function setProductType($type)
    {
        $this->product_type = array_key_exists($type, self::PRODUCT_TYPES) ? $type : '';
        $this->resetView();
    }

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'group_name' ? 'asc' : 'desc';
        }
        $this->resetPage();
    }

    public function toggle($key)
    {
        $key = (string) $key;
        $this->expanded = $this->expanded === $key ? null : $key;
    }

    public function updated($key)
    {
        if (in_array($key, ['from_date', 'to_date'], true)) {
            $this->preset = 'custom';
        }
        if (! in_array((int) $this->limit, self::PER_PAGE_OPTIONS, true)) {
            $this->limit = 25;
        }
        $this->resetView();
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('report.sale category'), 403);

        $rows = $this->categories()->orderBy($this->sortColumn(), $this->sortDirection())->orderBy('group_name')->get();
        $filters = ['from_date' => $this->from_date, 'to_date' => $this->to_date];

        return Excel::download(new CategoryWiseSaleReportExport($rows->all(), $this->summary(), $filters), 'CategoryWiseSaleReport_'.now()->timestamp.'.xlsx');
    }

    public function render()
    {
        $summary = $this->summary();

        $data = $this->categories()
            ->when(trim($this->search), fn ($q, $value) => $q->whereRaw('COALESCE(group_name, ?) like ?', ['Uncategorised', "%{$value}%"]))
            ->orderBy($this->sortColumn(), $this->sortDirection())
            ->orderBy('group_name')
            ->paginate((int) $this->limit);

        $mix = $this->categories()->orderByDesc('net_total')->orderBy('group_name')->limit(5)->get();

        $products = $this->expanded === null ? collect() : $this->products($this->expanded);

        return view('livewire.report.sale.category-wise-report', [
            'data' => $data,
            'summary' => $summary,
            'mix' => $mix,
            'products' => $products,
        ]);
    }

    /**
     * One row per main category: `group_id`, `group_name`, `group_meta`
     * (the category image path) and the summed figures.
     */
    public function categories(): Builder
    {
        return $this->aggregate(
            ['categories.id as group_id', 'categories.name as group_name', 'categories.image_path as group_meta'],
            ['categories.id', 'categories.name', 'categories.image_path'],
        );
    }

    /** The best-selling products inside one category row. */
    public function products(string $categoryKey)
    {
        $scope = fn ($q) => $categoryKey === self::UNCATEGORISED
            ? $q->whereNull('categories.id')
            : $q->where('categories.id', (int) $categoryKey);

        return $this->aggregate(
            ['products.id as group_id', 'products.name as group_name', 'products.code as group_meta'],
            ['products.id', 'products.name', 'products.code'],
            $scope,
        )
            ->orderByDesc('net_total')
            ->orderBy('group_name')
            ->limit(self::TOP_PRODUCTS)
            ->get();
    }

    /**
     * Whole-period totals across every category — ignores the search box so
     * shares stay relative to the full period.
     */
    public function summary(): array
    {
        $sales = $this->saleLines()->toBase()
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as bills_count')
            ->selectRaw('COUNT(DISTINCT sale_items.product_id) as products_count')
            ->selectRaw('COALESCE(SUM(sale_items.base_unit_quantity), 0) as sale_qty')
            ->selectRaw('COALESCE(SUM(sale_items.gross_amount), 0) as gross_amount')
            ->selectRaw('COALESCE(SUM(sale_items.discount), 0) as discount')
            ->selectRaw('COALESCE(SUM(sale_items.tax_amount), 0) as tax_amount')
            ->selectRaw('COALESCE(SUM(sale_items.total), 0) as sale_total')
            ->first();

        $returns = $this->returnLines()->toBase()
            ->selectRaw('COUNT(DISTINCT sale_return_items.sale_return_id) as returns_count')
            ->selectRaw('COALESCE(SUM(sale_return_items.base_unit_quantity), 0) as return_qty')
            ->selectRaw('COALESCE(SUM(sale_return_items.total), 0) as return_total')
            ->first();

        return [
            'categories' => $this->categories()->count(),
            'bills_count' => (int) $sales->bills_count,
            'products_count' => (int) $sales->products_count,
            'returns_count' => (int) $returns->returns_count,
            'sale_qty' => (float) $sales->sale_qty,
            'return_qty' => (float) $returns->return_qty,
            'quantity' => (float) $sales->sale_qty - (float) $returns->return_qty,
            'gross_amount' => (float) $sales->gross_amount,
            'discount' => (float) $sales->discount,
            'tax_amount' => (float) $sales->tax_amount,
            'sale_total' => (float) $sales->sale_total,
            'return_total' => (float) $returns->return_total,
            'net_total' => (float) $sales->sale_total - (float) $returns->return_total,
        ];
    }

    /**
     * Sale lines and return lines grouped the same way, stacked, then netted.
     * Returns a query over the netted rows so callers can sort, page or filter.
     */
    private function aggregate(array $select, array $groupBy, ?callable $scope = null): Builder
    {
        $sales = $this->saleLines()
            ->when($scope, $scope)
            ->groupBy($groupBy)
            ->select($select)
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as bills_count')
            ->selectRaw('COUNT(DISTINCT sale_items.product_id) as products_count')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sale_qty')
            ->selectRaw('0 as return_qty')
            ->selectRaw('SUM(sale_items.gross_amount) as gross_amount')
            ->selectRaw('SUM(sale_items.discount) as discount')
            ->selectRaw('SUM(sale_items.tax_amount) as tax_amount')
            ->selectRaw('SUM(sale_items.total) as sale_total')
            ->selectRaw('0 as return_total');

        $returns = $this->returnLines()
            ->when($scope, $scope)
            ->groupBy($groupBy)
            ->select($select)
            ->selectRaw('0 as bills_count')
            ->selectRaw('0 as products_count')
            ->selectRaw('0 as sale_qty')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_qty')
            ->selectRaw('0 as gross_amount')
            ->selectRaw('0 as discount')
            ->selectRaw('0 as tax_amount')
            ->selectRaw('0 as sale_total')
            ->selectRaw('SUM(sale_return_items.total) as return_total');

        $net = DB::query()
            ->fromSub($sales->unionAll($returns), 'u')
            ->groupBy('group_id', 'group_name', 'group_meta')
            ->select('group_id', 'group_name', 'group_meta')
            ->selectRaw('SUM(bills_count) as bills_count')
            ->selectRaw('SUM(products_count) as products_count')
            ->selectRaw('SUM(sale_qty) as sale_qty')
            ->selectRaw('SUM(return_qty) as return_qty')
            ->selectRaw('SUM(sale_qty) - SUM(return_qty) as quantity')
            ->selectRaw('SUM(gross_amount) as gross_amount')
            ->selectRaw('SUM(discount) as discount')
            ->selectRaw('SUM(tax_amount) as tax_amount')
            ->selectRaw('SUM(sale_total) as sale_total')
            ->selectRaw('SUM(return_total) as return_total')
            ->selectRaw('SUM(sale_total) - SUM(return_total) as net_total');

        return DB::query()->fromSub($net, 'n');
    }

    private function saleLines(): EloquentBuilder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.main_category_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->whereIn('sales.branch_id', $this->branchIds())
            ->when($this->from_date, fn ($q, $value) => $q->where('sales.date', '>=', $value))
            ->when($this->to_date, fn ($q, $value) => $q->where('sales.date', '<=', $value))
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->employee_id, fn ($q, $value) => $q->where('sale_items.employee_id', $value))
            ->when($this->product_type, fn ($q, $value) => $q->where('products.type', $value));
    }

    private function returnLines(): EloquentBuilder
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.main_category_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->whereIn('sale_returns.branch_id', $this->branchIds())
            ->when($this->from_date, fn ($q, $value) => $q->where('sale_returns.date', '>=', $value))
            ->when($this->to_date, fn ($q, $value) => $q->where('sale_returns.date', '<=', $value))
            ->when($this->branch_id, fn ($q, $value) => $q->where('sale_returns.branch_id', $value))
            ->when($this->employee_id, fn ($q, $value) => $q->where('sale_return_items.employee_id', $value))
            ->when($this->product_type, fn ($q, $value) => $q->where('products.type', $value));
    }

    private function branchIds()
    {
        return Auth::user()->branches->pluck('branch_id');
    }

    private function sortColumn(): string
    {
        return in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'net_total';
    }

    private function sortDirection(): string
    {
        return $this->sortDirection === 'asc' ? 'asc' : 'desc';
    }

    private function resetView(): void
    {
        $this->expanded = null;
        $this->resetPage();
    }
}
