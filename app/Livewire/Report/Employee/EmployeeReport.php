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

    public function render()
    {
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select(
                'users.name as employee',
                'products.name as product',
                'sale_items.quantity',
                'sale_items.total as amount'
            )
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->product_id, fn ($q, $value) => $q->where('sale_items.product_id', $value))
            ->when($this->employee_id, fn ($q, $value) => $q->where('sale_items.employee_id', $value))
            ->when($this->from_date, fn ($q, $value) => $q->whereDate('sales.date', '>=', $value))
            ->when($this->to_date, fn ($q, $value) => $q->whereDate('sales.date', '<=', $value))
            ->where('sales.status', 'completed');

        $summaryQuery = clone $query;
        $summary = $summaryQuery
            ->select('users.name as employee')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->groupBy('sale_items.employee_id')
            ->limit(10)
            ->orderBy('total_amount')
            ->get();

        $items = $query->paginate($this->perPage);

        $this->dispatch('updatePieChart', [
            'summary' => $summary->map(function ($item) {
                return ['label' => $item->employee, 'y' => $item->total_amount];
            }),
        ]);

        return view('livewire.report.employee.employee-report', [
            'items' => $items,
            'summary' => $summary,
        ]);
    }
}
