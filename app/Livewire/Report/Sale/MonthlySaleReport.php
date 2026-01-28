<?php

namespace App\Livewire\Report\Sale;

use App\Exports\MonthlySaleReportExport;
use App\Models\Sale;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class MonthlySaleReport extends Component
{
    public $branch_id = '';

    public $from_year;

    public $to_year;

    public $from_month;

    public $to_month;

    public function mount()
    {
        $this->branch_id = session('branch_id');
        $this->from_year = date('Y');
        $this->to_year = date('Y');
        $this->from_month = date('m');
        $this->to_month = date('m');
    }

    private function getReportData()
    {
        // Build date range
        $fromDate = Carbon::create($this->from_year, $this->from_month, 1)->startOfMonth();
        $toDate = Carbon::create($this->to_year, $this->to_month, 1)->endOfMonth();

        // Get sales data grouped by month
        $sales = Sale::query()
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('status', 'completed')
            ->whereBetween('date', [$fromDate, $toDate])
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%m') as month"), DB::raw('SUM(gross_amount) as gross_sales'), DB::raw('SUM(item_discount + other_discount) as discount'), DB::raw('SUM(grand_total) as net_sale'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        // Get payment data grouped by month
        $payments = SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->leftJoin('accounts', 'sale_payments.payment_method_id', '=', 'accounts.id')
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->where('sales.status', 'completed')
            ->whereBetween('sale_payments.date', [$fromDate, $toDate])
            ->select(DB::raw("DATE_FORMAT(sale_payments.date, '%Y-%m') as month"), DB::raw('SUM(sale_payments.amount) as paid_total'), DB::raw('SUM(CASE WHEN LOWER(accounts.name) LIKE "%card%" OR LOWER(accounts.name) LIKE "%debit%" THEN sale_payments.amount ELSE 0 END) as card'), DB::raw('SUM(CASE WHEN LOWER(accounts.name) LIKE "%cash%" THEN sale_payments.amount ELSE 0 END) as cash'))
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Build complete months list
        $allMonths = collect();
        $current = $fromDate->copy();

        while ($current <= $toDate) {
            $monthKey = $current->format('Y-m');
            // Use Carbon's format which ensures proper UTF-8 encoding
            $monthName = $current->format('M Y');

            $sale = $sales[$monthKey] ?? null;
            $payment = $payments[$monthKey] ?? null;

            $grossSales = $sale ? (float) $sale->gross_sales : 0;
            $discount = $sale ? (float) $sale->discount : 0;
            $netSale = $sale ? (float) $sale->net_sale : 0;
            $paidTotal = $payment ? (float) $payment->paid_total : 0;
            $card = $payment ? (float) $payment->card : 0;
            $cash = $payment ? (float) $payment->cash : 0;
            $credit = $netSale - $paidTotal;

            $allMonths[$monthKey] = [
                'month' => $monthKey,
                'month_name' => $monthName,
                'gross_sales' => $grossSales,
                'discount' => $discount,
                'net_sale' => $netSale,
                'paid_total' => $paidTotal,
                'credit' => $credit,
                'card' => $card,
                'cash' => $cash,
            ];

            $current->addMonth();
        }

        // Calculate totals
        $total = [
            'gross_sales' => $allMonths->sum('gross_sales'),
            'discount' => $allMonths->sum('discount'),
            'net_sale' => $allMonths->sum('net_sale'),
            'paid_total' => $allMonths->sum('paid_total'),
            'credit' => $allMonths->sum('credit'),
            'card' => $allMonths->sum('card'),
            'cash' => $allMonths->sum('cash'),
        ];

        return [$allMonths->values()->toArray(), $total];
    }

    public function exportExcel()
    {
        [$data, $total] = $this->getReportData();

        $filters = [
            'from_year' => $this->from_year,
            'from_month' => $this->from_month,
            'to_year' => $this->to_year,
            'to_month' => $this->to_month,
            'branch_id' => $this->branch_id,
        ];

        $exportFileName = 'Monthly_Sale_Report_'.$this->from_year.'-'.$this->from_month.'_to_'.$this->to_year.'-'.$this->to_month.'_'.now()->timestamp.'.xlsx';

        return Excel::download(new MonthlySaleReportExport($data, $total, $filters), $exportFileName);
    }

    public function render()
    {
        [$data, $total] = $this->getReportData();
        $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];

        return view('livewire.report.sale.monthly-sale-report', [
            'months' => $months,
            'data' => $data,
            'total' => $total,
        ]);
    }
}
