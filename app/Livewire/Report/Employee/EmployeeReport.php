<?php

namespace App\Livewire\Report\Employee;

use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeReport extends Component
{
    use WithPagination;

    public $branch_id;

    public $employee_id;

    public $product_id;

    public $from_date;

    public $to_date;

    public $perPage = 19;

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
            ->select('users.name as employee', 'products.name as product')
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->selectRaw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage')
            ->selectRaw('SUM(sale_items.total * COALESCE(employee_commissions.commission_percentage, 0) / 100) as total_commission')
            ->orderBy('total_amount', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Get summary by employee
     */
    protected function getEmployeeSummary()
    {
        return $this->getBaseQuery()
            ->select('users.name as employee')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->groupBy('sale_items.employee_id')
            ->limit(10)
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * Get chart data for visualization
     */
    protected function getChartData($summary)
    {
        return $summary->map(function ($item) {
            return [
                'label' => $item->employee,
                'y' => (float) $item->total_amount,
            ];
        })->toArray();
    }

    public function render()
    {
        $items = $this->getDetailedItems();
        $summary = $this->getEmployeeSummary();

        $this->dispatch('updatePieChart', [
            'summary' => $this->getChartData($summary),
        ]);

        return view('livewire.report.employee.employee-report', [
            'items' => $items,
            'summary' => $summary,
        ]);
    }
}
