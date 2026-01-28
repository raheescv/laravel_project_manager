<?php

namespace App\Livewire\Report;

use App\Exports\SaleReturnItemReportExport;
use App\Jobs\Export\ExportSaleItemReportJob;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SaleReturnItemReport extends Component
{
    use WithPagination;

    public $search = '';

    public $product_id = '';

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'sale_return_items.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
    }

    public function export()
    {
        $count = SaleReturnItem::join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                return $query->where('product_id', $value);
            })
            ->where('sale_returns.status', 'completed')
            ->count();

        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
        ];
        if ($count > 2000) {
            ExportSaleItemReportJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'SaleReturnItemReport_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleReturnItemReportExport($filter), $exportFileName);
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
        $data = SaleReturnItem::with('saleReturn:id,date,reference_no,branch_id,other_discount,total', 'product:id,name')->orderBy($this->sortField, $this->sortDirection)
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('sale_return_items.unit_price', 'like', "%{$value}%")
                        ->orWhere('sale_return_items.quantity', 'like', "%{$value}%")
                        ->orWhere('sale_return_items.discount', 'like', "%{$value}%")
                        ->orWhere('sale_return_items.tax', 'like', "%{$value}%");
                });
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                return $query->where('product_id', $value);
            })
            ->whereIn('sale_returns.branch_id', Auth::user()->branches->pluck('branch_id')->toArray())
            ->where('sale_returns.status', 'completed')
            ->latest('sale_return_items.id')
            ->select(
                'sale_return_items.*',
                'sale_returns.reference_no',
                'sale_returns.date',
            );
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['base_unit_quantity'] = $totalRow->sum('sale_return_items.base_unit_quantity');
        $total['gross_amount'] = $totalRow->sum('sale_return_items.gross_amount');
        $total['discount'] = $totalRow->sum('sale_return_items.discount');
        $total['net_amount'] = $totalRow->sum('sale_return_items.net_amount');
        $total['tax_amount'] = $totalRow->sum('sale_return_items.tax_amount');
        $total['total'] = $totalRow->sum('sale_return_items.total');
        $total['effective_total'] = $totalRow->get()->sum('effective_total');

        return view('livewire.report.sale-return-item-report', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
