<?php

namespace App\Livewire\Report;

use App\Exports\SaleItemReportExport;
use App\Jobs\Export\ExportSaleItemReportJob;
use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SaleItemReport extends Component
{
    use WithPagination;

    public $search = '';

    public $product_id = '';

    public $employee_id = '';

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'sale_items.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
    }

    public function export()
    {
        $count = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when($this->from_date ?? '', function ($query, $value) {
                $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->employee_id ?? '', function ($query, $value) {
                $query->where('employee_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                $query->where('product_id', $value);
            })
            ->completed()
            ->count();

        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'employee_id' => $this->employee_id,
            'product_id' => $this->product_id,
        ];
        if ($count > 2000) {
            ExportSaleItemReportJob::dispatch(auth()->user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'SaleItemReport_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleItemReportExport($filter), $exportFileName);
        }
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
        $data = SaleItem::with('sale:id,date,invoice_no,branch_id', 'employee:id,name', 'product:id,name')->orderBy($this->sortField, $this->sortDirection)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when($this->search, function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('sale_items.unit_price', 'like', "%{$value}%")
                        ->orWhere('sale_items.quantity', 'like', "%{$value}%")
                        ->orWhere('sale_items.discount', 'like', "%{$value}%")
                        ->orWhere('sale_items.tax', 'like', "%{$value}%");
                });
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->employee_id ?? '', function ($query, $value) {
                $query->where('employee_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                $query->where('product_id', $value);
            })
            ->completed()
            ->latest('sale_items.id')
            ->select(
                'sale_items.*',
                'sales.invoice_no',
                'sales.date',
            );
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['gross_amount'] = $totalRow->sum('sale_items.gross_amount');
        $total['discount'] = $totalRow->sum('sale_items.discount');
        $total['net_amount'] = $totalRow->sum('sale_items.net_amount');
        $total['tax_amount'] = $totalRow->sum('sale_items.tax_amount');
        $total['total'] = $totalRow->sum('sale_items.total');

        return view('livewire.report.sale-item-report', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
