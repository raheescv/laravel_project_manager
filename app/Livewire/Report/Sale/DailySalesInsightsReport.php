<?php

namespace App\Livewire\Report\Sale;

use App\Exports\DayBookReportExport;
use App\Jobs\Export\ExportSaleItemReportJob;
use App\Models\Account;
use App\Models\Models\Views\Ledger;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\TailoringOrder;
use App\Models\TailoringPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DailySalesInsightsReport extends Component
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

    public function export()
    {
        $count = Ledger::when($this->search, function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('description', 'like', "%{$value}%")
                    ->orWhere('reference_number', 'like', "%{$value}%")
                    ->orWhere('remarks', 'like', "%{$value}%");
            });
        })->when($this->from_date ?? '', function ($query, $value) {
            return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
        })->when($this->to_date ?? '', function ($query, $value) {
            return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
        })->when($this->branch_id ?? '', function ($query, $value) {
            return $query->where('branch_id', $value);
        })->when($this->account_id ?? '', function ($query, $value) {
            return $query->where('account_id', $value);
        })->count();

        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'account_id' => $this->account_id,
        ];

        if ($count > 2000) {
            ExportSaleItemReportJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'DayBookReport-'.now()->timestamp.'.xlsx';

            return Excel::download(new DayBookReportExport($filter), $exportFileName);
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

    public function prepareSalesChartData($summaryCollection)
    {
        // Generate complete date range between from_date and to_date
        $from = $this->from_date ? Carbon::parse($this->from_date) : Carbon::now()->startOfMonth();
        $to = $this->to_date ? Carbon::parse($this->to_date) : Carbon::now();

        $allDates = collect();
        $currentDate = $from->copy();

        while ($currentDate->lte($to)) {
            $allDates->push($currentDate->format('Y-m-d'));
            $currentDate->addDay();
        }

        // Group by branch to create series
        $chartData = $summaryCollection
            ->groupBy('branch_name')
            ->map(function ($branchData, $branchName) use ($allDates) {
                // Create a complete dataset for each branch with all dates
                $dataPoints = $allDates->map(function ($date) use ($branchData) {
                    // Find the matching record for this date/branch
                    $record = $branchData->first(function ($item) use ($date) {
                        return $item['date'] === $date;
                    });

                    return [
                        'x' => strtotime($date) * 1000, // Convert to JS timestamp
                        'y' => $record ? floatval($record['total_sales']) : 0,
                        'net_sales' => $record ? floatval($record['net_sales']) : 0,
                        'sales_discount' => $record ? floatval($record['sales_discount']) : 0,
                        'invoices' => $record ? intval($record['no_of_invoices']) : 0,
                    ];
                })->values()->toArray();

                return [
                    'type' => 'column',
                    'showInLegend' => true,
                    'name' => $branchName,
                    'dataPoints' => $dataPoints,
                ];
            })->values();

        // If no branch data exists, create a default series with all dates showing zero values
        if ($chartData->isEmpty()) {
            $chartData = collect([
                [
                    'type' => 'column',
                    'showInLegend' => true,
                    'name' => 'No Data',
                    'dataPoints' => $allDates->map(function ($date) {
                        return [
                            'x' => strtotime($date) * 1000,
                            'y' => 0,
                            'net_sales' => 0,
                            'sales_discount' => 0,
                            'invoices' => 0,
                        ];
                    })->values()->toArray(),
                ],
            ]);
        }

        $this->dispatch('updateChart', $chartData->toArray());
    }

    public function render()
    {
        // Get payment methods (cached)
        $paymentMethods = Account::whereIn('id', cache('payment_methods', []))
            ->pluck('name', 'id')
            ->toArray();

        // Helper: zero-filled array for payment method columns
        $emptyPaymentColumns = array_fill_keys(array_values($paymentMethods), 0);

        // Standardize dates
        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;
        $summarySortField = $this->sortField === 'branches.name' ? 'branch' : $this->sortField;

        $saleSummaryQuery = Sale::query()
            ->join('branches', 'branches.id', '=', 'branch_id')
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->completed()
            ->select(
                'sales.date',
                'sales.branch_id',
                'branches.name as branch',
                DB::raw('SUM(gross_amount) as item_total'),
                DB::raw('COUNT(DISTINCT sales.id) as no_of_invoices'),
                DB::raw('SUM(total) as net_sales'),
                DB::raw('SUM(other_discount) as sales_discount'),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('SUM(paid) as paid'),
                DB::raw('SUM(balance) as credit')
            )
            ->groupBy('sales.date', 'sales.branch_id', 'branches.name');

        $tailoringSummaryQuery = TailoringOrder::query()
            ->join('branches', 'branches.id', '=', 'tailoring_orders.branch_id')
            ->when($from, fn ($q) => $q->where('tailoring_orders.order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_orders.order_date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->select(
                DB::raw('tailoring_orders.order_date as date'),
                'tailoring_orders.branch_id',
                'branches.name as branch',
                DB::raw('SUM(gross_amount) as item_total'),
                DB::raw('COUNT(DISTINCT tailoring_orders.id) as no_of_invoices'),
                DB::raw('SUM(total) as net_sales'),
                DB::raw('SUM(other_discount) as sales_discount'),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('SUM(paid) as paid'),
                DB::raw('SUM(balance) as credit')
            )
            ->groupBy('tailoring_orders.order_date', 'tailoring_orders.branch_id', 'branches.name');

        $sales = DB::query()
            ->fromSub($saleSummaryQuery->unionAll($tailoringSummaryQuery), 'combined_sales')
            ->select(
                'date',
                'branch_id',
                'branch',
                DB::raw('SUM(item_total) as item_total'),
                DB::raw('SUM(no_of_invoices) as no_of_invoices'),
                DB::raw('SUM(net_sales) as net_sales'),
                DB::raw('SUM(sales_discount) as sales_discount'),
                DB::raw('SUM(total_sales) as total_sales'),
                DB::raw('SUM(paid) as paid'),
                DB::raw('SUM(credit) as credit')
            )
            ->groupBy('date', 'branch_id', 'branch')
            ->orderBy($summarySortField, $this->sortDirection)
            ->get();

        $salePaymentsQuery = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->join('accounts', 'accounts.id', '=', 'payment_method_id')
            ->when($from, fn ($q) => $q->where('sale_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_payments.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->where('sales.status', 'completed')
            ->select(
                'sale_payments.date',
                'sales.branch_id',
                'branches.name as branch',
                'accounts.name as payment_method_name',
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('sale_payments.date', 'sales.branch_id', 'branches.name', 'accounts.name');

        $tailoringPaymentsQuery = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_payments.tailoring_order_id')
            ->join('branches', 'branches.id', '=', 'tailoring_orders.branch_id')
            ->join('accounts', 'accounts.id', '=', 'tailoring_payments.payment_method_id')
            ->when($from, fn ($q) => $q->where('tailoring_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_payments.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->select(
                'tailoring_payments.date',
                'tailoring_orders.branch_id',
                'branches.name as branch',
                'accounts.name as payment_method_name',
                DB::raw('SUM(tailoring_payments.amount) as amount')
            )
            ->groupBy('tailoring_payments.date', 'tailoring_orders.branch_id', 'branches.name', 'accounts.name');

        $payments = DB::query()
            ->fromSub($salePaymentsQuery->unionAll($tailoringPaymentsQuery), 'combined_payments')
            ->select(
                'date',
                'branch_id',
                'branch',
                'payment_method_name',
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date', 'branch_id', 'branch', 'payment_method_name')
            ->orderBy('date')
            ->get();

        // Build summary
        $summary = [];

        foreach ($sales as $sale) {
            $key = "{$sale->date}_{$sale->branch_id}";

            $summary[$key] = array_merge([
                'date' => $sale->date,
                'branch_name' => $sale->branch,
                'net_sales' => (float) $sale->net_sales,
                'no_of_invoices' => (int) $sale->no_of_invoices,
                'sales_discount' => (float) $sale->sales_discount,
                'total_sales' => (float) $sale->total_sales,
                'credit' => (float) $sale->credit,
                'paid' => (float) $sale->paid,
                'item_total' => (float) $sale->item_total,
            ], $emptyPaymentColumns);
        }

        foreach ($payments as $payment) {
            $key = "{$payment->date}_{$payment->branch_id}";
            $methodName = ucwords(strtolower($payment->payment_method_name));

            // Create default row if not exists
            if (! isset($summary[$key])) {
                $summary[$key] = array_merge([
                    'date' => $payment->date,
                    'branch_name' => $payment->branch,
                    'net_sales' => 0,
                    'no_of_invoices' => 0,
                    'sales_discount' => 0,
                    'total_sales' => 0,
                    'credit' => 0,
                    'paid' => 0,
                    'item_total' => 0,
                ], $emptyPaymentColumns);
            }

            // Ensure payment method key exists
            if (! isset($summary[$key][$methodName])) {
                $summary[$key][$methodName] = 0;
            }

            // Fill the correct method
            $summary[$key][$methodName] += (float) $payment->amount;
        }

        // Compute totals
        $total = [];
        $summaryCollection = collect($summary);

        foreach (['net_sales', 'no_of_invoices', 'sales_discount', 'total_sales', 'credit', 'paid', 'item_total'] as $field) {
            $total[$field] = $summaryCollection->sum($field);
        }

        foreach ($paymentMethods as $name) {
            $total[$name] = $summaryCollection->sum($name);
        }

        $this->prepareSalesChartData($summaryCollection);

        return view('livewire.report.sale.daily-sales-insights-report', [
            'data' => $summary,
            'total' => $total,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
