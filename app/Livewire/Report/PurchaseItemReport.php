<?php

namespace App\Livewire\Report;

use App\Exports\PurchaseItemReportExport;
use App\Jobs\Export\ExportPurchaseItemReportJob;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseItemReport extends Component
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

    public $sortField = 'purchase_items.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
    }

    public function export()
    {
        $count = PurchaseItem::join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
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
            ->where('purchases.status', 'completed')
            ->count();

        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
        ];
        if ($count > 2000) {
            ExportPurchaseItemReportJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'PurchaseItemReport_'.now()->timestamp.'.xlsx';

            return Excel::download(new PurchaseItemReportExport($filter), $exportFileName);
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
        $data = PurchaseItem::with('purchase:id,date,invoice_no,branch_id', 'product:id,name')->orderBy($this->sortField, $this->sortDirection)
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('purchase_items.unit_price', 'like', "%{$value}%")
                        ->orWhere('purchase_items.quantity', 'like', "%{$value}%")
                        ->orWhere('purchase_items.discount', 'like', "%{$value}%")
                        ->orWhere('purchase_items.tax', 'like', "%{$value}%");
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
            ->whereIn('purchases.branch_id', Auth::user()->branches->pluck('branch_id')->toArray())
            ->where('purchases.status', 'completed')
            ->latest('purchase_items.id')
            ->select(
                'purchase_items.*',
                'purchases.invoice_no',
                'purchases.date',
            );
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['gross_amount'] = $totalRow->sum('purchase_items.gross_amount');
        $total['discount'] = $totalRow->sum('purchase_items.discount');
        $total['net_amount'] = $totalRow->sum('purchase_items.net_amount');
        $total['tax_amount'] = $totalRow->sum('purchase_items.tax_amount');
        $total['total'] = $totalRow->sum('purchase_items.total');

        return view('livewire.report.purchase-item-report', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
