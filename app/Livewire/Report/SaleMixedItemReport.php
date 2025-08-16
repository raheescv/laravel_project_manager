<?php

namespace App\Livewire\Report;

use App\Models\Configuration;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SaleMixedItemReport extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $branch_id = '';

    public $product_id = '';

    public $type = '';

    public $limit = 100;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    public array $sale_mixed_item_report_visible_column = [];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $config = Configuration::where('key', 'sale_mixed_item_report_visible_column')->value('value');
        $this->sale_mixed_item_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    public function updated($key, $value): void
    {
        $this->resetPage();
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
            'unit_price' => true,
            'quantity' => true,
            'gross_amount' => true,
            'discount' => true,
            'net_amount' => true,
            'tax_amount' => true,
            'total' => true,
        ];
    }

    public function render()
    {
        $query = $this->baseQuery();
        $totals = clone $query;
        $data = $query->paginate($this->limit);

        $total = [
            'quantity' => (float) $totals->sum('quantity'),
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

    protected function baseQuery()
    {
        $accessibleBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        // Sales side
        $saleQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->when($this->from_date, fn ($q) => $q->whereDate('sales.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sales.date', '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_items.product_id', $this->product_id))
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
                'products.code as product_code',
                'sale_items.unit_price',
                'sale_items.quantity',
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
            ->when($this->from_date, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('sale_returns.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_return_items.product_id', $this->product_id))
            ->whereIn('sale_returns.branch_id', $accessibleBranchIds)
            ->where('sale_returns.status', 'completed')
            ->select([
                DB::raw("'sale_return' as type"),
                'sale_return_items.id as id',
                'sale_return_items.sale_return_id as parent_id',
                'sale_returns.date as date',
                'sale_returns.created_at as created_at',
                'sale_returns.reference_no as reference',
                'products.name as product_name',
                'products.code as product_code',
                // Keep unit price as positive for readability; make quantities and amounts negative
                'sale_return_items.unit_price',
                DB::raw('(-1) * sale_return_items.quantity as quantity'),
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
}
