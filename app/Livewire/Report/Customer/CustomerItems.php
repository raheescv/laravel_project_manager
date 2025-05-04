<?php

namespace App\Livewire\Report\Customer;

use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerItems extends Component
{
    use WithPagination;

    public $product_id = null;

    public $branch_id = null;

    public $customer_id = null;

    public $nationality = null;

    public $employee_id = null;

    public int $productPerPage = 10;

    public int $employeePerPage = 10;

    public $dataPoints = [];

    public $from_date;

    public $to_date;

    protected $listeners = ['customerItemsFilterChanged' => 'filterChanged'];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null, $product_id = null, $employee_id = null, $branch_id = null, $nationality = null)
    {
        $this->customer_id = $customer_id;
        $this->branch_id = $branch_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->product_id = $product_id;
        $this->employee_id = $employee_id;
        $this->nationality = $nationality;
        $this->resetPage();
    }

    public function updated($key, $value)
    {
        $this->dispatch('updatePieChart', $this->dataPoints);
    }

    protected function basQuery()
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('sales.account_id', $value))
            ->when($this->nationality, fn ($q, $value) => $q->where('accounts.nationality', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->where('sales.status', 'completed');
    }

    protected function getProducts()
    {
        return $this->basQuery()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select([
                'accounts.name as customer',
                'products.name as product',
                SaleItem::raw('SUM(sale_items.quantity) as total_quantity'),
                SaleItem::raw('SUM(sale_items.total) as total_amount'),
            ])
            ->when($this->product_id, fn ($q, $value) => $q->where('sale_items.product_id', $value));

    }

    protected function getEmployees()
    {
        return $this->basQuery()
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->select([
                'accounts.name as customer',
                'users.name as employee',
                SaleItem::raw('SUM(sale_items.quantity) as total_quantity'),
                SaleItem::raw('SUM(sale_items.total) as total_amount'),
            ])
            ->when($this->employee_id, fn ($q, $value) => $q->where('sale_items.employee_id', $value));
    }

    public function render()
    {
        $products = $this->getProducts();
        $totalProducts = clone $products;

        $productQuantity = $totalProducts->sum('sale_items.quantity');
        $productAmount = $totalProducts->sum('sale_items.total');

        $products = $products
            ->groupBy('sales.account_id', 'sale_items.product_id')
            ->orderBy('total_quantity', 'desc');
        $productList = clone $products;
        $products = $products->paginate($this->productPerPage, ['*'], 'products_page');

        $employees = $this->getEmployees();
        $totalEmployees = clone $employees;

        $employeeQuantity = $totalEmployees->sum('sale_items.quantity');
        $employeeAmount = $totalEmployees->sum('sale_items.total');

        $employees = $employees
            ->groupBy('sales.account_id', 'sale_items.employee_id')
            ->orderBy('total_quantity', 'desc');
        $employeeList = clone $employees;

        $employees = $employees->paginate($this->employeePerPage, ['*'], 'employees_page');

        $this->dataPoints = [];
        $productList = $productList->orderBy('total_quantity', 'DESC')->limit(10)->pluck('total_amount', 'product')->toArray();
        foreach ($productList as $label => $value) {
            $this->dataPoints['product'][] = [
                'label' => $label,
                'y' => $value,
            ];
        }
        $employeeList = $employeeList->orderBy('total_quantity', 'DESC')->limit(10)->pluck('total_amount', 'employee')->toArray();
        foreach ($employeeList as $label => $value) {
            $this->dataPoints['employee'][] = [
                'label' => $label,
                'y' => $value,
            ];
        }
        $this->dispatch('updatePieChart', $this->dataPoints);

        return view('livewire.report.customer.customer-items', [
            'products' => $products,
            'productAmount' => $productAmount,
            'productQuantity' => $productQuantity,
            'employees' => $employees,
            'employeeAmount' => $employeeAmount,
            'employeeQuantity' => $employeeQuantity,
        ]);
    }
}
