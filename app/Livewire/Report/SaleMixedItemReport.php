<?php

namespace App\Livewire\Report;

use App\Exports\SaleMixedItemReportExport;
use App\Jobs\Export\ExportSaleMixedItemReportJob;
use App\Models\Configuration;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SaleMixedItemReport extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $branch_id = '';

    public $product_id = '';

    public $department_id = '';

    public $main_category_id = '';

    public $brand_id = '';

    public $type = '';

    public $limit = 100;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    public array $sale_mixed_item_report_visible_column = [];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->branch_id = Auth::user()->default_branch_id;
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $config = Configuration::where('key', 'sale_mixed_item_report_visible_column')->value('value');
        $this->sale_mixed_item_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    public function updated($key, $value): void
    {
        $this->resetPage();
    }

    public function export()
    {
        $count = $this->baseQuery()->count();
        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
            'department_id' => $this->department_id,
            'main_category_id' => $this->main_category_id,
            'brand_id' => $this->brand_id,
            'type' => $this->type,
        ];

        if ($count > 2000) {
            ExportSaleMixedItemReportJob::dispatch(Auth::user(), $filter, $this->sale_mixed_item_report_visible_column);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'SaleAndReturnItemReport_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleMixedItemReportExport($filter, $this->sale_mixed_item_report_visible_column), $exportFileName);
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    protected function getDefaultColumns(): array
    {
        return [
            'type' => true,
            'date' => true,
            'created_at' => true,
            'reference' => true,
            'product_name' => true,
            'product_code' => true,
            'department_name' => true,
            'main_category_name' => true,
            'brand_name' => true,
            'unit_price' => true,
            'quantity' => true,
            'gross_amount' => true,
            'discount' => true,
            'net_amount' => true,
            'tax_amount' => true,
            'total' => true,
        ];
    }

    protected function baseQuery()
    {
        $accessibleBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        // Sales side
        $saleQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('units', 'units.id', '=', 'sale_items.unit_id')
            ->leftJoin('departments', 'departments.id', '=', 'products.department_id')
            ->leftJoin('categories as main_categories', 'main_categories.id', '=', 'products.main_category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when($this->from_date, fn ($q) => $q->whereDate('sales.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sales.date', '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_items.product_id', $this->product_id))
            ->when($this->department_id, fn ($q) => $q->where('products.department_id', $this->department_id))
            ->when($this->main_category_id, fn ($q) => $q->where('products.main_category_id', $this->main_category_id))
            ->when($this->brand_id, fn ($q) => $q->where('products.brand_id', $this->brand_id))
            ->whereIn('sales.branch_id', $accessibleBranchIds)
            ->where('sales.status', 'completed')
            ->select([
                DB::raw("'sale' as type"),
                'sale_items.id as id',
                'sale_items.sale_id as parent_id',
                'sales.date as date',
                'sales.created_at as created_at',
                'sales.invoice_no as reference',
                'products.name as product_name',
                'products.cost',
                'products.code as product_code',
                'units.name as unit_name',
                'departments.name as department_name',
                'main_categories.name as main_category_name',
                'brands.name as brand_name',
                'sale_items.unit_price',
                'sale_items.quantity',
                'sale_items.base_unit_quantity',
                'sale_items.gross_amount',
                'sale_items.discount',
                'sale_items.net_amount',
                'sale_items.tax_amount',
                'sale_items.total',
                'sales.branch_id as branch_id',
            ]);

        // Sale returns side
        $returnQuery = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->leftJoin('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->join('units', 'units.id', '=', 'sale_items.unit_id')
            ->leftJoin('departments', 'departments.id', '=', 'products.department_id')
            ->leftJoin('categories as main_categories', 'main_categories.id', '=', 'products.main_category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when($this->from_date, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('sale_returns.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_return_items.product_id', $this->product_id))
            ->when($this->department_id, fn ($q) => $q->where('products.department_id', $this->department_id))
            ->when($this->main_category_id, fn ($q) => $q->where('products.main_category_id', $this->main_category_id))
            ->when($this->brand_id, fn ($q) => $q->where('products.brand_id', $this->brand_id))
            ->whereIn('sale_returns.branch_id', $accessibleBranchIds)
            ->where('sale_returns.status', 'completed')
            ->select([
                DB::raw("'sale_return' as type"),
                'sale_return_items.id as id',
                'sale_return_items.sale_return_id as parent_id',
                'sale_returns.date as date',
                'sale_returns.created_at as created_at',
                DB::raw('COALESCE(sale_returns.reference_no, sale_returns.id) as reference'),
                'products.name as product_name',
                'products.cost',
                'products.code as product_code',
                'units.name as unit_name',
                'departments.name as department_name',
                'main_categories.name as main_category_name',
                'brands.name as brand_name',
                // Keep unit price as positive for readability; make quantities and amounts negative
                'sale_return_items.unit_price',
                DB::raw('(-1) * sale_return_items.quantity as quantity'),
                DB::raw('(-1) * sale_return_items.base_unit_quantity as base_unit_quantity'),
                DB::raw('(-1) * sale_return_items.gross_amount as gross_amount'),
                DB::raw('(-1) * sale_return_items.discount as discount'),
                DB::raw('(-1) * sale_return_items.net_amount as net_amount'),
                DB::raw('(-1) * sale_return_items.tax_amount as tax_amount'),
                DB::raw('(-1) * sale_return_items.total as total'),
                'sale_returns.branch_id as branch_id',
            ]);

        $union = $saleQuery->unionAll($returnQuery);

        // Wrap union as a subquery to allow ordering and pagination
        $selectedType = $this->type;
        $outer = DB::query()->fromSub($union, 't')
            ->when($selectedType, fn ($q) => $q->where('type', $selectedType))
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('id', 'desc');

        return $outer;
    }

    public function render()
    {
        $query = $this->baseQuery();
        $totals = clone $query;
        $data = $query->paginate($this->limit);

        $total = [
            'quantity' => (float) $totals->sum('quantity'),
            'base_unit_quantity' => (float) $totals->sum('base_unit_quantity'),
            'gross_amount' => (float) $totals->sum('gross_amount'),
            'discount' => (float) $totals->sum('discount'),
            'net_amount' => (float) $totals->sum('net_amount'),
            'tax_amount' => (float) $totals->sum('tax_amount'),
            'total' => (float) $totals->sum('total'),
        ];

        return view('livewire.report.sale-mixed-item-report', [
            'data' => $data,
            'total' => $total,
        ]);
    }
}
