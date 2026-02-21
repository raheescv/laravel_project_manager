<?php

namespace App\Livewire\Report;

use App\Exports\TailoringNonDeliveryReportExport;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\TailoringCategory;
use App\Traits\Report\BuildsTailoringNonDeliveryQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class TailoringNonDeliveryReport extends Component
{
    use BuildsTailoringNonDeliveryQuery;
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $product_id = '';

    public $category_id = '';

    public array $status = ['pending', 'completed'];

    public $date_type = 'order_date';

    public $from_date = '';

    public $to_date = '';

    public $limit = 25;

    public $sortField = 'tailoring_orders.order_date';

    public $sortDirection = 'desc';

    public array $tailoring_non_delivery_report_visible_column = [];

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'from_date' => ['except' => ''],
        'to_date' => ['except' => ''],
        'branch_id' => ['except' => ''],
        'customer_id' => ['except' => ''],
        'product_id' => ['except' => ''],
        'category_id' => ['except' => ''],
        'status' => ['except' => []],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $config = Configuration::where('key', 'tailoring_non_delivery_report_visible_column')->value('value');
        $this->tailoring_non_delivery_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();

        if ($this->from_date === '' && $this->to_date === '') {
            $this->from_date = date('Y-m-d');
            $this->to_date = date('Y-m-d');
        }

        if ($this->branch_id === '') {
            $this->branch_id = session('branch_id', '');
        }

        if (empty($this->status)) {
            $this->status = ['pending', 'completed'];
        }
    }

    public function updated($key, $value): void
    {
        if (str_starts_with($key, 'tailoring_non_delivery_report_visible_column.')) {
            Configuration::updateOrCreate(
                ['key' => 'tailoring_non_delivery_report_visible_column'],
                ['value' => json_encode($this->tailoring_non_delivery_report_visible_column)]
            );
        } else {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->branch_id = session('branch_id', '');
        $this->customer_id = '';
        $this->product_id = '';
        $this->category_id = '';
        $this->status = ['pending', 'completed'];
        $this->date_type = 'order_date';
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $this->resetPage();
        $this->dispatch('tailoring-non-delivery-report-filters-reset');
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

    public function exportExcel()
    {
        $exportFileName = 'TailoringNonDeliveryReport_'.now()->timestamp.'.xlsx';

        return Excel::download(new TailoringNonDeliveryReportExport($this->getFilters()), $exportFileName);
    }

    public function exportPdf()
    {
        $filters = $this->getFilters();
        $allowedBranchIds = $this->allowedBranchIds();

        $rows = $this->nonDeliveryRowsQuery($filters, $allowedBranchIds)
            ->orderBy($this->nonDeliverySortField($this->sortField), $this->sortDirection)
            ->get();

        $totals = $this->nonDeliveryTotals($filters, $allowedBranchIds);

        $pdf = Pdf::loadView('report.tailoring-non-delivery-report-pdf', [
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $filters,
            'statusOptions' => tailoringOrderStatuses(),
        ])->setPaper('a4', 'landscape');

        $fileName = 'TailoringNonDeliveryReport_'.now()->format('Ymd_His').'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $fileName);
    }

    protected function getFilters(): array
    {
        return [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'search' => $this->search,
            'date_type' => $this->date_type,
            'branch_name' => $this->branch_id ? Branch::where('id', $this->branch_id)->value('name') : 'All Branches',
            'customer_name' => $this->customer_id ? Account::where('id', $this->customer_id)->value('name') : 'All Customers',
            'product_name' => $this->product_id ? Product::where('id', $this->product_id)->value('name') : 'All Products',
            'category_name' => $this->category_id ? TailoringCategory::where('id', $this->category_id)->value('name') : 'All Categories',
            'visible_columns' => $this->visibleColumns(),
        ];
    }

    protected function getDefaultColumns(): array
    {
        return [
            'order_no' => true,
            'order_date' => true,
            'delivery_date' => true,
            'customer' => true,
            'mobile' => true,
            'bill_amount' => true,
            'paid_amount' => true,
            'balance_amount' => true,
            'item_quantity' => true,
            'completed_qty' => true,
            'pending_qty' => true,
            'delivery_qty' => true,
            'order_status' => true,
        ];
    }

    public function getColumnDefinitions(): array
    {
        return [
            'order_no' => 'Order Ref',
            'order_date' => 'Order Date',
            'delivery_date' => 'Delivery Date',
            'customer' => 'Customer',
            'mobile' => 'Mobile',
            'bill_amount' => 'Bill Amount',
            'paid_amount' => 'Paid',
            'balance_amount' => 'Balance',
            'item_quantity' => 'Item Qty',
            'completed_qty' => 'Completed Qty',
            'pending_qty' => 'Pending Qty',
            'delivery_qty' => 'Delivery Qty',
            'order_status' => 'Order Status',
        ];
    }

    protected function visibleColumns(): array
    {
        return array_merge($this->getDefaultColumns(), $this->tailoring_non_delivery_report_visible_column);
    }

    protected function allowedBranchIds(): array
    {
        return Auth::user()->branches->pluck('branch_id')->toArray();
    }

    public function render()
    {
        $filters = $this->getFilters();
        $allowedBranchIds = $this->allowedBranchIds();

        $rowsQuery = $this->nonDeliveryRowsQuery($filters, $allowedBranchIds);
        $total = $this->nonDeliveryTotals($filters, $allowedBranchIds);
        $data = $rowsQuery->orderBy($this->nonDeliverySortField($this->sortField), $this->sortDirection)->paginate($this->limit);

        $branchOptions = ['' => 'All Branches'] + Branch::whereIn('id', $allowedBranchIds)->pluck('name', 'id')->toArray();
        $categoryOptions = ['' => 'All Categories'] + TailoringCategory::ordered()->active()->pluck('name', 'id')->toArray();

        return view('livewire.report.tailoring-non-delivery-report', [
            'data' => $data,
            'total' => $total,
            'branchOptions' => $branchOptions,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => tailoringOrderStatuses(),
            'columnDefinitions' => $this->getColumnDefinitions(),
            'visibleColumns' => $this->visibleColumns(),
        ]);
    }
}
