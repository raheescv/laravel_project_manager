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
        $paymentMethodColumns = $this->buildPaymentMethodColumns();
        $emptyPaymentColumns = $this->buildEmptyPaymentColumns($paymentMethodColumns);

        // Standardize dates
        $from = $this->from_date ? Carbon::parse($this->from_date)->toDateString() : null;
        $to = $this->to_date ? Carbon::parse($this->to_date)->toDateString() : null;
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
                DB::raw('COUNT(DISTINCT sales.id) as no_of_invoices'),
                DB::raw('SUM(total) as net_sales'),
                DB::raw('SUM(other_discount) as sales_discount'),
                DB::raw('SUM(grand_total) as total_sales')
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
                DB::raw('COUNT(DISTINCT tailoring_orders.id) as no_of_invoices'),
                DB::raw('SUM(total) as net_sales'),
                DB::raw('SUM(other_discount) as sales_discount'),
                DB::raw('SUM(grand_total) as total_sales')
            )
            ->groupBy('tailoring_orders.order_date', 'tailoring_orders.branch_id', 'branches.name');

        $sales = DB::query()
            ->fromSub($saleSummaryQuery->unionAll($tailoringSummaryQuery), 'combined_sales')
            ->select(
                'date',
                'branch_id',
                'branch',
                DB::raw('SUM(no_of_invoices) as no_of_invoices'),
                DB::raw('SUM(net_sales) as net_sales'),
                DB::raw('SUM(sales_discount) as sales_discount'),
                DB::raw('SUM(total_sales) as total_sales')
            )
            ->groupBy('date', 'branch_id', 'branch')
            ->get();

        // Invoice-day payments: only payments recorded on the same day as the sale/order.
        $saleInvoicePaymentsQuery = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->where('sales.status', 'completed')
            ->whereColumn('sale_payments.date', 'sales.date')
            ->select(
                'sales.date as date',
                'sales.branch_id',
                'branches.name as branch',
                DB::raw('SUM(sale_payments.amount) as amount')
            )
            ->groupBy('sales.date', 'sales.branch_id', 'branches.name');

        $tailoringInvoicePaymentsQuery = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_payments.tailoring_order_id')
            ->join('branches', 'branches.id', '=', 'tailoring_orders.branch_id')
            ->when($from, fn ($q) => $q->where('tailoring_orders.order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_orders.order_date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->whereColumn('tailoring_payments.date', 'tailoring_orders.order_date')
            ->select(
                'tailoring_orders.order_date as date',
                'tailoring_orders.branch_id',
                'branches.name as branch',
                DB::raw('SUM(tailoring_payments.amount) as amount')
            )
            ->groupBy('tailoring_orders.order_date', 'tailoring_orders.branch_id', 'branches.name');

        $invoicePayments = DB::query()
            ->fromSub($saleInvoicePaymentsQuery->unionAll($tailoringInvoicePaymentsQuery), 'combined_invoice_payments')
            ->select(
                'date',
                'branch_id',
                'branch',
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date', 'branch_id', 'branch')
            ->get();

        $invoicePaidByDayBranch = $invoicePayments
            ->mapWithKeys(fn ($payment) => [$this->summaryKey($payment->date, $payment->branch_id) => (float) $payment->amount])
            ->toArray();

        $saleInvoicePaymentsByMethodQuery = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->join('accounts', 'accounts.id', '=', 'payment_method_id')
            ->when($from, fn ($q) => $q->where('sale_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_payments.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->where('sales.status', 'completed')
            ->whereColumn('sale_payments.date', 'sales.date')
            ->select(
                'sale_payments.date',
                'sales.branch_id',
                'branches.name as branch',
                'accounts.name as payment_method_name',
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('sale_payments.date', 'sales.branch_id', 'branches.name', 'accounts.name');

        $tailoringInvoicePaymentsByMethodQuery = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_payments.tailoring_order_id')
            ->join('branches', 'branches.id', '=', 'tailoring_orders.branch_id')
            ->join('accounts', 'accounts.id', '=', 'tailoring_payments.payment_method_id')
            ->when($from, fn ($q) => $q->where('tailoring_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_payments.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->whereColumn('tailoring_payments.date', 'tailoring_orders.order_date')
            ->select(
                'tailoring_payments.date',
                'tailoring_orders.branch_id',
                'branches.name as branch',
                'accounts.name as payment_method_name',
                DB::raw('SUM(tailoring_payments.amount) as amount')
            )
            ->groupBy('tailoring_payments.date', 'tailoring_orders.branch_id', 'branches.name', 'accounts.name');

        $invoicePaymentsByMethod = DB::query()
            ->fromSub($saleInvoicePaymentsByMethodQuery->unionAll($tailoringInvoicePaymentsByMethodQuery), 'combined_invoice_payments_by_method')
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

        $saleDuePaymentsQuery = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_id')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->join('accounts', 'accounts.id', '=', 'payment_method_id')
            ->when($from, fn ($q) => $q->where('sale_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sale_payments.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->where('sales.status', 'completed')
            ->whereColumn('sale_payments.date', '!=', 'sales.date')
            ->select(
                'sale_payments.date',
                'sales.branch_id',
                'branches.name as branch',
                'accounts.name as payment_method_name',
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('sale_payments.date', 'sales.branch_id', 'branches.name', 'accounts.name');

        $tailoringDuePaymentsQuery = TailoringPayment::query()
            ->join('tailoring_orders', 'tailoring_orders.id', '=', 'tailoring_payments.tailoring_order_id')
            ->join('branches', 'branches.id', '=', 'tailoring_orders.branch_id')
            ->join('accounts', 'accounts.id', '=', 'tailoring_payments.payment_method_id')
            ->when($from, fn ($q) => $q->where('tailoring_payments.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('tailoring_payments.date', '<=', $to))
            ->when($this->branch_id, fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->whereColumn('tailoring_payments.date', '!=', 'tailoring_orders.order_date')
            ->select(
                'tailoring_payments.date',
                'tailoring_orders.branch_id',
                'branches.name as branch',
                'accounts.name as payment_method_name',
                DB::raw('SUM(tailoring_payments.amount) as amount')
            )
            ->groupBy('tailoring_payments.date', 'tailoring_orders.branch_id', 'branches.name', 'accounts.name');

        $duePayments = DB::query()
            ->fromSub($saleDuePaymentsQuery->unionAll($tailoringDuePaymentsQuery), 'combined_due_payments')
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
            $key = $this->summaryKey($sale->date, $sale->branch_id);
            $paid = (float) ($invoicePaidByDayBranch[$key] ?? 0);
            $credit = max((float) $sale->total_sales - $paid, 0);

            $summary[$key] = array_merge([
                'date' => $sale->date,
                'branch_name' => $sale->branch,
                'net_sales' => (float) $sale->net_sales,
                'no_of_invoices' => (int) $sale->no_of_invoices,
                'sales_discount' => (float) $sale->sales_discount,
                'total_sales' => (float) $sale->total_sales,
                'credit' => $credit,
                'paid' => $paid,
            ], $emptyPaymentColumns);
        }

        $this->mergePaymentCollectionIntoSummary($summary, $payments, 'total', $invoicePaidByDayBranch, $emptyPaymentColumns);
        $this->mergePaymentCollectionIntoSummary($summary, $invoicePaymentsByMethod, 'invoice', $invoicePaidByDayBranch, $emptyPaymentColumns);
        $this->mergePaymentCollectionIntoSummary($summary, $duePayments, 'due', $invoicePaidByDayBranch, $emptyPaymentColumns);

        $sortedSummary = $this->sortSummaryData($summary);
        $summaryCollection = collect($sortedSummary);

        // Compute totals
        $total = [];

        foreach (['net_sales', 'no_of_invoices', 'sales_discount', 'total_sales', 'credit', 'paid'] as $field) {
            $total[$field] = $summaryCollection->sum($field);
        }

        foreach ($paymentMethodColumns as $column) {
            $total[$column['invoice_key']] = $summaryCollection->sum($column['invoice_key']);
            $total[$column['due_key']] = $summaryCollection->sum($column['due_key']);
            $total[$column['total_key']] = $summaryCollection->sum($column['total_key']);
        }

        $this->prepareSalesChartData($summaryCollection);

        return view('livewire.report.sale.daily-sales-insights-report', [
            'data' => $sortedSummary,
            'total' => $total,
            'paymentMethodColumns' => $paymentMethodColumns,
        ]);
    }

    private function normalizePaymentMethodName(?string $name): string
    {
        return ucwords(strtolower(trim((string) $name)));
    }

    private function paymentMethodColumnKey(string $prefix, string $methodName): string
    {
        $key = preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim($methodName)));

        return $prefix.'_'.trim($key ?? '', '_');
    }

    private function sortSummaryData(array $summary): array
    {
        $data = array_values($summary);
        $sortFieldMap = [
            'branches.name' => 'branch_name',
        ];
        $sortField = $sortFieldMap[$this->sortField] ?? $this->sortField;
        $direction = $this->sortDirection === 'asc' ? 1 : -1;

        usort($data, function (array $a, array $b) use ($sortField, $direction): int {
            $valueA = $a[$sortField] ?? null;
            $valueB = $b[$sortField] ?? null;

            if ($sortField === 'date') {
                return $direction * strcmp((string) $valueA, (string) $valueB);
            }

            if (is_numeric($valueA) || is_numeric($valueB)) {
                return $direction * ((float) $valueA <=> (float) $valueB);
            }

            return $direction * strnatcasecmp((string) $valueA, (string) $valueB);
        });

        return $data;
    }

    private function buildPaymentMethodColumns(): array
    {
        $paymentMethodNames = Account::whereIn('id', cache('payment_methods', []))
            ->pluck('name', 'id')
            ->values()
            ->map(fn ($name) => $this->normalizePaymentMethodName($name))
            ->unique()
            ->values()
            ->all();

        return array_map(function (string $name): array {
            return [
                'name' => $name,
                'invoice_key' => $this->paymentMethodColumnKey('invoice', $name),
                'due_key' => $this->paymentMethodColumnKey('due', $name),
                'total_key' => $this->paymentMethodColumnKey('total', $name),
            ];
        }, $paymentMethodNames);
    }

    private function buildEmptyPaymentColumns(array $paymentMethodColumns): array
    {
        $empty = [];
        foreach ($paymentMethodColumns as $column) {
            $empty[$column['invoice_key']] = 0;
            $empty[$column['due_key']] = 0;
            $empty[$column['total_key']] = 0;
        }

        return $empty;
    }

    private function mergePaymentCollectionIntoSummary(array &$summary, $payments, string $type, array $invoicePaidByDayBranch, array $emptyPaymentColumns): void
    {
        foreach ($payments as $payment) {
            $key = $this->summaryKey($payment->date, $payment->branch_id);
            $methodName = $this->normalizePaymentMethodName($payment->payment_method_name);
            $columnKey = $this->paymentMethodColumnKey($type, $methodName);

            if (! isset($summary[$key])) {
                $summary[$key] = $this->buildEmptySummaryRow(
                    (string) $payment->date,
                    (string) $payment->branch,
                    (float) ($invoicePaidByDayBranch[$key] ?? 0),
                    $emptyPaymentColumns
                );
            }

            if (! isset($summary[$key][$columnKey])) {
                $summary[$key][$columnKey] = 0;
            }

            $summary[$key][$columnKey] += (float) $payment->amount;
        }
    }

    private function buildEmptySummaryRow(string $date, string $branchName, float $paid, array $emptyPaymentColumns): array
    {
        return array_merge([
            'date' => $date,
            'branch_name' => $branchName,
            'net_sales' => 0,
            'no_of_invoices' => 0,
            'sales_discount' => 0,
            'total_sales' => 0,
            'credit' => 0,
            'paid' => $paid,
        ], $emptyPaymentColumns);
    }

    private function summaryKey(string $date, int $branchId): string
    {
        return "{$date}_{$branchId}";
    }
}
