<?php

namespace App\Livewire\Report;

use App\Exports\TailoringOrderItemReportExport;
use App\Models\Configuration;
use App\Models\TailoringCategory;
use App\Models\TailoringOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class TailoringOrderItemReport extends Component
{
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $product_id = '';

    public $category_id = '';

    public $tailor_id = '';

    public $status = '';

    public $date_type = 'order_date';

    public $from_date = '';

    public $to_date = '';

    public $limit = 25;

    public $sortField = 'tailoring_order_items.id';

    public $sortDirection = 'desc';

    public array $tailoring_order_item_report_visible_column = [];

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'from_date' => ['except' => ''],
        'to_date' => ['except' => ''],
        'branch_id' => ['except' => ''],
        'customer_id' => ['except' => ''],
        'product_id' => ['except' => ''],
        'category_id' => ['except' => ''],
        'tailor_id' => ['except' => ''],
        'status' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $config = Configuration::where('key', 'tailoring_order_item_report_visible_column')->value('value');
        $this->tailoring_order_item_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
        if ($this->from_date === '' && $this->to_date === '') {
            $this->from_date = date('Y-m-d');
            $this->to_date = date('Y-m-d');
        }
        if ($this->branch_id === '') {
            $this->branch_id = session('branch_id', '');
        }
    }

    public function updated($key, $value): void
    {
        if (str_starts_with($key, 'tailoring_order_item_report_visible_column.')) {
            Configuration::updateOrCreate(
                ['key' => 'tailoring_order_item_report_visible_column'],
                ['value' => json_encode($this->tailoring_order_item_report_visible_column)]
            );
        } elseif (! in_array($key, ['tailoring_order_item_report_visible_column'])) {
            $this->resetPage();
        }
    }

    public function updatedTailoringOrderItemReportVisibleColumn(): void
    {
        Configuration::updateOrCreate(
            ['key' => 'tailoring_order_item_report_visible_column'],
            ['value' => json_encode($this->tailoring_order_item_report_visible_column)]
        );
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->branch_id = session('branch_id', '');
        $this->customer_id = '';
        $this->product_id = '';
        $this->category_id = '';
        $this->tailor_id = '';
        $this->status = '';
        $this->date_type = 'order_date';
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $this->resetPage();
        $this->dispatch('tailoring-item-report-filters-reset');
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

    public function export()
    {
        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
            'tailor_id' => $this->tailor_id,
            'status' => $this->status,
            'search' => $this->search,
            'date_type' => $this->date_type,
            'visible_columns' => $this->tailoring_order_item_report_visible_column,
        ];
        $exportFileName = 'TailoringOrderItemReport_'.now()->timestamp.'.xlsx';

        return Excel::download(new TailoringOrderItemReportExport($filter), $exportFileName);
    }

    protected function getDefaultColumns(): array
    {
        return [
            'order_no' => true,
            'order_date' => true,
            'customer' => true,
            'item_no' => true,
            'category' => true,
            'category_model' => true,
            'product_name' => true,
            'product_color' => true,
            'unit' => true,
            'quantity' => true,
            'quantity_per_item' => true,
            'completed_quantity' => true,
            'unit_price' => true,
            'stitch_rate' => true,
            'gross_amount' => true,
            'discount' => true,
            'net_amount' => true,
            'tax' => true,
            'tax_amount' => true,
            'total' => true,
            'tailor' => true,
            'tailor_commission' => true,
            'used_quantity' => true,
            'wastage' => true,
            'item_completion_date' => true,
            'is_selected_for_completion' => true,
            'tailoring_notes' => true,
            'rating' => true,
            'status' => true,
        ];
    }

    public function getColumnDefinitions(): array
    {
        return [
            'order_no' => 'Order No',
            'order_date' => 'Order Date',
            'customer' => 'Customer',
            'item_no' => 'Item #',
            'category' => 'Category',
            'category_model' => 'Model',
            'product_name' => 'Product',
            'product_color' => 'Color',
            'unit' => 'Unit',
            'quantity' => 'Qty',
            'quantity_per_item' => 'Qty Per Item',
            'completed_quantity' => 'Completed Qty',
            'unit_price' => 'Unit Price',
            'stitch_rate' => 'Stitch Rate',
            'gross_amount' => 'Gross',
            'discount' => 'Discount',
            'net_amount' => 'Net',
            'tax' => 'Tax %',
            'tax_amount' => 'Tax Amt',
            'total' => 'Total',
            'tailor' => 'Tailor',
            'tailor_commission' => 'Tailor Commission',
            'used_quantity' => 'Used Qty',
            'wastage' => 'Wastage',
            'item_completion_date' => 'Completion Date',
            'is_selected_for_completion' => 'Selected for Completion',
            'tailoring_notes' => 'Notes',
            'rating' => 'Rating',
            'status' => 'Item Status',
        ];
    }

    protected function getBaseQuery()
    {
        $allowedBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        return TailoringOrderItem::query()
            ->with(['order:id,order_no,order_date,delivery_date,account_id,customer_name,status,branch_id', 'order.account:id,name', 'category:id,name', 'categoryModel:id,name', 'unit:id,name', 'tailor:id,name'])
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->when($this->from_date, fn ($q) => $q->whereDate('tailoring_orders.'.$this->date_type, '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('tailoring_orders.'.$this->date_type, '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->when($this->customer_id, fn ($q) => $q->where('tailoring_orders.account_id', $this->customer_id))
            ->when($this->product_id, fn ($q) => $q->where('tailoring_order_items.product_id', $this->product_id))
            ->when($this->category_id, fn ($q) => $q->where('tailoring_order_items.tailoring_category_id', $this->category_id))
            ->when($this->tailor_id, fn ($q) => $q->where('tailoring_order_items.tailor_id', $this->tailor_id))
            ->when($this->status, fn ($q) => $q->where('tailoring_orders.status', $this->status))
            ->when($this->search, function ($q) {
                $term = trim($this->search);

                return $q->where(function ($q) use ($term) {
                    $q->where('tailoring_orders.order_no', 'like', "%{$term}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_color', 'like', "%{$term}%");
                });
            })
            ->whereIn('tailoring_orders.branch_id', $allowedBranchIds)
            ->select('tailoring_order_items.*');
    }

    public function render()
    {
        $query = $this->getBaseQuery();
        $totals = (clone $query);
        $total = [
            'quantity' => $totals->sum('tailoring_order_items.quantity'),
            'quantity_per_item' => null,
            'completed_quantity' => $totals->sum('tailoring_order_items.completed_quantity'),
            'used_quantity' => $totals->sum('tailoring_order_items.used_quantity'),
            'wastage' => $totals->sum('tailoring_order_items.wastage'),
            'gross_amount' => $totals->sum('tailoring_order_items.gross_amount'),
            'discount' => $totals->sum('tailoring_order_items.discount'),
            'net_amount' => $totals->sum('tailoring_order_items.net_amount'),
            'tax_amount' => $totals->sum('tailoring_order_items.tax_amount'),
            'total' => $totals->sum('tailoring_order_items.total'),
            'tailor_commission' => $totals->sum('tailoring_order_items.tailor_total_commission'),
        ];

        $data = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->limit);

        $branchIds = Auth::user()->branches->pluck('branch_id')->toArray();
        $branchOptions = ['' => 'All Branches'] + \App\Models\Branch::whereIn('id', $branchIds)->pluck('name', 'id')->toArray();

        $categoryOptions = ['' => 'All Categories'] + TailoringCategory::ordered()->active()->pluck('name', 'id')->toArray();

        $tailorIds = TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->whereIn('tailoring_orders.branch_id', $branchIds)
            ->whereNotNull('tailoring_order_items.tailor_id')
            ->distinct()
            ->pluck('tailoring_order_items.tailor_id');
        $tailorOptions = ['' => 'All Tailors'] + User::whereIn('id', $tailorIds)->pluck('name', 'id')->toArray();

        return view('livewire.report.tailoring-order-item-report', [
            'data' => $data,
            'total' => $total,
            'columnDefinitions' => $this->getColumnDefinitions(),
            'branchOptions' => $branchOptions,
            'categoryOptions' => $categoryOptions,
            'tailorOptions' => $tailorOptions,
        ]);
    }
}
