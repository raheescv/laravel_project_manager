<?php

namespace App\Livewire\Report\Employee;

use App\Exports\ProductivityReportExport;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ProductivityReport extends Component
{
    use WithPagination;

    public $fromDate;

    public $toDate;

    public $employeeId;

    public $branchId;

    public $department;

    public $employees;

    public $totalSales = 0;

    public $totalTransactions = 0;

    public $totalItems = 0;

    public $avgTransaction = 0;

    public $sortField = 'total_sales';

    public $sortDirection = 'desc';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->fromDate = date('Y-m-01');
        $this->toDate = date('Y-m-d');
        $this->branchId = session('branch_id');
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

    public function render()
    {
        $this->employees = $this->getEmployeesData();
        $this->calculateTotals();
        $topCategories = $this->getTopCategories();

        return view('livewire.report.employee.productivity-report', [
            'topCategories' => $topCategories,
        ]);
    }

    private function getEmployeesData()
    {
        $employeeSales = $this->getEmployeeSalesQuery()->get();
        $employees = [];

        foreach ($employeeSales as $item) {
            $effectiveTotal = $this->calculateEffectiveTotal($item);

            $employeeData = [
                'id' => $item->id,
                'name' => $item->employee,
                'email' => $item->email,
                'items_sold' => 1,
                'total_sales' => $effectiveTotal,
            ];

            if (! isset($employees[$item->employee_id])) {
                $employees[$item->employee_id] = $employeeData;
                $employees[$item->employee_id]['sale_ids'] = [];
            } else {
                $employees[$item->employee_id]['items_sold']++;
                $employees[$item->employee_id]['total_sales'] += $effectiveTotal;
            }

            $employees[$item->employee_id]['sale_ids'][$item->sale_id] = $item->sale_id;
            $employees[$item->employee_id]['total_transactions'] = count($employees[$item->employee_id]['sale_ids']);
            $employees[$item->employee_id]['avg_transaction_value'] = $employees[$item->employee_id]['total_sales'] / $employees[$item->employee_id]['total_transactions'];
        }

        return $employees;
    }

    private function getEmployeeSalesQuery()
    {
        return $this->getSalesQuery()
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->select(
                'users.id',
                'users.name as employee',
                'users.email',
                'sale_items.sale_id',
                'sale_items.employee_id',
                'sales.total as sale_total',
                'sales.other_discount',
                'sale_items.total',
            );
    }

    private function getSalesQuery()
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.date', [date('Y-m-d', strtotime($this->fromDate)), date('Y-m-d', strtotime($this->toDate))]);
    }

    private function calculateEffectiveTotal($item)
    {
        if ($item->other_discount == 0 || $item->sale_total == 0) {
            return $item->total;
        }

        $discountPercentage = ($item->other_discount / $item->sale_total) * 100;

        return $item->total - ($discountPercentage / 100 * $item->total);
    }

    private function calculateTotals()
    {
        $salesData = $this->getSalesQuery()
            ->select(['sales.total as sale_total', 'sales.other_discount', 'sale_items.id', 'sale_items.total'])
            ->get();

        $this->totalSales = $salesData->sum(function ($item) {
            return $this->calculateEffectiveTotal($item);
        });

        $this->totalTransactions = $this->getSalesQuery()->distinct('sales.id')->count('sales.id');
        $this->totalItems = $this->getSalesQuery()->count('sale_items.id');
        $this->avgTransaction = $this->totalTransactions > 0 ? $this->totalSales / $this->totalTransactions : 0;
    }

    private function getTopCategories()
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.main_category_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->whereBetween('sales.date', [$this->fromDate, $this->toDate])
            ->groupBy('sale_items.employee_id', 'categories.name', 'users.name')
            ->select(
                'sale_items.employee_id',
                'users.name as employee_name',
                'categories.name as category',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(sale_items.total) as total')
            )
            ->orderBy('total', 'desc')
            ->get()
            ->groupBy('employee_id');
    }

    public function downloadReport()
    {
        $employees = $this->getEmployeesData();
        $topCategories = $this->getTopCategories()->toArray();

        $summaryData = [
            'totalSales' => $this->totalSales,
            'totalTransactions' => $this->totalTransactions,
            'totalItems' => $this->totalItems,
            'avgTransaction' => $this->avgTransaction,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ];

        $fileName = 'employee_productivity_report_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new ProductivityReportExport($this->getFilters(), $employees, $topCategories, $summaryData),
            $fileName
        );
    }

    private function getFilters()
    {
        return [
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'branchId' => $this->branchId,
        ];
    }
}
