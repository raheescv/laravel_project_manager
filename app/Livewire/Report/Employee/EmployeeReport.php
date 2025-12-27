<?php

namespace App\Livewire\Report\Employee;

use App\Exports\EmployeeReportExport;
use App\Traits\Report\EmployeeReportQueryBuilder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeReport extends Component
{
    use WithPagination, EmployeeReportQueryBuilder;

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
     * Get detailed items with commission information
     */
    protected function getDetailedItems()
    {
        $filters = $this->getFilters();

        return $this->buildBaseQuery($filters)
            ->leftJoinSub($this->buildReturnSubqueryByEmployeeAndProduct($filters), 'returns', function ($join) {
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
        $filters = $this->getFilters();
        $commissions = $this->calculateEmployeeCommissions($filters);

        return $this->buildBaseQuery($filters)
            ->leftJoinSub($this->buildReturnSubqueryByEmployee($filters), 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id');
            })
            ->select('sale_items.employee_id', 'users.name as employee')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->selectRaw('COALESCE(MAX(returns.return_amount), 0) as return_amount')
            ->selectRaw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            ->groupBy('sale_items.employee_id')
            ->limit(10)
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function ($item) use ($commissions) {
                $item->total_commission = $commissions->get($item->employee_id) ?? 0;
                return $item;
            });
    }

    /**
     * Get totals across all pages (not just current page)
     */
    protected function getTotalSummary()
    {
        $filters = $this->getFilters();

        // Get total sale amounts
        $saleTotals = $this->buildBaseQuery($filters)
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->first();

        // Get total return amounts
        $returnTotals = $this->buildReturnTotalsQuery($filters)->first();

        // Get total commission
        $commissions = $this->calculateEmployeeCommissions($filters);
        $totalCommission = $commissions->sum();

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
        $fileName = 'Employee_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new EmployeeReportExport($this->getFilters()), $fileName);
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
