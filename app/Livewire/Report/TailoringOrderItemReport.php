<?php

namespace App\Livewire\Report;

use App\Exports\TailoringOrderItemReportExport;
use App\Models\Configuration;
use App\Models\TailoringCategory;
use App\Models\TailoringOrderItem;
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
            'category_model_type' => true,
            'product_name' => true,
            'product_color' => true,
            'unit' => true,
            'quantity' => true,
            'quantity_per_item' => true,
            'completed_quantity' => true,
            'pending_quantity' => true,
            'delivered_quantity' => true,
            'unit_price' => true,
            'stitch_rate' => true,
            'gross_amount' => true,
            'discount' => true,
            'net_amount' => true,
            'tax' => true,
            'tax_amount' => true,
            'total' => true,
            'tailor_total_commission' => true,
            'used_quantity' => true,
            'wastage' => true,
            'total_quantity_used' => true,
            'tailoring_notes' => true,
            'completion_status' => true,
            'delivery_status' => true,
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
            'category_model_type' => 'Model Type',
            'product_name' => 'Product',
            'product_color' => 'Color',
            'unit' => 'Unit',
            'quantity' => 'Qty',
            'quantity_per_item' => 'Meter Per Item',
            'completed_quantity' => 'Completed Qty',
            'pending_quantity' => 'Pending Qty',
            'delivered_quantity' => 'Delivered Qty',
            'unit_price' => 'Unit Price',
            'stitch_rate' => 'Stitch Rate',
            'gross_amount' => 'Gross',
            'discount' => 'Discount',
            'net_amount' => 'Net',
            'tax' => 'Tax %',
            'tax_amount' => 'Tax Amt',
            'total' => 'Total',
            'tailor_total_commission' => 'Tailor Commission',
            'used_quantity' => 'Used Qty',
            'wastage' => 'Wastage',
            'total_quantity_used' => 'Total Used Qty',
            'item_completion_date' => 'Completion Date',
            'tailoring_notes' => 'Notes',
            'completion_status' => 'Completion Status',
            'delivery_status' => 'Delivery Status',
            'status' => 'Item Status',
        ];
    }

    protected function getBaseQuery()
    {
        $allowedBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        return TailoringOrderItem::query()
            ->with([
                'order:id,order_no,order_date,delivery_date,account_id,customer_name,status,branch_id',
                'order.account:id,name',
                'category:id,name',
                'categoryModel:id,name',
                'categoryModelType:id,name',
                'unit:id,name',
            ])
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->when($this->from_date, fn ($q) => $q->whereDate('tailoring_orders.'.$this->date_type, '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('tailoring_orders.'.$this->date_type, '<=', $this->to_date))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->when($this->customer_id, fn ($q) => $q->where('tailoring_orders.account_id', $this->customer_id))
            ->when($this->product_id, fn ($q) => $q->where('tailoring_order_items.product_id', $this->product_id))
            ->when($this->category_id, fn ($q) => $q->where('tailoring_order_items.tailoring_category_id', $this->category_id))
            ->when($this->status, fn ($q) => $q->whereIn('tailoring_order_items.status', $this->status))
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
        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(tailoring_order_items.quantity), 0) as quantity,
            COALESCE(SUM(tailoring_order_items.completed_quantity), 0) as completed_quantity,
            COALESCE(SUM(tailoring_order_items.pending_quantity), 0) as pending_quantity,
            COALESCE(SUM(tailoring_order_items.delivered_quantity), 0) as delivered_quantity,
            COALESCE(SUM(tailoring_order_items.used_quantity), 0) as used_quantity,
            COALESCE(SUM(tailoring_order_items.wastage), 0) as wastage,
            COALESCE(SUM(tailoring_order_items.total_quantity_used), 0) as total_quantity_used,
            COALESCE(SUM(tailoring_order_items.gross_amount), 0) as gross_amount,
            COALESCE(SUM(tailoring_order_items.discount), 0) as discount,
            COALESCE(SUM(tailoring_order_items.net_amount), 0) as net_amount,
            COALESCE(SUM(tailoring_order_items.tax_amount), 0) as tax_amount,
            COALESCE(SUM(tailoring_order_items.total), 0) as total,
            COALESCE(SUM(tailoring_order_items.tailor_total_commission), 0) as tailor_total_commission
        ')->first();

        $total = [
            'quantity' => (float) ($totals->quantity ?? 0),
            'quantity_per_item' => null,
            'completed_quantity' => (float) ($totals->completed_quantity ?? 0),
            'pending_quantity' => (float) ($totals->pending_quantity ?? 0),
            'delivered_quantity' => (float) ($totals->delivered_quantity ?? 0),
            'used_quantity' => (float) ($totals->used_quantity ?? 0),
            'wastage' => (float) ($totals->wastage ?? 0),
            'total_quantity_used' => (float) ($totals->total_quantity_used ?? 0),
            'gross_amount' => (float) ($totals->gross_amount ?? 0),
            'discount' => (float) ($totals->discount ?? 0),
            'net_amount' => (float) ($totals->net_amount ?? 0),
            'tax_amount' => (float) ($totals->tax_amount ?? 0),
            'total' => (float) ($totals->total ?? 0),
            'tailor_total_commission' => (float) ($totals->tailor_total_commission ?? 0),
        ];

        $data = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->limit);

        $branchIds = Auth::user()->branches->pluck('branch_id')->toArray();
        $branchOptions = ['' => 'All Branches'] + \App\Models\Branch::whereIn('id', $branchIds)->pluck('name', 'id')->toArray();

        $categoryOptions = ['' => 'All Categories'] + TailoringCategory::ordered()->active()->pluck('name', 'id')->toArray();

        return view('livewire.report.tailoring-order-item-report', [
            'data' => $data,
            'total' => $total,
            'columnDefinitions' => $this->getColumnDefinitions(),
            'branchOptions' => $branchOptions,
            'categoryOptions' => $categoryOptions,
        ]);
    }
}
