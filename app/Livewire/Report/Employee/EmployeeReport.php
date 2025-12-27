<?php

namespace App\Livewire\Report\Employee;

use App\Exports\EmployeeReportExport;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeReport extends Component
{
    use WithPagination;

    public $branch_id;

    public $employee_id;

    public $product_id;

    public $from_date;

    public $to_date;

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['employeeReportFilterChanged' => '$refresh'];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['branch_id', 'employee_id', 'product_id', 'from_date', 'to_date'])) {
            $this->resetPage();
        }
    }

    /**
     * Apply common filters to a query
     */
    protected function applyFilters($query, string $tablePrefix = '')
    {
        $prefix = $tablePrefix ? "{$tablePrefix}." : '';

        return $query
            ->when($this->branch_id, fn ($q) => $q->where("{$prefix}branch_id", $this->branch_id))
            ->when($this->employee_id, fn ($q) => $q->where("{$prefix}employee_id", $this->employee_id))
            ->when($this->product_id, fn ($q) => $q->where("{$prefix}product_id", $this->product_id))
            ->when($this->from_date, fn ($q) => $q->whereDate("{$prefix}date", '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate("{$prefix}date", '<=', $this->to_date));
    }

    /**
     * Apply employee ID validation filters
     */
    protected function applyEmployeeValidation($query, string $tablePrefix = '')
    {
        $prefix = $tablePrefix ? "{$tablePrefix}." : '';

        return $query
            ->whereNotNull("{$prefix}employee_id")
            ->where("{$prefix}employee_id", '!=', 0);
    }

    /**
     * Build return subquery for employee and product grouping
     */
    protected function getReturnSubqueryByEmployeeAndProduct()
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($this->branch_id, fn ($q) => $q->where('sale_returns.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_return_items.product_id', $this->product_id))
            ->when($this->employee_id, fn ($q) => $q->where('sale_return_items.employee_id', $this->employee_id))
            ->when($this->from_date, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->to_date))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->select('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount');
    }

    /**
     * Build return subquery for employee grouping only
     */
    protected function getReturnSubqueryByEmployee()
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($this->branch_id, fn ($q) => $q->where('sale_returns.branch_id', $this->branch_id))
            ->when($this->employee_id, fn ($q) => $q->where('sale_return_items.employee_id', $this->employee_id))
            ->when($this->from_date, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->to_date))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id')
            ->select('sale_return_items.employee_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount');
    }

    /**
     * Build return totals query (no grouping)
     */
    protected function getReturnTotalsQuery()
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($this->branch_id, fn ($q) => $q->where('sale_returns.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_return_items.product_id', $this->product_id))
            ->when($this->employee_id, fn ($q) => $q->where('sale_return_items.employee_id', $this->employee_id))
            ->when($this->from_date, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->to_date))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->selectRaw('SUM(sale_return_items.total) as return_amount');
    }

    /**
     * Build the base query with all necessary joins and filters
     */
    protected function getBaseQuery()
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('employee_commissions', function ($join) {
                $join->on('employee_commissions.employee_id', '=', 'sale_items.employee_id')
                    ->on('employee_commissions.product_id', '=', 'sale_items.product_id');
            })
            ->where('sales.status', 'completed')
            ->when($this->branch_id, fn ($q) => $q->where('sales.branch_id', $this->branch_id))
            ->when($this->product_id, fn ($q) => $q->where('sale_items.product_id', $this->product_id))
            ->when($this->employee_id, fn ($q) => $q->where('sale_items.employee_id', $this->employee_id))
            ->when($this->from_date, fn ($q) => $q->whereDate('sales.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('sales.date', '<=', $this->to_date));
    }

    /**
     * Get detailed items with commission information
     */
    protected function getDetailedItems()
    {
        return $this->getBaseQuery()
            ->leftJoinSub($this->getReturnSubqueryByEmployeeAndProduct(), 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->select('users.name as employee', 'products.name as product')
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->selectRaw('COALESCE(MAX(returns.return_amount), 0) as return_amount')
            ->selectRaw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            ->selectRaw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage')
            ->selectRaw('(SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0)) * COALESCE(MAX(employee_commissions.commission_percentage), 0) / 100 as total_commission')
            ->orderBy('total_amount', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Get summary by employee
     */
    protected function getEmployeeSummary()
    {
        return $this->getBaseQuery()
            ->leftJoinSub($this->getReturnSubqueryByEmployee(), 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id');
            })
            ->select('users.name as employee')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->selectRaw('COALESCE(MAX(returns.return_amount), 0) as return_amount')
            ->selectRaw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            ->groupBy('sale_items.employee_id')
            ->limit(10)
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * Get totals across all pages (not just current page)
     */
    protected function getTotalSummary()
    {
        // Get total sale amounts
        $saleTotals = $this->getBaseQuery()
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->first();

        // Get total return amounts
        $returnTotals = $this->getReturnTotalsQuery()->first();

        // Get total commission (need to calculate from grouped data)
        $commissionQuery = $this->getBaseQuery()
            ->leftJoinSub($this->getReturnSubqueryByEmployeeAndProduct(), 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->selectRaw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            ->selectRaw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage')
            ->get();

        $totalCommission = $commissionQuery->sum(function ($item) {
            return ($item->net_amount * $item->commission_percentage) / 100;
        });

        return (object) [
            'total_quantity' => $saleTotals->total_quantity ?? 0,
            'total_amount' => $saleTotals->total_amount ?? 0,
            'return_amount' => $returnTotals->return_amount ?? 0,
            'net_amount' => ($saleTotals->total_amount ?? 0) - ($returnTotals->return_amount ?? 0),
            'total_commission' => $totalCommission,
        ];
    }

    /**
     * Get chart data for visualization
     */
    protected function getChartData($summary)
    {
        return $summary->map(function ($item) {
            return [
                'label' => $item->employee,
                'y' => (float) ($item->net_amount ?? $item->total_amount),
            ];
        })->toArray();
    }

    /**
     * Export employee report to Excel
     */
    public function export()
    {
        $filters = [
            'branch_id' => $this->branch_id,
            'employee_id' => $this->employee_id,
            'product_id' => $this->product_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];

        $fileName = 'Employee_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new EmployeeReportExport($filters), $fileName);
    }

    public function render()
    {
        $items = $this->getDetailedItems();
        $summary = $this->getEmployeeSummary();
        $totals = $this->getTotalSummary();

        $this->dispatch('updatePieChart', [
            'summary' => $this->getChartData($summary),
        ]);

        return view('livewire.report.employee.employee-report', [
            'items' => $items,
            'summary' => $summary,
            'totals' => $totals,
        ]);
    }
}
