<?php

namespace App\Livewire\Report\Sale;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class OverviewReport extends Component
{
    use WithPagination;

    // Common properties
    public $branchId;

    public $fromDate;

    public $toDate;

    public $dataPoints = [];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;

    // Product table properties
    public $productSearch = '';

    public $productSortField = 'total';

    public $productSortDirection = 'desc';

    // Employee table properties
    public $employeeSearch = '';

    public $employeeSortField = 'total';

    public $employeeSortDirection = 'desc';

    // Page limit properties
    public $employeePerPage = 10;

    public $productPerPage = 10;

    public function mount()
    {
        $this->fromDate = date('Y-m-01');
        $this->toDate = date('Y-m-d');
        $this->branchId = session('branch_id');
    }

    public function updated($key, $value)
    {
        $this->dispatch('updatePieChart', $this->dataPoints);
    }

    public function export() {}

    public function sortBy($field)
    {
        [$type, $field] = explode('.', $field);
        $sortField = "{$type}SortField";
        $sortDirection = "{$type}SortDirection";

        if ($this->{$sortField} === $field) {
            $this->{$sortDirection} = $this->{$sortDirection} === 'asc' ? 'desc' : 'asc';
        } else {
            $this->{$sortField} = $field;
            $this->{$sortDirection} = 'desc';
        }
    }

    public function updatedProductSearch()
    {
        $this->resetPage('products-page');
    }

    public function updatedEmployeeSearch()
    {
        $this->resetPage('employees-page');
    }

    public function updatedEmployeePerPage()
    {
        $this->resetPage('employees-page');
    }

    public function updatedProductPerPage()
    {
        $this->resetPage('products-page');
    }

    public function render()
    {
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->toDateString() : null;
        $to = $this->toDate ? Carbon::parse($this->toDate)->toDateString() : null;

        $baseQuery = fn ($query) => $query
            ->when($this->branchId, fn ($q) => $q->where('sales.branch_id', $this->branchId))
            ->when($from, fn ($q) => $q->where('sales.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.date', '<=', $to));

        $employees = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->tap($baseQuery)
            ->where(function ($query) {
                $query->where('users.name', 'like', '%'.$this->employeeSearch.'%');
            });
        $totalEmployees = clone $employees;
        $employees = $employees->groupBy('sale_items.employee_id')
            ->select('users.id', 'users.name as employee')
            ->selectRaw('sum(sale_items.total) as total')
            ->selectRaw('sum(sale_items.quantity) as quantity')
            ->orderBy($this->employeeSortField, $this->employeeSortDirection)
            ->paginate($this->employeePerPage, ['*'], 'employees-page');
        $employeeQuantity = clone $totalEmployees;
        $employeeQuantity = $employeeQuantity->sum('quantity');

        $employeeTotal = clone $totalEmployees;
        $employeeTotal = $employeeTotal->sum('sale_items.total');

        $products = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->tap($baseQuery)
            ->where(function ($query) {
                $query->where('products.name', 'like', '%'.$this->productSearch.'%');
            });
        $totalProducts = clone $products;

        $products = $products->select('products.name as product', 'products.type')
            ->selectRaw('sum(sale_items.total) as total')
            ->selectRaw('sum(sale_items.quantity) as quantity')
            ->orderBy($this->productSortField, $this->productSortDirection)
            ->groupBy('sale_items.product_id')
            ->paginate($this->productPerPage, ['*'], 'products-page');

        $sales = Sale::query()->customerSearch($this->branchId, $from, $to);
        $saleReturns = SaleReturn::query()->customerSearch($this->branchId, $from, $to);

        $payments = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('accounts', 'accounts.id', '=', 'sale_payments.payment_method_id')
            ->tap($baseQuery)
            ->select('accounts.name as payment_method')
            ->selectRaw('sum(sale_payments.amount) as total')
            ->groupBy('sale_payments.payment_method_id')
            ->pluck('total', 'payment_method');

        $netSales = $sales->sum('gross_amount');
        $saleDiscount = $sales->sum('other_discount');

        $noOfSales = $sales->count();
        $noOfSalesReturns = $saleReturns->count();

        $totalSales = $sales->sum('total');
        $totalSalesReturn = $saleReturns->sum('grand_total');

        foreach ($payments as $title => $amount) {
            $paymentMethods[$title] = $amount;
        }
        $credit = $paymentMethods['Credit'] = $sales->sum('balance');
        $totalPayment = $payments->sum();

        $totalProductQuantity = clone $totalProducts;
        $totalProductQuantity = $totalProductQuantity->sum('quantity');

        $itemTotal = clone $totalProducts;
        $itemTotal = $itemTotal->sum('sale_items.total');

        $productSale = clone $totalProducts;
        $productSale = $productSale->where('type', 'product')->sum('sale_items.total');

        $serviceSale = clone $totalProducts;
        $serviceSale = $serviceSale->where('type', 'service')->sum('sale_items.total');

        $this->dataPoints = [];
        foreach ($paymentMethods as $label => $value) {
            $this->dataPoints[] = [
                'label' => $label,
                'y' => $value,
            ];
        }
        $this->dispatch('updatePieChart', $this->dataPoints);

        return view('livewire.report.sale.overview-report', [
            'employees' => $employees,
            'products' => $products,
            'netSales' => $netSales,
            'saleDiscount' => $saleDiscount,
            'serviceSale' => $serviceSale,
            'productSale' => $productSale,
            'itemTotal' => $itemTotal,
            'totalProductQuantity' => $totalProductQuantity,
            'totalPayment' => $totalPayment,
            'credit' => $credit,
            'paymentMethods' => $paymentMethods,
            'dataPoints' => $this->dataPoints,
            'noOfSales' => $noOfSales,
            'noOfSalesReturns' => $noOfSalesReturns,
            'totalSales' => $totalSales,
            'totalSalesReturn' => $totalSalesReturn,
            'employeeQuantity' => $employeeQuantity,
            'employeeTotal' => $employeeTotal,
        ]);
    }
}
