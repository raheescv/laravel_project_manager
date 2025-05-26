<?php

namespace App\Livewire\Report\Employee;

use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProductivityReport extends Component
{
    use WithPagination;

    public $fromDate;

    public $toDate;

    public $employeeId;

    public $branchId;

    public $department;

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
        $query = User::query()
            ->employee()
            ->when($this->branchId, fn ($q) => $q->whereHas('branches', fn ($q) => $q->where('branch_id', $this->branchId)))
            ->when($this->employeeId, fn ($q) => $q->where('id', $this->employeeId));

        // Sales Performance
        $salesQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.date', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ]);

        // Get employee performance metrics
        $employees = $query->select([
            'users.id',
            'users.name',
            'users.email',
            DB::raw('(SELECT COUNT(DISTINCT sale_items.sale_id) FROM sale_items
                    JOIN sales ON sales.id = sale_items.sale_id
                    WHERE (sale_items.employee_id = users.id OR sale_items.assistant_id = users.id)
                    AND sales.status = "completed"
                    AND sales.date BETWEEN ? AND ?) as total_transactions'),
            DB::raw('(SELECT SUM(sale_items.total) FROM sale_items
                    JOIN sales ON sales.id = sale_items.sale_id
                    WHERE (sale_items.employee_id = users.id OR sale_items.assistant_id = users.id)
                    AND sales.status = "completed"
                    AND sales.date BETWEEN ? AND ?) as total_sales'),
            DB::raw('(SELECT COUNT(*) FROM sale_items
                    JOIN sales ON sales.id = sale_items.sale_id
                    WHERE (sale_items.employee_id = users.id OR sale_items.assistant_id = users.id)
                    AND sales.status = "completed"
                    AND sales.date BETWEEN ? AND ?) as items_sold'),
            DB::raw('(SELECT AVG(sale_items.total) FROM sale_items
                    JOIN sales ON sales.id = sale_items.sale_id
                    WHERE (sale_items.employee_id = users.id OR sale_items.assistant_id = users.id)
                    AND sales.status = "completed"
                    AND sales.date BETWEEN ? AND ?) as avg_transaction_value'),
        ])
            ->addBinding([
                $this->fromDate, $this->toDate,
                $this->fromDate, $this->toDate,
                $this->fromDate, $this->toDate,
                $this->fromDate, $this->toDate,
            ], 'select')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Calculate department averages for comparison
        // Calculate overall totals and averages
        $this->totalSales = $salesQuery->sum('sale_items.total');
        $this->totalTransactions = $salesQuery->distinct('sales.id')->count('sales.id');
        $this->totalItems = $salesQuery->count('sale_items.id');
        $this->avgTransaction = $this->totalTransactions > 0 ? $this->totalSales / $this->totalTransactions : 0;

        $departmentAverages = $query->get()->groupBy('department')->map(function ($group) {
            return [
                'avg_sales' => $group->avg('total_sales'),
                'avg_transactions' => $group->avg('total_transactions'),
                'avg_items' => $group->avg('items_sold'),
                'avg_transaction_value' => $group->avg('avg_transaction_value'),
            ];
        });

        // Get top selling categories per employee
        $topCategories = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.main_category_id')
            ->whereBetween('sales.date', [$this->fromDate, $this->toDate])
            ->groupBy('sale_items.employee_id', 'categories.name')
            ->select(
                'sale_items.employee_id',
                'categories.name as category',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(sale_items.total) as total')
            )
            ->orderBy('total', 'desc')
            ->get()
            ->groupBy('employee_id');

        return view('livewire.report.employee.productivity-report', [
            'employees' => $employees,
            'departmentAverages' => $departmentAverages,
            'topCategories' => $topCategories,
        ]);
    }
}
