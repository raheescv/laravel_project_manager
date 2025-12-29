<?php

namespace App\Livewire\Report;

use App\Exports\SaleItemReportExport;
use App\Jobs\Export\ExportSaleItemReportJob;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SaleBookingItemReport  extends Component
{
    use WithPagination;

    public $search = '';

    public $product_id = '';

    public $employee_id = '';

    public $status = 'completed';

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
        $count = $this->baseQuery()->count();
        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'employee_id' => $this->employee_id,
            'product_id' => $this->product_id,
        ];
        if ($count > 2000) {
            ExportSaleItemReportJob::dispatch(Auth::user(), $filter);
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
        $query = $this->baseQuery();
        $totals = clone $query;
        $data = $query->paginate($this->limit);

        $total = [
            'quantity' => $totals->sum('sale_items.quantity'),
            'gross_amount' => $totals->sum('sale_items.gross_amount'),
            'discount' => $totals->sum('sale_items.discount'),
            'net_amount' => $totals->sum('sale_items.net_amount'),
            'tax_amount' => $totals->sum('sale_items.tax_amount'),
            'total' => $totals->sum('sale_items.total'),
            'effective_total' => $totals->get()->sum('effective_total'),
        ];

        return view('livewire.report.sale-booking-item-report', [
            'total' => $total,
            'data' => $data,
        ]);
    }

   public function baseQuery()
{
    return SaleItem::with([
            'sale:id,date,invoice_no,branch_id,other_discount,total,type',
            'employee:id,name',
            'product:id,name'
        ])
        ->select('sale_items.*', 'sales.invoice_no', 'sales.date')
        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')

        // ✅ ADD THIS LINE
        ->where('sales.type', 'booking')

        ->when($this->search, function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('sale_items.unit_price', 'like', "%{$value}%")
                  ->orWhere('sale_items.quantity', 'like', "%{$value}%")
                  ->orWhere('sale_items.discount', 'like', "%{$value}%")
                  ->orWhere('sale_items.tax', 'like', "%{$value}%");
            });
        })
        ->when($this->from_date, fn ($q) => $q->whereDate('sales.date', '>=', $this->from_date))
        ->when($this->to_date, fn ($q) => $q->whereDate('sales.date', '<=', $this->to_date))
        ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
        ->when($this->employee_id, function ($q): void {
            $q->where(function ($query): void {
                $query->where('sale_items.employee_id', $this->employee_id)
                      ->orWhere('sale_items.assistant_id', $this->employee_id);
            });
        })
        ->when($this->product_id, fn ($q) => $q->where('sale_items.product_id', $this->product_id))
        ->when($this->status, fn ($q) => $q->where('sales.status', $this->status))
        ->whereIn('sales.branch_id', Auth::user()->branches->pluck('branch_id'))
        ->orderBy($this->sortField, $this->sortDirection);
}

}
