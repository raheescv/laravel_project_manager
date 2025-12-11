<?php

namespace App\Livewire\Report\Sale;

use App\Exports\DayWiseSaleReportExport;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DayWiseSaleReport extends Component
{
    use WithPagination;

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
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

    private function getReportData()
    {
        // Standardize dates
        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;

        // Sales query - grouped by date
        $sales = Sale::query()
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->completed()
            ->select(
                'sales.date',
                DB::raw('COUNT(DISTINCT sales.id) as count'),
                DB::raw('SUM(total) as net_sale'),
                DB::raw('SUM(gross_amount) as gross_sale'),
                DB::raw('SUM(tax_amount) as tax_amount'),
                DB::raw('SUM(other_discount) as discount')
            )
            ->groupBy('sales.date')
            ->toBase()
            ->get();

        // Get quantities from sale_items grouped by date
        $quantities = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->where('sales.status', 'completed')
            ->select(
                'sales.date',
                DB::raw('SUM(sale_items.quantity) as quantity')
            )
            ->groupBy('sales.date')
            ->toBase()
            ->get()
            ->keyBy('date');

        // Sale Returns query - grouped by date
        $saleReturns = SaleReturn::query()
            ->when($from, fn ($q) => $q->where('sale_returns.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_returns.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('status', 'completed')
            ->select(
                'sale_returns.date',
                DB::raw('SUM(grand_total) as return_amount')
            )
            ->groupBy('sale_returns.date')
            ->toBase()
            ->get()
            ->keyBy('date');

        // Build summary by combining sales and returns
        $summary = [];

        foreach ($sales as $sale) {
            $key = $sale->date;
            $returnAmount = isset($saleReturns[$key]) ? (float) $saleReturns[$key]->return_amount : 0;
            $quantity = isset($quantities[$key]) ? (float) $quantities[$key]->quantity : 0;

            $summary[$key] = [
                'date' => $sale->date,
                'count' => (int) $sale->count,
                'quantity' => $quantity,
                'net_sale' => (float) $sale->net_sale,
                'gross_sale' => (float) $sale->gross_sale,
                'tax_amount' => (float) $sale->tax_amount,
                'discount' => (float) $sale->discount,
                'return_amount' => $returnAmount,
            ];
        }

        // Add dates that have returns but no sales
        foreach ($saleReturns as $return) {
            $key = $return->date;
            if (! isset($summary[$key])) {
                $summary[$key] = [
                    'date' => $return->date,
                    'count' => 0,
                    'quantity' => 0,
                    'net_sale' => 0,
                    'gross_sale' => 0,
                    'tax_amount' => 0,
                    'discount' => 0,
                    'return_amount' => (float) $return->return_amount,
                ];
            }
        }

        // Compute totals
        $total = [
            'count' => array_sum(array_column($summary, 'count')),
            'quantity' => array_sum(array_column($summary, 'quantity')),
            'net_sale' => array_sum(array_column($summary, 'net_sale')),
            'gross_sale' => array_sum(array_column($summary, 'gross_sale')),
            'tax_amount' => array_sum(array_column($summary, 'tax_amount')),
            'discount' => array_sum(array_column($summary, 'discount')),
            'return_amount' => array_sum(array_column($summary, 'return_amount')),
        ];

        return [$summary, $total];
    }

    public function export()
    {
        [$summary, $total] = $this->getReportData();

        // Sort by date for export
        usort($summary, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        $filters = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
        ];

        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;
        $exportFileName = 'DayWiseSaleReport_'.($from ? systemDate($from) : '').'_'.($to ? systemDate($to) : '').'_'.now()->timestamp.'.xlsx';

        return Excel::download(new DayWiseSaleReportExport($summary, $total, $filters), $exportFileName);
    }

    public function render()
    {
        [$summary, $total] = $this->getReportData();

        // Sort the summary
        usort($summary, function ($a, $b) {
            $field = $this->sortField;
            $direction = $this->sortDirection === 'asc' ? 1 : -1;

            if ($field === 'date') {
                return $direction * strcmp($a['date'], $b['date']);
            }

            // Map sort field to array key
            $fieldMap = [
                'count' => 'count',
                'quantity' => 'quantity',
                'net_sale' => 'net_sale',
                'gross_sale' => 'gross_sale',
                'tax_amount' => 'tax_amount',
                'discount' => 'discount',
                'return_amount' => 'return_amount',
            ];

            $key = $fieldMap[$field] ?? 'date';
            $valueA = $a[$key] ?? 0;
            $valueB = $b[$key] ?? 0;

            return $direction * ($valueA <=> $valueB);
        });

        return view('livewire.report.sale.day-wise-sale-report', [
            'data' => $summary,
            'total' => $total,
        ]);
    }
}
