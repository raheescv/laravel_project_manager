<?php

namespace App\Livewire\Report;

use App\Exports\TailoringOrderItemTailorReportExport;
use App\Models\Configuration;
use App\Models\TailoringCategory;
use App\Models\TailoringOrderItemTailor;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class TailoringOrderItemTailorReport extends Component
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

    public $sortField = 'tailoring_order_item_tailors.id';

    public $sortDirection = 'desc';

    public array $tailoring_order_item_tailor_report_visible_column = [];

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
        $config = Configuration::where('key', 'tailoring_order_item_tailor_report_visible_column')->value('value');
        $this->tailoring_order_item_tailor_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();

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
        if (str_starts_with($key, 'tailoring_order_item_tailor_report_visible_column.')) {
            Configuration::updateOrCreate(
                ['key' => 'tailoring_order_item_tailor_report_visible_column'],
                ['value' => json_encode($this->tailoring_order_item_tailor_report_visible_column)]
            );
        } elseif (! in_array($key, ['tailoring_order_item_tailor_report_visible_column'])) {
            $this->resetPage();
        }
    }

    public function updatedTailoringOrderItemTailorReportVisibleColumn(): void
    {
        Configuration::updateOrCreate(
            ['key' => 'tailoring_order_item_tailor_report_visible_column'],
            ['value' => json_encode($this->tailoring_order_item_tailor_report_visible_column)]
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
        $this->dispatch('tailoring-item-tailor-report-filters-reset');
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

    public function getTailorStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'delivered' => 'Delivered',
        ];
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
            'visible_columns' => $this->tailoring_order_item_tailor_report_visible_column,
        ];
        $exportFileName = 'TailoringOrderItemTailorReport_'.now()->timestamp.'.xlsx';

        return Excel::download(new TailoringOrderItemTailorReportExport($filter), $exportFileName);
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
            'item_quantity' => true,
            'item_status' => true,
            'tailor' => true,
            'tailor_commission' => true,
            'completion_date' => true,
            'rating' => true,
            'status' => true,
            'created_at' => true,
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
            'item_quantity' => 'Item Qty',
            'item_status' => 'Item Status',
            'tailor' => 'Tailor',
            'tailor_commission' => 'Commission',
            'completion_date' => 'Completion Date',
            'rating' => 'Rating',
            'status' => 'Tailor Status',
            'created_at' => 'Assigned At',
        ];
    }

    protected function getBaseQuery()
    {
        $allowedBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        return TailoringOrderItemTailor::query()
            ->with([
                'tailor:id,name',
                'tailoringOrderItem:id,tailoring_order_id,item_no,tailoring_category_id,tailoring_category_model_id,tailoring_category_model_type_id,product_name,product_color,quantity,status',
                'tailoringOrderItem.order:id,order_no,order_date,delivery_date,account_id,customer_name,branch_id',
                'tailoringOrderItem.order.account:id,name',
                'tailoringOrderItem.category:id,name',
                'tailoringOrderItem.categoryModel:id,name',
                'tailoringOrderItem.categoryModelType:id,name',
            ])
            ->join('tailoring_order_items', 'tailoring_order_items.id', '=', 'tailoring_order_item_tailors.tailoring_order_item_id')
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_order_items.tailoring_order_id')
            ->leftJoin('users as tailors', 'tailors.id', '=', 'tailoring_order_item_tailors.tailor_id')
            ->when($this->date_type === 'completion_date', function ($q) {
                return $q->when($this->from_date, fn ($q) => $q->whereDate('tailoring_order_item_tailors.completion_date', '>=', $this->from_date))
                    ->when($this->to_date, fn ($q) => $q->whereDate('tailoring_order_item_tailors.completion_date', '<=', $this->to_date));
            }, function ($q) {
                return $q->when($this->from_date, fn ($q) => $q->whereDate('tailoring_orders.'.$this->date_type, '>=', $this->from_date))
                    ->when($this->to_date, fn ($q) => $q->whereDate('tailoring_orders.'.$this->date_type, '<=', $this->to_date));
            })
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->when($this->customer_id, fn ($q) => $q->where('tailoring_orders.account_id', $this->customer_id))
            ->when($this->product_id, fn ($q) => $q->where('tailoring_order_items.product_id', $this->product_id))
            ->when($this->category_id, fn ($q) => $q->where('tailoring_order_items.tailoring_category_id', $this->category_id))
            ->when($this->tailor_id, fn ($q) => $q->where('tailoring_order_item_tailors.tailor_id', $this->tailor_id))
            ->when($this->status, fn ($q) => $q->whereIn('tailoring_order_item_tailors.status', $this->status))
            ->when($this->search, function ($q) {
                $term = trim($this->search);

                return $q->where(function ($q) use ($term) {
                    $q->where('tailoring_orders.order_no', 'like', "%{$term}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_name', 'like', "%{$term}%")
                        ->orWhere('tailoring_order_items.product_color', 'like', "%{$term}%")
                        ->orWhere('tailors.name', 'like', "%{$term}%");
                });
            })
            ->whereIn('tailoring_orders.branch_id', $allowedBranchIds)
            ->select('tailoring_order_item_tailors.*');
    }

    public function render()
    {
        $query = $this->getBaseQuery();

        $totals = (clone $query)->selectRaw("\n            COUNT(*) as total_assignments,\n            COALESCE(SUM(tailoring_order_item_tailors.tailor_commission), 0) as total_commission,\n            COALESCE(AVG(tailoring_order_item_tailors.rating), 0) as avg_rating,\n            SUM(CASE WHEN tailoring_order_item_tailors.status = 'pending' THEN 1 ELSE 0 END) as pending_count,\n            SUM(CASE WHEN tailoring_order_item_tailors.status = 'completed' THEN 1 ELSE 0 END) as completed_count,\n            SUM(CASE WHEN tailoring_order_item_tailors.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count\n        ")->first();

        $total = [
            'total_assignments' => (int) ($totals->total_assignments ?? 0),
            'total_commission' => (float) ($totals->total_commission ?? 0),
            'avg_rating' => (float) ($totals->avg_rating ?? 0),
            'pending_count' => (int) ($totals->pending_count ?? 0),
            'completed_count' => (int) ($totals->completed_count ?? 0),
            'delivered_count' => (int) ($totals->delivered_count ?? 0),
        ];

        $data = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->limit);

        $branchIds = Auth::user()->branches->pluck('branch_id')->toArray();
        $branchOptions = ['' => 'All Branches'] + \App\Models\Branch::whereIn('id', $branchIds)->pluck('name', 'id')->toArray();

        $categoryOptions = ['' => 'All Categories'] + TailoringCategory::ordered()->active()->pluck('name', 'id')->toArray();

        return view('livewire.report.tailoring-order-item-tailor-report', [
            'data' => $data,
            'total' => $total,
            'columnDefinitions' => $this->getColumnDefinitions(),
            'branchOptions' => $branchOptions,
            'categoryOptions' => $categoryOptions,
            'tailorStatusOptions' => $this->getTailorStatusOptions(),
        ]);
    }
}
